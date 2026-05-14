<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/UnitaPersone.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$assemblea = Assemblee::find($id);
if (!$assemblea) { echo 'Assemblea non trovata.'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna') {
        Assemblee::update($id, $_POST);
        $msg = 'Assemblea aggiornata.';
        $assemblea = Assemblee::find($id);
        audit_log('update', 'assemblee', $id);
    } elseif ($azione === 'presenza') {
        Assemblee::salvaPresenza($id, (int)$_POST['persona_id'], !empty($_POST['unita_id']) ? (int)$_POST['unita_id'] : null, isset($_POST['presente']), !empty($_POST['delegato_da']) ? (int)$_POST['delegato_da'] : null, (float)($_POST['millesimi'] ?? 0));
        $msg = 'Presenza aggiornata.';
    } elseif ($azione === 'elimina') {
        Assemblee::delete($id);
        header('Location: assemblee.php?msg=eliminata');
        exit;
    }
}

$presenze = Assemblee::getPresenze($id);
$totMillesimi = Assemblee::totaleMillesimiPresenti($id);
$unitaCond = get_unita((int)$assemblea['condominio_id']);
$persone = get_persone();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($assemblea['titolo']); ?> - <?php echo h($assemblea['condominio_nome']); ?></h2>
    <div>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina">
            <button class="btn btn-danger btn-sm">Elimina</button>
        </form>
        <a href="stampa-convocazione.php?id=<?php echo $id; ?>" class="btn btn-outline-info btn-sm" target="_blank">Stampa Convocazione</a>
        <a href="stampa-verbale.php?id=<?php echo $id; ?>" class="btn btn-outline-info btn-sm" target="_blank">Stampa Verbale</a>
        <a href="assemblee.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-5">
    <h4>Dati assemblea</h4>
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiorna">
        <div class="mb-2"><label class="form-label">Titolo*</label><input name="titolo" class="form-control" value="<?php echo h($assemblea['titolo']); ?>" required></div>
        <div class="mb-2"><label class="form-label">1a convocazione</label><input type="datetime-local" name="data_prima_convocazione" class="form-control" value="<?php echo h(str_replace(' ','T',$assemblea['data_prima_convocazione'] ?? '')); ?>"></div>
        <div class="mb-2"><label class="form-label">2a convocazione*</label><input type="datetime-local" name="data_seconda_convocazione" class="form-control" value="<?php echo h(str_replace(' ','T',$assemblea['data_seconda_convocazione'])); ?>" required></div>
        <div class="mb-2"><label class="form-label">Luogo</label><input name="luogo" class="form-control" value="<?php echo h($assemblea['luogo'] ?? ''); ?>"></div>
        <div class="mb-2"><label class="form-label">Stato</label>
        <select name="stato" class="form-select">
            <?php foreach (['bozza','convocata','svolta','annullata'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $assemblea['stato']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="mb-2"><label class="form-label">Ordine del giorno*</label><textarea name="ordine_giorno" class="form-control" rows="4" required><?php echo h($assemblea['ordine_giorno']); ?></textarea></div>
        <div class="mb-2"><label class="form-label">Verbale</label><textarea name="verbale" class="form-control" rows="4"><?php echo h($assemblea['verbale'] ?? ''); ?></textarea></div>
        <button class="btn btn-primary">Salva</button>
    </form>
</div>
<div class="col-md-7">
    <h4>Presenze</h4>
    <p>Millesimi presenti: <strong><?php echo number_format($totMillesimi,4,',','.'); ?></strong> / 1000</p>

    <?php if ($presenze): ?>
    <table class="table table-sm table-striped mb-3">
    <thead><tr><th>Persona</th><th>Presente</th><th>Delegato da</th><th>Millesimi</th></tr></thead>
    <tbody>
    <?php foreach ($presenze as $pr): ?>
    <tr>
        <td><?php echo h($pr['cognome'].' '.$pr['nome']); ?></td>
        <td><?php echo $pr['presente'] ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
        <td><?php echo $pr['delegante_cognome'] ? h($pr['delegante_cognome'].' '.$pr['delegante_nome']) : '-'; ?></td>
        <td><?php echo number_format((float)$pr['millesimi_presenti'],4,',','.'); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>

    <h5>Registra presenza</h5>
    <form method="post" class="row g-2">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="presenza">
        <div class="col-md-3"><label class="form-label">Persona*</label>
        <select name="persona_id" class="form-select" required><option value="">Seleziona...</option>
            <?php foreach ($persone as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['cognome'].' '.$p['nome']); ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-3"><label class="form-label">Unita</label>
        <select name="unita_id" class="form-select"><option value="">-</option>
            <?php foreach ($unitaCond as $u): ?><option value="<?php echo (int)$u['id']; ?>"><?php echo h('Sc.'.$u['scala'].' P.'.$u['piano'].' Int.'.$u['interno']); ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><label class="form-label">Millesimi</label><input type="number" step="0.0001" name="millesimi" class="form-control" value="0"></div>
        <div class="col-md-2">
            <div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="presente" id="presente" checked><label class="form-check-label" for="presente">Presente</label></div>
        </div>
        <div class="col-auto align-self-end"><button class="btn btn-primary">Salva</button></div>
    </form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
