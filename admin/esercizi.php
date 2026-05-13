<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

$msg = $_GET['msg'] ?? '';
if ($msg === 'eliminato') $msg = 'Esercizio eliminato.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = Esercizi::create($_POST);
    $msg = $id ? 'Esercizio creato.' : 'Errore nella creazione.';
    if ($id) audit_log('create', 'esercizi', (int)$id);
}

$condominioFilter = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;
$esercizi = Esercizi::all($condominioFilter);
$condomini = get_condomini();
include __DIR__ . '/../includes/header.php';
?>
<h2>Esercizi Contabili</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Condominio</label>
        <select name="condominio_id" class="form-select" onchange="this.form.submit()">
            <option value="">Tutti</option>
            <?php foreach ($condomini as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioFilter==(int)$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option>
            <?php endforeach; ?>
        </select></div>
    </div>
</form>

<div class="row">
<div class="col-md-8">
<table class="table table-bordered table-striped">
<thead><tr><th>ID</th><th>Condominio</th><th>Nome</th><th>Periodo</th><th>Stato</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($esercizi as $e): ?>
<tr>
    <td><?php echo (int)$e['id']; ?></td>
    <td><?php echo h($e['condominio_nome']); ?></td>
    <td><?php echo h($e['nome']); ?></td>
    <td><?php echo h($e['data_inizio'].' - '.$e['data_fine']); ?></td>
    <td><?php echo stato_badge($e['stato']); ?></td>
    <td><a href="esercizio-detail.php?id=<?php echo (int)$e['id']; ?>" class="btn btn-sm btn-primary">Dettaglio</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="col-md-4">
<h4>Nuovo esercizio</h4>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-2"><label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required>
        <option value="">Seleziona...</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Nome*</label><input name="nome" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Data inizio*</label><input type="date" name="data_inizio" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Data fine*</label><input type="date" name="data_fine" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Stato</label>
        <select name="stato" class="form-select">
            <option value="bozza">Bozza</option>
            <option value="aperto">Aperto</option>
        </select>
    </div>
    <button class="btn btn-primary">Crea</button>
</form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
