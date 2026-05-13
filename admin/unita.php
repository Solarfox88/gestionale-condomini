<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $condominioId = (int)($_POST['condominio_id'] ?? 0);
    if ($condominioId > 0) {
        if (create_unita($_POST)) {
            $msg = 'Unita creata con successo.';
        } else {
            $msg = 'Errore nella creazione dell\'unita.';
        }
    } else {
        $msg = 'Selezionare un condominio.';
    }
}

$condominioFilter = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;
$unita = get_unita($condominioFilter);
$condomini = get_condomini();
include __DIR__ . '/../includes/header.php';
?>
<h2>Unita Immobiliari</h2>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Filtro per condominio</label>
            <select name="condominio_id" class="form-select" onchange="this.form.submit()">
                <option value="">Tutti</option>
                <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioFilter == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-md-8">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Condominio</th>
                    <th>Scala</th>
                    <th>Piano</th>
                    <th>Interno</th>
                    <th>MQ</th>
                    <th>Mill. Prop.</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unita as $u): ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['condominio_nome']); ?></td>
                        <td><?php echo htmlspecialchars($u['scala'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($u['piano'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($u['interno'] ?? ''); ?></td>
                        <td><?php echo $u['mq'] ? number_format((float)$u['mq'], 2, ',', '.') : ''; ?></td>
                        <td><?php echo number_format((float)$u['millesimi_proprieta'], 4, ',', '.'); ?></td>
                        <td><a href="<?php echo url('/admin/unita-detail.php?id=' . (int)$u['id']); ?>" class="btn btn-sm btn-secondary">Dettaglio</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        <h4>Nuova Unita</h4>
        <form method="post">
            <?php echo csrf_field(); ?>
            <div class="mb-2">
                <label class="form-label">Condominio*</label>
                <select name="condominio_id" class="form-select" required>
                    <option value="">Seleziona...</option>
                    <?php foreach ($condomini as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Scala</label>
                <input type="text" class="form-control" name="scala">
            </div>
            <div class="mb-2">
                <label class="form-label">Piano</label>
                <input type="text" class="form-control" name="piano">
            </div>
            <div class="mb-2">
                <label class="form-label">Interno</label>
                <input type="text" class="form-control" name="interno">
            </div>
            <div class="mb-2">
                <label class="form-label">Subalterno</label>
                <input type="text" class="form-control" name="subalterno">
            </div>
            <div class="mb-2">
                <label class="form-label">Descrizione</label>
                <input type="text" class="form-control" name="descrizione">
            </div>
            <div class="mb-2">
                <label class="form-label">MQ</label>
                <input type="number" step="0.01" class="form-control" name="mq">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Proprieta</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_proprieta" value="0">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Scale</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_scale" value="0">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Ascensore</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_ascensore" value="0">
            </div>
            <div class="mb-2">
                <label class="form-label">Millesimi Riscaldamento</label>
                <input type="number" step="0.0001" class="form-control" name="millesimi_riscaldamento" value="0">
            </div>
            <button type="submit" class="btn btn-primary">Crea</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php';
