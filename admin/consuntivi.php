<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Preventivi.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_login();
require_admin();

$esercizioId = isset($_GET['esercizio_id']) ? (int)$_GET['esercizio_id'] : 0;
$condomini = get_condomini();
$esercizi = Esercizi::all();

$confronto = [];
$totali = ['entrate_previste' => 0, 'uscite_previste' => 0, 'entrate_reali' => 0, 'uscite_reali' => 0];
$esercizio = null;
$preventivo = null;
$quadrature = null;

if ($esercizioId > 0) {
    $esercizio = Esercizi::find($esercizioId);
    $preventivo = Preventivi::findByEsercizio($esercizioId);
    $confronto = Preventivi::confronto($esercizioId);
    $quadrature = Esercizi::quadrature($esercizioId);
    foreach ($confronto as $row) {
        $totali['entrate_previste'] += $row['entrate_previste'];
        $totali['uscite_previste'] += $row['uscite_previste'];
        $totali['entrate_reali'] += $row['entrate_reali'];
        $totali['uscite_reali'] += $row['uscite_reali'];
    }
}

$export = $_GET['export'] ?? '';
if ($export === 'csv' && $esercizioId > 0) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="consuntivo_' . $esercizioId . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Categoria', 'Entrate previste', 'Uscite previste', 'Entrate reali', 'Uscite reali', 'Scostamento entrate', 'Scostamento uscite']);
    foreach ($confronto as $row) {
        fputcsv($out, [
            $row['categoria_nome'],
            number_format($row['entrate_previste'], 2, '.', ''),
            number_format($row['uscite_previste'], 2, '.', ''),
            number_format($row['entrate_reali'], 2, '.', ''),
            number_format($row['uscite_reali'], 2, '.', ''),
            number_format($row['scostamento_entrate'], 2, '.', ''),
            number_format($row['scostamento_uscite'], 2, '.', ''),
        ]);
    }
    fclose($out);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Consuntivo</h2>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">Esercizio*</label>
    <select name="esercizio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Seleziona esercizio...</option>
        <?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>" <?php echo $esercizioId==$e['id']?'selected':''; ?>><?php echo h($e['nome'].' - '.$e['condominio_nome']); ?></option><?php endforeach; ?>
    </select></div>
</div>
</form>

<?php if ($esercizio): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?php echo h($esercizio['nome']); ?> &mdash; <?php echo h($esercizio['condominio_nome']); ?> <?php echo stato_badge($esercizio['stato']); ?></h4>
    <div>
        <?php if ($preventivo): ?>
        <a href="<?php echo url('/admin/preventivo-detail.php?id=' . (int)$preventivo['id']); ?>" class="btn btn-outline-primary btn-sm">Vedi Preventivo</a>
        <?php else: ?>
        <span class="badge bg-secondary">Nessun preventivo collegato</span>
        <?php endif; ?>
        <a href="<?php echo url('/admin/consuntivi.php?esercizio_id=' . $esercizioId . '&export=csv'); ?>" class="btn btn-outline-success btn-sm">Esporta CSV</a>
        <a href="stampa-consuntivo.php?esercizio_id=<?php echo $esercizioId; ?>" class="btn btn-outline-info btn-sm" target="_blank">Stampa Consuntivo</a>
    </div>
</div>

<?php if ($quadrature): ?>
<div class="row mb-4">
    <div class="col-md-2"><div class="card text-bg-success"><div class="card-body text-center">
        <h6>&euro; <?php echo format_euro($quadrature['entrate']); ?></h6><small>Entrate</small>
    </div></div></div>
    <div class="col-md-2"><div class="card text-bg-danger"><div class="card-body text-center">
        <h6>&euro; <?php echo format_euro($quadrature['uscite']); ?></h6><small>Uscite</small>
    </div></div></div>
    <div class="col-md-2"><div class="card text-bg-primary"><div class="card-body text-center">
        <h6>&euro; <?php echo format_euro($quadrature['saldo']); ?></h6><small>Saldo</small>
    </div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body text-center">
        <h6>&euro; <?php echo format_euro($quadrature['totale_rate']); ?></h6><small>Rate emesse</small>
    </div></div></div>
    <div class="col-md-2"><div class="card"><div class="card-body text-center">
        <h6>&euro; <?php echo format_euro($quadrature['totale_incassato']); ?></h6><small>Incassato</small>
    </div></div></div>
    <div class="col-md-2"><div class="card <?php echo $quadrature['residuo'] > 0 ? 'text-bg-warning' : ''; ?>"><div class="card-body text-center">
        <h6>&euro; <?php echo format_euro($quadrature['residuo']); ?></h6><small>Residuo</small>
    </div></div></div>
</div>
<?php endif; ?>

<h4>Confronto Preventivo vs Consuntivo</h4>
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
    <th rowspan="2">Categoria</th>
    <th colspan="2" class="text-center table-info">Preventivo</th>
    <th colspan="2" class="text-center table-success">Consuntivo</th>
    <th colspan="2" class="text-center table-warning">Scostamento</th>
</tr>
<tr>
    <th class="text-end">Entrate</th><th class="text-end">Uscite</th>
    <th class="text-end">Entrate</th><th class="text-end">Uscite</th>
    <th class="text-end">Entrate</th><th class="text-end">Uscite</th>
</tr>
</thead>
<tbody>
<?php foreach ($confronto as $row): ?>
<tr>
    <td><?php echo h($row['categoria_nome']); ?></td>
    <td class="text-end"><?php echo $row['entrate_previste'] > 0 ? '&euro; ' . format_euro($row['entrate_previste']) : '-'; ?></td>
    <td class="text-end"><?php echo $row['uscite_previste'] > 0 ? '&euro; ' . format_euro($row['uscite_previste']) : '-'; ?></td>
    <td class="text-end"><?php echo $row['entrate_reali'] > 0 ? '&euro; ' . format_euro($row['entrate_reali']) : '-'; ?></td>
    <td class="text-end"><?php echo $row['uscite_reali'] > 0 ? '&euro; ' . format_euro($row['uscite_reali']) : '-'; ?></td>
    <td class="text-end <?php echo $row['scostamento_entrate'] < 0 ? 'text-danger' : ($row['scostamento_entrate'] > 0 ? 'text-success' : ''); ?>">
        <?php echo $row['scostamento_entrate'] != 0 ? '&euro; ' . format_euro($row['scostamento_entrate']) : '-'; ?>
    </td>
    <td class="text-end <?php echo $row['scostamento_uscite'] > 0 ? 'text-danger' : ($row['scostamento_uscite'] < 0 ? 'text-success' : ''); ?>">
        <?php echo $row['scostamento_uscite'] != 0 ? '&euro; ' . format_euro($row['scostamento_uscite']) : '-'; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($confronto)): ?>
<tr><td colspan="7" class="text-muted">Nessun dato. Assicurarsi che ci siano movimenti registrati per questo esercizio.</td></tr>
<?php endif; ?>
</tbody>
<tfoot>
<tr class="table-dark fw-bold">
    <td>TOTALE</td>
    <td class="text-end">&euro; <?php echo format_euro($totali['entrate_previste']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($totali['uscite_previste']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($totali['entrate_reali']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($totali['uscite_reali']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($totali['entrate_reali'] - $totali['entrate_previste']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($totali['uscite_reali'] - $totali['uscite_previste']); ?></td>
</tr>
</tfoot>
</table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
