<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Preventivi.php';
require_once __DIR__ . '/../app/CategorieSpesa.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preventivo = Preventivi::find($id);
if (!$preventivo) { echo 'Preventivo non trovato.'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna') {
        if (Preventivi::update($id, $_POST)) {
            $msg = 'Preventivo aggiornato.';
            $preventivo = Preventivi::find($id);
            audit_log('update', 'preventivi', $id);
        } else {
            $msg = 'Errore.';
        }
    } elseif ($azione === 'aggiungi_voce') {
        $vid = Preventivi::addVoce($id, $_POST);
        $msg = $vid ? 'Voce aggiunta.' : 'Errore.';
    } elseif ($azione === 'aggiorna_voce') {
        $ok = Preventivi::updateVoce((int)$_POST['voce_id'], $_POST);
        $msg = $ok ? 'Voce aggiornata.' : 'Errore.';
    } elseif ($azione === 'elimina_voce') {
        $ok = Preventivi::deleteVoce((int)$_POST['voce_id']);
        $msg = $ok ? 'Voce eliminata.' : 'Errore.';
    } elseif ($azione === 'approva') {
        if (Preventivi::update($id, ['titolo' => $preventivo['titolo'], 'stato' => 'approvato', 'note' => $preventivo['note']])) {
            $msg = 'Preventivo approvato.';
            $preventivo = Preventivi::find($id);
            audit_log('approva', 'preventivi', $id);
        }
    }
}

$voci = Preventivi::voci($id);
$totali = Preventivi::totali($id);
$categorie = CategorieSpesa::all();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($preventivo['titolo']); ?> <?php echo stato_badge($preventivo['stato']); ?></h2>
    <div>
        <?php if ($preventivo['stato'] === 'bozza'): ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Approvare il preventivo?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="approva">
            <button class="btn btn-success btn-sm">Approva</button>
        </form>
        <?php endif; ?>
        <a href="<?php echo url('/admin/consuntivi.php?esercizio_id=' . (int)$preventivo['esercizio_id']); ?>" class="btn btn-outline-info btn-sm">Confronta Consuntivo</a>
        <a href="stampa-preventivo.php?id=<?php echo (int)$preventivo['id']; ?>" class="btn btn-outline-info btn-sm" target="_blank">Stampa Preventivo</a>
        <a href="<?php echo url('/admin/preventivi.php'); ?>" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <p><strong>Condominio:</strong> <?php echo h($preventivo['condominio_nome']); ?></p>
                <p><strong>Esercizio:</strong> <?php echo h($preventivo['esercizio_nome']); ?></p>
                <?php if ($preventivo['note']): ?><p><strong>Note:</strong> <?php echo h($preventivo['note']); ?></p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row text-center">
            <div class="col-md-4"><div class="card text-bg-success"><div class="card-body">
                <h5>&euro; <?php echo format_euro((float)$totali['entrate_previste']); ?></h5><p class="mb-0">Entrate previste</p>
            </div></div></div>
            <div class="col-md-4"><div class="card text-bg-danger"><div class="card-body">
                <h5>&euro; <?php echo format_euro((float)$totali['uscite_previste']); ?></h5><p class="mb-0">Uscite previste</p>
            </div></div></div>
            <div class="col-md-4"><div class="card text-bg-primary"><div class="card-body">
                <h5>&euro; <?php echo format_euro((float)$totali['entrate_previste'] - (float)$totali['uscite_previste']); ?></h5><p class="mb-0">Saldo previsto</p>
            </div></div></div>
        </div>
    </div>
</div>

<div class="row">
<div class="col-md-8">
<h4>Voci di preventivo</h4>
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>Categoria</th><th>Descrizione</th><th>Tipo</th><th class="text-end">Importo previsto</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($voci as $v): ?>
<tr>
    <td><?php echo h($v['categoria_nome'] ?? '-'); ?></td>
    <td><?php echo h($v['descrizione']); ?></td>
    <td><?php echo $v['tipo'] === 'entrata' ? '<span class="text-success">Entrata</span>' : '<span class="text-danger">Uscita</span>'; ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$v['importo_previsto']); ?></td>
    <td>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina_voce"><input type="hidden" name="voce_id" value="<?php echo (int)$v['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">X</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($voci)): ?><tr><td colspan="5" class="text-muted">Nessuna voce. Aggiungi le voci del preventivo.</td></tr><?php endif; ?>
</tbody>
<tfoot>
<tr class="table-secondary fw-bold">
    <td colspan="3">Totale uscite previste</td>
    <td class="text-end">&euro; <?php echo format_euro((float)$totali['uscite_previste']); ?></td>
    <td></td>
</tr>
<tr class="table-secondary fw-bold">
    <td colspan="3">Totale entrate previste</td>
    <td class="text-end">&euro; <?php echo format_euro((float)$totali['entrate_previste']); ?></td>
    <td></td>
</tr>
</tfoot>
</table>
</div>
<div class="col-md-4">
<h4>Aggiungi voce</h4>
<form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiungi_voce">
    <div class="mb-2"><label class="form-label">Categoria</label>
    <select name="categoria_id" class="form-select">
        <option value="">Nessuna</option>
        <?php foreach ($categorie as $cat): ?><option value="<?php echo (int)$cat['id']; ?>"><?php echo h($cat['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Descrizione*</label>
    <input name="descrizione" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Tipo*</label>
    <select name="tipo" class="form-select" required>
        <option value="uscita">Uscita</option>
        <option value="entrata">Entrata</option>
    </select></div>
    <div class="mb-2"><label class="form-label">Importo previsto*</label>
    <input type="number" step="0.01" name="importo_previsto" class="form-control" required></div>
    <button class="btn btn-primary">Aggiungi</button>
</form>

<hr>
<h4>Modifica preventivo</h4>
<form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiorna">
    <div class="mb-2"><label class="form-label">Titolo</label>
    <input name="titolo" class="form-control" value="<?php echo h($preventivo['titolo']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Note</label>
    <textarea name="note" class="form-control" rows="2"><?php echo h($preventivo['note']); ?></textarea></div>
    <div class="mb-2"><label class="form-label">Stato</label>
    <select name="stato" class="form-select">
        <option value="bozza" <?php echo $preventivo['stato']==='bozza'?'selected':''; ?>>Bozza</option>
        <option value="approvato" <?php echo $preventivo['stato']==='approvato'?'selected':''; ?>>Approvato</option>
    </select></div>
    <button class="btn btn-primary">Salva</button>
</form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
