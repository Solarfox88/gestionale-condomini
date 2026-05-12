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
        $stmt = $pdo->prepare('SELECT * FROM assemblee WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
