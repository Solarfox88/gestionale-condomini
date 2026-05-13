<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$msg = '';
$condominioId = isset($_GET['condominio_id']) && $_GET['condominio_id'] !== '' ? (int)$_GET['condominio_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? 'salva_standard';
    $condominioId = (int)$_POST['condominio_id'];

    if ($azione === 'salva_standard') {
        $unitaIds = $_POST['unita_id'] ?? [];
        $millProprieta = $_POST['millesimi_proprieta'] ?? [];
        $millScale = $_POST['millesimi_scale'] ?? [];
        $millAscensore = $_POST['millesimi_ascensore'] ?? [];
        $millRiscaldamento = $_POST['millesimi_riscaldamento'] ?? [];
        $ok = true;
        foreach ($unitaIds as $i => $uid) {
            $unita = get_unita_singola((int)$uid);
            if (!$unita) continue;
            $data = array_merge($unita, [
                'millesimi_proprieta' => (float)($millProprieta[$i] ?? 0),
                'millesimi_scale' => (float)($millScale[$i] ?? 0),
                'millesimi_ascensore' => (float)($millAscensore[$i] ?? 0),
                'millesimi_riscaldamento' => (float)($millRiscaldamento[$i] ?? 0),
            ]);
            if (!update_unita((int)$uid, $data)) $ok = false;
        }
        $msg = $ok ? 'Millesimi aggiornati.' : 'Errore.';
    } elseif ($azione === 'crea_tabella') {
        $nome = trim($_POST['nome_tabella'] ?? '');
        $desc = trim($_POST['desc_tabella'] ?? '');
        if ($nome) {
            Riparti::createTabella($condominioId, $nome, $desc);
            $msg = 'Tabella personalizzata creata.';
        }
    } elseif ($azione === 'elimina_tabella') {
        Riparti::deleteTabella((int)$_POST['tabella_id']);
        $msg = 'Tabella eliminata.';
    } elseif ($azione === 'salva_personalizzata') {
        $tabellaId = (int)$_POST['tabella_id'];
        $unitaIds = $_POST['unita_id'] ?? [];
        $valori = $_POST['valore'] ?? [];
        if (Riparti::salvaMillesimiPersonalizzati($tabellaId, $unitaIds, $valori)) {
            $msg = 'Millesimi personalizzati salvati.';
        } else {
            $msg = 'Errore nel salvataggio.';
        }
    }
}

$condomini = get_condomini();
$unita = $condominioId ? get_unita($condominioId) : [];
$riepilogo = $condominioId ? Riparti::riepilogoMillesimi($condominioId) : [];
$tabellePersonalizzate = $condominioId ? Riparti::tabelleMillesimali($condominioId) : [];
include __DIR__ . '/../includes/header.php';
?>
<h2>Tabelle millesimali</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="row g-2 mb-3">
<div class="col-md-4">
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Seleziona condominio --</option>
        <?php foreach ($condomini as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>" <?php echo $condominioId === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['nome']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
</form>

<?php if ($condominioId && !empty($unita)): ?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-standard">Tabelle standard</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-personalizzate">Tabelle personalizzate (<?php echo count($tabellePersonalizzate); ?>)</a></li>
</ul>

<div class="tab-content">

<div class="tab-pane fade show active" id="tab-standard">
<?php if (!empty($riepilogo)): ?>
<div class="alert alert-secondary">
    <strong>Riepilogo totali:</strong>
    Proprieta <strong><?php echo number_format((float)$riepilogo['tot_proprieta'], 4, ',', '.'); ?></strong> |
    Scale <strong><?php echo number_format((float)$riepilogo['tot_scale'], 4, ',', '.'); ?></strong> |
    Ascensore <strong><?php echo number_format((float)$riepilogo['tot_ascensore'], 4, ',', '.'); ?></strong> |
    Riscaldamento <strong><?php echo number_format((float)$riepilogo['tot_riscaldamento'], 4, ',', '.'); ?></strong> |
    Unita: <strong><?php echo (int)$riepilogo['num_unita']; ?></strong>
    <br><small>I totali dovrebbero essere 1000,0000 per ogni tabella millesimale.</small>
</div>
<?php endif; ?>

<form method="post">
<?php echo csrf_field(); ?>
<input type="hidden" name="condominio_id" value="<?php echo (int)$condominioId; ?>">
<input type="hidden" name="azione" value="salva_standard">
<table class="table table-striped table-bordered">
<thead>
<tr>
    <th>Scala</th><th>Piano</th><th>Interno</th><th>Descrizione</th>
    <th class="text-end">Proprieta</th>
    <th class="text-end">Scale</th>
    <th class="text-end">Ascensore</th>
    <th class="text-end">Riscaldamento</th>
</tr>
</thead>
<tbody>
<?php foreach ($unita as $i => $u): ?>
<tr>
    <td><?php echo h($u['scala'] ?? ''); ?></td>
    <td><?php echo h($u['piano'] ?? ''); ?></td>
    <td><?php echo h($u['interno'] ?? ''); ?></td>
    <td><?php echo h($u['descrizione'] ?? ''); ?></td>
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
document.querySelectorAll('.mill-prop, .mill-scale, .mill-asc, .mill-risc').forEach(function(el) { el.addEventListener('input', calcTotali); });
calcTotali();
</script>
</div>

<div class="tab-pane fade" id="tab-personalizzate">
<div class="row">
<div class="col-md-4">
<h5>Crea nuova tabella</h5>
<form method="post" class="mb-3">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="crea_tabella">
    <input type="hidden" name="condominio_id" value="<?php echo (int)$condominioId; ?>">
    <div class="mb-2"><input name="nome_tabella" class="form-control" placeholder="Nome tabella (es. Giardino)" required></div>
    <div class="mb-2"><input name="desc_tabella" class="form-control" placeholder="Descrizione (opzionale)"></div>
    <button class="btn btn-success btn-sm">Crea tabella</button>
</form>
</div>
<div class="col-md-8">
<?php if (empty($tabellePersonalizzate)): ?>
<p class="text-muted">Nessuna tabella personalizzata. Creane una per gestire millesimi custom.</p>
<?php else: ?>
<?php foreach ($tabellePersonalizzate as $tab):
    $millPers = Riparti::getMillesimiPersonalizzati((int)$tab['id']);
    $totTab = Riparti::totaleMillesimiTabella((int)$tab['id']);
    $valoriMap = [];
    foreach ($millPers as $mp) { $valoriMap[(int)$mp['unita_id']] = (float)$mp['valore']; }
?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong><?php echo h($tab['nome']); ?></strong>
    <span>
        Totale: <strong class="<?php echo abs($totTab - 1000) < 0.01 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($totTab, 4, ',', '.'); ?></strong>
        <form method="post" class="d-inline ms-2" onsubmit="return confirm('Eliminare questa tabella?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina_tabella"><input type="hidden" name="condominio_id" value="<?php echo (int)$condominioId; ?>"><input type="hidden" name="tabella_id" value="<?php echo (int)$tab['id']; ?>">
            <button class="btn btn-outline-danger btn-sm">&times;</button>
        </form>
    </span>
</div>
<div class="card-body">
<?php if ($tab['descrizione']): ?><p class="text-muted small"><?php echo h($tab['descrizione']); ?></p><?php endif; ?>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="salva_personalizzata">
    <input type="hidden" name="condominio_id" value="<?php echo (int)$condominioId; ?>">
    <input type="hidden" name="tabella_id" value="<?php echo (int)$tab['id']; ?>">
    <table class="table table-sm table-bordered">
    <thead><tr><th>Scala</th><th>Piano</th><th>Int.</th><th class="text-end">Millesimi</th></tr></thead>
    <tbody>
    <?php foreach ($unita as $u): ?>
    <tr>
        <td><?php echo h($u['scala'] ?? ''); ?></td>
        <td><?php echo h($u['piano'] ?? ''); ?></td>
        <td><?php echo h($u['interno'] ?? ''); ?></td>
        <td>
            <input type="hidden" name="unita_id[]" value="<?php echo (int)$u['id']; ?>">
            <input type="number" step="0.0001" name="valore[]" class="form-control form-control-sm text-end" value="<?php echo $valoriMap[(int)$u['id']] ?? 0; ?>">
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <button class="btn btn-primary btn-sm">Salva</button>
</form>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>

</div>

<?php elseif ($condominioId): ?>
<div class="alert alert-warning">Nessuna unita trovata per questo condominio.</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
