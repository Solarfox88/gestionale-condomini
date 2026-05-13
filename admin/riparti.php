<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = Riparti::create($_POST);
        $msg = $id ? 'Riparto creato con successo.' : 'Errore durante la creazione del riparto.';
    } elseif ($azione === 'elimina') {
        $ok = Riparti::delete((int)$_POST['riparto_id']);
        $msg = $ok ? 'Riparto eliminato.' : 'Errore durante l\'eliminazione.';
    }
}

$condominioFiltro = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;
$condomini = get_condomini();
$esercizi = Esercizi::all();
$riparti = Riparti::all($condominioFiltro);
include __DIR__ . '/../includes/header.php';
?>
<h2>Riparti spese</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<form method="get" class="row g-2 mb-3">
<div class="col-md-4">
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Tutti i condomini --</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioFiltro === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
</form>

<table class="table table-striped table-bordered">
<thead>
<tr>
    <th>ID</th><th>Condominio</th><th>Esercizio</th><th>Descrizione</th>
    <th>Tipo millesimi</th><th>Tipo spesa</th>
    <th class="text-end">Importo</th><th>N. rate</th><th>Stato</th><th>Azioni</th>
</tr>
</thead>
<tbody>
<?php foreach ($riparti as $r): ?>
<tr>
    <td><?php echo (int)$r['id']; ?></td>
    <td><?php echo htmlspecialchars($r['condominio_nome']); ?></td>
    <td><?php echo htmlspecialchars($r['esercizio_nome']); ?></td>
    <td><?php echo htmlspecialchars($r['descrizione']); ?></td>
    <td><?php echo htmlspecialchars($r['tipo_millesimi']); ?></td>
    <td><?php echo htmlspecialchars($r['tipo_spesa']); ?></td>
    <td class="text-end"><?php echo number_format((float)$r['importo_totale'], 2, ',', '.'); ?></td>
    <td><?php echo (int)$r['num_rate']; ?></td>
    <td>
        <?php
        $badge = ['bozza' => 'secondary', 'calcolato' => 'info', 'approvato' => 'success', 'rate_generate' => 'primary'];
        $cls = $badge[$r['stato']] ?? 'secondary';
        ?>
        <span class="badge bg-<?php echo $cls; ?>"><?php echo htmlspecialchars($r['stato']); ?></span>
    </td>
    <td>
        <a href="/admin/riparto-detail.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a>
        <?php if ($r['stato'] === 'bozza'): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questo riparto?');">
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
<tr><td colspan="10" class="text-center text-muted">Nessun riparto presente.</td></tr>
<?php endif; ?>
</tbody>
</table>

<h4>Nuovo riparto</h4>
<form method="post" class="row g-2">
<?php echo csrf_field(); ?>
<input type="hidden" name="azione" value="crea">
<div class="col-md-3">
    <label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" required>
        <option value="">-- Seleziona --</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Esercizio</label>
    <select name="esercizio_id" class="form-select" required>
        <option value="">-- Seleziona --</option>
        <?php foreach ($esercizi as $e): ?>
        <option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars(($e['condominio_nome'] ?? '') . ' - ' . $e['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">Descrizione</label>
    <input name="descrizione" class="form-control" required placeholder="Es. Spese ordinarie Q1">
</div>
<div class="col-md-3">
    <label class="form-label">Tabella millesimale</label>
    <select name="tipo_millesimi" class="form-select">
        <option value="proprieta">Propriet&agrave;</option>
        <option value="scale">Scale</option>
        <option value="ascensore">Ascensore</option>
        <option value="riscaldamento">Riscaldamento</option>
    </select>
</div>
<div class="col-md-2">
    <label class="form-label">Importo totale</label>
    <input name="importo_totale" type="number" step="0.01" class="form-control" required>
</div>
<div class="col-md-2">
    <label class="form-label">Tipo spesa</label>
    <select name="tipo_spesa" class="form-select">
        <option value="ordinaria">Ordinaria</option>
        <option value="straordinaria">Straordinaria</option>
    </select>
</div>
<div class="col-md-2">
    <label class="form-label">Numero rate</label>
    <input name="num_rate" type="number" min="1" max="12" class="form-control" value="1">
</div>
<div class="col-md-6">
    <label class="form-label">Note</label>
    <input name="note" class="form-control" placeholder="(opzionale)">
</div>
<div class="col-md-12"><button class="btn btn-primary">Crea riparto</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
