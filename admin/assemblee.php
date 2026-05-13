<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

$msg = $_GET['msg'] ?? '';
if ($msg === 'eliminata') $msg = 'Assemblea eliminata.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = Assemblee::create($_POST);
    $msg = $id ? 'Assemblea creata.' : 'Errore.';
    if ($id) audit_log('create', 'assemblee', (int)$id);
}

$condFilter = !empty($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : null;
$assemblee = Assemblee::all($condFilter);
$condomini = get_condomini();
include __DIR__ . '/../includes/header.php';
?>
<h2>Assemblee</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $condFilter==(int)$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><a href="assemblee.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead><tr><th>ID</th><th>Condominio</th><th>Titolo</th><th>Data 2a Conv.</th><th>Luogo</th><th>Stato</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($assemblee as $a): ?>
<tr>
    <td><?php echo (int)$a['id']; ?></td>
    <td><?php echo h($a['condominio_nome']); ?></td>
    <td><?php echo h($a['titolo']); ?></td>
    <td><?php echo h($a['data_seconda_convocazione']); ?></td>
    <td><?php echo h($a['luogo'] ?? ''); ?></td>
    <td><?php echo stato_badge($a['stato']); ?></td>
    <td><a href="assemblea-detail.php?id=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-primary">Dettaglio</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<hr>
<h4>Nuova assemblea</h4>
<form method="post" class="row g-2 mb-3">
    <?php echo csrf_field(); ?>
    <div class="col-md-2"><label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required><option value="">Seleziona...</option>
    <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Titolo*</label><input name="titolo" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">1a convocazione</label><input type="datetime-local" name="data_prima_convocazione" class="form-control"></div>
    <div class="col-md-2"><label class="form-label">2a convocazione*</label><input type="datetime-local" name="data_seconda_convocazione" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">Luogo</label><input name="luogo" class="form-control"></div>
    <div class="col-md-12"><label class="form-label">Ordine del giorno*</label><textarea name="ordine_giorno" class="form-control" rows="2" required></textarea></div>
    <div class="col-auto"><button class="btn btn-primary">Crea</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
