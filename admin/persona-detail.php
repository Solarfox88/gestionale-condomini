<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/UnitaPersone.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$persona = get_persona($id);
if (!$persona) { echo 'Persona non trovata.'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna') {
        if (update_persona($id, $_POST)) {
            $msg = 'Persona aggiornata.';
            $persona = get_persona($id);
        } else {
            $msg = 'Errore nell\'aggiornamento.';
        }
    } elseif ($azione === 'elimina') {
        if (delete_persona($id)) {
            header('Location: persone.php?msg=eliminata');
            exit;
        }
        $msg = 'Errore nell\'eliminazione.';
    }
}

$unitaCollegate = get_persona_unita($id);
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($persona['cognome'] . ' ' . $persona['nome']); ?></h2>
    <div>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare questa persona?')">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="elimina">
            <button class="btn btn-danger btn-sm">Elimina</button>
        </form>
        <a href="persone.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-6">
<h4>Modifica dati</h4>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="aggiorna">
    <div class="mb-2"><label class="form-label">Nome*</label><input name="nome" class="form-control" value="<?php echo h($persona['nome']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Cognome*</label><input name="cognome" class="form-control" value="<?php echo h($persona['cognome']); ?>" required></div>
    <div class="mb-2"><label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
            <?php foreach (['persona','azienda','fornitore'] as $t): ?>
            <option value="<?php echo $t; ?>" <?php echo $persona['tipo']===$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Ragione Sociale</label><input name="ragione_sociale" class="form-control" value="<?php echo h($persona['ragione_sociale'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">Codice Fiscale</label><input name="codice_fiscale" class="form-control" value="<?php echo h($persona['codice_fiscale'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">Partita IVA</label><input name="partita_iva" class="form-control" value="<?php echo h($persona['partita_iva'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo h($persona['email'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">PEC</label><input type="email" name="pec" class="form-control" value="<?php echo h($persona['pec'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">Telefono</label><input name="telefono" class="form-control" value="<?php echo h($persona['telefono'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">Indirizzo</label><input name="indirizzo" class="form-control" value="<?php echo h($persona['indirizzo'] ?? ''); ?>"></div>
    <div class="mb-2"><label class="form-label">Note</label><textarea name="note" class="form-control"><?php echo h($persona['note'] ?? ''); ?></textarea></div>
    <button class="btn btn-primary">Salva</button>
</form>
</div>
<div class="col-md-6">
<h4>Unita collegate</h4>
<?php if ($unitaCollegate): ?>
<table class="table table-sm table-striped">
<thead><tr><th>Condominio</th><th>Unita</th><th>Ruolo</th><th>%</th><th>Da</th><th>A</th></tr></thead>
<tbody>
<?php foreach ($unitaCollegate as $uc): ?>
<tr>
    <td><?php echo h($uc['condominio_nome'] ?? ''); ?></td>
    <td><?php echo h(trim(($uc['scala'] ?? '').' '.($uc['piano'] ?? '').' '.($uc['interno'] ?? ''))); ?></td>
    <td><?php echo h($uc['ruolo']); ?></td>
    <td><?php echo number_format((float)($uc['percentuale'] ?? 0), 2); ?>%</td>
    <td><?php echo h($uc['data_inizio'] ?? '-'); ?></td>
    <td><?php echo h($uc['data_fine'] ?? '-'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?><p class="text-muted">Nessuna unita collegata.</p><?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
