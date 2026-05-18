<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Comunicazioni.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = Comunicazioni::createTemplate($_POST);
        $msg = $id ? 'Template creato.' : 'Errore.';
        if ($id) audit_log('create', 'template_comunicazioni', $id);
    } elseif ($azione === 'modifica') {
        $ok = Comunicazioni::updateTemplate((int)$_POST['template_id'], $_POST);
        $msg = $ok ? 'Template aggiornato.' : 'Errore.';
        if ($ok) audit_log('update', 'template_comunicazioni', (int)$_POST['template_id']);
    } elseif ($azione === 'elimina') {
        $ok = Comunicazioni::deleteTemplate((int)$_POST['template_id']);
        $msg = $ok ? 'Template eliminato.' : 'Errore.';
        if ($ok) audit_log('delete', 'template_comunicazioni', (int)$_POST['template_id']);
    }
}

$templates = Comunicazioni::allTemplate();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editTpl = $editId ? Comunicazioni::findTemplate($editId) : null;
include __DIR__ . '/../includes/header.php';
?>
<h2>Template comunicazioni</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<table class="table table-striped">
<thead><tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Oggetto</th><th>Azioni</th></tr></thead>
<tbody>
<?php if (empty($templates)): ?>
<tr><td colspan="5" class="text-center text-muted">Nessun template.</td></tr>
<?php endif; ?>
<?php foreach ($templates as $t): ?>
<tr>
    <td><?php echo (int)$t['id']; ?></td>
    <td><?php echo h($t['nome']); ?></td>
    <td><span class="badge bg-secondary"><?php echo h($t['tipo']); ?></span></td>
    <td><?php echo h($t['oggetto']); ?></td>
    <td>
        <a href="?edit=<?php echo (int)$t['id']; ?>" class="btn btn-sm btn-outline-primary">Modifica</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina"><input type="hidden" name="template_id" value="<?php echo (int)$t['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Elimina</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<hr>
<h4><?php echo $editTpl ? 'Modifica template' : 'Nuovo template'; ?></h4>
<form method="post">
<?php echo csrf_field(); ?>
<?php if ($editTpl): ?>
<input type="hidden" name="azione" value="modifica">
<input type="hidden" name="template_id" value="<?php echo (int)$editTpl['id']; ?>">
<?php else: ?>
<input type="hidden" name="azione" value="crea">
<?php endif; ?>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Nome*</label>
        <input type="text" name="nome" class="form-control" required value="<?php echo h($editTpl['nome'] ?? ''); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
            <?php foreach (['generico','sollecito','convocazione','verbale'] as $tipo): ?>
            <option value="<?php echo $tipo; ?>" <?php echo ($editTpl['tipo'] ?? '')===$tipo?'selected':''; ?>><?php echo ucfirst($tipo); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Oggetto</label>
        <input type="text" name="oggetto" class="form-control" value="<?php echo h($editTpl['oggetto'] ?? ''); ?>">
    </div>
    <div class="col-12">
        <label class="form-label">Corpo*</label>
        <textarea name="corpo" class="form-control" rows="8" required><?php echo h($editTpl['corpo'] ?? ''); ?></textarea>
        <small class="text-muted">Variabili: {nome_destinatario}, {totale_dovuto}, {dettaglio_rate}, {iban_condominio}, {data_prima}, {data_seconda}, {luogo}, {ordine_giorno}, {data_assemblea}, {oggetto}, {corpo_messaggio}</small>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><?php echo $editTpl ? 'Salva modifiche' : 'Crea template'; ?></button>
        <?php if ($editTpl): ?><a href="template-comunicazioni.php" class="btn btn-secondary">Annulla</a><?php endif; ?>
    </div>
</div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
