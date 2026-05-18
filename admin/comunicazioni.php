<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Comunicazioni.php';
require_login();
require_admin();

$msg = '';

// Auto-create sollecito from morosità
if (isset($_GET['azione_sollecito']) && $_GET['azione_sollecito'] === 'massivo' && !empty($_GET['condominio_id'])) {
    require_once __DIR__ . '/../app/Rate.php';
    $sollCondId = (int)$_GET['condominio_id'];
    $tplSollecito = null;
    foreach (Comunicazioni::allTemplate() as $t) { if ($t['tipo'] === 'sollecito') { $tplSollecito = $t; break; } }
    $corpo = $tplSollecito ? $tplSollecito['corpo'] : 'Sollecito di pagamento per rate scadute.';
    $oggetto = $tplSollecito ? $tplSollecito['oggetto'] : 'Sollecito pagamento rate condominiali';
    $id = Comunicazioni::create([
        'condominio_id' => $sollCondId,
        'oggetto' => $oggetto,
        'corpo' => $corpo,
        'tipo' => 'sollecito',
        'destinatari_tipo' => 'tutti',
        'created_by' => current_user()['id'],
        'template_id' => $tplSollecito ? $tplSollecito['id'] : null,
    ]);
    if ($id) {
        Comunicazioni::calcolaDestinatari($id);
        audit_log('create', 'comunicazioni', $id);
        header('Location: ' . url('/admin/comunicazione-detail.php?id=' . $id . '&msg=Sollecito+creato+con+destinatari'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = Comunicazioni::create(array_merge($_POST, ['created_by' => current_user()['id']]));
        if ($id) {
            $msg = 'Comunicazione creata.';
            audit_log('create', 'comunicazioni', $id);
            header('Location: ' . url('/admin/comunicazione-detail.php?id=' . $id . '&msg=creata'));
            exit;
        } else {
            $msg = 'Errore nella creazione.';
        }
    } elseif ($azione === 'elimina') {
        $ok = Comunicazioni::delete((int)$_POST['comunicazione_id']);
        $msg = $ok ? 'Comunicazione eliminata.' : 'Errore.';
    }
}

$condFilter = !empty($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : null;
$statoFilter = !empty($_GET['stato']) ? $_GET['stato'] : null;
$comunicazioni = Comunicazioni::all($condFilter, $statoFilter);
$condomini = get_condomini();
$templates = Comunicazioni::allTemplate();
include __DIR__ . '/../includes/header.php';
?>
<h2>Comunicazioni</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $condFilter==(int)$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Stato</label>
    <select name="stato" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <option value="bozza" <?php echo $statoFilter==='bozza'?'selected':''; ?>>Bozza</option>
        <option value="inviata" <?php echo $statoFilter==='inviata'?'selected':''; ?>>Inviata</option>
        <option value="archiviata" <?php echo $statoFilter==='archiviata'?'selected':''; ?>>Archiviata</option>
    </select></div>
    <div class="col-auto"><a href="comunicazioni.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<table class="table table-striped">
<thead><tr><th>ID</th><th>Condominio</th><th>Oggetto</th><th>Tipo</th><th>Stato</th><th>Data</th><th>Azioni</th></tr></thead>
<tbody>
<?php if (empty($comunicazioni)): ?>
<tr><td colspan="7" class="text-center text-muted">Nessuna comunicazione trovata.</td></tr>
<?php endif; ?>
<?php foreach ($comunicazioni as $c): ?>
<tr>
    <td><?php echo (int)$c['id']; ?></td>
    <td><?php echo h($c['condominio_nome']); ?></td>
    <td><a href="comunicazione-detail.php?id=<?php echo (int)$c['id']; ?>"><?php echo h($c['oggetto']); ?></a></td>
    <td><?php echo h(ucfirst($c['tipo'])); ?></td>
    <td><?php echo stato_badge($c['stato']); ?></td>
    <td><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
    <td>
        <a href="comunicazione-detail.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-primary">Dettaglio</a>
        <?php if ($c['stato'] === 'bozza'): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina"><input type="hidden" name="comunicazione_id" value="<?php echo (int)$c['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Elimina</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<hr>
<h4>Nuova comunicazione</h4>
<form method="post">
<?php echo csrf_field(); ?><input type="hidden" name="azione" value="crea">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Condominio*</label>
        <select name="condominio_id" class="form-select" required>
            <option value="">Seleziona...</option>
            <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
            <option value="comunicazione">Comunicazione</option>
            <option value="sollecito">Sollecito</option>
            <option value="convocazione">Convocazione</option>
            <option value="verbale">Verbale</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Destinatari</label>
        <select name="destinatari_tipo" class="form-select">
            <option value="tutti">Tutti i condomini</option>
            <option value="scala">Per scala</option>
            <option value="unita">Singola unita</option>
            <option value="persona">Singola persona</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Filtro (scala/ID)</label>
        <input type="text" name="destinatari_filtro" class="form-control" placeholder="Es: A, 5">
    </div>
    <div class="col-md-4">
        <label class="form-label">Template (opzionale)</label>
        <select name="template_id" class="form-select" id="selTemplate">
            <option value="">Nessuno</option>
            <?php foreach ($templates as $t): ?><option value="<?php echo (int)$t['id']; ?>" data-oggetto="<?php echo h($t['oggetto']); ?>" data-corpo="<?php echo h($t['corpo']); ?>"><?php echo h($t['nome']); ?> (<?php echo h($t['tipo']); ?>)</option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label">Oggetto*</label>
        <input type="text" name="oggetto" class="form-control" required id="inputOggetto">
    </div>
    <div class="col-12">
        <label class="form-label">Corpo messaggio*</label>
        <textarea name="corpo" class="form-control" rows="6" required id="inputCorpo"></textarea>
        <small class="text-muted">Variabili: {nome_destinatario}, {totale_dovuto}, {dettaglio_rate}, {iban_condominio}</small>
    </div>
    <div class="col-12"><button type="submit" class="btn btn-primary">Crea comunicazione (bozza)</button></div>
</div>
</form>

<script>
document.getElementById('selTemplate').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.dataset.oggetto) document.getElementById('inputOggetto').value = opt.dataset.oggetto;
    if (opt.dataset.corpo) document.getElementById('inputCorpo').value = opt.dataset.corpo.replace(/\\n/g, '\n');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
