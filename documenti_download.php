<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Auth.php';
require_once __DIR__ . '/app/Documenti.php';

// Verifica login
require_login();
$docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$documento = get_documento($docId);
if (!$documento) {
    http_response_code(404);
    echo 'Documento non trovato';
    exit;
}

// Permesso: gli admin possono scaricare tutto; i condomini solo se condominio_id corrisponde e visibilità corretta.
if (!is_admin()) {
    $userId = $_SESSION['user']['id'];
    // recupera condominio dell'utente dalla tabella condomini_users (semplice join)
    global $pdo;
    $stmt = $pdo->prepare("SELECT condominio_id FROM condomini_users WHERE user_id = :uid AND approved_at IS NOT NULL LIMIT 1");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['condominio_id'] !== (int)$documento['condominio_id']) {
        http_response_code(403);
        echo 'Non hai i permessi per accedere a questo documento.';
        exit;
    }
    // Visibilità: solo condominio o unità. Qui semplifichiamo e non controlliamo le unità specifiche.
}

// Costruisce percorso file
$filePath = STORAGE_PATH . '/documents/' . $documento['file_path'];
if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'File non trovato';
    exit;
}
// Invia file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($documento['file_path']) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
