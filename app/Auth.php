<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Helpers.php';

/**
 * Effettua il login dell'utente.
 * @param string $email
 * @param string $password
 * @return bool
 */
function login_user(string $email, string $password): bool
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND status = 'active'");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        // rigenera la sessione per prevenire fixation
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $upd = $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=:id');
        $upd->execute(['id' => $user['id']]);
        audit_log('login', 'users', (int)$user['id']);
        return true;
    }
    return false;
}

/**
 * Registra un nuovo utente come condomino (status pending).
 * @param array $data
 * @return bool|string Ritorna l'ID dell'utente in caso di successo, false altrimenti.
 */
function register_user(array $data)
{
    global $pdo;
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, fiscal_code, role, status, created_at, updated_at) VALUES (:name, :email, :password_hash, :phone, :fiscal_code, :role, :status, NOW(), NOW())");
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'phone' => $data['phone'] ?? null,
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'role' => 'condomino',
            'status' => 'pending'
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        // In caso di email duplicata o altro errore, restituiamo false
        return false;
    }
}

/**
 * Verifica se l'utente è loggato.
 * @return bool
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Verifica se l'utente loggato ha il ruolo di amministratore.
 * @return bool
 */
function is_admin(): bool
{
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}

/**
 * Effettua il logout dell'utente.
 */
function logout_user(): void
{
    // distrugge tutti i dati della sessione
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Protegge le pagine che richiedono autenticazione.  
 * Se l'utente non è loggato, viene reindirizzato al login.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Protegge le pagine riservate agli amministratori.
 */
function require_admin(): void
{
    if (!is_admin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Accesso negato';
        exit;
    }
}

/**
 * Cambia la password dell'utente.
 */
function change_password(int $userId, string $currentPassword, string $newPassword): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=:id');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        return false;
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash=:hash, updated_at=NOW() WHERE id=:id');
    $upd->execute(['hash' => $hash, 'id' => $userId]);
    audit_log('change_password', 'users', $userId);
    return true;
}

/**
 * Restituisce un utente per ID.
 */
function get_user(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id=:id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Aggiorna il profilo utente.
 */
function update_user_profile(int $id, array $data): bool
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE users SET name=:name, phone=:phone, fiscal_code=:fiscal_code, updated_at=NOW() WHERE id=:id');
    return $stmt->execute([
        'id' => $id,
        'name' => $data['name'],
        'phone' => $data['phone'] ?? null,
        'fiscal_code' => $data['fiscal_code'] ?? null,
    ]);
}
