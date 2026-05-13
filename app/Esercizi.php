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
        $stmt = $pdo->prepare('SELECT e.*, c.nome AS condominio_nome FROM esercizi e JOIN condomini c ON c.id=e.condominio_id WHERE e.id=:id');
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

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE esercizi SET nome=:nome, data_inizio=:data_inizio, data_fine=:data_fine, stato=:stato, updated_at=NOW() WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'nome' => trim($data['nome']),
            'data_inizio' => $data['data_inizio'],
            'data_fine' => $data['data_fine'],
            'stato' => $data['stato'] ?? 'bozza',
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM esercizi WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public static function riepilogo(int $id): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo ELSE 0 END),0) AS entrate, COALESCE(SUM(CASE WHEN tipo='uscita' THEN importo ELSE 0 END),0) AS uscite FROM movimenti WHERE esercizio_id=:id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
