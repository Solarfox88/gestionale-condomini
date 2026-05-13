<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Tickets.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Persone.php';
require_login();
require_admin();

$msg = $_GET['msg'] ?? '';
if ($msg === 'eliminato') $msg = 'Ticket eliminato.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'crea') {
        $id = Tickets::create($_POST, (int)$_SESSION['user']['id']);
        $msg = $id ? 'Ticket creato.' : 'Errore.';
        if ($id) audit_log('create', 'ticket', (int)$id);
    } elseif ($azione === 'stato') {
        Tickets::updateStatus((int)$_POST['id'], $_POST['stato']);
        $msg = 'Stato aggiornato.';
        audit_log('update_status', 'ticket', (int)$_POST['id'], $_POST['stato']);
    }
}

$filters = [];
if (!empty($_GET['condominio_id'])) $filters['condominio_id'] = (int)$_GET['condominio_id'];
if (!empty($_GET['stato'])) $filters['stato'] = $_GET['stato'];
if (!empty($_GET['priorita'])) $filters['priorita'] = $_GET['priorita'];
$tickets = Tickets::all($filters);
$condomini = get_condomini();
$persone = get_persone();
include __DIR__ . '/../includes/header.php';
?>
<h2>Ticket</h2>
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
        <?php foreach (['aperto','preso_in_carico','in_attesa','in_lavorazione','risolto','chiuso','respinto'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($filters['stato'] ?? '')===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Priorita</label>
    <select name="priorita" class="form-select" onchange="this.form.submit()">
        <option value="">Tutte</option>
        <?php foreach (['bassa','media','alta','urgente'] as $p): ?><option value="<?php echo $p; ?>" <?php echo ($filters['priorita'] ?? '')===$p?'selected':''; ?>><?php echo ucfirst($p); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><a href="ticket.php" class="btn btn-secondary">Reset</a></div>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>ID</th><th>Condominio</th><th>Titolo</th><th>Autore</th><th>Priorita</th><th>Stato</th><th>Aggiornato</th><th>Azioni</th></tr></thead>
<tbody>
<?php foreach ($tickets as $t): ?>
<tr>
    <td><?php echo (int)$t['id']; ?></td>
    <td><?php echo h($t['condominio_nome']); ?></td>
    <td><?php echo h($t['titolo']); ?></td>
    <td><?php echo h($t['autore_nome']); ?></td>
    <td><?php echo stato_badge($t['priorita']); ?></td>
    <td><?php echo stato_badge($t['stato']); ?></td>
    <td><?php echo h($t['updated_at']); ?></td>
    <td><a href="ticket-detail.php?id=<?php echo (int)$t['id']; ?>" class="btn btn-sm btn-primary">Dettaglio</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<hr>
<h4>Nuovo ticket</h4>
<form method="post" class="row g-2 mb-3">
    <?php echo csrf_field(); ?><input type="hidden" name="azione" value="crea">
    <div class="col-md-2"><label class="form-label">Condominio*</label>
    <select name="condominio_id" class="form-select" required><option value="">Seleziona...</option>
    <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><label class="form-label">Titolo*</label><input name="titolo" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Descrizione*</label><textarea name="descrizione" class="form-control" rows="1" required></textarea></div>
    <div class="col-md-1"><label class="form-label">Priorita</label>
    <select name="priorita" class="form-select"><option value="media">Media</option><option value="bassa">Bassa</option><option value="alta">Alta</option><option value="urgente">Urgente</option></select></div>
    <div class="col-md-2"><label class="form-label">Assegnato a</label>
    <select name="assegnato_a" class="form-select"><option value="">-</option>
    <?php foreach ($persone as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['cognome'].' '.$p['nome']); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto align-self-end"><button class="btn btn-primary">Crea</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
