<?php
require_once __DIR__ . '/../config/config.php';

class Movimenti
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        $sql = 'SELECT m.*, c.nome AS condominio_nome, e.nome AS esercizio_nome, cs.nome AS categoria_nome
                FROM movimenti m
                JOIN condomini c ON c.id=m.condominio_id
                JOIN esercizi e ON e.id=m.esercizio_id
                LEFT JOIN categorie_spesa cs ON cs.id=m.categoria_id';
        $params = [];
        if ($condominioId) {
            $sql .= ' WHERE m.condominio_id=:cid';
            $params['cid'] = $condominioId;
        }
        $sql .= ' ORDER BY m.data_movimento DESC, m.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO movimenti (esercizio_id,condominio_id,unita_id,persona_id,categoria_id,tipo,descrizione,importo,data_movimento,metodo_pagamento,riferimento) VALUES (:esercizio_id,:condominio_id,:unita_id,:persona_id,:categoria_id,:tipo,:descrizione,:importo,:data_movimento,:metodo_pagamento,:riferimento)');
        $ok = $stmt->execute([
            'esercizio_id' => (int)$data['esercizio_id'],
            'condominio_id' => (int)$data['condominio_id'],
            'unita_id' => $data['unita_id'] !== '' ? (int)$data['unita_id'] : null,
            'persona_id' => $data['persona_id'] !== '' ? (int)$data['persona_id'] : null,
            'categoria_id' => $data['categoria_id'] !== '' ? (int)$data['categoria_id'] : null,
            'tipo' => $data['tipo'],
            'descrizione' => trim($data['descrizione'] ?? ''),
            'importo' => (float)$data['importo'],
            'data_movimento' => $data['data_movimento'],
            'metodo_pagamento' => trim($data['metodo_pagamento'] ?? ''),
            'riferimento' => trim($data['riferimento'] ?? '')
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function saldoCondominio(int $condominioId): float
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo ELSE -importo END),0) AS saldo FROM movimenti WHERE condominio_id=:cid");
        $stmt->execute(['cid' => $condominioId]);
        return (float)$stmt->fetchColumn();
    }
}
