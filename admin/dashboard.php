<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/Documenti.php';
require_once __DIR__ . '/../app/Tickets.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_login();
require_admin();

Rate::aggiornaScadute();

$condomini = get_condomini();
$numCondomini = count($condomini);
$unita = get_unita();
$numUnita = count($unita);
$persone = get_persone();
$numPersone = count($persone);

global $pdo;
$ticketAperti = $pdo->query("SELECT COUNT(*) FROM ticket WHERE stato NOT IN ('chiuso','risolto','respinto')")->fetchColumn();
$rateScadenza = $pdo->query("SELECT COUNT(*) FROM rate WHERE stato IN ('da_pagare','parziale') AND scadenza <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$morositaTot = $pdo->query("SELECT COALESCE(SUM(r.importo - COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id=r.id),0)),0) FROM rate r WHERE r.stato IN ('scaduta','parziale','da_pagare')")->fetchColumn();
$saldoMov = $pdo->query("SELECT COALESCE(SUM(CASE WHEN tipo='entrata' THEN importo ELSE -importo END),0) FROM movimenti")->fetchColumn();

$documentiRecenti = $pdo->query("SELECT d.titolo, d.created_at, c.nome AS condominio_nome FROM documenti d JOIN condomini c ON c.id=d.condominio_id ORDER BY d.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$prossimeAssemblee = Assemblee::prossime(5);

include __DIR__ . '/../includes/header.php';
?>
<h1>Dashboard Amministratore</h1>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-bg-primary"><div class="card-body">
            <h5 class="card-title"><?php echo $numCondomini; ?></h5>
            <p class="card-text">Condomini</p>
            <a href="condomini.php" class="btn btn-sm btn-light">Gestisci</a>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-bg-info"><div class="card-body">
            <h5 class="card-title"><?php echo $numUnita; ?></h5>
            <p class="card-text">Unita immobiliari</p>
            <a href="unita.php" class="btn btn-sm btn-light">Gestisci</a>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-bg-success"><div class="card-body">
            <h5 class="card-title"><?php echo $numPersone; ?></h5>
            <p class="card-text">Persone</p>
            <a href="persone.php" class="btn btn-sm btn-light">Gestisci</a>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-bg-warning"><div class="card-body">
            <h5 class="card-title"><?php echo (int)$ticketAperti; ?></h5>
            <p class="card-text">Ticket aperti</p>
            <a href="ticket.php" class="btn btn-sm btn-light">Gestisci</a>
        </div></div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-bg-danger"><div class="card-body">
            <h5 class="card-title"><?php echo (int)$rateScadenza; ?></h5>
            <p class="card-text">Rate in scadenza (30gg)</p>
            <a href="rate.php" class="btn btn-sm btn-light">Gestisci</a>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-bg-dark"><div class="card-body">
            <h5 class="card-title">&euro; <?php echo format_euro((float)$morositaTot); ?></h5>
            <p class="card-text">Morosita totale</p>
            <a href="morosita.php" class="btn btn-sm btn-light">Dettagli</a>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-bg-secondary"><div class="card-body">
            <h5 class="card-title">&euro; <?php echo format_euro((float)$saldoMov); ?></h5>
            <p class="card-text">Saldo movimenti</p>
            <a href="movimenti.php" class="btn btn-sm btn-light">Dettagli</a>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card"><div class="card-body">
            <h5 class="card-title"><?php echo count($prossimeAssemblee); ?></h5>
            <p class="card-text">Prossime assemblee</p>
            <a href="assemblee.php" class="btn btn-sm btn-primary">Gestisci</a>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h4>Documenti recenti</h4>
        <?php if ($documentiRecenti): ?>
        <table class="table table-sm table-striped">
            <thead><tr><th>Titolo</th><th>Condominio</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ($documentiRecenti as $d): ?>
            <tr><td><?php echo h($d['titolo']); ?></td><td><?php echo h($d['condominio_nome']); ?></td><td><?php echo h($d['created_at']); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="text-muted">Nessun documento recente.</p><?php endif; ?>
    </div>
    <div class="col-md-6">
        <h4>Prossime assemblee</h4>
        <?php if ($prossimeAssemblee): ?>
        <table class="table table-sm table-striped">
            <thead><tr><th>Titolo</th><th>Condominio</th><th>Data</th><th>Stato</th></tr></thead>
            <tbody>
            <?php foreach ($prossimeAssemblee as $a): ?>
            <tr>
                <td><a href="assemblea-detail.php?id=<?php echo (int)$a['id']; ?>"><?php echo h($a['titolo']); ?></a></td>
                <td><?php echo h($a['condominio_nome']); ?></td>
                <td><?php echo h($a['data_seconda_convocazione']); ?></td>
                <td><?php echo stato_badge($a['stato']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="text-muted">Nessuna assemblea in programma.</p><?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
