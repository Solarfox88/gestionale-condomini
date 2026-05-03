<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Restituisce l'elenco degli utenti.
 * Se specificato un ruolo, filtra per ruolo.
 * @param string|null $status
 * @return array
 */
function get_users(?string $status = null): array
{
    global $pdo;
    if ($status) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE status = :status ORDER BY created_at DESC");
        $stmt->execute(['status' => $status]);
    } else {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Aggiorna lo status e il ruolo di un utente.
 * @param int $id
 * @param string $role
 * @param string $status
 * @return bool
 */
function update_user_role_status(int $id, string $role, string $status): bool
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET role = :role, status = :status, updated_at = NOW() WHERE id = :id");
    return $stmt->execute([
        'id' => $id,
        'role' => $role,
        'status' => $status,
    ]);
}
