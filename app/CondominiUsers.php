<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Restituisce il condominio associato all'utente (approvato) o null.
 * @param int $userId
 * @return array|null
 */
function get_user_condominio(int $userId): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT cu.*, c.nome AS condominio_nome FROM condomini_users cu JOIN condomini c ON cu.condominio_id = c.id WHERE cu.user_id = :uid AND cu.approved_at IS NOT NULL LIMIT 1");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
