<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esercizio = Esercizi::find($id);
if (!$esercizio) { echo 'Esercizio non trovato.'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna') {
        if (Esercizi::update($id, $_POST)) {
            $msg = 'Esercizio aggiornato.';
            $esercizio = Esercizi::find($id);
            audit_log('update', 'esercizi', $id);
        } else {
            $msg = 'Errore.';
        }
    } elseif ($azione === 'elimina') {
        if (Esercizi::delete($id)) {
            header('Location: esercizi.php?msg=eliminato');
            exit;
        }
        $msg = 'Impossibile eliminare (verificare che non ci siano movimenti/rate collegate).';
    }
}

$riepilogo = Esercizi::riepilogo($id);
$movimenti = Movimenti::all(['esercizio_id' => $id, 'condominio_id' => $esercizio['condominio_id']]);

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($esercizio['nome']); ?> - <?php echo h($esercizio['condominio_nome']); ?></h2>
    <div>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questo esercizio?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina">
            <button class="btn btn-danger btn-sm">Elimina</button>
        </form>
        <a href="esercizi.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row mb-4">
<div class="col-md-3"><div class="card text-bg-success"><div class="card-body">
    <h5>&euro; <?php echo format_euro((float)$riepilogo['entrate']); ?></h5><p>Entrate</p>
</div></div></div>
<div class="col-md-3"><div class="card text-bg-danger"><div class="card-body">
    <h5>&euro; <?php echo format_euro((float)$riepilogo['uscite']); ?></h5><p>Uscite</p>
</div></div></div>
<div class="col-md-3"><div class="card text-bg-primary"><div class="card-body">
    <h5>&euro; <?php echo format_euro((float)$riepilogo['entrate'] - (float)$riepilogo['uscite']); ?></h5><p>Saldo</p>
</div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body">
    <h5><?php echo count($movimenti); ?></h5><p>Movimenti</p>
</div></div></div>
</div>

<div class="row">
<div class="col-md-5">
<h4>Modifica esercizio</h4>
<?php if ($esercizio['stato'] === 'chiuso'): ?><div class="alert alert-warning">Esercizio chiuso. Modifiche non raccomandate.</div><?php endif; ?>
<form method="post">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiorna">
    <div class="mb-2"><label class="form-label">Nome*</label><input name="nome" class="form-control" value="<?php echo h($esercizio['nome']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Data inizio</label><input type="date" name="data_inizio" class="form-control" value="<?php echo h($esercizio['data_inizio']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Data fine</label><input type="date" name="data_fine" class="form-control" value="<?php echo h($esercizio['data_fine']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Stato</label>
        <select name="stato" class="form-select">
            <?php foreach (['bozza','aperto','chiuso'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $esercizio['stato']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary">Salva</button>
</form>
</div>
<div class="col-md-7">
<h4>Movimenti</h4>
<table class="table table-sm table-striped">
<thead><tr><th>Data</th><th>Tipo</th><th>Descrizione</th><th class="text-end">Importo</th></tr></thead>
<tbody>
<?php
$saldo = 0;
foreach ($movimenti as $m):
    $saldo += ($m['tipo']==='entrata' ? (float)$m['importo'] : -(float)$m['importo']);
?>
<tr>
    <td><?php echo h($m['data_movimento']); ?></td>
    <td><?php echo $m['tipo']==='entrata' ? '<span class="text-success">Entrata</span>' : '<span class="text-danger">Uscita</span>'; ?></td>
    <td><?php echo h($m['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['importo']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
