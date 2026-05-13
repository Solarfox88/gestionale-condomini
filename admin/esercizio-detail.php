<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_once __DIR__ . '/../app/Preventivi.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esercizio = Esercizi::find($id);
if (!$esercizio) { echo 'Esercizio non trovato.'; exit; }

$isChiuso = ($esercizio['stato'] === 'chiuso');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna' && !$isChiuso) {
        if (Esercizi::update($id, $_POST)) {
            $msg = 'Esercizio aggiornato.';
            $esercizio = Esercizi::find($id);
            $isChiuso = ($esercizio['stato'] === 'chiuso');
            audit_log('update', 'esercizi', $id);
        } else {
            $msg = 'Errore.';
        }
    } elseif ($azione === 'chiudi') {
        if (Esercizi::chiudi($id)) {
            $msg = 'Esercizio chiuso. Movimenti, rate e pagamenti non sono piu modificabili.';
            $esercizio = Esercizi::find($id);
            $isChiuso = true;
            audit_log('chiudi', 'esercizi', $id);
        } else {
            $msg = 'Impossibile chiudere. L\'esercizio deve essere in stato "aperto".';
        }
    } elseif ($azione === 'riapri') {
        if (Esercizi::riapri($id)) {
            $msg = 'Esercizio riaperto.';
            $esercizio = Esercizi::find($id);
            $isChiuso = false;
            audit_log('riapri', 'esercizi', $id);
        } else {
            $msg = 'Impossibile riaprire.';
        }
    } elseif ($azione === 'calcola_conguagli') {
        $results = Esercizi::calcolaConguagli($id);
        $msg = count($results) > 0 ? 'Conguagli calcolati per ' . count($results) . ' unita.' : 'Nessun conguaglio calcolato (verificare millesimi).';
    } elseif ($azione === 'genera_rate_conguaglio') {
        $scadenza = $_POST['scadenza_conguaglio'] ?? date('Y-m-d', strtotime('+30 days'));
        $count = Esercizi::generaRateConguaglio($id, $scadenza);
        $msg = $count > 0 ? $count . ' rate di conguaglio generate.' : 'Nessuna rata generata (nessun conguaglio positivo o gia generato).';
    } elseif ($azione === 'elimina') {
        if (Esercizi::delete($id)) {
            header('Location: esercizi.php?msg=eliminato');
            exit;
        }
        $msg = 'Impossibile eliminare (verificare che non ci siano movimenti/rate collegate).';
    }
}

$riepilogo = Esercizi::riepilogo($id);
$quadrature = Esercizi::quadrature($id);
$movimenti = Movimenti::all(['esercizio_id' => $id, 'condominio_id' => $esercizio['condominio_id']]);
$preventivo = Preventivi::findByEsercizio($id);
$conguagli = Esercizi::conguagli($id);

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($esercizio['nome']); ?> - <?php echo h($esercizio['condominio_nome']); ?> <?php echo stato_badge($esercizio['stato']); ?></h2>
    <div>
        <?php if ($esercizio['stato'] === 'aperto'): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('ATTENZIONE: Chiudendo l\'esercizio, non sara piu possibile modificare movimenti, rate e pagamenti collegati. Continuare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="chiudi">
            <button class="btn btn-warning btn-sm">Chiudi esercizio</button>
        </form>
        <?php endif; ?>
        <?php if ($isChiuso): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Riaprire l\'esercizio? Sara possibile modificare di nuovo movimenti e rate.')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="riapri">
            <button class="btn btn-info btn-sm">Riapri esercizio</button>
        </form>
        <?php endif; ?>
        <?php if ($preventivo): ?>
        <a href="<?php echo url('/admin/preventivo-detail.php?id=' . (int)$preventivo['id']); ?>" class="btn btn-outline-primary btn-sm">Preventivo</a>
        <?php endif; ?>
        <a href="<?php echo url('/admin/consuntivi.php?esercizio_id=' . $id); ?>" class="btn btn-outline-info btn-sm">Consuntivo</a>
        <?php if (!$isChiuso): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questo esercizio?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina">
            <button class="btn btn-danger btn-sm">Elimina</button>
        </form>
        <?php endif; ?>
        <a href="esercizi.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>
<?php if ($isChiuso): ?><div class="alert alert-warning"><strong>Esercizio chiuso.</strong> Movimenti, rate e pagamenti non sono modificabili. Riaprire l'esercizio per consentire modifiche.</div><?php endif; ?>

<div class="row mb-4">
<div class="col-md-2"><div class="card text-bg-success"><div class="card-body text-center">
    <h5>&euro; <?php echo format_euro($quadrature['entrate']); ?></h5><small>Entrate</small>
</div></div></div>
<div class="col-md-2"><div class="card text-bg-danger"><div class="card-body text-center">
    <h5>&euro; <?php echo format_euro($quadrature['uscite']); ?></h5><small>Uscite</small>
</div></div></div>
<div class="col-md-2"><div class="card text-bg-primary"><div class="card-body text-center">
    <h5>&euro; <?php echo format_euro($quadrature['saldo']); ?></h5><small>Saldo</small>
</div></div></div>
<div class="col-md-2"><div class="card"><div class="card-body text-center">
    <h5>&euro; <?php echo format_euro($quadrature['totale_rate']); ?></h5><small>Rate emesse</small>
</div></div></div>
<div class="col-md-2"><div class="card"><div class="card-body text-center">
    <h5>&euro; <?php echo format_euro($quadrature['totale_incassato']); ?></h5><small>Incassato</small>
</div></div></div>
<div class="col-md-2"><div class="card <?php echo $quadrature['residuo'] > 0 ? 'text-bg-warning' : ''; ?>"><div class="card-body text-center">
    <h5>&euro; <?php echo format_euro($quadrature['residuo']); ?></h5><small>Residuo</small>
</div></div></div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-modifica">Modifica</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-movimenti">Movimenti (<?php echo count($movimenti); ?>)</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-conguagli">Conguagli (<?php echo count($conguagli); ?>)</a></li>
</ul>

<div class="tab-content">

<div class="tab-pane fade show active" id="tab-modifica">
<?php if (!$isChiuso): ?>
<div class="col-md-6">
<h4>Modifica esercizio</h4>
<form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiorna">
    <div class="mb-2"><label class="form-label">Nome*</label><input name="nome" class="form-control" value="<?php echo h($esercizio['nome']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Data inizio</label><input type="date" name="data_inizio" class="form-control" value="<?php echo h($esercizio['data_inizio']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Data fine</label><input type="date" name="data_fine" class="form-control" value="<?php echo h($esercizio['data_fine']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Stato</label>
        <select name="stato" class="form-select">
            <?php foreach (['bozza','aperto'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $esercizio['stato']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary">Salva</button>
</form>
</div>
<?php else: ?>
<div class="alert alert-secondary">
    <p><strong>Nome:</strong> <?php echo h($esercizio['nome']); ?></p>
    <p><strong>Periodo:</strong> <?php echo h($esercizio['data_inizio']); ?> &mdash; <?php echo h($esercizio['data_fine']); ?></p>
    <p class="mb-0">Per modificare, riaprire l'esercizio.</p>
</div>
<?php endif; ?>
</div>

<div class="tab-pane fade" id="tab-movimenti">
<table class="table table-sm table-striped">
<thead><tr><th>Data</th><th>Tipo</th><th>Descrizione</th><th class="text-end">Importo</th></tr></thead>
<tbody>
<?php foreach ($movimenti as $m): ?>
<tr>
    <td><?php echo h($m['data_movimento']); ?></td>
    <td><?php echo $m['tipo']==='entrata' ? '<span class="text-success">Entrata</span>' : '<span class="text-danger">Uscita</span>'; ?></td>
    <td><?php echo h($m['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['importo']); ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($movimenti)): ?><tr><td colspan="4" class="text-muted">Nessun movimento.</td></tr><?php endif; ?>
</tbody>
</table>
</div>

<div class="tab-pane fade" id="tab-conguagli">
<div class="mb-3">
    <form method="post" class="d-inline">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="calcola_conguagli">
        <button class="btn btn-primary btn-sm" onclick="return confirm('Ricalcolare i conguagli? I dati precedenti saranno sovrascritti.')">Calcola conguagli</button>
    </form>
    <?php if (count($conguagli) > 0): ?>
    <form method="post" class="d-inline ms-2">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="genera_rate_conguaglio">
        <label class="form-label d-inline ms-2">Scadenza:</label>
        <input type="date" name="scadenza_conguaglio" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" class="form-control d-inline" style="width:160px">
        <button class="btn btn-success btn-sm">Genera rate conguaglio</button>
    </form>
    <?php endif; ?>
</div>
<?php if (count($conguagli) > 0): ?>
<table class="table table-bordered table-sm">
<thead><tr><th>Unita</th><th class="text-end">Quota spettante</th><th class="text-end">Consuntivo</th><th class="text-end">Conguaglio</th><th>Rata</th></tr></thead>
<tbody>
<?php $totCong = 0; foreach ($conguagli as $cg): $totCong += (float)$cg['importo_conguaglio']; ?>
<tr>
    <td>Sc.<?php echo h($cg['scala'] ?? '-'); ?> P.<?php echo h($cg['piano'] ?? '-'); ?> Int.<?php echo h($cg['interno'] ?? '-'); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$cg['importo_previsto']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$cg['importo_consuntivo']); ?></td>
    <td class="text-end <?php echo (float)$cg['importo_conguaglio'] > 0 ? 'text-danger' : 'text-success'; ?>">
        &euro; <?php echo format_euro((float)$cg['importo_conguaglio']); ?>
    </td>
    <td><?php echo $cg['rata_id'] ? '<span class="badge bg-success">Generata #' . (int)$cg['rata_id'] . '</span>' : '<span class="badge bg-secondary">Non generata</span>'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr class="fw-bold"><td>Totale</td><td></td><td></td><td class="text-end">&euro; <?php echo format_euro($totCong); ?></td><td></td></tr></tfoot>
</table>
<?php else: ?>
<p class="text-muted">Nessun conguaglio calcolato. Clicca "Calcola conguagli" per generarli.</p>
<?php endif; ?>
</div>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
