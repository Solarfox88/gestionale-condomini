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
            $msg = 'Profilo aggiornato.';
            $user = get_user((int)$user['id']);
            $_SESSION['user']['name'] = $user['name'];
        } else {
            $msg = 'Errore aggiornamento.';
        }
    } elseif ($azione === 'password') {
        $result = change_password((int)$user['id'], $_POST['current_password'], $_POST['new_password']);
        $msg = $result ? 'Password modificata con successo.' : 'Password attuale errata.';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Il mio profilo</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-6">
    <h4>Dati personali</h4>
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="profilo">
        <div class="mb-2"><label class="form-label">Nome completo</label><input name="name" class="form-control" value="<?php echo h($user['name']); ?>" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo h($user['email']); ?>" disabled></div>
        <button class="btn btn-primary">Salva</button>
    </form>
</div>
<div class="col-md-6">
    <h4>Cambio password</h4>
    <form method="post">
        <?php echo csrf_field(); ?><input type="hidden" name="azione" value="password">
        <div class="mb-2"><label class="form-label">Password attuale*</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Nuova password* (min 6 car.)</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
        <button class="btn btn-warning">Cambia password</button>
    </form>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
