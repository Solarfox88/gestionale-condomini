<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/CategorieSpesa.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = CategorieSpesa::create($_POST);
        $msg = $id ? 'Categoria creata.' : 'Errore nella creazione.';
    } elseif ($azione === 'modifica') {
        $ok = CategorieSpesa::update((int)$_POST['id'], $_POST);
        $msg = $ok ? 'Categoria aggiornata.' : 'Errore nell\'aggiornamento.';
    } elseif ($azione === 'elimina') {
        $ok = CategorieSpesa::delete((int)$_POST['id']);
        $msg = $ok ? 'Categoria eliminata.' : 'Errore nell\'eliminazione.';
    }
}

$categorie = CategorieSpesa::all();
$edit = null;
if (isset($_GET['edit'])) {
    $edit = CategorieSpesa::find((int)$_GET['edit']);
}
include __DIR__ . '/../includes/header.php';
?>
<h2>Categorie Spesa</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-8">
<table class="table table-bordered table-striped">
<thead><tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Descrizione</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($categorie as $cat): ?>
<tr>
    <td><?php echo (int)$cat['id']; ?></td>
    <td><?php echo h($cat['nome']); ?></td>
    <td><?php echo h($cat['tipo_default']); ?></td>
    <td><?php echo h($cat['descrizione'] ?? ''); ?></td>
    <td>
        <a href="?edit=<?php echo (int)$cat['id']; ?>" class="btn btn-sm btn-secondary">Modifica</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="elimina">
            <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
            <button class="btn btn-sm btn-danger">Elimina</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="col-md-4">
<?php if ($edit): ?>
<h4>Modifica categoria</h4>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="modifica">
    <input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>">
    <div class="mb-2"><label class="form-label">Nome*</label><input name="nome" class="form-control" value="<?php echo h($edit['nome']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Tipo default</label>
        <select name="tipo_default" class="form-select">
            <option value="uscita" <?php echo $edit['tipo_default']==='uscita'?'selected':''; ?>>Uscita</option>
            <option value="entrata" <?php echo $edit['tipo_default']==='entrata'?'selected':''; ?>>Entrata</option>
            <option value="entrambi" <?php echo $edit['tipo_default']==='entrambi'?'selected':''; ?>>Entrambi</option>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Descrizione</label><textarea name="descrizione" class="form-control"><?php echo h($edit['descrizione'] ?? ''); ?></textarea></div>
    <button class="btn btn-primary">Salva</button>
    <a href="categorie-spesa.php" class="btn btn-secondary">Annulla</a>
</form>
<?php else: ?>
<h4>Nuova categoria</h4>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="crea">
    <div class="mb-2"><label class="form-label">Nome*</label><input name="nome" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Tipo default</label>
        <select name="tipo_default" class="form-select">
            <option value="uscita">Uscita</option>
            <option value="entrata">Entrata</option>
            <option value="entrambi">Entrambi</option>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Descrizione</label><textarea name="descrizione" class="form-control"></textarea></div>
    <button class="btn btn-primary">Crea</button>
</form>
<?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
