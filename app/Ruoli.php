<?php
require_once __DIR__ . '/../config/config.php';

class Ruoli
{
    public static function all(): array
    {
        global $pdo;
        return $pdo->query("SELECT * FROM ruoli ORDER BY is_system DESC, nome")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM ruoli WHERE id=:id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM ruoli WHERE slug=:slug");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO ruoli (nome, slug, descrizione) VALUES (:nome,:slug,:desc)");
        $stmt->execute([
            'nome' => $data['nome'],
            'slug' => $data['slug'],
            'desc' => $data['descrizione'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE ruoli SET nome=:nome, descrizione=:desc WHERE id=:id AND is_system=0");
        return $stmt->execute([
            'id' => $id,
            'nome' => $data['nome'],
            'desc' => $data['descrizione'] ?? null,
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM ruoli WHERE id=:id AND is_system=0");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function getPermessi(int $ruoloId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT p.* FROM permessi p JOIN ruolo_permessi rp ON rp.permesso_id=p.id WHERE rp.ruolo_id=:rid ORDER BY p.modulo, p.azione");
        $stmt->execute(['rid' => $ruoloId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function setPermessi(int $ruoloId, array $permessoIds): void
    {
        global $pdo;
        $pdo->prepare("DELETE FROM ruolo_permessi WHERE ruolo_id=:rid")->execute(['rid' => $ruoloId]);
        $stmt = $pdo->prepare("INSERT INTO ruolo_permessi (ruolo_id, permesso_id) VALUES (:rid,:pid)");
        foreach ($permessoIds as $pid) {
            $stmt->execute(['rid' => $ruoloId, 'pid' => (int)$pid]);
        }
    }

    public static function getAllPermessi(): array
    {
        global $pdo;
        return $pdo->query("SELECT * FROM permessi ORDER BY modulo, azione")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a user has permission for a given module/action.
     * Super admin always has access. Admin studio has access to own studio data.
     */
    public static function userHasPermission(int $userId, string $modulo, string $azione = 'lettura'): bool
    {
        global $pdo;

        // Legacy admin role always has full access
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id=:uid");
        $stmt->execute(['uid' => $userId]);
        $role = $stmt->fetchColumn();
        if ($role === 'admin') return true;

        // Check via ruolo_permessi through studio_users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM studio_users su JOIN ruolo_permessi rp ON rp.ruolo_id=su.ruolo_id JOIN permessi p ON p.id=rp.permesso_id WHERE su.user_id=:uid AND p.modulo=:mod AND p.azione=:act");
        $stmt->execute(['uid' => $userId, 'mod' => $modulo, 'act' => $azione]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get user's role slug in their studio.
     */
    public static function getUserRoleSlug(int $userId): ?string
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT r.slug FROM studio_users su JOIN ruoli r ON r.id=su.ruolo_id WHERE su.user_id=:uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $slug = $stmt->fetchColumn();
        return $slug ?: null;
    }

    /**
     * Get user's studio_id.
     */
    public static function getUserStudioId(int $userId): ?int
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT studio_id FROM studio_users WHERE user_id=:uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $sid = $stmt->fetchColumn();
        return $sid ? (int)$sid : null;
    }
}
