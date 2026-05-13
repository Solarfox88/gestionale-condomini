<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Tickets.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ticket = Tickets::find($id);
if (!$ticket || (int)$ticket['aperto_da'] !== (int)$_SESSION['user']['id']) {
    echo 'Ticket non trovato o non autorizzato.'; exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    Tickets::addMessaggio($id, (int)$_SESSION['user']['id'], $_POST['messaggio'], false);
    $msg = 'Messaggio inviato.';
}

$messaggi = Tickets::getMessaggi($id);
$messaggiPubblici = array_filter($messaggi, fn($m) => !$m['interno']);

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Ticket #<?php echo $id; ?> - <?php echo h($ticket['titolo']); ?></h2>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?php echo h($msg); ?></div><?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <p><strong>Stato:</strong> <?php echo stato_badge($ticket['stato']); ?></p>
        <p><strong>Priorita:</strong> <?php echo stato_badge($ticket['priorita']); ?></p>
        <p><strong>Descrizione:</strong></p>
        <div class="border p-2 bg-light mb-2"><?php echo nl2br(h($ticket['descrizione'])); ?></div>
        <p class="text-muted">Creato: <?php echo h($ticket['created_at']); ?></p>
    </div>
</div>

<h4>Messaggi</h4>
<?php foreach ($messaggiPubblici as $m): ?>
<div class="card mb-2">
    <div class="card-header d-flex justify-content-between">
        <strong><?php echo h($m['autore_nome']); ?></strong>
        <small><?php echo h($m['created_at']); ?></small>
    </div>
    <div class="card-body"><?php echo nl2br(h($m['messaggio'])); ?></div>
</div>
<?php endforeach; ?>

<?php if (!in_array($ticket['stato'], ['chiuso','risolto','respinto'])): ?>
<h5 class="mt-3">Rispondi</h5>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-2"><textarea name="messaggio" class="form-control" rows="3" required placeholder="Scrivi..."></textarea></div>
    <button class="btn btn-primary">Invia</button>
</form>
<?php else: ?>
<div class="alert alert-secondary">Questo ticket e stato chiuso.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
