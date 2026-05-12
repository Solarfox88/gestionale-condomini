<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Rate.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione']) && csrf_verify()) {
    if ($_POST['azione'] === 'crea_rata') {
        $id = Rate::create($_POST);
        $msg = $id ? 'Rata creata correttamente.' : 'Errore durante la creazione della rata.';
    }
    if ($_POST['azione'] === 'registra_pagamento') {
        $ok = Rate::registraPagamento((int)$_POST['rata_id'], $_POST);
        $msg = $ok ? 'Pagamento registrato correttamente.' : 'Errore durante la registrazione del pagamento.';
    }
}

$condomini = get_condomini();
$esercizi = Esercizi::all();
$rate = Rate::all(isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null);
include __DIR__ . '/../includes/header.php';
?>
<h2>Rate e pagamenti</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<table class="table table-striped table-bordered">
<thead><tr><th>ID</th><th>Condominio</th><th>Esercizio</th><th>Unità</th><th>Descrizione</th><th>Scadenza</th><th class="text-end">Importo</th><th>Stato</th><th>Pagamento</th></tr></thead>
<tbody>
<?php foreach ($rate as $r): ?>
<tr>
<td><?php echo (int)$r['id']; ?></td>
<td><?php echo htmlspecialchars($r['condominio_nome']); ?></td>
<td><?php echo htmlspecialchars($r['esercizio_nome']); ?></td>
<td><?php echo htmlspecialchars(trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? ''))); ?></td>
<td><?php echo htmlspecialchars($r['descrizione']); ?></td>
<td><?php echo htmlspecialchars($r['scadenza']); ?></td>
<td class="text-end"><?php echo number_format((float)$r['importo'], 2, ',', '.'); ?></td>
<td><?php echo htmlspecialchars($r['stato']); ?></td>
<td>
<form method="post" class="d-flex gap-1">
<?php echo csrf_field(); ?>
<input type="hidden" name="azione" value="registra_pagamento">
<input type="hidden" name="rata_id" value="<?php echo (int)$r['id']; ?>">
<input type="hidden" name="persona_id" value="">
<input type="date" name="data_pagamento" class="form-control form-control-sm" required>
<input type="number" step="0.01" name="importo" class="form-control form-control-sm" placeholder="Importo" required>
<input type="hidden" name="metodo" value=""><input type="hidden" name="riferimento" value=""><input type="hidden" name="note" value="">
<button class="btn btn-sm btn-success">OK</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4>Nuova rata</h4>
<form method="post" class="row g-2">
<?php echo csrf_field(); ?>
<input type="hidden" name="azione" value="crea_rata">
<div class="col-md-3"><label class="form-label">Esercizio</label><select name="esercizio_id" class="form-select" required><?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars(($e['condominio_nome'] ?? '').' - '.$e['nome']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Condominio</label><select name="condominio_id" class="form-select" required><?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label">ID Unità</label><input name="unita_id" type="number" class="form-control" required></div>
<div class="col-md-2"><label class="form-label">Importo</label><input name="importo" type="number" step="0.01" class="form-control" required></div>
<div class="col-md-2"><label class="form-label">Scadenza</label><input name="scadenza" type="date" class="form-control" required></div>
<div class="col-md-12"><label class="form-label">Descrizione</label><input name="descrizione" class="form-control" required></div>
<div class="col-md-12"><button class="btn btn-primary">Crea rata</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
