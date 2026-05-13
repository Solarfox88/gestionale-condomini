<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Documenti.php';
require_once __DIR__ . '/../app/Tickets.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_login();

$userId = (int)$_SESSION['user']['id'];
global $pdo;

$stmt = $pdo->prepare("SELECT c.* FROM condomini c JOIN condomini_users cu ON cu.condominio_id = c.id WHERE cu.user_id = :uid ORDER BY c.nome");
$stmt->execute(['uid' => $userId]);
$condomini = $stmt->fetchAll(PDO::FETCH_ASSOC);

$documenti = [];
foreach ($condomini as $c) {
    $docs = get_documenti((int)$c['id']);
    foreach ($docs as $d) {
        if ($d['visibility'] === 'pubblico' || $d['visibility'] === 'condominio') {
            $documenti[] = $d;
        }
    }
}

$stmtT = $pdo->prepare("SELECT t.*, c.nome AS condominio_nome FROM ticket t JOIN condomini c ON c.id = t.condominio_id WHERE t.aperto_da = :uid ORDER BY t.updated_at DESC LIMIT 20");
$stmtT->execute(['uid' => $userId]);
$myTickets = $stmtT->fetchAll(PDO::FETCH_ASSOC);

$condIds = array_column($condomini, 'id');
$myRate = []; $myAssemblee = [];
if ($condIds) {
    $placeholders = implode(',', array_fill(0, count($condIds), '?'));
    Rate::aggiornaScadute();
    $stmtR = $pdo->prepare("SELECT r.*, c.nome AS condominio_nome, ui.scala, ui.piano, ui.interno FROM rate r JOIN condomini c ON c.id=r.condominio_id JOIN unita_immobiliari ui ON ui.id=r.unita_id WHERE r.condominio_id IN ($placeholders) AND r.stato IN ('da_pagare','parziale','scaduta') ORDER BY r.scadenza ASC");
    $stmtR->execute($condIds);
    $myRate = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $stmtA = $pdo->prepare("SELECT a.*, c.nome AS condominio_nome FROM assemblee a JOIN condomini c ON c.id=a.condominio_id WHERE a.condominio_id IN ($placeholders) ORDER BY a.data_seconda_convocazione DESC LIMIT 10");
    $stmtA->execute($condIds);
    $myAssemblee = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Area Condomino</h2>
<p>Benvenuto, <strong><?php echo h($_SESSION['user']['name']); ?></strong></p>

<div class="row mb-4">
    <div class="col-md-3 mb-3"><div class="card text-bg-primary"><div class="card-body"><h5><?php echo count($condomini); ?></h5><p>Condomini</p></div></div></div>
    <div class="col-md-3 mb-3"><div class="card text-bg-info"><div class="card-body"><h5><?php echo count($documenti); ?></h5><p>Documenti</p></div></div></div>
    <div class="col-md-3 mb-3"><div class="card text-bg-warning"><div class="card-body"><h5><?php echo count($myRate); ?></h5><p>Rate da pagare</p></div></div></div>
    <div class="col-md-3 mb-3"><div class="card text-bg-success"><div class="card-body"><h5><?php echo count($myTickets); ?></h5><p>I miei ticket</p></div></div></div>
</div>

<?php if ($condomini): ?>
<h4>I miei condomini</h4>
<table class="table table-bordered table-striped mb-4">
    <thead><tr><th>Nome</th><th>Indirizzo</th><th>Comune</th></tr></thead>
    <tbody>
    <?php foreach ($condomini as $c): ?>
    <tr><td><?php echo h($c['nome']); ?></td><td><?php echo h($c['indirizzo'] ?? ''); ?></td><td><?php echo h($c['comune'] ?? ''); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="alert alert-info">Non sei ancora associato a nessun condominio. Contatta l'amministratore.</div>
<?php endif; ?>

<?php if ($myRate): ?>
<h4>Rate da pagare</h4>
<table class="table table-bordered table-striped table-sm mb-4">
    <thead><tr><th>Condominio</th><th>Unita</th><th>Descrizione</th><th class="text-end">Importo</th><th>Scadenza</th><th>Stato</th></tr></thead>
    <tbody>
    <?php foreach ($myRate as $r): ?>
    <tr><td><?php echo h($r['condominio_nome']); ?></td><td><?php echo h(trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? ''))); ?></td><td><?php echo h($r['descrizione']); ?></td><td class="text-end">&euro; <?php echo format_euro((float)$r['importo']); ?></td><td><?php echo h($r['scadenza']); ?></td><td><?php echo stato_badge($r['stato']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($documenti): ?>
<h4>Documenti disponibili</h4>
<table class="table table-bordered table-striped table-sm mb-4">
    <thead><tr><th>Titolo</th><th>Categoria</th><th>Data</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($documenti as $d): ?>
    <tr><td><?php echo h($d['titolo']); ?></td><td><?php echo h($d['categoria']); ?></td><td><?php echo h($d['created_at']); ?></td><td><a href="/documenti_download.php?id=<?php echo (int)$d['id']; ?>" class="btn btn-sm btn-secondary">Scarica</a></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($myTickets): ?>
<h4>I miei ticket</h4>
<table class="table table-bordered table-striped table-sm mb-4">
    <thead><tr><th>ID</th><th>Condominio</th><th>Titolo</th><th>Stato</th><th>Aggiornato</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($myTickets as $t): ?>
    <tr><td><?php echo (int)$t['id']; ?></td><td><?php echo h($t['condominio_nome']); ?></td><td><?php echo h($t['titolo']); ?></td><td><?php echo stato_badge($t['stato']); ?></td><td><?php echo h($t['updated_at']); ?></td><td><a href="ticket-view.php?id=<?php echo (int)$t['id']; ?>" class="btn btn-sm btn-outline-primary">Vedi</a></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($myAssemblee): ?>
<h4>Assemblee</h4>
<table class="table table-bordered table-striped table-sm mb-4">
    <thead><tr><th>Condominio</th><th>Titolo</th><th>Data</th><th>Luogo</th><th>Stato</th></tr></thead>
    <tbody>
    <?php foreach ($myAssemblee as $a): ?>
    <tr><td><?php echo h($a['condominio_nome']); ?></td><td><?php echo h($a['titolo']); ?></td><td><?php echo h($a['data_seconda_convocazione']); ?></td><td><?php echo h($a['luogo'] ?? ''); ?></td><td><?php echo stato_badge($a['stato']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($condomini): ?>
<h4>Apri un nuovo ticket</h4>
<form method="post" action="/area-condomino/ticket-crea.php">
    <?php echo csrf_field(); ?>
    <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Condominio*</label>
        <select name="condominio_id" class="form-select" required>
            <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-3"><label class="form-label">Titolo*</label><input name="titolo" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Descrizione*</label><textarea name="descrizione" class="form-control" rows="1" required></textarea></div>
        <div class="col-md-1"><label class="form-label">Priorita</label>
        <select name="priorita" class="form-select"><option value="media">Media</option><option value="bassa">Bassa</option><option value="alta">Alta</option><option value="urgente">Urgente</option></select></div>
        <div class="col-auto align-self-end"><button class="btn btn-primary">Invia</button></div>
    </div>
</form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
