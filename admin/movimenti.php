<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/CategorieSpesa.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? 'crea';
    if ($azione === 'crea') {
        $id = Movimenti::create($_POST);
        $msg = $id ? 'Movimento registrato.' : 'Errore.';
        if ($id) audit_log('create', 'movimenti', (int)$id);
    } elseif ($azione === 'elimina') {
        $ok = Movimenti::delete((int)$_POST['id']);
        $msg = $ok ? 'Movimento eliminato.' : 'Errore.';
    }
}

$filters = [];
if (!empty($_GET['condominio_id'])) $filters['condominio_id'] = (int)$_GET['condominio_id'];
if (!empty($_GET['esercizio_id'])) $filters['esercizio_id'] = (int)$_GET['esercizio_id'];
if (!empty($_GET['tipo'])) $filters['tipo'] = $_GET['tipo'];
$movimenti = Movimenti::all($filters);
$condomini = get_condomini();
$esercizi = Esercizi::all();
$categorie = CategorieSpesa::all();
$persone = get_persone();
$unitaAll = get_unita();
include __DIR__ . '/../includes/header.php';
?>
<h2>Movimenti / Prima Nota</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
<div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Condominio</label>
    <select name="condominio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo ($filters['condominio_id'] ?? '')==$c['id']?'selected':''; ?>><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Esercizio</label>
    <select name="esercizio_id" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>" <?php echo ($filters['esercizio_id'] ?? '')==$e['id']?'selected':''; ?>><?php echo h($e['nome'].' - '.$e['condominio_nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Tipo</label>
    <select name="tipo" class="form-select" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <option value="entrata" <?php echo ($filters['tipo'] ?? '')==='entrata'?'selected':''; ?>>Entrata</option>
        <option value="uscita" <?php echo ($filters['tipo'] ?? '')==='uscita'?'selected':''; ?>>Uscita</option>
    </select></div>
    <div class="col-auto"><a href="movimenti.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<?php
$totEntrate = 0; $totUscite = 0;
foreach ($movimenti as $m) { if ($m['tipo']==='entrata') $totEntrate += (float)$m['importo']; else $totUscite += (float)$m['importo']; }
?>
<div class="mb-3">
<span class="badge bg-success">Entrate: &euro; <?php echo format_euro($totEntrate); ?></span>
<span class="badge bg-danger">Uscite: &euro; <?php echo format_euro($totUscite); ?></span>
<span class="badge bg-primary">Saldo: &euro; <?php echo format_euro($totEntrate - $totUscite); ?></span>
</div>

<div class="row">
<div class="col-md-8">
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>Data</th><th>Condominio</th><th>Tipo</th><th>Categoria</th><th>Descrizione</th><th>Persona</th><th class="text-end">Importo</th><th class="text-end">Saldo</th><th></th></tr></thead>
<tbody>
<?php
$saldo = 0;
$movRev = array_reverse($movimenti);
$saldiMap = [];
foreach ($movRev as $m) {
    $saldo += ($m['tipo']==='entrata' ? (float)$m['importo'] : -(float)$m['importo']);
    $saldiMap[$m['id']] = $saldo;
}
foreach ($movimenti as $m): ?>
<tr>
    <td><?php echo h($m['data_movimento']); ?></td>
    <td><?php echo h($m['condominio_nome']); ?></td>
    <td><?php echo $m['tipo']==='entrata' ? '<span class="text-success">Entrata</span>' : '<span class="text-danger">Uscita</span>'; ?></td>
    <td><?php echo h($m['categoria_nome'] ?? '-'); ?></td>
    <td><?php echo h($m['descrizione']); ?></td>
    <td><?php echo h(trim(($m['persona_cognome'] ?? '').' '.($m['persona_nome'] ?? ''))); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($saldiMap[$m['id']] ?? 0); ?></td>
    <td>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina"><input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">&times;</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<div class="col-md-4">
<h4>Nuovo movimento</h4>
<form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="crea">
    <div class="mb-2"><label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required>
        <option value="">Seleziona...</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Esercizio*</label>
    <select name="esercizio_id" class="form-select" required>
        <option value="">Seleziona...</option>
        <?php foreach ($esercizi as $e): ?><option value="<?php echo (int)$e['id']; ?>"><?php echo h($e['nome'].' - '.$e['condominio_nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Tipo*</label>
    <select name="tipo" class="form-select" required>
        <option value="uscita">Uscita</option>
        <option value="entrata">Entrata</option>
    </select></div>
    <div class="mb-2"><label class="form-label">Categoria</label>
    <select name="categoria_id" class="form-select">
        <option value="">Nessuna</option>
        <?php foreach ($categorie as $cat): ?><option value="<?php echo (int)$cat['id']; ?>"><?php echo h($cat['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Persona/Fornitore</label>
    <select name="persona_id" class="form-select">
        <option value="">Nessuna</option>
        <?php foreach ($persone as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['cognome'].' '.$p['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Unita</label>
    <select name="unita_id" class="form-select">
        <option value="">Nessuna</option>
        <?php foreach ($unitaAll as $u): ?><option value="<?php echo (int)$u['id']; ?>"><?php echo h($u['condominio_nome'].' - Sc.'.$u['scala'].' P.'.$u['piano'].' Int.'.$u['interno']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="mb-2"><label class="form-label">Descrizione*</label><input name="descrizione" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Importo (&euro;)*</label><input type="number" step="0.01" name="importo" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Data*</label><input type="date" name="data_movimento" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
    <div class="mb-2"><label class="form-label">Metodo pagamento</label><input name="metodo_pagamento" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Riferimento</label><input name="riferimento" class="form-control"></div>
    <button class="btn btn-primary">Registra</button>
</form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
