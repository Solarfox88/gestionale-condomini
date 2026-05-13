<?php
require_once __DIR__ . '/../config/config.php';

class CategorieSpesa
{
    public static function all(): array
    {
        global $pdo;
        $stmt = $pdo->query('SELECT * FROM categorie_spesa ORDER BY nome ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM categorie_spesa WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO categorie_spesa (nome,descrizione,tipo_default) VALUES (:nome,:descrizione,:tipo_default)');
        $ok = $stmt->execute([
            'nome' => trim($data['nome']),
            'descrizione' => trim($data['descrizione'] ?? ''),
            'tipo_default' => $data['tipo_default'] ?? 'uscita',
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE categorie_spesa SET nome=:nome, descrizione=:descrizione, tipo_default=:tipo_default WHERE id=:id');
        return $stmt->execute([
            'id' => $id,
            'nome' => trim($data['nome']),
            'descrizione' => trim($data['descrizione'] ?? ''),
            'tipo_default' => $data['tipo_default'] ?? 'uscita',
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM categorie_spesa WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }
}
