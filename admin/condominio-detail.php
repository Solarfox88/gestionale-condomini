<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/UnitaPersone.php';
require_once __DIR__ . '/../app/Documenti.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Tickets.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cond = get_condominio($id);
if (!$cond) { echo 'Condominio non trovato.'; exit; }

$unitaList = get_unita($id);
$documenti = get_documenti($id);
$esercizi = Esercizi::all($id);
$rate = Rate::all(['condominio_id' => $id]);
$tickets = Tickets::all(['condominio_id' => $id]);
$assemblee = Assemblee::all($id);
$saldo = Movimenti::saldoCondominio($id);

global $pdo;
$morositaQ = $pdo->prepare("SELECT COALESCE(SUM(r.importo - COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id=r.id),0)),0) FROM rate r WHERE r.condominio_id=:cid AND r.stato IN ('scaduta','parziale','da_pagare')");
$morositaQ->execute(['cid' => $id]);
$morosita = (float)$morositaQ->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($cond['nome']); ?></h2>
    <div>
        <a href="condominio-edit.php?id=<?php echo $id; ?>" class="btn btn-secondary">Modifica</a>
        <a href="stampa-condominio.php?id=<?php echo $id; ?>" class="btn btn-outline-info btn-sm" target="_blank">Stampa Scheda</a>
        <a href="condomini.php" class="btn btn-outline-secondary">Indietro</a>
    </div>
</div>

<?php echo stato_badge($cond['status']); ?>

<div class="row mt-3 mb-4">
    <div class="col-md-6">
        <table class="table table-sm">
            <tr><th>Indirizzo</th><td><?php echo h($cond['indirizzo'] ?? '-'); ?></td></tr>
            <tr><th>Comune</th><td><?php echo h(($cond['comune'] ?? '') . ' (' . ($cond['provincia'] ?? '') . ') ' . ($cond['cap'] ?? '')); ?></td></tr>
            <tr><th>Codice Fiscale</th><td><?php echo h($cond['codice_fiscale'] ?? '-'); ?></td></tr>
            <tr><th>Email</th><td><?php echo h($cond['email'] ?? '-'); ?></td></tr>
            <tr><th>PEC</th><td><?php echo h($cond['pec'] ?? '-'); ?></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-sm">
            <tr><th>IBAN</th><td><?php echo h($cond['iban'] ?? '-'); ?></td></tr>
            <tr><th>Banca</th><td><?php echo h($cond['banca'] ?? '-'); ?></td></tr>
            <tr><th>Saldo movimenti</th><td>&euro; <?php echo format_euro($saldo); ?></td></tr>
            <tr><th>Morosita</th><td class="<?php echo $morosita > 0 ? 'text-danger fw-bold' : ''; ?>">&euro; <?php echo format_euro($morosita); ?></td></tr>
            <tr><th>Note</th><td><?php echo nl2br(h($cond['note'] ?? '-')); ?></td></tr>
        </table>
    </div>
</div>

<ul class="nav nav-tabs" id="condTab" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabUnita">Unita (<?php echo count($unitaList); ?>)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabDocumenti">Documenti (<?php echo count($documenti); ?>)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabEsercizi">Esercizi (<?php echo count($esercizi); ?>)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRate">Rate (<?php echo count($rate); ?>)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabTicket">Ticket (<?php echo count($tickets); ?>)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAssemblee">Assemblee (<?php echo count($assemblee); ?>)</a></li>
</ul>
<div class="tab-content mt-3">
    <div class="tab-pane fade show active" id="tabUnita">
        <table class="table table-sm table-striped">
        <thead><tr><th>Scala</th><th>Piano</th><th>Interno</th><th>MQ</th><th>Mill. Prop.</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($unitaList as $u): ?>
        <tr>
            <td><?php echo h($u['scala'] ?? '-'); ?></td>
            <td><?php echo h($u['piano'] ?? '-'); ?></td>
            <td><?php echo h($u['interno'] ?? '-'); ?></td>
            <td><?php echo $u['mq'] ? number_format((float)$u['mq'],2,',','.') : '-'; ?></td>
            <td><?php echo number_format((float)$u['millesimi_proprieta'],4,',','.'); ?></td>
            <td><a href="unita-detail.php?id=<?php echo (int)$u['id']; ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="tabDocumenti">
        <table class="table table-sm table-striped">
        <thead><tr><th>Titolo</th><th>Categoria</th><th>Visibilita</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($documenti as $d): ?>
        <tr>
            <td><?php echo h($d['titolo']); ?></td>
            <td><?php echo h($d['categoria']); ?></td>
            <td><?php echo h($d['visibility']); ?></td>
            <td><?php echo h($d['created_at']); ?></td>
            <td><a href="<?php echo url('/documenti_download.php?id=' . (int)$d['id']); ?>" class="btn btn-sm btn-outline-secondary">Scarica</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="tabEsercizi">
        <table class="table table-sm table-striped">
        <thead><tr><th>Nome</th><th>Periodo</th><th>Stato</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($esercizi as $e): ?>
        <tr>
            <td><?php echo h($e['nome']); ?></td>
            <td><?php echo h($e['data_inizio'].' - '.$e['data_fine']); ?></td>
            <td><?php echo stato_badge($e['stato']); ?></td>
            <td><a href="esercizio-detail.php?id=<?php echo (int)$e['id']; ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="tabRate">
        <table class="table table-sm table-striped">
        <thead><tr><th>Descrizione</th><th>Unita</th><th>Importo</th><th>Scadenza</th><th>Stato</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($rate, 0, 20) as $r): ?>
        <tr>
            <td><?php echo h($r['descrizione']); ?></td>
            <td><?php echo h(trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? ''))); ?></td>
            <td class="text-end">&euro; <?php echo format_euro((float)$r['importo']); ?></td>
            <td><?php echo h($r['scadenza']); ?></td>
            <td><?php echo stato_badge($r['stato']); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php if (count($rate) > 20): ?><a href="rate.php?condominio_id=<?php echo $id; ?>">Vedi tutte &rarr;</a><?php endif; ?>
    </div>
    <div class="tab-pane fade" id="tabTicket">
        <table class="table table-sm table-striped">
        <thead><tr><th>Titolo</th><th>Priorita</th><th>Stato</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
            <td><?php echo h($t['titolo']); ?></td>
            <td><?php echo stato_badge($t['priorita']); ?></td>
            <td><?php echo stato_badge($t['stato']); ?></td>
            <td><?php echo h($t['created_at']); ?></td>
            <td><a href="ticket-detail.php?id=<?php echo (int)$t['id']; ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="tabAssemblee">
        <table class="table table-sm table-striped">
        <thead><tr><th>Titolo</th><th>Data</th><th>Luogo</th><th>Stato</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($assemblee as $a): ?>
        <tr>
            <td><?php echo h($a['titolo']); ?></td>
            <td><?php echo h($a['data_seconda_convocazione']); ?></td>
            <td><?php echo h($a['luogo'] ?? ''); ?></td>
            <td><?php echo stato_badge($a['stato']); ?></td>
            <td><a href="assemblea-detail.php?id=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
