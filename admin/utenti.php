<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Users.php';
require_once __DIR__ . '/../app/Helpers.php';
require_login();
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'condomino';
    $status = $_POST['status'] ?? 'pending';
    if ($userId > 0) {
        if (update_user_role_status($userId, $role, $status)) {
            audit_log('update', 'users', $userId, 'role=' . $role . ' status=' . $status);
            $msg = 'Utente aggiornato con successo.';
        } else {
            $msg = 'Errore nell\'aggiornamento dell\'utente.';
        }
    }
}

$statusFilter = $_GET['status'] ?? null;
$users = get_users($statusFilter);
include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Utenti</h2>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Filtro stato</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Tutti</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>In attesa</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Attivi</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inattivi</option>
            </select>
        </div>
    </div>
</form>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Telefono</th>
            <th>Ruolo</th>
            <th>Stato</th>
            <th>Registrato il</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo (int)$u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['name']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($u['role']); ?></td>
                <td>
                    <?php if ($u['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">In attesa</span>
                    <?php elseif ($u['status'] === 'active'): ?>
                        <span class="badge bg-success">Attivo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inattivo</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                <td>
                    <form method="post" class="d-flex gap-1 align-items-center">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                        <select name="role" class="form-select form-select-sm" style="width:auto">
                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="condomino" <?php echo $u['role'] === 'condomino' ? 'selected' : ''; ?>>Condomino</option>
                            <option value="fornitore" <?php echo $u['role'] === 'fornitore' ? 'selected' : ''; ?>>Fornitore</option>
                        </select>
                        <select name="status" class="form-select form-select-sm" style="width:auto">
                            <option value="active" <?php echo $u['status'] === 'active' ? 'selected' : ''; ?>>Attivo</option>
                            <option value="pending" <?php echo $u['status'] === 'pending' ? 'selected' : ''; ?>>In attesa</option>
                            <option value="inactive" <?php echo $u['status'] === 'inactive' ? 'selected' : ''; ?>>Inattivo</option>
                        </select>
                        <button class="btn btn-sm btn-primary">Salva</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php';
