<?php
require_once __DIR__ . '/../config/config.php';

class Movimenti
{
    public static function all(array $filters = []): array
    {
        global $pdo;
        $sql = 'SELECT m.*, c.nome AS condominio_nome, e.nome AS esercizio_nome,
                cs.nome AS categoria_nome, p.cognome AS persona_cognome, p.nome AS persona_nome
                FROM movimenti m
                JOIN condomini c ON c.id=m.condominio_id
                JOIN esercizi e ON e.id=m.esercizio_id
                LEFT JOIN categorie_spesa cs ON cs.id=m.categoria_id
                LEFT JOIN persone p ON p.id=m.persona_id';
        $where = [];
        $params = [];
        if (!empty($filters['condominio_id'])) {
            $where[] = 'm.condominio_id=:cid';
            $params['cid'] = (int)$filters['condominio_id'];
        }
        if (!empty($filters['esercizio_id'])) {
            $where[] = 'm.esercizio_id=:eid';
            $params['eid'] = (int)$filters['esercizio_id'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = 'm.tipo=:tipo';
            $params['tipo'] = $filters['tipo'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY m.data_movimento DESC, m.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT m.*, c.nome AS condominio_nome, e.nome AS esercizio_nome FROM movimenti m JOIN condomini c ON c.id=m.condominio_id JOIN esercizi e ON e.id=m.esercizio_id WHERE m.id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO movimenti (esercizio_id,condominio_id,unita_id,persona_id,categoria_id,tipo,descrizione,importo,data_movimento,metodo_pagamento,riferimento) VALUES (:esercizio_id,:condominio_id,:unita_id,:persona_id,:categoria_id,:tipo,:descrizione,:importo,:data_movimento,:metodo_pagamento,:riferimento)');
        $ok = $stmt->execute([
            'esercizio_id' => (int)$data['esercizio_id'],
            'condominio_id' => (int)$data['condominio_id'],
            'unita_id' => !empty($data['unita_id']) ? (int)$data['unita_id'] : null,
            'persona_id' => !empty($data['persona_id']) ? (int)$data['persona_id'] : null,
            'categoria_id' => !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            'tipo' => $data['tipo'],
            'descrizione' => trim($data['descrizione'] ?? ''),
            'importo' => (float)$data['importo'],
            'data_movimento' => $data['data_movimento'],
            'metodo_pagamento' => trim($data['metodo_pagamento'] ?? ''),
            'riferimento' => trim($data['riferimento'] ?? '')
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE movimenti SET esercizio_id=:esercizio_id, condominio_id=:condominio_id, unita_id=:unita_id, persona_id=:persona_id, categoria_id=:categoria_id, tipo=:tipo, descrizione=:descrizione, importo=:importo, data_movimento=:data_movimento, metodo_pagamento=:metodo_pagamento, riferimento=:riferimento WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'esercizio_id' => (int)$data['esercizio_id'],
            'condominio_id' => (int)$data['condominio_id'],
            'unita_id' => !empty($data['unita_id']) ? (int)$data['unita_id'] : null,
            'persona_id' => !empty($data['persona_id']) ? (int)$data['persona_id'] : null,
            'categoria_id' => !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            'tipo' => $data['tipo'],
            'descrizione' => trim($data['descrizione'] ?? ''),
            'importo' => (float)$data['importo'],
            'data_movimento' => $data['data_movimento'],
            'metodo_pagamento' => trim($data['metodo_pagamento'] ?? ''),
            'riferimento' => trim($data['riferimento'] ?? '')
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM movimenti WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public static function saldoCondominio(int $condominioId): float
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo ELSE -importo END),0) AS saldo FROM movimenti WHERE condominio_id=:cid");
        $stmt->execute(['cid' => $condominioId]);
        return (float)$stmt->fetchColumn();
    }
}
