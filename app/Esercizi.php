<?php
require_once __DIR__ . '/../config/config.php';

class Esercizi
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        if ($condominioId) {
            $stmt = $pdo->prepare('SELECT e.*, c.nome AS condominio_nome FROM esercizi e JOIN condomini c ON c.id=e.condominio_id WHERE e.condominio_id=:cid ORDER BY e.data_inizio DESC');
            $stmt->execute(['cid' => $condominioId]);
        } else {
            $stmt = $pdo->query('SELECT e.*, c.nome AS condominio_nome FROM esercizi e JOIN condomini c ON c.id=e.condominio_id ORDER BY e.data_inizio DESC');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM esercizi WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO esercizi (condominio_id,nome,data_inizio,data_fine,stato) VALUES (:condominio_id,:nome,:data_inizio,:data_fine,:stato)');
        $ok = $stmt->execute([
            'condominio_id' => (int)$data['condominio_id'],
            'nome' => trim($data['nome']),
            'data_inizio' => $data['data_inizio'],
            'data_fine' => $data['data_fine'],
            'stato' => $data['stato'] ?? 'bozza'
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }
}
