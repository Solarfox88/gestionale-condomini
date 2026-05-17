<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/EmailService.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'salva') {
        EmailService::saveConfig($_POST);
        $msg = 'Configurazione SMTP salvata.';
        audit_log('update', 'impostazioni', 0);
    } elseif ($azione === 'test') {
        $result = EmailService::testConnection();
        $msg = ($result === true) ? 'Connessione SMTP riuscita!' : 'Test: ' . $result;
    } elseif ($azione === 'test_invio') {
        $to = trim($_POST['test_email'] ?? '');
        if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $result = EmailService::send($to, 'Test Gestionale Condomini', 'Questa e una email di test dal Gestionale Condomini. Se la ricevi, la configurazione e corretta.');
            $msg = ($result === true) ? "Email di test inviata a $to" : "Errore: $result";
            EmailService::log(null, $to, 'Test', $result === true ? 'inviato' : 'errore', is_string($result) ? $result : null);
        } else {
            $msg = 'Inserire un indirizzo email valido.';
        }
    }
}

$config = EmailService::getConfig();
$logs = EmailService::getLogs(20);
include __DIR__ . '/../includes/header.php';
?>
<h2>Impostazioni Email / SMTP</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-8">
<div class="card mb-3">
<div class="card-header">Configurazione SMTP</div>
<div class="card-body">
<form method="post">
<?php echo csrf_field(); ?><input type="hidden" name="azione" value="salva">
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">SMTP attivo</label>
        <select name="smtp_attivo" class="form-select">
            <option value="0" <?php echo ($config['smtp_attivo'] ?? '0')==='0'?'selected':''; ?>>No (usa mail())</option>
            <option value="1" <?php echo ($config['smtp_attivo'] ?? '0')==='1'?'selected':''; ?>>Si</option>
        </select>
        <small class="text-muted">Se disattivo, usa la funzione mail() di PHP</small>
    </div>
    <div class="col-md-5">
        <label class="form-label">Host SMTP</label>
        <input type="text" name="smtp_host" class="form-control" value="<?php echo h($config['smtp_host']); ?>" placeholder="smtp.gmail.com">
    </div>
    <div class="col-md-3">
        <label class="form-label">Porta</label>
        <input type="number" name="smtp_port" class="form-control" value="<?php echo h($config['smtp_port']); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Utente</label>
        <input type="text" name="smtp_user" class="form-control" value="<?php echo h($config['smtp_user']); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Password</label>
        <input type="password" name="smtp_password" class="form-control" value="<?php echo h($config['smtp_password']); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Crittografia</label>
        <select name="smtp_encryption" class="form-select">
            <option value="tls" <?php echo ($config['smtp_encryption']??'')==='tls'?'selected':''; ?>>TLS (porta 587)</option>
            <option value="ssl" <?php echo ($config['smtp_encryption']??'')==='ssl'?'selected':''; ?>>SSL (porta 465)</option>
            <option value="none" <?php echo ($config['smtp_encryption']??'')==='none'?'selected':''; ?>>Nessuna</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email mittente</label>
        <input type="email" name="smtp_from_email" class="form-control" value="<?php echo h($config['smtp_from_email']); ?>" placeholder="admin@condominio.it">
    </div>
    <div class="col-md-6">
        <label class="form-label">Nome mittente</label>
        <input type="text" name="smtp_from_name" class="form-control" value="<?php echo h($config['smtp_from_name']); ?>">
    </div>
    <div class="col-12"><button type="submit" class="btn btn-primary">Salva configurazione</button></div>
</div>
</form>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card mb-3">
<div class="card-header">Test connessione</div>
<div class="card-body">
    <form method="post" class="mb-3">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="test">
        <button class="btn btn-outline-info w-100">Test connessione SMTP</button>
    </form>
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="test_invio">
        <div class="mb-2"><input type="email" name="test_email" class="form-control" placeholder="email@esempio.it" required></div>
        <button class="btn btn-outline-success w-100">Invia email di test</button>
    </form>
</div>
</div>
</div>
</div>

<div class="card">
<div class="card-header">Log invii recenti (ultimi 20)</div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
<thead><tr><th>Data</th><th>Destinatario</th><th>Oggetto</th><th>Stato</th><th>Errore</th></tr></thead>
<tbody>
<?php if (empty($logs)): ?>
<tr><td colspan="5" class="text-center text-muted">Nessun log.</td></tr>
<?php endif; ?>
<?php foreach ($logs as $l): ?>
<tr>
    <td><?php echo date('d/m/Y H:i', strtotime($l['created_at'])); ?></td>
    <td><?php echo h($l['destinatario_email']); ?></td>
    <td><?php echo h($l['oggetto']); ?></td>
    <td><?php echo stato_badge($l['stato']); ?></td>
    <td><?php echo h($l['errore'] ?? ''); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
