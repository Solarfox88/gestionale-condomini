<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Auth.php';

// Se già loggato, reindirizza
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . url('/admin/dashboard.php'));
    } else {
        header('Location: ' . url('/area-condomino/dashboard.php'));
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login_user($email, $password)) {
        // login ok
        if (is_admin()) {
            header('Location: ' . url('/admin/dashboard.php'));
        } else {
            header('Location: ' . url('/area-condomino/dashboard.php'));
        }
        exit;
    } else {
        $error = 'Credenziali non valide o account non attivo.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<h2>Login</h2>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" novalidate>
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Accedi</button>
</form>
<?php include __DIR__ . '/includes/footer.php';
