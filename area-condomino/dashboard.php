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
usort($documenti, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
$documentiRecenti = array_slice($documenti, 0, 5);

$stmtT = $pdo->prepare("SELECT t.*, c.nome AS condominio_nome FROM ticket t JOIN condomini c ON c.id = t.condominio_id WHERE t.aperto_da = :uid ORDER BY t.updated_at DESC LIMIT 5");
$stmtT->execute(['uid' => $userId]);
$myTickets = $stmtT->fetchAll(PDO::FETCH_ASSOC);
$ticketAperti = array_filter($myTickets, fn($t) => !in_array($t['stato'], ['chiuso','risolto','respinto']));

$condIds = array_column($condomini, 'id');
$myRate = []; $myAssemblee = []; $totDovuto = 0; $totPagato = 0; $rateScadute = 0; $pagamentiCount = 0;

if ($condIds) {
    $ph = implode(',', array_fill(0, count($condIds), '?'));
    Rate::aggiornaScadute();

    $stmtR = $pdo->prepare("SELECT r.*, c.nome AS condominio_nome, ui.scala, ui.piano, ui.interno FROM rate r JOIN condomini c ON c.id=r.condominio_id JOIN unita_immobiliari ui ON ui.id=r.unita_id WHERE r.condominio_id IN ($ph) AND r.stato IN ('da_pagare','parziale','scaduta') ORDER BY r.scadenza ASC LIMIT 10");
    $stmtR->execute($condIds);
    $myRate = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $stmtTot = $pdo->prepare("SELECT COALESCE(SUM(r.importo),0) FROM rate r WHERE r.condominio_id IN ($ph) AND r.stato IN ('da_pagare','parziale','scaduta')");
    $stmtTot->execute($condIds);
    $totDovuto = (float)$stmtTot->fetchColumn();

    $stmtPag = $pdo->prepare("SELECT COALESCE(SUM(pg.importo),0), COUNT(pg.id) FROM pagamenti pg JOIN rate r ON r.id=pg.rata_id WHERE r.condominio_id IN ($ph)");
    $stmtPag->execute($condIds);
    $pagRow = $stmtPag->fetch(PDO::FETCH_NUM);
    $totPagato = (float)$pagRow[0];
    $pagamentiCount = (int)$pagRow[1];

    $stmtSc = $pdo->prepare("SELECT COUNT(*) FROM rate r WHERE r.condominio_id IN ($ph) AND r.stato='scaduta'");
    $stmtSc->execute($condIds);
    $rateScadute = (int)$stmtSc->fetchColumn();

    $stmtA = $pdo->prepare("SELECT a.*, c.nome AS condominio_nome FROM assemblee a JOIN condomini c ON c.id=a.condominio_id WHERE a.condominio_id IN ($ph) AND a.data_prima_convocazione >= CURDATE() ORDER BY a.data_prima_convocazione ASC LIMIT 5");
    $stmtA->execute($condIds);
    $myAssemblee = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}

$comNonLette = 0;
try {
    $stmtCom = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM comunicazioni_destinatari cd JOIN comunicazioni c ON c.id=cd.comunicazione_id JOIN condomini_users cu ON cu.condominio_id=c.condominio_id WHERE cu.user_id=:uid AND c.stato='inviata' AND cd.letto_at IS NULL");
    $stmtCom->execute(['uid' => $userId]);
    $comNonLette = (int)$stmtCom->fetchColumn();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>
<h2>Area Condomino</h2>
<p>Benvenuto, <strong><?php echo h($_SESSION['user']['name']); ?></strong></p>

<div class="row mb-4">
    <div class="col-md-2 mb-3"><div class="card text-bg-primary"><div class="card-body text-center"><h4><?php echo count($condomini); ?></h4><small>Condomini</small></div></div></div>
    <div class="col-md-2 mb-3"><div class="card text-bg-danger"><div class="card-body text-center"><h4>&euro; <?php echo format_euro($totDovuto - $totPagato); ?></h4><small>Saldo residuo</small></div></div></div>
    <div class="col-md-2 mb-3"><div class="card text-bg-warning"><div class="card-body text-center"><h4><?php echo count($myRate); ?></h4><small>Rate aperte</small></div></div></div>
    <div class="col-md-2 mb-3"><div class="card <?php echo $rateScadute > 0 ? 'text-bg-danger' : ''; ?>"><div class="card-body text-center"><h4><?php echo $rateScadute; ?></h4><small>Rate scadute</small></div></div></div>
    <div class="col-md-2 mb-3"><div class="card text-bg-success"><div class="card-body text-center"><h4><?php echo $pagamentiCount; ?></h4><small>Pagamenti</small></div></div></div>
    <div class="col-md-2 mb-3"><div class="card <?php echo $comNonLette > 0 ? 'text-bg-info' : ''; ?>"><div class="card-body text-center"><h4><?php echo $comNonLette; ?></h4><small>Comunicazioni</small></div></div></div>
</div>

<div class="row">
<div class="col-md-6">

<?php if ($myRate): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between"><strong>Rate da pagare</strong><a href="rate-pagamenti.php" class="btn btn-sm btn-outline-primary">Tutte</a></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead><tr><th>Descrizione</th><th class="text-end">Importo</th><th>Scadenza</th><th>Stato</th></tr></thead>
    <tbody>
    <?php foreach ($myRate as $r): ?>
    <tr><td><?php echo h($r['descrizione']); ?></td><td class="text-end">&euro; <?php echo format_euro((float)$r['importo']); ?></td><td><?php echo date('d/m/Y', strtotime($r['scadenza'])); ?></td><td><?php echo stato_badge($r['stato']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php if ($documentiRecenti): ?>
<div class="card mb-3">
<div class="card-header"><strong>Documenti recenti</strong></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead><tr><th>Titolo</th><th>Categoria</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($documentiRecenti as $d): ?>
    <tr><td><?php echo h($d['titolo']); ?></td><td><?php echo h($d['categoria']); ?></td><td><a href="<?php echo url('/documenti_download.php?id=' . (int)$d['id']); ?>" class="btn btn-sm btn-secondary">Scarica</a></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
<?php endif; ?>

</div>
<div class="col-md-6">

<?php if ($myAssemblee): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between"><strong>Prossime assemblee</strong><a href="assemblee.php" class="btn btn-sm btn-outline-primary">Tutte</a></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead><tr><th>Titolo</th><th>Data</th><th>Stato</th></tr></thead>
    <tbody>
    <?php foreach ($myAssemblee as $a): ?>
    <tr><td><?php echo h($a['titolo']); ?></td><td><?php echo !empty($a['data_prima_convocazione']) ? date('d/m/Y', strtotime($a['data_prima_convocazione'])) : '-'; ?></td><td><?php echo stato_badge($a['stato']); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php if ($ticketAperti): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between"><strong>Ticket aperti</strong><a href="ticket-crea.php" class="btn btn-sm btn-outline-success">Nuovo</a></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead><tr><th>Titolo</th><th>Stato</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($ticketAperti as $t): ?>
    <tr><td><?php echo h($t['titolo']); ?></td><td><?php echo stato_badge($t['stato']); ?></td><td><a href="ticket-view.php?id=<?php echo (int)$t['id']; ?>" class="btn btn-sm btn-outline-primary">Vedi</a></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php if ($condomini): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between"><strong>I miei condomini</strong><a href="unita.php" class="btn btn-sm btn-outline-primary">Unita</a></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead><tr><th>Nome</th><th>Indirizzo</th></tr></thead>
    <tbody>
    <?php foreach ($condomini as $c): ?>
    <tr><td><?php echo h($c['nome']); ?></td><td><?php echo h(($c['indirizzo'] ?? '') . ' ' . ($c['comune'] ?? '')); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
<?php endif; ?>

</div>
</div>

<?php if ($condomini): ?>
<div class="card">
<div class="card-header"><strong>Apri un nuovo ticket</strong></div>
<div class="card-body">
<form method="post" action="<?php echo url('/area-condomino/ticket-crea.php'); ?>">
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
</div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
