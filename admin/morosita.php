<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

Rate::aggiornaScadute();

$condFilter = !empty($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : null;

global $pdo;
$sql = "SELECT r.id, r.descrizione, r.importo, r.scadenza, r.stato,
        c.nome AS condominio_nome, c.id AS condominio_id,
        ui.scala, ui.piano, ui.interno, ui.id AS unita_id,
        COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id=r.id),0) AS pagato,
        DATEDIFF(CURDATE(), r.scadenza) AS giorni_ritardo
        FROM rate r
        JOIN condomini c ON c.id=r.condominio_id
        JOIN unita_immobiliari ui ON ui.id=r.unita_id
        WHERE r.stato IN ('scaduta','parziale','da_pagare')
        AND r.scadenza < CURDATE()";
$params = [];
if ($condFilter) { $sql .= ' AND r.condominio_id=:cid'; $params['cid'] = $condFilter; }
$sql .= ' ORDER BY r.scadenza ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$morosi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totaleDovuto = 0; $totalePagato = 0;
foreach ($morosi as $m) { $totaleDovuto += (float)$m['importo']; $totalePagato += (float)$m['pagato']; }
$residuoTot = $totaleDovuto - $totalePagato;

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="morosita_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Condominio','Unita','Descrizione','Importo','Pagato','Residuo','Scadenza','Giorni ritardo','Stato']);
    foreach ($morosi as $m) {
        fputcsv($out, [$m['condominio_nome'], trim($m['scala'].' '.$m['piano'].' '.$m['interno']), $m['descrizione'], $m['importo'], $m['pagato'], (float)$m['importo']-(float)$m['pagato'], $m['scadenza'], $m['giorni_ritardo'], $m['stato']]);
    }
    fclose($out);
    exit;
}

$condomini = get_condomini();
include __DIR__ . '/../includes/header.php';
?>
<h2>Morosita</h2>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $condFilter==(int)$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><a href="morosita.php" class="btn btn-secondary">Reset</a></div>
    <div class="col-auto"><a href="?<?php echo http_build_query(array_merge($_GET, ['export'=>'csv'])); ?>" class="btn btn-success">Esporta CSV</a></div>
    <div class="col-auto"><button onclick="window.print()" class="btn btn-outline-primary">Stampa</button></div>
    <?php if ($condFilter): ?>
    <div class="col-auto"><a href="comunicazioni.php?azione_sollecito=massivo&condominio_id=<?php echo $condFilter; ?>" class="btn btn-warning">Sollecito massivo</a></div>
    <?php endif; ?>
</div>
</form>

<div class="row mb-3">
<div class="col-md-3"><div class="card text-bg-danger"><div class="card-body"><h5>&euro; <?php echo format_euro($residuoTot); ?></h5><p>Residuo totale</p></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body"><h5>&euro; <?php echo format_euro($totaleDovuto); ?></h5><p>Dovuto totale</p></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body"><h5>&euro; <?php echo format_euro($totalePagato); ?></h5><p>Pagato totale</p></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body"><h5><?php echo count($morosi); ?></h5><p>Rate scadute</p></div></div></div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>Condominio</th><th>Unita</th><th>Descrizione</th><th class="text-end">Importo</th><th class="text-end">Pagato</th><th class="text-end">Residuo</th><th>Scadenza</th><th>Giorni ritardo</th><th>Stato</th></tr></thead>
<tbody>
<?php foreach ($morosi as $m): $residuo = (float)$m['importo'] - (float)$m['pagato']; ?>
<tr class="<?php echo $residuo > 0 ? 'table-danger' : ''; ?>">
    <td><?php echo h($m['condominio_nome']); ?></td>
    <td><?php echo h(trim($m['scala'].' '.$m['piano'].' '.$m['interno'])); ?></td>
    <td><?php echo h($m['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['pagato']); ?></td>
    <td class="text-end fw-bold">&euro; <?php echo format_euro($residuo); ?></td>
    <td><?php echo h($m['scadenza']); ?></td>
    <td class="text-center"><?php echo (int)$m['giorni_ritardo']; ?></td>
    <td><?php echo stato_badge($m['stato']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
