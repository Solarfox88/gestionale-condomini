<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Riparti.php';
require_once __DIR__ . '/../app/CategorieSpesa.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = Riparti::create($_POST);
        $msg = $id ? 'Riparto creato.' : 'Errore.';
        if ($id) audit_log('create', 'riparti', (int)$id);
    } elseif ($azione === 'elimina') {
        $ok = Riparti::delete((int)$_POST['riparto_id']);
        $msg = $ok ? 'Riparto eliminato.' : 'Errore.';
    }
}

$condominioFiltro = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;
$condomini = get_condomini();
$esercizi = Esercizi::all();
$categorie = CategorieSpesa::all();
$riparti = Riparti::all($condominioFiltro);

// Get all custom tables for the form
$tabelleCustom = [];
foreach ($condomini as $c) {
    $tabs = Riparti::tabelleMillesimali((int)$c['id']);
    foreach ($tabs as $t) {
        $tabelleCustom[] = ['id' => $t['id'], 'nome' => $t['nome'], 'condominio_id' => $c['id'], 'condominio_nome' => $c['nome']];
    }
}
include __DIR__ . '/../includes/header.php';
?>
<h2>Riparti spese</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="row g-2 mb-3">
<div class="col-md-4">
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Tutti i condomini --</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioFiltro === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
</form>

<div class="table-responsive">
<table class="table table-striped table-bordered">
<thead>
<tr>
    <th>ID</th><th>Condominio</th><th>Esercizio</th><th>Descrizione</th>
    <th>Tipo mill.</th><th>Tipo spesa</th><th>Categoria</th><th>Scala</th>
    <th class="text-end">Importo</th><th>N. rate</th><th>Stato</th><th>Azioni</th>
</tr>
</thead>
<tbody>
<?php foreach ($riparti as $r): ?>
<tr>
    <td><?php echo (int)$r['id']; ?></td>
    <td><?php echo h($r['condominio_nome']); ?></td>
    <td><?php echo h($r['esercizio_nome']); ?></td>
    <td><?php echo h($r['descrizione']); ?></td>
    <td><?php echo h($r['tipo_millesimi']); ?><?php if ($r['tabella_nome']): ?> <small>(<?php echo h($r['tabella_nome']); ?>)</small><?php endif; ?></td>
    <td><?php echo h($r['tipo_spesa']); ?></td>
    <td><?php echo h($r['categoria_nome'] ?? '-'); ?></td>
    <td><?php echo h($r['scala_filtro'] ?? 'Tutte'); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$r['importo_totale']); ?></td>
    <td><?php echo (int)$r['num_rate']; ?></td>
    <td><?php echo stato_badge($r['stato']); ?></td>
    <td>
        <a href="<?php echo url('/admin/riparto-detail.php?id=' . (int)$r['id']); ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a>
        <?php if ($r['stato'] === 'bozza'): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="elimina">
            <input type="hidden" name="riparto_id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Elimina</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($riparti)): ?>
<tr><td colspan="12" class="text-center text-muted">Nessun riparto presente.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<h4>Nuovo riparto</h4>
<form method="post" class="row g-2">
<?php echo csrf_field(); ?>
<input type="hidden" name="azione" value="crea">
<div class="col-md-3">
    <label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required id="sel-cond">
        <option value="">-- Seleziona --</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Esercizio*</label>
    <select name="esercizio_id" class="form-select" required>
        <option value="">-- Seleziona --</option>
        <?php foreach ($esercizi as $e): ?>
        <option value="<?php echo (int)$e['id']; ?>"><?php echo h(($e['condominio_nome'] ?? '') . ' - ' . $e['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Descrizione*</label>
    <input name="descrizione" class="form-control" required placeholder="Es. Spese ordinarie Q1">
</div>
<div class="col-md-3">
    <label class="form-label">Tabella millesimale</label>
    <select name="tipo_millesimi" class="form-select" id="sel-tipo-mill">
        <option value="proprieta">Proprieta</option>
        <option value="scale">Scale</option>
        <option value="ascensore">Ascensore</option>
        <option value="riscaldamento">Riscaldamento</option>
        <option value="personalizzato">Personalizzata</option>
    </select>
</div>
<div class="col-md-3" id="div-tab-pers" style="display:none">
    <label class="form-label">Tabella personalizzata</label>
    <select name="tabella_personalizzata_id" class="form-select" id="sel-tab-pers">
        <option value="">-- Seleziona --</option>
        <?php foreach ($tabelleCustom as $tc): ?>
        <option value="<?php echo (int)$tc['id']; ?>" data-cond="<?php echo (int)$tc['condominio_id']; ?>"><?php echo h($tc['condominio_nome'] . ' - ' . $tc['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-2">
    <label class="form-label">Importo totale*</label>
    <input name="importo_totale" type="number" step="0.01" class="form-control" required>
</div>
<div class="col-md-2">
    <label class="form-label">Tipo spesa</label>
    <select name="tipo_spesa" class="form-select">
        <option value="ordinaria">Ordinaria</option>
        <option value="straordinaria">Straordinaria</option>
        <option value="individuale">Individuale</option>
    </select>
</div>
<div class="col-md-2">
    <label class="form-label">Categoria</label>
    <select name="categoria_id" class="form-select">
        <option value="">Nessuna</option>
        <?php foreach ($categorie as $cat): ?>
        <option value="<?php echo (int)$cat['id']; ?>"><?php echo h($cat['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-2">
    <label class="form-label">Filtra scala</label>
    <input name="scala_filtro" class="form-control" placeholder="Es. A (vuoto=tutte)">
</div>
<div class="col-md-2">
    <label class="form-label">Numero rate</label>
    <input name="num_rate" type="number" min="1" max="12" class="form-control" value="1">
</div>
<div class="col-md-4">
    <label class="form-label">Note</label>
    <input name="note" class="form-control" placeholder="(opzionale)">
</div>
<div class="col-md-12"><button class="btn btn-primary">Crea riparto</button></div>
</form>

<script>
document.getElementById('sel-tipo-mill').addEventListener('change', function() {
    document.getElementById('div-tab-pers').style.display = this.value === 'personalizzato' ? '' : 'none';
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
