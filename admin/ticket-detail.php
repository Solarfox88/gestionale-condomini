<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Tickets.php';
require_once __DIR__ . '/../app/Persone.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ticket = Tickets::find($id);
if (!$ticket) { echo 'Ticket non trovato.'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'aggiorna') {
        Tickets::update($id, $_POST);
        $msg = 'Ticket aggiornato.';
        $ticket = Tickets::find($id);
        audit_log('update', 'ticket', $id, 'stato=' . ($_POST['stato'] ?? ''));
    } elseif ($azione === 'messaggio') {
        $interno = isset($_POST['interno']) && $_POST['interno'] === '1';
        Tickets::addMessaggio($id, (int)$_SESSION['user']['id'], $_POST['messaggio'], $interno);
        $msg = 'Messaggio aggiunto.';
    } elseif ($azione === 'elimina') {
        Tickets::delete($id);
        header('Location: ticket.php?msg=eliminato');
        exit;
    }
}

$messaggi = Tickets::getMessaggi($id);
$persone = get_persone();
$stati = ['aperto','preso_in_carico','in_attesa','in_lavorazione','risolto','chiuso','respinto'];
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Ticket #<?php echo $id; ?> - <?php echo h($ticket['titolo']); ?></h2>
    <div>
        <form method="post" class="d-inline" onsubmit="return confirm('Eliminare?')">
            <?php echo csrf_field(); ?><input type="hidden" name="azione" value="elimina">
            <button class="btn btn-danger btn-sm">Elimina</button>
        </form>
        <a href="ticket.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-4">
    <div class="card mb-3"><div class="card-body">
        <p><strong>Condominio:</strong> <?php echo h($ticket['condominio_nome']); ?></p>
        <p><strong>Autore:</strong> <?php echo h($ticket['autore_nome']); ?></p>
        <p><strong>Stato:</strong> <?php echo stato_badge($ticket['stato']); ?></p>
        <p><strong>Priorita:</strong> <?php echo stato_badge($ticket['priorita']); ?></p>
        <p><strong>Categoria:</strong> <?php echo h($ticket['categoria'] ?? '-'); ?></p>
        <p><strong>Assegnato a:</strong> <?php echo $ticket['assegnato_nome'] ? h($ticket['assegnato_cognome'].' '.$ticket['assegnato_nome']) : '-'; ?></p>
        <p><strong>Creato:</strong> <?php echo h($ticket['created_at']); ?></p>
        <p><strong>Aggiornato:</strong> <?php echo h($ticket['updated_at']); ?></p>
    </div></div>

    <h5>Aggiorna ticket</h5>
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="aggiorna">
        <div class="mb-2"><label class="form-label">Stato</label>
        <select name="stato" class="form-select">
            <?php foreach ($stati as $s): ?><option value="<?php echo $s; ?>" <?php echo $ticket['stato']===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?>
        </select></div>
        <div class="mb-2"><label class="form-label">Priorita</label>
        <select name="priorita" class="form-select">
            <?php foreach (['bassa','media','alta','urgente'] as $p): ?><option value="<?php echo $p; ?>" <?php echo $ticket['priorita']===$p?'selected':''; ?>><?php echo ucfirst($p); ?></option><?php endforeach; ?>
        </select></div>
        <div class="mb-2"><label class="form-label">Categoria</label><input name="categoria" class="form-control" value="<?php echo h($ticket['categoria'] ?? ''); ?>"></div>
        <div class="mb-2"><label class="form-label">Assegnato a</label>
        <select name="assegnato_a" class="form-select"><option value="">-</option>
            <?php foreach ($persone as $p): ?><option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$ticket['assegnato_a']==(int)$p['id']?'selected':''; ?>><?php echo h($p['cognome'].' '.$p['nome']); ?></option><?php endforeach; ?>
        </select></div>
        <button class="btn btn-primary">Salva</button>
    </form>
</div>
<div class="col-md-8">
    <h4>Descrizione</h4>
    <div class="card mb-3"><div class="card-body"><?php echo nl2br(h($ticket['descrizione'])); ?></div></div>

    <h4>Messaggi (<?php echo count($messaggi); ?>)</h4>
    <?php foreach ($messaggi as $m): ?>
    <div class="card mb-2 <?php echo $m['interno'] ? 'border-warning' : ''; ?>">
        <div class="card-header d-flex justify-content-between">
            <strong><?php echo h($m['autore_nome']); ?></strong>
            <small><?php echo h($m['created_at']); ?> <?php if ($m['interno']): ?><span class="badge bg-warning text-dark">Nota interna</span><?php endif; ?></small>
        </div>
        <div class="card-body"><?php echo nl2br(h($m['messaggio'])); ?></div>
    </div>
    <?php endforeach; ?>

    <h5 class="mt-3">Aggiungi messaggio</h5>
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="messaggio">
        <div class="mb-2"><textarea name="messaggio" class="form-control" rows="3" required placeholder="Scrivi messaggio..."></textarea></div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="interno" value="1" id="notaInterna">
            <label class="form-check-label" for="notaInterna">Nota interna (non visibile al condomino)</label>
        </div>
        <button class="btn btn-primary">Invia</button>
    </form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
