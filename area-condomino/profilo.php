<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_login();

$user = get_user((int)$_SESSION['user']['id']);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? '';
    if ($azione === 'profilo') {
        if (update_user_profile((int)$user['id'], $_POST)) {
            $msg = 'Profilo aggiornato con successo.';
            $user = get_user((int)$user['id']);
            $_SESSION['user']['name'] = $user['name'];
        } else {
            $msg = 'Errore aggiornamento.';
        }
    } elseif ($azione === 'password') {
        if (empty($_POST['new_password']) || strlen($_POST['new_password']) < 6) {
            $msg = 'La nuova password deve avere almeno 6 caratteri.';
        } elseif ($_POST['new_password'] !== ($_POST['confirm_password'] ?? '')) {
            $msg = 'Le password non coincidono.';
        } else {
            $result = change_password((int)$user['id'], $_POST['current_password'], $_POST['new_password']);
            $msg = $result ? 'Password modificata con successo.' : 'Password attuale errata.';
        }
    } elseif ($azione === 'privacy') {
        global $pdo;
        $consenso_email = !empty($_POST['consenso_email']) ? 1 : 0;
        $consenso_sms = !empty($_POST['consenso_sms']) ? 1 : 0;
        try {
            $stmtP = $pdo->prepare("REPLACE INTO impostazioni (chiave, valore) VALUES (:k1, :v1)");
            $stmtP->execute(['k1' => 'privacy_email_'.$user['id'], 'v1' => $consenso_email]);
            $stmtP->execute(['k1' => 'privacy_sms_'.$user['id'], 'v1' => $consenso_sms]);
            $msg = 'Preferenze privacy aggiornate.';
        } catch (Exception $e) {
            $msg = 'Errore salvataggio preferenze.';
        }
    }
}

global $pdo;
$privacyEmail = 0; $privacySms = 0;
try {
    $stmtPr = $pdo->prepare("SELECT chiave, valore FROM impostazioni WHERE chiave IN (:k1, :k2)");
    $stmtPr->execute(['k1' => 'privacy_email_'.$user['id'], 'k2' => 'privacy_sms_'.$user['id']]);
    foreach ($stmtPr->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['chiave'] === 'privacy_email_'.$user['id']) $privacyEmail = (int)$row['valore'];
        if ($row['chiave'] === 'privacy_sms_'.$user['id']) $privacySms = (int)$row['valore'];
    }
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>
<h2>Il mio profilo</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-6">
    <div class="card mb-3">
    <div class="card-header"><strong>Dati personali</strong></div>
    <div class="card-body">
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="profilo">
        <div class="mb-2"><label class="form-label">Nome completo*</label><input name="name" class="form-control" value="<?php echo h($user['name']); ?>" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo h($user['email']); ?>" disabled></div>
        <div class="mb-2"><label class="form-label">Telefono</label><input name="phone" class="form-control" value="<?php echo h($user['phone'] ?? ''); ?>" placeholder="+39 ..."></div>
        <div class="mb-2"><label class="form-label">Codice fiscale</label><input name="fiscal_code" class="form-control" value="<?php echo h($user['fiscal_code'] ?? ''); ?>" maxlength="16" placeholder="RSSMRA80A01H501U"></div>
        <div class="mb-2">
            <label class="form-label">Ruolo</label>
            <input class="form-control" value="<?php echo h(ucfirst($user['role'])); ?>" disabled>
        </div>
        <div class="mb-2">
            <label class="form-label">Registrato il</label>
            <input class="form-control" value="<?php echo h($user['created_at']); ?>" disabled>
        </div>
        <button class="btn btn-primary">Salva modifiche</button>
    </form>
    </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card mb-3">
    <div class="card-header"><strong>Cambio password</strong></div>
    <div class="card-body">
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="password">
        <div class="mb-2"><label class="form-label">Password attuale*</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Nuova password* (min 6 car.)</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
        <div class="mb-2"><label class="form-label">Conferma nuova password*</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
        <button class="btn btn-warning">Cambia password</button>
    </form>
    </div>
    </div>

    <div class="card mb-3">
    <div class="card-header"><strong>Privacy e consensi</strong></div>
    <div class="card-body">
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="privacy">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="consenso_email" value="1" id="cEmail" <?php echo $privacyEmail ? 'checked' : ''; ?>>
            <label class="form-check-label" for="cEmail">Acconsento a ricevere comunicazioni via email</label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="consenso_sms" value="1" id="cSms" <?php echo $privacySms ? 'checked' : ''; ?>>
            <label class="form-check-label" for="cSms">Acconsento a ricevere comunicazioni via SMS</label>
        </div>
        <small class="text-muted d-block mb-2">I tuoi dati saranno trattati ai sensi del GDPR e della normativa italiana sulla privacy.</small>
        <button class="btn btn-secondary">Salva preferenze</button>
    </form>
    </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
