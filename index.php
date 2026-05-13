<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Auth.php';
// Se l'utente è loggato reindirizza alla dashboard appropriata
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . url('/admin/dashboard.php'));
        exit;
    } else {
        header('Location: ' . url('/area-condomino/dashboard.php'));
        exit;
    }
}

// Homepage pubblica
include __DIR__ . '/includes/header.php';
?>
<div class="text-center">
    <h1>Benvenuto nel Gestionale Condomini</h1>
    <p>Accedi per gestire i tuoi condomini, documenti, rate e comunicazioni.</p>
    <a href="<?php echo url('/login.php'); ?>" class="btn btn-primary me-2">Login</a>
    <a href="<?php echo url('/register.php'); ?>" class="btn btn-secondary">Registrati</a>
</div>
<?php include __DIR__ . '/includes/footer.php';
