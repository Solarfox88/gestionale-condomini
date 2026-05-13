<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Pagamenti.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    if (($_POST['azione'] ?? '') === 'elimina') {
        $ok = Pagamenti::delete((int)$_POST['id']);
        $msg = $ok ? 'Pagamento eliminato.' : 'Errore.';
    }
}

$filters = [];
if (!empty($_GET['condominio_id'])) $filters['condominio_id'] = (int)$_GET['condominio_id'];
$pagamenti = Pagamenti::all($filters);
$condomini = get_condomini();
include __DIR__ . '/../includes/header.php';
?>
<h2>Pagamenti</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo ($filters['condominio_id'] ?? '')==$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><a href="pagamenti.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>ID</th><th>Condominio</th><th>Rata</th><th>Unita</th><th>Persona</th><th>Data</th><th>Metodo</th><th class="text-end">Importo</th><th>Rif.</th><th></th></tr></thead>
<tbody>
<?php foreach ($pagamenti as $pg): ?>
<tr>
    <td><?php echo (int)$pg['id']; ?></td>
    <td><?php echo h($pg['condominio_nome']); ?></td>
    <td><?php echo h($pg['rata_descrizione']); ?></td>
    <td><?php echo h(trim(($pg['scala'] ?? '').' '.($pg['piano'] ?? '').' '.($pg['interno'] ?? ''))); ?></td>
    <td><?php echo h(trim(($pg['persona_cognome'] ?? '').' '.($pg['persona_nome'] ?? ''))); ?></td>
    <td><?php echo h($pg['data_pagamento']); ?></td>
    <td><?php echo h($pg['metodo'] ?? '-'); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$pg['importo']); ?></td>
    <td><?php echo h($pg['riferimento'] ?? ''); ?></td>
    <td>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questo pagamento?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina"><input type="hidden" name="id" value="<?php echo (int)$pg['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">&times;</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
