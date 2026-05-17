<?php

class Comunicazioni
{
    public static function all(?int $condominioId = null, ?string $stato = null): array
    {
        global $pdo;
        $sql = 'SELECT c.*, co.nome AS condominio_nome, u.name AS creato_da_nome
                FROM comunicazioni c
                JOIN condomini co ON co.id = c.condominio_id
                LEFT JOIN users u ON u.id = c.created_by
                WHERE 1=1';
        $params = [];
        if ($condominioId) { $sql .= ' AND c.condominio_id = :cid'; $params['cid'] = $condominioId; }
        if ($stato) { $sql .= ' AND c.stato = :stato'; $params['stato'] = $stato; }
        $sql .= ' ORDER BY c.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT c.*, co.nome AS condominio_nome, co.email AS condominio_email,
                               co.iban AS condominio_iban, u.name AS creato_da_nome
                               FROM comunicazioni c
                               JOIN condomini co ON co.id = c.condominio_id
                               LEFT JOIN users u ON u.id = c.created_by
                               WHERE c.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO comunicazioni (condominio_id, oggetto, corpo, tipo, destinatari_tipo, destinatari_filtro, stato, template_id, created_by)
                               VALUES (:cid, :oggetto, :corpo, :tipo, :dest_tipo, :dest_filtro, :stato, :tid, :uid)');
        $stmt->execute([
            'cid' => (int)$data['condominio_id'],
            'oggetto' => trim($data['oggetto']),
            'corpo' => trim($data['corpo']),
            'tipo' => $data['tipo'] ?? 'comunicazione',
            'dest_tipo' => $data['destinatari_tipo'] ?? 'tutti',
            'dest_filtro' => !empty($data['destinatari_filtro']) ? trim($data['destinatari_filtro']) : null,
            'stato' => 'bozza',
            'tid' => !empty($data['template_id']) ? (int)$data['template_id'] : null,
            'uid' => !empty($data['created_by']) ? (int)$data['created_by'] : null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE comunicazioni SET oggetto=:oggetto, corpo=:corpo, tipo=:tipo,
                               destinatari_tipo=:dest_tipo, destinatari_filtro=:dest_filtro, updated_at=NOW()
                               WHERE id=:id AND stato="bozza"');
        return $stmt->execute([
            'id' => $id,
            'oggetto' => trim($data['oggetto']),
            'corpo' => trim($data['corpo']),
            'tipo' => $data['tipo'] ?? 'comunicazione',
            'dest_tipo' => $data['destinatari_tipo'] ?? 'tutti',
            'dest_filtro' => !empty($data['destinatari_filtro']) ? trim($data['destinatari_filtro']) : null,
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM comunicazioni WHERE id=:id AND stato="bozza"');
        return $stmt->execute(['id' => $id]);
    }

    public static function archivia(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE comunicazioni SET stato="archiviata", updated_at=NOW() WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public static function getDestinatari(int $comId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT cd.*, p.nome AS p_nome, p.cognome AS p_cognome
                               FROM comunicazioni_destinatari cd
                               LEFT JOIN persone p ON p.id = cd.persona_id
                               WHERE cd.comunicazione_id = :cid ORDER BY cd.id');
        $stmt->execute(['cid' => $comId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function calcolaDestinatari(int $comId): int
    {
        global $pdo;
        $com = self::find($comId);
        if (!$com) return 0;

        $pdo->prepare('DELETE FROM comunicazioni_destinatari WHERE comunicazione_id=:cid')->execute(['cid' => $comId]);

        $sql = 'SELECT DISTINCT p.id, p.nome, p.cognome, p.email
                FROM unita_persone up
                JOIN persone p ON p.id = up.persona_id
                JOIN unita_immobiliari ui ON ui.id = up.unita_id
                WHERE ui.condominio_id = :cid AND (up.data_fine IS NULL OR up.data_fine >= CURDATE())';
        $params = ['cid' => $com['condominio_id']];

        if ($com['destinatari_tipo'] === 'scala' && $com['destinatari_filtro']) {
            $sql .= ' AND ui.scala = :scala';
            $params['scala'] = $com['destinatari_filtro'];
        } elseif ($com['destinatari_tipo'] === 'unita' && $com['destinatari_filtro']) {
            $sql .= ' AND ui.id = :uid';
            $params['uid'] = (int)$com['destinatari_filtro'];
        } elseif ($com['destinatari_tipo'] === 'persona' && $com['destinatari_filtro']) {
            $sql .= ' AND p.id = :pid';
            $params['pid'] = (int)$com['destinatari_filtro'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $persone = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        $ins = $pdo->prepare('INSERT INTO comunicazioni_destinatari (comunicazione_id, persona_id, email, nome, stato) VALUES (:cid, :pid, :email, :nome, "pending")');
        foreach ($persone as $p) {
            $ins->execute([
                'cid' => $comId,
                'pid' => $p['id'],
                'email' => $p['email'] ?? '',
                'nome' => trim(($p['cognome'] ?? '') . ' ' . ($p['nome'] ?? '')),
            ]);
            $count++;
        }
        return $count;
    }

    public static function invia(int $comId): array
    {
        global $pdo;
        require_once __DIR__ . '/EmailService.php';

        $com = self::find($comId);
        if (!$com) return ['ok' => 0, 'errori' => 0, 'msg' => 'Comunicazione non trovata'];

        $destinatari = self::getDestinatari($comId);
        if (empty($destinatari)) return ['ok' => 0, 'errori' => 0, 'msg' => 'Nessun destinatario'];

        $ok = 0; $errori = 0;
        foreach ($destinatari as $d) {
            if (empty($d['email'])) {
                $pdo->prepare('UPDATE comunicazioni_destinatari SET stato="errore", errore="Email mancante" WHERE id=:id')->execute(['id' => $d['id']]);
                EmailService::log($comId, $d['email'] ?? '', $com['oggetto'], 'errore', 'Email mancante');
                $errori++;
                continue;
            }

            $corpo = str_replace('{nome_destinatario}', $d['nome'] ?? '', $com['corpo']);
            $result = EmailService::send($d['email'], $com['oggetto'], $corpo);

            if ($result === true) {
                $pdo->prepare('UPDATE comunicazioni_destinatari SET stato="inviato", inviato_at=NOW() WHERE id=:id')->execute(['id' => $d['id']]);
                EmailService::log($comId, $d['email'], $com['oggetto'], 'inviato');
                $ok++;
            } else {
                $pdo->prepare('UPDATE comunicazioni_destinatari SET stato="errore", errore=:err WHERE id=:id')->execute(['id' => $d['id'], 'err' => $result]);
                EmailService::log($comId, $d['email'], $com['oggetto'], 'errore', $result);
                $errori++;
            }
        }

        $pdo->prepare('UPDATE comunicazioni SET stato="inviata", inviata_at=NOW(), updated_at=NOW() WHERE id=:id')->execute(['id' => $comId]);

        return ['ok' => $ok, 'errori' => $errori, 'msg' => "$ok inviati, $errori errori"];
    }

    public static function perCondomino(int $personaId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT c.oggetto, c.corpo, c.tipo, c.inviata_at, cd.stato AS dest_stato, cd.letto_at,
                               co.nome AS condominio_nome
                               FROM comunicazioni_destinatari cd
                               JOIN comunicazioni c ON c.id = cd.comunicazione_id
                               JOIN condomini co ON co.id = c.condominio_id
                               WHERE cd.persona_id = :pid AND c.stato = "inviata"
                               ORDER BY c.inviata_at DESC');
        $stmt->execute(['pid' => $personaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Template methods
    public static function allTemplate(): array
    {
        global $pdo;
        return $pdo->query('SELECT * FROM template_comunicazioni ORDER BY tipo, nome')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findTemplate(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM template_comunicazioni WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createTemplate(array $data): int
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO template_comunicazioni (nome, oggetto, corpo, tipo) VALUES (:nome, :oggetto, :corpo, :tipo)');
        $stmt->execute([
            'nome' => trim($data['nome']),
            'oggetto' => trim($data['oggetto'] ?? ''),
            'corpo' => trim($data['corpo']),
            'tipo' => $data['tipo'] ?? 'generico',
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateTemplate(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE template_comunicazioni SET nome=:nome, oggetto=:oggetto, corpo=:corpo, tipo=:tipo, updated_at=NOW() WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'nome' => trim($data['nome']),
            'oggetto' => trim($data['oggetto'] ?? ''),
            'corpo' => trim($data['corpo']),
            'tipo' => $data['tipo'] ?? 'generico',
        ]);
    }

    public static function deleteTemplate(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM template_comunicazioni WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }
}
