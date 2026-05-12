<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Documenti.php';
require_login();
require_admin();

// Carica documenti
$condominioFilter = isset($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : null;
$documenti = get_documenti($condominioFilter);
$condomini = get_condomini();

$msg = '';
// Gestione upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && csrf_verify()) {
    $cid = (int)($_POST['condominio_id'] ?? 0);
    $titolo = trim($_POST['titolo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'Generale');
    $visibility = $_POST['visibility'] ?? 'condominio';
    if ($cid > 0 && $titolo !== '') {
        $result = upload_documento($_FILES['file'], $cid, $titolo, $categoria, null, $visibility, $_SESSION['user']['id']);
        if ($result) {
            $msg = 'Documento caricato con successo.';
            $documenti = get_documenti($condominioFilter);
        } else {
            $msg = 'Errore nel caricamento del documento.';
        }
    } else {
        $msg = 'Selezionare condominio e inserire il titolo.';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Documenti</h2>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<h4>Elenco documenti</h4>
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
            <th>Categoria</th>
            <th>Visibilità</th>
            <th>Caricato il</th>
            <th>Azione</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($documenti as $doc): ?>
        <tr>
            <td><?php echo (int)$doc['id']; ?></td>
            <td><?php echo htmlspecialchars($doc['condominio_nome']); ?></td>
            <td><?php echo htmlspecialchars($doc['titolo']); ?></td>
            <td><?php echo htmlspecialchars($doc['categoria']); ?></td>
            <td><?php echo htmlspecialchars($doc['visibility']); ?></td>
            <td><?php echo htmlspecialchars($doc['created_at']); ?></td>
            <td><a href="/documenti_download.php?id=<?php echo (int)$doc['id']; ?>" class="btn btn-sm btn-primary">Scarica</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h4>Carica nuovo documento</h4>
<form method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="mb-2">
        <label class="form-label">Condominio</label>
        <select name="condominio_id" class="form-select" required>
            <option value="">Seleziona...</option>
            <?php foreach ($condomini as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2">
        <label class="form-label">Titolo</label>
        <input type="text" class="form-control" name="titolo" required>
    </div>
    <div class="mb-2">
        <label class="form-label">Categoria</label>
        <input type="text" class="form-control" name="categoria" value="Generale">
    </div>
    <div class="mb-2">
        <label class="form-label">Visibilità</label>
        <select name="visibility" class="form-select">
            <option value="condominio">Tutto il condominio</option>
            <option value="unita">Solo unità</option>
        </select>
    </div>
    <div class="mb-2">
        <label class="form-label">File</label>
        <input type="file" class="form-control" name="file" required>
    </div>
    <button type="submit" class="btn btn-primary">Carica</button>
</form>

<?php include __DIR__ . '/../includes/footer.php';
