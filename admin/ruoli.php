<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Ruoli.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $nome = trim($_POST['nome'] ?? '');
        $slug = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($_POST['slug'] ?? '')));
        if ($nome && $slug) {
            $id = Ruoli::create(['nome' => $nome, 'slug' => $slug, 'descrizione' => $_POST['descrizione'] ?? '']);
            if ($id) {
                audit_log('create', 'ruoli', $id, $nome);
                $msg = 'Ruolo creato.';
            }
        }
    } elseif ($azione === 'permessi') {
        $ruoloId = (int)$_POST['ruolo_id'];
        $permessoIds = array_map('intval', $_POST['permessi'] ?? []);
        Ruoli::setPermessi($ruoloId, $permessoIds);
        audit_log('update', 'ruolo_permessi', $ruoloId, count($permessoIds) . ' permessi');
        $msg = 'Permessi aggiornati.';
    } elseif ($azione === 'elimina') {
        $ruoloId = (int)$_POST['ruolo_id'];
        if (Ruoli::delete($ruoloId)) {
            audit_log('delete', 'ruoli', $ruoloId);
            $msg = 'Ruolo eliminato.';
        } else {
            $msg = 'Impossibile eliminare un ruolo di sistema.';
        }
    }
}

$ruoli = Ruoli::all();
$allPermessi = Ruoli::getAllPermessi();
$moduli = [];
foreach ($allPermessi as $p) {
    $moduli[$p['modulo']][] = $p;
}

$editRuolo = null;
$editPermessi = [];
if (isset($_GET['edit'])) {
    $editRuolo = Ruoli::find((int)$_GET['edit']);
    if ($editRuolo) {
        $editPermessi = array_column(Ruoli::getPermessi((int)$editRuolo['id']), 'id');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Ruoli e Permessi</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-4">
<h4>Ruoli</h4>
<table class="table table-sm">
<thead><tr><th>Nome</th><th>Slug</th><th>Tipo</th><th></th></tr></thead>
<tbody>
<?php foreach ($ruoli as $r): ?>
<tr>
    <td><a href="?edit=<?php echo (int)$r['id']; ?>"><?php echo h($r['nome']); ?></a></td>
    <td><code><?php echo h($r['slug']); ?></code></td>
    <td><?php echo $r['is_system'] ? '<span class="badge bg-secondary">Sistema</span>' : '<span class="badge bg-primary">Custom</span>'; ?></td>
    <td>
        <?php if (!$r['is_system']): ?>
        <form method="post" class="d-inline">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="elimina">
            <input type="hidden" name="ruolo_id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare?')">X</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h5>Nuovo ruolo</h5>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="crea">
    <div class="mb-2"><input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome" required></div>
    <div class="mb-2"><input type="text" name="slug" class="form-control form-control-sm" placeholder="slug_univoco" required></div>
    <div class="mb-2"><textarea name="descrizione" class="form-control form-control-sm" rows="2" placeholder="Descrizione"></textarea></div>
    <button class="btn btn-primary btn-sm">Crea</button>
</form>
</div>

<div class="col-md-8">
<?php if ($editRuolo): ?>
<h4>Permessi: <?php echo h($editRuolo['nome']); ?></h4>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="permessi">
    <input type="hidden" name="ruolo_id" value="<?php echo (int)$editRuolo['id']; ?>">
    <div class="table-responsive">
    <table class="table table-sm">
    <thead><tr><th>Modulo</th><th>Lettura</th><th>Scrittura</th></tr></thead>
    <tbody>
    <?php foreach ($moduli as $mod => $perms): ?>
    <tr>
        <td><strong><?php echo h(ucfirst($mod)); ?></strong></td>
        <?php foreach (['lettura','scrittura'] as $act): ?>
        <td>
            <?php
            $found = null;
            foreach ($perms as $p) { if ($p['azione'] === $act) { $found = $p; break; } }
            if ($found): ?>
            <input type="checkbox" name="permessi[]" value="<?php echo (int)$found['id']; ?>" <?php echo in_array((int)$found['id'], $editPermessi) ? 'checked' : ''; ?>>
            <?php else: ?>-<?php endif; ?>
        </td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <button class="btn btn-primary btn-sm">Salva permessi</button>
</form>
<?php else: ?>
<p class="text-muted">Seleziona un ruolo dalla lista per gestire i permessi.</p>
<?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
