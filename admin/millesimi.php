<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$msg = '';
$condominioId = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $condominioId = (int)$_POST['condominio_id'];
    $unitaIds = $_POST['unita_id'] ?? [];
    $millProprieta = $_POST['millesimi_proprieta'] ?? [];
    $millScale = $_POST['millesimi_scale'] ?? [];
    $millAscensore = $_POST['millesimi_ascensore'] ?? [];
    $millRiscaldamento = $_POST['millesimi_riscaldamento'] ?? [];

    $ok = true;
    foreach ($unitaIds as $i => $uid) {
        $unita = get_unita_singola((int)$uid);
        if (!$unita) {
            continue;
        }
        $data = array_merge($unita, [
            'millesimi_proprieta' => (float)($millProprieta[$i] ?? 0),
            'millesimi_scale' => (float)($millScale[$i] ?? 0),
            'millesimi_ascensore' => (float)($millAscensore[$i] ?? 0),
            'millesimi_riscaldamento' => (float)($millRiscaldamento[$i] ?? 0),
        ]);
        if (!update_unita((int)$uid, $data)) {
            $ok = false;
        }
    }
    $msg = $ok ? 'Millesimi aggiornati con successo.' : 'Errore durante l\'aggiornamento di alcuni millesimi.';
}

$condomini = get_condomini();
$unita = $condominioId ? get_unita($condominioId) : [];
$riepilogo = $condominioId ? Riparti::riepilogoMillesimi($condominioId) : [];
include __DIR__ . '/../includes/header.php';
?>
<h2>Tabelle millesimali</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<form method="get" class="row g-2 mb-3">
<div class="col-md-4">
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Seleziona condominio --</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioId === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
</form>

<?php if ($condominioId && !empty($unita)): ?>
<?php if (!empty($riepilogo)): ?>
<div class="alert alert-secondary">
    <strong>Riepilogo totali:</strong>
    Propriet&agrave; <strong><?php echo number_format((float)$riepilogo['tot_proprieta'], 4, ',', '.'); ?></strong> |
    Scale <strong><?php echo number_format((float)$riepilogo['tot_scale'], 4, ',', '.'); ?></strong> |
    Ascensore <strong><?php echo number_format((float)$riepilogo['tot_ascensore'], 4, ',', '.'); ?></strong> |
    Riscaldamento <strong><?php echo number_format((float)$riepilogo['tot_riscaldamento'], 4, ',', '.'); ?></strong> |
    Unit&agrave;: <strong><?php echo (int)$riepilogo['num_unita']; ?></strong>
    <br><small>I totali dovrebbero essere 1000,0000 per ogni tabella millesimale.</small>
</div>
<?php endif; ?>

<form method="post">
<?php echo csrf_field(); ?>
<input type="hidden" name="condominio_id" value="<?php echo (int)$condominioId; ?>">

<table class="table table-striped table-bordered">
<thead>
<tr>
    <th>Scala</th><th>Piano</th><th>Interno</th><th>Descrizione</th>
    <th class="text-end">Propriet&agrave;</th>
    <th class="text-end">Scale</th>
    <th class="text-end">Ascensore</th>
    <th class="text-end">Riscaldamento</th>
</tr>
</thead>
<tbody>
<?php foreach ($unita as $i => $u): ?>
<tr>
    <td><?php echo htmlspecialchars($u['scala'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($u['piano'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($u['interno'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($u['descrizione'] ?? ''); ?></td>
    <td><input type="hidden" name="unita_id[]" value="<?php echo (int)$u['id']; ?>">
        <input type="number" step="0.0001" name="millesimi_proprieta[]" class="form-control form-control-sm text-end mill-prop" value="<?php echo (float)$u['millesimi_proprieta']; ?>"></td>
    <td><input type="number" step="0.0001" name="millesimi_scale[]" class="form-control form-control-sm text-end mill-scale" value="<?php echo (float)$u['millesimi_scale']; ?>"></td>
    <td><input type="number" step="0.0001" name="millesimi_ascensore[]" class="form-control form-control-sm text-end mill-asc" value="<?php echo (float)$u['millesimi_ascensore']; ?>"></td>
    <td><input type="number" step="0.0001" name="millesimi_riscaldamento[]" class="form-control form-control-sm text-end mill-risc" value="<?php echo (float)$u['millesimi_riscaldamento']; ?>"></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="table-warning fw-bold">
    <td colspan="4">Totale</td>
    <td class="text-end" id="tot-prop">0</td>
    <td class="text-end" id="tot-scale">0</td>
    <td class="text-end" id="tot-asc">0</td>
    <td class="text-end" id="tot-risc">0</td>
</tr>
</tfoot>
</table>

<button class="btn btn-primary">Salva millesimi</button>
</form>

<script>
function calcTotali() {
    ['prop','scale','asc','risc'].forEach(function(t) {
        var tot = 0;
        document.querySelectorAll('.mill-' + t).forEach(function(el) { tot += parseFloat(el.value) || 0; });
        var cell = document.getElementById('tot-' + t);
        cell.textContent = tot.toFixed(4).replace('.', ',');
        cell.style.color = (Math.abs(tot - 1000) < 0.01) ? 'green' : 'red';
    });
}
document.querySelectorAll('input[type=number]').forEach(function(el) { el.addEventListener('input', calcTotali); });
calcTotali();
</script>

<?php elseif ($condominioId): ?>
<div class="alert alert-warning">Nessuna unit&agrave; trovata per questo condominio.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
