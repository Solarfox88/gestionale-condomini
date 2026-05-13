<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = Assemblee::create($_POST);
    $msg = $id ? 'Assemblea creata correttamente.' : 'Errore nella creazione dell\'assemblea.';
}

$condominioFilter = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;
$condomini = get_condomini();
$assemblee = Assemblee::all($condominioFilter);
include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Assemblee</h2>
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

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Condominio</th>
            <th>Titolo</th>
            <th>1a Conv.</th>
            <th>2a Conv.</th>
            <th>Luogo</th>
            <th>Stato</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($assemblee as $a): ?>
            <tr>
                <td><?php echo (int)$a['id']; ?></td>
                <td><?php echo htmlspecialchars($a['condominio_nome']); ?></td>
                <td><?php echo htmlspecialchars($a['titolo']); ?></td>
                <td><?php echo htmlspecialchars($a['data_prima_convocazione'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($a['data_seconda_convocazione']); ?></td>
                <td><?php echo htmlspecialchars($a['luogo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($a['stato']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h4>Nuova Assemblea</h4>
<form method="post" class="row g-2">
    <?php echo csrf_field(); ?>
    <div class="col-md-4">
        <label class="form-label">Condominio*</label>
        <select name="condominio_id" class="form-select" required>
            <option value="">Seleziona...</option>
            <?php foreach ($condomini as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Titolo*</label>
        <input type="text" class="form-control" name="titolo" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Stato</label>
        <select name="stato" class="form-select">
            <option value="bozza">Bozza</option>
            <option value="convocata">Convocata</option>
            <option value="svolta">Svolta</option>
            <option value="annullata">Annullata</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">1a Convocazione</label>
        <input type="datetime-local" class="form-control" name="data_prima_convocazione">
    </div>
    <div class="col-md-4">
        <label class="form-label">2a Convocazione*</label>
        <input type="datetime-local" class="form-control" name="data_seconda_convocazione" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Luogo</label>
        <input type="text" class="form-control" name="luogo">
    </div>
    <div class="col-md-12">
        <label class="form-label">Ordine del giorno*</label>
        <textarea class="form-control" name="ordine_giorno" rows="4" required></textarea>
    </div>
    <div class="col-md-12"><button class="btn btn-primary">Crea Assemblea</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php';
