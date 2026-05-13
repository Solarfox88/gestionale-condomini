<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Persone.php';
require_login();
require_admin();

Rate::aggiornaScadute();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea_rata') {
        $id = Rate::create($_POST);
        $msg = $id ? 'Rata creata.' : 'Errore.';
        if ($id) audit_log('create', 'rate', (int)$id);
    } elseif ($azione === 'paga') {
        $ok = Rate::registraPagamento((int)$_POST['rata_id'], $_POST);
        $msg = $ok ? 'Pagamento registrato.' : 'Errore.';
        if ($ok) audit_log('pagamento', 'rate', (int)$_POST['rata_id'], 'importo=' . ($_POST['importo'] ?? ''));
    } elseif ($azione === 'elimina') {
        $ok = Rate::delete((int)$_POST['id']);
        $msg = $ok ? 'Rata eliminata.' : 'Errore.';
    }
}

$filters = [];
if (!empty($_GET['condominio_id'])) $filters['condominio_id'] = (int)$_GET['condominio_id'];
if (!empty($_GET['unita_id'])) $filters['unita_id'] = (int)$_GET['unita_id'];
if (!empty($_GET['stato'])) $filters['stato'] = $_GET['stato'];
$rate = Rate::all($filters);
$condomini = get_condomini();
$esercizi = Esercizi::all();
$unitaAll = get_unita();
$persone = get_persone();
include __DIR__ . '/../includes/header.php';
?>
<h2>Rate</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo ($filters['condominio_id'] ?? '')==$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Stato</label>
    <select name="stato" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach (['da_pagare','parziale','pagata','scaduta'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($filters['stato'] ?? '')===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><a href="rate.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>ID</th><th>Condominio</th><th>Esercizio</th><th>Unita</th><th>Descrizione</th><th class="text-end">Importo</th><th class="text-end">Pagato</th><th>Scadenza</th><th>Stato</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($rate as $r):
$pagato = Rate::totalePagato((int)$r['id']);
$residuo = (float)$r['importo'] - $pagato;
?>
<tr>
    <td><?php echo (int)$r['id']; ?></td>
    <td><?php echo h($r['condominio_nome']); ?></td>
    <td><?php echo h($r['esercizio_nome']); ?></td>
    <td><?php echo h(trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? ''))); ?></td>
    <td><?php echo h($r['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$r['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($pagato); ?></td>
    <td><?php echo h($r['scadenza']); ?></td>
    <td><?php echo stato_badge($r['stato']); ?></td>
    <td>
        <?php if ($r['stato'] !== 'pagata'): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal<?php echo (int)$r['id']; ?>">Paga</button>
        <?php endif; ?>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">&times;</button>
        </form>
    </td>
</tr>
<?php if ($r['stato'] !== 'pagata'): ?>
<div class="modal fade" id="payModal<?php echo (int)$r['id']; ?>" tabindex="-1">
<div class="modal-dialog"><div class="modal-content"><form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="paga"><input type="hidden" name="rata_id" value="<?php echo (int)$r['id']; ?>">
    <div class="modal-header"><h5 class="modal-title">Pagamento rata #<?php echo (int)$r['id']; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p>Importo rata: &euro; <?php echo format_euro((float)$r['importo']); ?> | Residuo: &euro; <?php echo format_euro($residuo); ?></p>
        <div class="mb-2"><label class="form-label">Importo*</label><input type="number" step="0.01" name="importo" class="form-control" value="<?php echo number_format($residuo,2,'.',''); ?>" required></div>
        <div class="mb-2"><label class="form-label">Data*</label><input type="date" name="data_pagamento" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
        <div class="mb-2"><label class="form-label">Metodo</label>
        <select name="metodo" class="form-select"><option value="bonifico">Bonifico</option><option value="contanti">Contanti</option><option value="assegno">Assegno</option><option value="altro">Altro</option></select></div>
        <div class="mb-2"><label class="form-label">Persona</label>
        <select name="persona_id" class="form-select"><option value="">-</option>
        <?php foreach ($persone as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['cognome'].' '.$p['nome']); ?></option><?php endforeach; ?>
        </select></div>
        <div class="mb-2"><label class="form-label">Riferimento</label><input name="riferimento" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Note</label><input name="note" class="form-control"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-success">Registra pagamento</button></div>
</form></div></div>
</div>
<?php endif; endforeach; ?>
</tbody>
</table>
</div>

<hr>
<h4>Nuova rata manuale</h4>
<form method="post" class="row g-2 mb-4">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="crea_rata">
    <div class="col-md-2"><label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required><option value="">Seleziona...</option>
    <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Esercizio*</label>
    <select name="esercizio_id" class="form-select" required><option value="">Seleziona...</option>
    <?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>"><?php echo h($e['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Unita*</label>
    <select name="unita_id" class="form-select" required><option value="">Seleziona...</option>
    <?php foreach ($unitaAll as $u): ?><option value="<?php echo (int)$u['id']; ?>"><?php echo h($u['condominio_nome'].' Sc.'.$u['scala'].' Int.'.$u['interno']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Descrizione*</label><input name="descrizione" class="form-control" required></div>
    <div class="col-md-1"><label class="form-label">Importo*</label><input type="number" step="0.01" name="importo" class="form-control" required></div>
    <div class="col-md-1"><label class="form-label">Scadenza*</label><input type="date" name="scadenza" class="form-control" required></div>
    <div class="col-auto align-self-end"><button class="btn btn-primary">Crea</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
