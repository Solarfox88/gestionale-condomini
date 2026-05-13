<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$riparto = Riparti::find($id);
if (!$riparto) {
    header('Location: ' . url('/admin/riparti.php'));
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiorna') {
        $ok = Riparti::update($id, $_POST);
        $msg = $ok ? 'Riparto aggiornato con successo.' : 'Errore durante l\'aggiornamento.';
        $riparto = Riparti::find($id);
    } elseif ($azione === 'calcola') {
        $ok = Riparti::calcola($id);
        $msg = $ok ? 'Riparto calcolato con successo.' : 'Errore durante il calcolo. Verifica che ci siano unita con millesimi.';
        $riparto = Riparti::find($id);
    } elseif ($azione === 'approva') {
        $ok = Riparti::updateStato($id, 'approvato');
        $msg = $ok ? 'Riparto approvato.' : 'Errore.';
        $riparto = Riparti::find($id);
    } elseif ($azione === 'genera_rate') {
        $dataScadenza = $_POST['data_scadenza'] ?? date('Y-m-01', strtotime('+1 month'));
        $ok = Riparti::generaRate($id, $dataScadenza);
        $msg = $ok ? 'Rate generate con successo! Vedi la pagina Rate.' : 'Errore durante la generazione delle rate.';
        $riparto = Riparti::find($id);
    }
}

$dettaglio = Riparti::getDettaglio($id);
$totaleQuote = 0;
$totaleMillesimi = 0;
foreach ($dettaglio as $d) {
    $totaleQuote += (float)$d['importo'];
    $totaleMillesimi += (float)$d['millesimi'];
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Riparto #<?php echo (int)$riparto['id']; ?> &mdash; <?php echo htmlspecialchars($riparto['descrizione']); ?></h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<div class="row mb-4">
<div class="col-md-6">
    <div class="card">
    <div class="card-body">
        <p><strong>Condominio:</strong> <?php echo htmlspecialchars($riparto['condominio_nome']); ?></p>
        <p><strong>Esercizio:</strong> <?php echo htmlspecialchars($riparto['esercizio_nome']); ?></p>
        <p><strong>Tipo millesimi:</strong> <?php echo htmlspecialchars($riparto['tipo_millesimi']); ?></p>
        <p><strong>Tipo spesa:</strong> <?php echo htmlspecialchars($riparto['tipo_spesa']); ?></p>
        <p><strong>Importo totale:</strong> &euro; <?php echo number_format((float)$riparto['importo_totale'], 2, ',', '.'); ?></p>
        <p><strong>Numero rate:</strong> <?php echo (int)$riparto['num_rate']; ?></p>
        <p><strong>Stato:</strong>
            <?php
            $badge = ['bozza' => 'secondary', 'calcolato' => 'info', 'approvato' => 'success', 'rate_generate' => 'primary'];
            $cls = $badge[$riparto['stato']] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $cls; ?>"><?php echo htmlspecialchars($riparto['stato']); ?></span>
        </p>
        <?php if ($riparto['note']): ?>
        <p><strong>Note:</strong> <?php echo htmlspecialchars($riparto['note']); ?></p>
        <?php endif; ?>
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
        <div class="col-12"><label class="form-label">Descrizione</label><input name="descrizione" class="form-control" value="<?php echo htmlspecialchars($riparto['descrizione']); ?>" required></div>
        <div class="col-6"><label class="form-label">Tabella millesimale</label>
            <select name="tipo_millesimi" class="form-select">
                <?php foreach (['proprieta' => 'Proprieta', 'scale' => 'Scale', 'ascensore' => 'Ascensore', 'riscaldamento' => 'Riscaldamento'] as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $riparto['tipo_millesimi'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6"><label class="form-label">Tipo spesa</label>
            <select name="tipo_spesa" class="form-select">
                <option value="ordinaria" <?php echo $riparto['tipo_spesa'] === 'ordinaria' ? 'selected' : ''; ?>>Ordinaria</option>
                <option value="straordinaria" <?php echo $riparto['tipo_spesa'] === 'straordinaria' ? 'selected' : ''; ?>>Straordinaria</option>
            </select>
        </div>
        <div class="col-6"><label class="form-label">Importo totale</label><input name="importo_totale" type="number" step="0.01" class="form-control" value="<?php echo (float)$riparto['importo_totale']; ?>" required></div>
        <div class="col-6"><label class="form-label">Numero rate</label><input name="num_rate" type="number" min="1" max="12" class="form-control" value="<?php echo (int)$riparto['num_rate']; ?>"></div>
        <div class="col-12"><label class="form-label">Note</label><input name="note" class="form-control" value="<?php echo htmlspecialchars($riparto['note'] ?? ''); ?>"></div>
        <div class="col-12"><button class="btn btn-primary">Salva modifiche</button></div>
    </form>
    </div>
    </div>
    <?php endif; ?>

    <div class="card">
    <div class="card-header">Azioni</div>
    <div class="card-body d-flex gap-2 flex-wrap">
        <?php if (in_array($riparto['stato'], ['bozza', 'calcolato'])): ?>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="calcola">
            <button class="btn btn-info">Calcola riparto</button>
        </form>
        <?php endif; ?>

        <?php if ($riparto['stato'] === 'calcolato'): ?>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="approva">
            <button class="btn btn-success">Approva</button>
        </form>
        <?php endif; ?>

        <?php if (in_array($riparto['stato'], ['calcolato', 'approvato'])): ?>
        <form method="post" class="d-flex gap-1 align-items-end">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="genera_rate">
            <div>
                <label class="form-label mb-0 small">Scadenza 1&ordf; rata</label>
                <input type="date" name="data_scadenza" class="form-control form-control-sm" value="<?php echo date('Y-m-01', strtotime('+1 month')); ?>" required>
            </div>
            <button class="btn btn-warning">Genera rate</button>
        </form>
        <?php endif; ?>

        <a href="<?php echo url('/admin/riparti.php'); ?>" class="btn btn-outline-secondary">Torna alla lista</a>
    </div>
    </div>
</div>
</div>

<?php if (!empty($dettaglio)): ?>
<h4>Dettaglio quote</h4>
<table class="table table-striped table-bordered">
<thead>
<tr>
    <th>Unit&agrave;</th><th>Scala</th><th>Piano</th><th>Interno</th>
    <th class="text-end">Millesimi</th><th class="text-end">Quota &euro;</th><th class="text-end">%</th>
</tr>
</thead>
<tbody>
<?php foreach ($dettaglio as $d): ?>
<tr>
    <td><?php echo htmlspecialchars($d['unita_desc'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($d['scala'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($d['piano'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($d['interno'] ?? ''); ?></td>
    <td class="text-end"><?php echo number_format((float)$d['millesimi'], 4, ',', '.'); ?></td>
    <td class="text-end"><?php echo number_format((float)$d['importo'], 2, ',', '.'); ?></td>
    <td class="text-end"><?php echo $totaleQuote > 0 ? number_format((float)$d['importo'] / (float)$riparto['importo_totale'] * 100, 2, ',', '.') : '0,00'; ?>%</td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="table-warning fw-bold">
    <td colspan="4">Totale</td>
    <td class="text-end"><?php echo number_format($totaleMillesimi, 4, ',', '.'); ?></td>
    <td class="text-end"><?php echo number_format($totaleQuote, 2, ',', '.'); ?></td>
    <td class="text-end">100,00%</td>
</tr>
</tfoot>
</table>
<?php elseif ($riparto['stato'] === 'bozza'): ?>
<div class="alert alert-secondary">Premi "Calcola riparto" per generare le quote in base ai millesimi.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
