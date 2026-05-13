<?php
// Configurazione principale del progetto.
// Impostare qui i parametri di connessione al database.

// Host del database (di solito localhost su servizi di hosting condiviso)
$host = 'localhost';
// Nome del database
$dbname = 'condomini_db';
// Utente del database
$user = 'db_user';
// Password del database
$password = 'db_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Impossibile connettersi al database. Verifica la configurazione.');
}

// Avvia la sessione se non già avviata
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definizione del percorso per la directory storage
define('STORAGE_PATH', __DIR__ . '/../storage');

// --------------- CSRF helpers ---------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrf_token(), $token);
}

// --------------- Upload validation ---------------
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10 MB
define('UPLOAD_ALLOWED_MIME', [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv',
]);
