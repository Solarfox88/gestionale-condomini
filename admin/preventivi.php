<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Preventivi.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/CategorieSpesa.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = Preventivi::create($_POST);
        if ($id) {
            $msg = 'Preventivo creato.';
            audit_log('create', 'preventivi', (int)$id);
        } else {
            $msg = 'Errore nella creazione.';
        }
    } elseif ($azione === 'elimina') {
        $ok = Preventivi::delete((int)$_POST['id']);
        $msg = $ok ? 'Preventivo eliminato.' : 'Errore.';
    }
}

$filters = [];
if (!empty($_GET['condominio_id'])) $filters['condominio_id'] = (int)$_GET['condominio_id'];
if (!empty($_GET['esercizio_id'])) $filters['esercizio_id'] = (int)$_GET['esercizio_id'];
$preventivi = Preventivi::all($filters);
$condomini = get_condomini();
$esercizi = Esercizi::all();
include __DIR__ . '/../includes/header.php';
?>
<h2>Preventivi</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo ($filters['condominio_id'] ?? '')==$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Esercizio</label>
    <select name="esercizio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>" <?php echo ($filters['esercizio_id'] ?? '')==$e['id']?'selected':''; ?>><?php echo h($e['nome'].' - '.$e['condominio_nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><a href="preventivi.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<div class="row">
<div class="col-md-8">
<table class="table table-bordered table-striped">
<thead><tr><th>ID</th><th>Condominio</th><th>Esercizio</th><th>Titolo</th><th>Stato</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($preventivi as $p): ?>
<tr>
    <td><?php echo (int)$p['id']; ?></td>
    <td><?php echo h($p['condominio_nome']); ?></td>
    <td><?php echo h($p['esercizio_nome']); ?></td>
    <td><?php echo h($p['titolo']); ?></td>
    <td><?php echo stato_badge($p['stato']); ?></td>
    <td>
        <a href="<?php echo url('/admin/preventivo-detail.php?id=' . (int)$p['id']); ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina"><input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Elimina</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($preventivi)): ?><tr><td colspan="6" class="text-muted">Nessun preventivo.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<div class="col-md-4">
<h4>Nuovo Preventivo</h4>
<form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="crea">
    <div class="mb-2"><label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required>
        <option value="">Seleziona...</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Esercizio*</label>
    <select name="esercizio_id" class="form-select" required>
        <option value="">Seleziona...</option>
        <?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>"><?php echo h($e['nome'].' - '.$e['condominio_nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Titolo*</label>
    <input name="titolo" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Note</label>
    <textarea name="note" class="form-control" rows="2"></textarea></div>
    <button class="btn btn-primary">Crea</button>
</form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
