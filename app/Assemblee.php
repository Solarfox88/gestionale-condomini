<?php
require_once __DIR__ . '/../config/config.php';

class Assemblee
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        $sql = 'SELECT a.*, c.nome AS condominio_nome FROM assemblee a JOIN condomini c ON c.id=a.condominio_id';
        $params = [];
        if ($condominioId) {
            $sql .= ' WHERE a.condominio_id=:cid';
            $params['cid'] = $condominioId;
        }
        $sql .= ' ORDER BY a.data_seconda_convocazione DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO assemblee (condominio_id,titolo,data_prima_convocazione,data_seconda_convocazione,luogo,ordine_giorno,stato) VALUES (:condominio_id,:titolo,:data_prima,:data_seconda,:luogo,:ordine_giorno,:stato)');
        $ok = $stmt->execute([
            'condominio_id' => (int)$data['condominio_id'],
            'titolo' => trim($data['titolo']),
            'data_prima' => !empty($data['data_prima_convocazione']) ? $data['data_prima_convocazione'] : null,
            'data_seconda' => $data['data_seconda_convocazione'],
            'luogo' => trim($data['luogo'] ?? ''),
            'ordine_giorno' => trim($data['ordine_giorno']),
            'stato' => $data['stato'] ?? 'bozza'
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT a.*, c.nome AS condominio_nome FROM assemblee a JOIN condomini c ON c.id=a.condominio_id WHERE a.id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE assemblee SET titolo=:titolo, data_prima_convocazione=:data_prima, data_seconda_convocazione=:data_seconda, luogo=:luogo, ordine_giorno=:ordine_giorno, verbale=:verbale, stato=:stato, updated_at=NOW() WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'titolo' => trim($data['titolo']),
            'data_prima' => !empty($data['data_prima_convocazione']) ? $data['data_prima_convocazione'] : null,
            'data_seconda' => $data['data_seconda_convocazione'],
            'luogo' => trim($data['luogo'] ?? ''),
            'ordine_giorno' => trim($data['ordine_giorno']),
            'verbale' => trim($data['verbale'] ?? ''),
            'stato' => $data['stato'] ?? 'bozza',
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM assemblee WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public static function getPresenze(int $assembleaId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT ap.*, p.nome, p.cognome, d.nome AS delegante_nome, d.cognome AS delegante_cognome FROM assemblee_presenze ap JOIN persone p ON p.id=ap.persona_id LEFT JOIN persone d ON d.id=ap.delegato_da WHERE ap.assemblea_id=:aid ORDER BY p.cognome, p.nome');
        $stmt->execute(['aid' => $assembleaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function salvaPresenza(int $assembleaId, int $personaId, ?int $unitaId, bool $presente, ?int $delegatoDa, float $millesimi): bool
    {
        global $pdo;
        $check = $pdo->prepare('SELECT id FROM assemblee_presenze WHERE assemblea_id=:aid AND persona_id=:pid');
        $check->execute(['aid' => $assembleaId, 'pid' => $personaId]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare('UPDATE assemblee_presenze SET unita_id=:uid, presente=:presente, delegato_da=:del, millesimi_presenti=:mill WHERE assemblea_id=:aid AND persona_id=:pid');
        } else {
            $stmt = $pdo->prepare('INSERT INTO assemblee_presenze (assemblea_id,persona_id,unita_id,presente,delegato_da,millesimi_presenti) VALUES (:aid,:pid,:uid,:presente,:del,:mill)');
        }
        return $stmt->execute([
            'aid' => $assembleaId,
            'pid' => $personaId,
            'uid' => $unitaId,
            'presente' => $presente ? 1 : 0,
            'del' => $delegatoDa,
            'mill' => $millesimi,
        ]);
    }

    public static function totaleMillesimiPresenti(int $assembleaId): float
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(millesimi_presenti),0) FROM assemblee_presenze WHERE assemblea_id=:aid AND presente=1');
        $stmt->execute(['aid' => $assembleaId]);
        return (float)$stmt->fetchColumn();
    }

    public static function prossime(int $limit = 5): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT a.*, c.nome AS condominio_nome FROM assemblee a JOIN condomini c ON c.id=a.condominio_id WHERE a.data_seconda_convocazione >= CURDATE() AND a.stato IN ('bozza','convocata') ORDER BY a.data_seconda_convocazione ASC LIMIT " . (int)$limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
