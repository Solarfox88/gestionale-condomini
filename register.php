<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Auth.php';

// Se già loggato, reindirizza
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /area-condomino/dashboard.php');
    }
    exit;
}

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $fiscal_code = trim($_POST['fiscal_code'] ?? '');
    // Validazioni di base
    if ($name === '') $errors[] = 'Il nome è obbligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
    if (strlen($password) < 6) $errors[] = 'La password deve contenere almeno 6 caratteri.';
    if ($password !== $password_confirm) $errors[] = 'Le password non coincidono.';

    if (empty($errors)) {
        $userId = register_user([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'fiscal_code' => $fiscal_code,
        ]);
        if ($userId) {
            $success = true;
        } else {
            $errors[] = 'Registrazione fallita. L\'email potrebbe essere già utilizzata.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h2>Registrazione Condòmino</h2>
<?php if ($success): ?>
    <div class="alert alert-success">Registrazione avvenuta con successo! Attendere l&apos;approvazione dell&apos;amministratore.</div>
<?php else: ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" novalidate>
        <?php echo csrf_field(); ?>
        <div class="mb-3">
            <label for="name" class="form-label">Nome completo</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Telefono</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="fiscal_code" class="form-label">Codice fiscale</label>
            <input type="text" class="form-control" id="fiscal_code" name="fiscal_code" value="<?php echo htmlspecialchars($_POST['fiscal_code'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="password_confirm" class="form-label">Conferma password</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
        </div>
        <button type="submit" class="btn btn-primary">Registrati</button>
    </form>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php';
