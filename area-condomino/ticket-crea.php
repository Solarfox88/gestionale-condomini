<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Tickets.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = Tickets::create($_POST, (int)$_SESSION['user']['id']);
    if ($id) {
        audit_log('create', 'ticket', (int)$id, 'condomino');
        header('Location: dashboard.php?msg=ticket_creato');
    } else {
        header('Location: dashboard.php?msg=errore');
    }
} else {
    header('Location: dashboard.php');
}
exit;
