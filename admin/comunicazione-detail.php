<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Comunicazioni.php';
require_login();
require_admin();

$id = (int)($_GET['id'] ?? 0);
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna') {
        Comunicazioni::update($id, $_POST);
        $msg = 'Comunicazione aggiornata.';
        audit_log('update', 'comunicazioni', $id);
    } elseif ($azione === 'calcola_destinatari') {
        $count = Comunicazioni::calcolaDestinatari($id);
        $msg = "$count destinatari calcolati.";
    } elseif ($azione === 'invia') {
        $result = Comunicazioni::invia($id);
        $msg = 'Invio: ' . $result['msg'];
        audit_log('send', 'comunicazioni', $id);
    } elseif ($azione === 'archivia') {
        Comunicazioni::archivia($id);
        $msg = 'Comunicazione archiviata.';
        audit_log('archive', 'comunicazioni', $id);
    }
}

$com = Comunicazioni::find($id);
if (!$com) { header('Location: ' . url('/admin/comunicazioni.php')); exit; }

$destinatari = Comunicazioni::getDestinatari($id);
include __DIR__ . '/../includes/header.php';
?>
<h2>Comunicazione #<?php echo $id; ?>
    <?php echo stato_badge($com['stato']); ?>
</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-8">
    <div class="card mb-3">
    <div class="card-header">Dettaglio comunicazione</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><th style="width:180px;">Condominio</th><td><?php echo h($com['condominio_nome']); ?></td></tr>
            <tr><th>Tipo</th><td><?php echo h(ucfirst($com['tipo'])); ?></td></tr>
            <tr><th>Destinatari</th><td><?php echo h(ucfirst($com['destinatari_tipo'])); ?><?php echo $com['destinatari_filtro'] ? ' — ' . h($com['destinatari_filtro']) : ''; ?></td></tr>
            <tr><th>Stato</th><td><?php echo stato_badge($com['stato']); ?></td></tr>
            <tr><th>Creato da</th><td><?php echo h($com['creato_da_nome'] ?? ''); ?></td></tr>
            <tr><th>Data creazione</th><td><?php echo date('d/m/Y H:i', strtotime($com['created_at'])); ?></td></tr>
            <?php if ($com['inviata_at']): ?><tr><th>Inviata il</th><td><?php echo date('d/m/Y H:i', strtotime($com['inviata_at'])); ?></td></tr><?php endif; ?>
        </table>

        <?php if ($com['stato'] === 'bozza'): ?>
        <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiorna">
        <div class="mb-3">
            <label class="form-label">Oggetto</label>
            <input type="text" name="oggetto" class="form-control" value="<?php echo h($com['oggetto']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Corpo</label>
            <textarea name="corpo" class="form-control" rows="8" required><?php echo h($com['corpo']); ?></textarea>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <?php foreach (['comunicazione','sollecito','convocazione','verbale'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $com['tipo']===$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Destinatari</label>
                <select name="destinatari_tipo" class="form-select">
                    <?php foreach (['tutti','scala','unita','persona'] as $dt): ?>
                    <option value="<?php echo $dt; ?>" <?php echo $com['destinatari_tipo']===$dt?'selected':''; ?>><?php echo ucfirst($dt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filtro</label>
                <input type="text" name="destinatari_filtro" class="form-control" value="<?php echo h($com['destinatari_filtro'] ?? ''); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Salva modifiche</button>
        </form>
        <?php else: ?>
        <h5>Oggetto</h5>
        <p><?php echo h($com['oggetto']); ?></p>
        <h5>Corpo</h5>
        <div class="border p-3 bg-light"><?php echo nl2br(h($com['corpo'])); ?></div>
        <?php endif; ?>
    </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card mb-3">
    <div class="card-header">Azioni</div>
    <div class="card-body">
        <?php if ($com['stato'] === 'bozza'): ?>
        <form method="post" class="mb-2">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="calcola_destinatari">
            <button class="btn btn-outline-primary w-100">Calcola destinatari</button>
        </form>
        <?php if (count($destinatari) > 0): ?>
        <form method="post" class="mb-2" onsubmit="return confirm('Inviare a <?php echo count($destinatari); ?> destinatari?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="invia">
            <button class="btn btn-success w-100">Invia comunicazione</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($com['stato'] === 'inviata'): ?>
        <form method="post" class="mb-2">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="archivia">
            <button class="btn btn-outline-secondary w-100">Archivia</button>
        </form>
        <?php endif; ?>
        <a href="comunicazioni.php" class="btn btn-secondary w-100 mt-2">Torna alla lista</a>
    </div>
    </div>

    <div class="card">
    <div class="card-header">Destinatari (<?php echo count($destinatari); ?>)</div>
    <div class="card-body p-0">
    <?php if (empty($destinatari)): ?>
    <div class="p-3 text-center text-muted">Nessun destinatario. Clicca "Calcola destinatari".</div>
    <?php else: ?>
    <table class="table table-sm mb-0">
    <thead><tr><th>Nome</th><th>Email</th><th>Stato</th></tr></thead>
    <tbody>
    <?php foreach ($destinatari as $d): ?>
    <tr>
        <td><?php echo h($d['nome']); ?></td>
        <td><?php echo h($d['email']); ?></td>
        <td><?php echo stato_badge($d['stato']); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>
    </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
