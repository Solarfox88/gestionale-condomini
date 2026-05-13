<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$riparto = Riparti::find($id);
if (!$riparto) { header('Location: ' . url('/admin/riparti.php')); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiorna' && $riparto['stato'] === 'bozza') {
        $ok = Riparti::update($id, $_POST);
        $msg = $ok ? 'Riparto aggiornato.' : 'Errore.';
        $riparto = Riparti::find($id);
    } elseif ($azione === 'calcola') {
        $ok = Riparti::calcola($id);
        $msg = $ok ? 'Riparto calcolato. Verifica le quote e apporta rettifiche se necessario.' : 'Errore. Verifica che ci siano unita con millesimi.';
        $riparto = Riparti::find($id);
    } elseif ($azione === 'approva') {
        $ok = Riparti::updateStato($id, 'approvato');
        $msg = $ok ? 'Riparto approvato.' : 'Errore.';
        $riparto = Riparti::find($id);
        audit_log('approva', 'riparti', $id);
    } elseif ($azione === 'genera_rate') {
        $dataScadenza = $_POST['data_scadenza'] ?? date('Y-m-01', strtotime('+1 month'));
        $ok = Riparti::generaRate($id, $dataScadenza);
        $msg = $ok ? 'Rate generate! Vedi la pagina Rate.' : 'Errore.';
        $riparto = Riparti::find($id);
        if ($ok) audit_log('genera_rate', 'riparti', $id);
    } elseif ($azione === 'escludi') {
        $unitaId = (int)$_POST['unita_id'];
        Riparti::toggleEsclusione($id, $unitaId);
        Riparti::calcola($id);
        $riparto = Riparti::find($id);
        $msg = 'Esclusione aggiornata e quote ricalcolate.';
    } elseif ($azione === 'rettifica') {
        $detId = (int)$_POST['dettaglio_id'];
        $nuovoImporto = (float)$_POST['nuovo_importo'];
        Riparti::rettificaQuota($detId, $nuovoImporto);
        $msg = 'Quota rettificata.';
    } elseif ($azione === 'reset_rettifica') {
        $detId = (int)$_POST['dettaglio_id'];
        global $pdo;
        $pdo->prepare('UPDATE riparti_dettaglio SET importo_rettificato = NULL WHERE id = :id')->execute(['id' => $detId]);
        $msg = 'Rettifica rimossa.';
    }
}

$dettaglio = Riparti::getDettaglio($id);
$totaleQuote = 0;
$totaleEffettivo = 0;
$totaleMillesimi = 0;
$totaleMillNonEscl = 0;
foreach ($dettaglio as $d) {
    $totaleQuote += (float)$d['importo'];
    $totaleEffettivo += Riparti::importoEffettivo($d);
    $totaleMillesimi += (float)$d['millesimi'];
    if (!$d['esclusa']) $totaleMillNonEscl += (float)$d['millesimi'];
}
$differenza = (float)$riparto['importo_totale'] - $totaleEffettivo;
$rateGenerate = Riparti::rateGenerate($id);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=riparto_' . $id . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Riparto: ' . $riparto['descrizione'], 'Condominio: ' . $riparto['condominio_nome'], 'Importo: ' . $riparto['importo_totale']]);
    fputcsv($out, ['Scala', 'Piano', 'Interno', 'Millesimi', 'Quota calcolata', 'Quota rettificata', 'Quota effettiva', 'Esclusa']);
    foreach ($dettaglio as $d) {
        fputcsv($out, [
            $d['scala'] ?? '', $d['piano'] ?? '', $d['interno'] ?? '',
            number_format((float)$d['millesimi'], 4, '.', ''),
            number_format((float)$d['importo'], 2, '.', ''),
            $d['importo_rettificato'] !== null ? number_format((float)$d['importo_rettificato'], 2, '.', '') : '',
            number_format(Riparti::importoEffettivo($d), 2, '.', ''),
            $d['esclusa'] ? 'Si' : 'No'
        ]);
    }
    fputcsv($out, ['', '', '', number_format($totaleMillNonEscl, 4, '.', ''), '', '', number_format($totaleEffettivo, 2, '.', ''), '']);
    fclose($out);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Riparto #<?php echo (int)$riparto['id']; ?> &mdash; <?php echo h($riparto['descrizione']); ?> <?php echo stato_badge($riparto['stato']); ?></h2>
    <div>
        <a href="<?php echo url('/admin/riparto-detail.php?id=' . $id . '&export=csv'); ?>" class="btn btn-outline-success btn-sm">Esporta CSV</a>
        <a href="<?php echo url('/admin/riparti.php'); ?>" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row mb-4">
<div class="col-md-6">
    <div class="card">
    <div class="card-body">
        <p><strong>Condominio:</strong> <?php echo h($riparto['condominio_nome']); ?></p>
        <p><strong>Esercizio:</strong> <?php echo h($riparto['esercizio_nome']); ?></p>
        <p><strong>Tipo millesimi:</strong> <?php echo h($riparto['tipo_millesimi']); ?>
            <?php if ($riparto['tabella_nome']): ?> (<?php echo h($riparto['tabella_nome']); ?>)<?php endif; ?></p>
        <p><strong>Tipo spesa:</strong> <?php echo h($riparto['tipo_spesa']); ?></p>
        <?php if ($riparto['categoria_nome']): ?><p><strong>Categoria:</strong> <?php echo h($riparto['categoria_nome']); ?></p><?php endif; ?>
        <?php if ($riparto['scala_filtro']): ?><p><strong>Filtro scala:</strong> <?php echo h($riparto['scala_filtro']); ?></p><?php endif; ?>
        <p><strong>Importo totale:</strong> &euro; <?php echo format_euro((float)$riparto['importo_totale']); ?></p>
        <p><strong>Numero rate:</strong> <?php echo (int)$riparto['num_rate']; ?></p>
        <?php if ($riparto['note']): ?><p><strong>Note:</strong> <?php echo h($riparto['note']); ?></p><?php endif; ?>
    </div>
    </div>
</div>
<div class="col-md-6">
    <?php if ($riparto['stato'] === 'bozza'): ?>
    <div class="card mb-3">
    <div class="card-header">Modifica riparto</div>
    <div class="card-body">
    <form method="post" class="row g-2">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="azione" value="aggiorna">
        <div class="col-12"><label class="form-label">Descrizione</label><input name="descrizione" class="form-control" value="<?php echo h($riparto['descrizione']); ?>" required></div>
        <div class="col-6"><label class="form-label">Tabella millesimale</label>
            <select name="tipo_millesimi" class="form-select">
                <?php foreach (['proprieta' => 'Proprieta', 'scale' => 'Scale', 'ascensore' => 'Ascensore', 'riscaldamento' => 'Riscaldamento', 'personalizzato' => 'Personalizzata'] as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $riparto['tipo_millesimi'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6"><label class="form-label">Tipo spesa</label>
            <select name="tipo_spesa" class="form-select">
                <?php foreach (['ordinaria' => 'Ordinaria', 'straordinaria' => 'Straordinaria', 'individuale' => 'Individuale'] as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $riparto['tipo_spesa'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6"><label class="form-label">Importo totale</label><input name="importo_totale" type="number" step="0.01" class="form-control" value="<?php echo (float)$riparto['importo_totale']; ?>" required></div>
        <div class="col-6"><label class="form-label">Numero rate</label><input name="num_rate" type="number" min="1" max="12" class="form-control" value="<?php echo (int)$riparto['num_rate']; ?>"></div>
        <div class="col-6"><label class="form-label">Filtro scala</label><input name="scala_filtro" class="form-control" value="<?php echo h($riparto['scala_filtro'] ?? ''); ?>" placeholder="Vuoto=tutte"></div>
        <div class="col-12"><label class="form-label">Note</label><input name="note" class="form-control" value="<?php echo h($riparto['note'] ?? ''); ?>"></div>
        <div class="col-12"><button class="btn btn-primary btn-sm">Salva</button></div>
    </form>
    </div>
    </div>
    <?php endif; ?>

    <div class="card">
    <div class="card-header">Azioni</div>
    <div class="card-body">
        <?php if (in_array($riparto['stato'], ['bozza', 'calcolato'])): ?>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="calcola">
            <button class="btn btn-info btn-sm" onclick="return confirm('Calcolare/ricalcolare le quote?')">Calcola quote</button>
        </form>
        <?php endif; ?>
        <?php if ($riparto['stato'] === 'calcolato'): ?>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="approva">
            <button class="btn btn-success btn-sm">Approva riparto</button>
        </form>
        <?php endif; ?>
        <?php if (in_array($riparto['stato'], ['calcolato', 'approvato'])): ?>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="genera_rate">
            <label class="form-label d-inline ms-2">Scadenza 1a rata:</label>
            <input type="date" name="data_scadenza" value="<?php echo date('Y-m-01', strtotime('+1 month')); ?>" class="form-control d-inline" style="width:160px">
            <button class="btn btn-primary btn-sm">Genera rate</button>
        </form>
        <?php endif; ?>
    </div>
    </div>
</div>
</div>

<?php if (!empty($dettaglio)): ?>
<h4>Dettaglio quote</h4>
<?php if (abs($differenza) > 0.01 && !empty($dettaglio)): ?>
<div class="alert alert-warning">
    <strong>Attenzione:</strong> Differenza tra importo totale (&euro; <?php echo format_euro((float)$riparto['importo_totale']); ?>) e somma quote effettive (&euro; <?php echo format_euro($totaleEffettivo); ?>): <strong>&euro; <?php echo format_euro($differenza); ?></strong>
</div>
<?php endif; ?>
<div class="table-responsive">
<table class="table table-bordered table-sm">
<thead>
<tr>
    <th>Scala</th><th>Piano</th><th>Int.</th>
    <th class="text-end">Millesimi</th><th class="text-end">Quota calcolata</th>
    <th class="text-end">Rettifica</th><th class="text-end">Quota effettiva</th>
    <th>Esclusa</th>
    <?php if (in_array($riparto['stato'], ['calcolato', 'approvato'])): ?><th>Azioni</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($dettaglio as $d):
    $effettivo = Riparti::importoEffettivo($d);
?>
<tr class="<?php echo $d['esclusa'] ? 'table-secondary text-decoration-line-through' : ''; ?>">
    <td><?php echo h($d['scala'] ?? ''); ?></td>
    <td><?php echo h($d['piano'] ?? ''); ?></td>
    <td><?php echo h($d['interno'] ?? ''); ?></td>
    <td class="text-end"><?php echo number_format((float)$d['millesimi'], 4, ',', '.'); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$d['importo']); ?></td>
    <td class="text-end"><?php echo $d['importo_rettificato'] !== null ? '&euro; ' . format_euro((float)$d['importo_rettificato']) : '-'; ?></td>
    <td class="text-end fw-bold">&euro; <?php echo format_euro($effettivo); ?></td>
    <td><?php echo $d['esclusa'] ? '<span class="badge bg-danger">Esclusa</span>' : '<span class="badge bg-success">Inclusa</span>'; ?></td>
    <?php if (in_array($riparto['stato'], ['calcolato', 'approvato'])): ?>
    <td>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="escludi"><input type="hidden" name="unita_id" value="<?php echo (int)$d['unita_id']; ?>">
            <button class="btn btn-sm <?php echo $d['esclusa'] ? 'btn-outline-success' : 'btn-outline-warning'; ?>"><?php echo $d['esclusa'] ? 'Includi' : 'Escludi'; ?></button>
        </form>
        <?php if (!$d['esclusa']): ?>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="rettifica"><input type="hidden" name="dettaglio_id" value="<?php echo (int)$d['id']; ?>">
            <input type="number" step="0.01" name="nuovo_importo" value="<?php echo format_euro($effettivo); ?>" class="form-control form-control-sm d-inline" style="width:100px">
            <button class="btn btn-sm btn-outline-primary">Rettifica</button>
        </form>
        <?php if ($d['importo_rettificato'] !== null): ?>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="reset_rettifica"><input type="hidden" name="dettaglio_id" value="<?php echo (int)$d['id']; ?>">
            <button class="btn btn-sm btn-outline-secondary">Reset</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="fw-bold table-info">
    <td colspan="3">Totale</td>
    <td class="text-end"><?php echo number_format($totaleMillNonEscl, 4, ',', '.'); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($totaleQuote); ?></td>
    <td></td>
    <td class="text-end">&euro; <?php echo format_euro($totaleEffettivo); ?></td>
    <td colspan="<?php echo in_array($riparto['stato'], ['calcolato', 'approvato']) ? 2 : 1; ?>"></td>
</tr>
</tfoot>
</table>
</div>
<?php endif; ?>

<?php if (!empty($rateGenerate)): ?>
<h4>Rate generate da questo riparto (<?php echo count($rateGenerate); ?>)</h4>
<table class="table table-bordered table-sm">
<thead><tr><th>Unita</th><th>Descrizione</th><th class="text-end">Importo</th><th>Scadenza</th><th>Stato</th></tr></thead>
<tbody>
<?php foreach ($rateGenerate as $rg): ?>
<tr>
    <td>Sc.<?php echo h($rg['scala'] ?? ''); ?> P.<?php echo h($rg['piano'] ?? ''); ?> Int.<?php echo h($rg['interno'] ?? ''); ?></td>
    <td><?php echo h($rg['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$rg['importo']); ?></td>
    <td><?php echo h($rg['scadenza']); ?></td>
    <td><?php echo stato_badge($rg['stato']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
