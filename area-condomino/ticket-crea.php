<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Tickets.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $_POST['categoria'] = '';
    $_POST['unita_id'] = '';
    $_POST['assegnato_a'] = '';
    $id = Tickets::create($_POST, $_SESSION['user']['id']);
    if ($id) {
        header('Location: /area-condomino/dashboard.php?msg=ticket_creato');
    } else {
        header('Location: /area-condomino/dashboard.php?msg=errore');
    }
} else {
    header('Location: /area-condomino/dashboard.php');
}
exit;
