<?php
require_once __DIR__ . '/../config/config.php';

class Rate
{
    public static function all(array $filters = []): array
    {
        global $pdo;
        $sql = 'SELECT r.*, c.nome AS condominio_nome, e.nome AS esercizio_nome,
                ui.scala, ui.piano, ui.interno
                FROM rate r
                JOIN condomini c ON c.id=r.condominio_id
                JOIN esercizi e ON e.id=r.esercizio_id
                JOIN unita_immobiliari ui ON ui.id=r.unita_id';
        $where = [];
        $params = [];
        if (!empty($filters['condominio_id'])) {
            $where[] = 'r.condominio_id=:cid';
            $params['cid'] = (int)$filters['condominio_id'];
        }
        if (!empty($filters['unita_id'])) {
            $where[] = 'r.unita_id=:uid';
            $params['uid'] = (int)$filters['unita_id'];
        }
        if (!empty($filters['stato'])) {
            $where[] = 'r.stato=:stato';
            $params['stato'] = $filters['stato'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.scadenza ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO rate (esercizio_id,condominio_id,unita_id,descrizione,importo,scadenza,stato) VALUES (:esercizio_id,:condominio_id,:unita_id,:descrizione,:importo,:scadenza,:stato)');
        $ok = $stmt->execute([
            'esercizio_id' => (int)$data['esercizio_id'],
            'condominio_id' => (int)$data['condominio_id'],
            'unita_id' => (int)$data['unita_id'],
            'descrizione' => trim($data['descrizione']),
            'importo' => (float)$data['importo'],
            'scadenza' => $data['scadenza'],
            'stato' => $data['stato'] ?? 'da_pagare'
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function registraPagamento(int $rataId, array $data): bool
    {
        global $pdo;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO pagamenti (rata_id,persona_id,data_pagamento,importo,metodo,riferimento,note) VALUES (:rata_id,:persona_id,:data_pagamento,:importo,:metodo,:riferimento,:note)');
            $stmt->execute([
                'rata_id' => $rataId,
                'persona_id' => !empty($data['persona_id']) ? (int)$data['persona_id'] : null,
                'data_pagamento' => $data['data_pagamento'],
                'importo' => (float)$data['importo'],
                'metodo' => trim($data['metodo'] ?? ''),
                'riferimento' => trim($data['riferimento'] ?? ''),
                'note' => trim($data['note'] ?? '')
            ]);
            self::aggiornaStato($rataId);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function aggiornaStato(int $rataId): void
    {
        global $pdo;
        $tot = self::totalePagato($rataId);
        $rata = self::find($rataId);
        if (!$rata) return;
        if ($tot >= (float)$rata['importo']) {
            $stato = 'pagata';
        } elseif ($tot > 0) {
            $stato = 'parziale';
        } elseif (strtotime($rata['scadenza']) < time()) {
            $stato = 'scaduta';
        } else {
            $stato = 'da_pagare';
        }
        $upd = $pdo->prepare('UPDATE rate SET stato=:stato WHERE id=:id');
        $upd->execute(['stato' => $stato, 'id' => $rataId]);
    }

    public static function aggiornaScadute(): void
    {
        global $pdo;
        $pdo->exec("UPDATE rate SET stato='scaduta' WHERE stato='da_pagare' AND scadenza < CURDATE()");
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM rate WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function totalePagato(int $rataId): float
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(importo),0) FROM pagamenti WHERE rata_id=:id');
        $stmt->execute(['id' => $rataId]);
        return (float)$stmt->fetchColumn();
    }

    public static function pagamenti(int $rataId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT pg.*, p.nome AS persona_nome, p.cognome AS persona_cognome FROM pagamenti pg LEFT JOIN persone p ON p.id=pg.persona_id WHERE pg.rata_id=:id ORDER BY pg.data_pagamento DESC');
        $stmt->execute(['id' => $rataId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE rate SET deleted_at=NOW() WHERE id=:id AND deleted_at IS NULL');
        return $stmt->execute(['id' => $id]);
    }

    public static function restore(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE rate SET deleted_at=NULL WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }
}
