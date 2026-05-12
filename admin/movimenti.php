<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = Movimenti::create($_POST);
    $msg = $id ? 'Movimento registrato correttamente.' : 'Errore durante la registrazione del movimento.';
}

$condomini = get_condomini();
$esercizi = Esercizi::all();
$movimenti = Movimenti::all(isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null);
include __DIR__ . '/../includes/header.php';
?>
<h2>Prima nota / Movimenti</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<table class="table table-striped table-bordered">
<thead><tr><th>Data</th><th>Condominio</th><th>Esercizio</th><th>Tipo</th><th>Descrizione</th><th class="text-end">Importo</th></tr></thead>
<tbody>
<?php foreach ($movimenti as $m): ?>
<tr>
<td><?php echo htmlspecialchars($m['data_movimento']); ?></td>
<td><?php echo htmlspecialchars($m['condominio_nome']); ?></td>
<td><?php echo htmlspecialchars($m['esercizio_nome']); ?></td>
<td><?php echo htmlspecialchars($m['tipo']); ?></td>
<td><?php echo htmlspecialchars($m['descrizione']); ?></td>
<td class="text-end"><?php echo number_format((float)$m['importo'], 2, ',', '.'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4>Nuovo movimento</h4>
<form method="post" class="row g-2">
<?php echo csrf_field(); ?>
<div class="col-md-3"><label class="form-label">Esercizio</label><select name="esercizio_id" class="form-select" required><?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>" data-condominio="<?php echo (int)$e['condominio_id']; ?>"><?php echo htmlspecialchars(($e['condominio_nome'] ?? '').' - '.$e['nome']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Condominio</label><select name="condominio_id" class="form-select" required><?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="entrata">Entrata</option><option value="uscita">Uscita</option></select></div>
<div class="col-md-2"><label class="form-label">Importo</label><input name="importo" type="number" step="0.01" class="form-control" required></div>
<div class="col-md-2"><label class="form-label">Data</label><input name="data_movimento" type="date" class="form-control" required></div>
<div class="col-md-12"><label class="form-label">Descrizione</label><input name="descrizione" class="form-control"></div>
<input type="hidden" name="unita_id" value=""><input type="hidden" name="persona_id" value=""><input type="hidden" name="categoria_id" value=""><input type="hidden" name="metodo_pagamento" value=""><input type="hidden" name="riferimento" value="">
<div class="col-md-12"><button class="btn btn-primary">Registra</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
