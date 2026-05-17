<?php
require_once __DIR__ . '/../config/config.php';

class Studi
{
    public static function all(): array
    {
        global $pdo;
        return $pdo->query("SELECT * FROM studi ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM studi WHERE id=:id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO studi (nome, codice_fiscale, partita_iva, indirizzo, comune, provincia, cap, email, pec, telefono, nome_amministratore, piano, max_condomini, max_unita, max_storage_mb) VALUES (:nome,:cf,:piva,:ind,:com,:prov,:cap,:email,:pec,:tel,:admin,:piano,:mc,:mu,:ms)");
        $stmt->execute([
            'nome' => $data['nome'],
            'cf' => $data['codice_fiscale'] ?? null,
            'piva' => $data['partita_iva'] ?? null,
            'ind' => $data['indirizzo'] ?? null,
            'com' => $data['comune'] ?? null,
            'prov' => $data['provincia'] ?? null,
            'cap' => $data['cap'] ?? null,
            'email' => $data['email'] ?? null,
            'pec' => $data['pec'] ?? null,
            'tel' => $data['telefono'] ?? null,
            'admin' => $data['nome_amministratore'] ?? null,
            'piano' => $data['piano'] ?? 'free',
            'mc' => (int)($data['max_condomini'] ?? 5),
            'mu' => (int)($data['max_unita'] ?? 50),
            'ms' => (int)($data['max_storage_mb'] ?? 500),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE studi SET nome=:nome, codice_fiscale=:cf, partita_iva=:piva, indirizzo=:ind, comune=:com, provincia=:prov, cap=:cap, email=:email, pec=:pec, telefono=:tel, nome_amministratore=:admin, piano=:piano, max_condomini=:mc, max_unita=:mu, max_storage_mb=:ms WHERE id=:id");
        return $stmt->execute([
            'id' => $id,
            'nome' => $data['nome'],
            'cf' => $data['codice_fiscale'] ?? null,
            'piva' => $data['partita_iva'] ?? null,
            'ind' => $data['indirizzo'] ?? null,
            'com' => $data['comune'] ?? null,
            'prov' => $data['provincia'] ?? null,
            'cap' => $data['cap'] ?? null,
            'email' => $data['email'] ?? null,
            'pec' => $data['pec'] ?? null,
            'tel' => $data['telefono'] ?? null,
            'admin' => $data['nome_amministratore'] ?? null,
            'piano' => $data['piano'] ?? 'free',
            'mc' => (int)($data['max_condomini'] ?? 5),
            'mu' => (int)($data['max_unita'] ?? 50),
            'ms' => (int)($data['max_storage_mb'] ?? 500),
        ]);
    }

    public static function updateLogo(int $id, string $logoPath): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE studi SET logo_path=:lp WHERE id=:id");
        return $stmt->execute(['id' => $id, 'lp' => $logoPath]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM studi WHERE id=:id");
        return $stmt->execute(['id' => $id]);
    }

    public static function getUsers(int $studioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT su.*, u.name, u.email, u.role, u.status AS user_status, r.nome AS ruolo_nome, r.slug AS ruolo_slug FROM studio_users su JOIN users u ON u.id=su.user_id LEFT JOIN ruoli r ON r.id=su.ruolo_id WHERE su.studio_id=:sid ORDER BY u.name");
        $stmt->execute(['sid' => $studioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addUser(int $studioId, int $userId, ?int $ruoloId = null): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("INSERT IGNORE INTO studio_users (studio_id, user_id, ruolo_id) VALUES (:sid,:uid,:rid)");
        return $stmt->execute(['sid' => $studioId, 'uid' => $userId, 'rid' => $ruoloId]);
    }

    public static function removeUser(int $studioId, int $userId): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM studio_users WHERE studio_id=:sid AND user_id=:uid");
        return $stmt->execute(['sid' => $studioId, 'uid' => $userId]);
    }

    public static function updateUserRole(int $studioId, int $userId, int $ruoloId): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE studio_users SET ruolo_id=:rid WHERE studio_id=:sid AND user_id=:uid");
        return $stmt->execute(['sid' => $studioId, 'uid' => $userId, 'rid' => $ruoloId]);
    }

    public static function getUserStudio(int $userId): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT s.*, su.ruolo_id, r.slug AS ruolo_slug FROM studio_users su JOIN studi s ON s.id=su.studio_id LEFT JOIN ruoli r ON r.id=su.ruolo_id WHERE su.user_id=:uid AND s.status='active' LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getCondominiCount(int $studioId): int
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM condomini WHERE studio_id=:sid AND status='active'");
        $stmt->execute(['sid' => $studioId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getUnitaCount(int $studioId): int
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM unita_immobiliari ui JOIN condomini c ON c.id=ui.condominio_id WHERE c.studio_id=:sid");
        $stmt->execute(['sid' => $studioId]);
        return (int)$stmt->fetchColumn();
    }
}
