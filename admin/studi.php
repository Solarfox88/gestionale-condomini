<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Studi.php';
require_once __DIR__ . '/../app/Ruoli.php';
require_login();
require_admin();

global $pdo;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $azione = $_POST['azione'] ?? 'crea';
    if ($azione === 'crea') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome) {
            $id = Studi::create([
                'nome' => $nome,
                'codice_fiscale' => $_POST['codice_fiscale'] ?? '',
                'partita_iva' => $_POST['partita_iva'] ?? '',
                'indirizzo' => $_POST['indirizzo'] ?? '',
                'comune' => $_POST['comune'] ?? '',
                'provincia' => $_POST['provincia'] ?? '',
                'cap' => $_POST['cap'] ?? '',
                'email' => $_POST['email'] ?? '',
                'pec' => $_POST['pec'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'nome_amministratore' => $_POST['nome_amministratore'] ?? '',
                'piano' => $_POST['piano'] ?? 'free',
                'max_condomini' => $_POST['max_condomini'] ?? 5,
                'max_unita' => $_POST['max_unita'] ?? 50,
                'max_storage_mb' => $_POST['max_storage_mb'] ?? 500,
            ]);
            if ($id) {
                audit_log('create', 'studi', $id, $nome);
                $msg = 'Studio creato con successo.';
            }
        } else {
            $msg = 'Il nome e obbligatorio.';
        }
    } elseif ($azione === 'aggiungi_utente') {
        $studioId = (int)$_POST['studio_id'];
        $userId = (int)$_POST['user_id'];
        $ruoloId = (int)$_POST['ruolo_id'];
        if (Studi::addUser($studioId, $userId, $ruoloId ?: null)) {
            audit_log('create', 'studio_users', $studioId, 'user_id=' . $userId);
            $msg = 'Utente aggiunto allo studio.';
        }
    } elseif ($azione === 'rimuovi_utente') {
        $studioId = (int)$_POST['studio_id'];
        $userId = (int)$_POST['user_id'];
        if (Studi::removeUser($studioId, $userId)) {
            audit_log('delete', 'studio_users', $studioId, 'user_id=' . $userId);
            $msg = 'Utente rimosso dallo studio.';
        }
    } elseif ($azione === 'aggiorna_ruolo') {
        $studioId = (int)$_POST['studio_id'];
        $userId = (int)$_POST['user_id'];
        $ruoloId = (int)$_POST['ruolo_id'];
        if (Studi::updateUserRole($studioId, $userId, $ruoloId)) {
            audit_log('update', 'studio_users', $studioId, 'user_id=' . $userId . ' ruolo_id=' . $ruoloId);
            $msg = 'Ruolo aggiornato.';
        }
    }
}

$studi = Studi::all();
$ruoli = Ruoli::all();
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Studi / Tenant</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row">
<div class="col-md-8">
<h4>Studi registrati</h4>
<?php if (!$studi): ?>
<p class="text-muted">Nessuno studio configurato.</p>
<?php else: ?>
<?php foreach ($studi as $s): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><?php echo h($s['nome']); ?></strong>
        <span class="badge bg-info"><?php echo h($s['piano']); ?></span>
    </div>
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-md-6">
                <small class="text-muted">Amministratore:</small> <?php echo h($s['nome_amministratore']); ?><br>
                <small class="text-muted">Email:</small> <?php echo h($s['email']); ?><br>
                <small class="text-muted">P.IVA:</small> <?php echo h($s['partita_iva']); ?>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Limiti piano:</small>
                Max <?php echo (int)$s['max_condomini']; ?> condomini,
                <?php echo (int)$s['max_unita']; ?> unita,
                <?php echo (int)$s['max_storage_mb']; ?> MB storage<br>
                <small class="text-muted">Utilizzo:</small>
                <?php echo Studi::getCondominiCount((int)$s['id']); ?> condomini,
                <?php echo Studi::getUnitaCount((int)$s['id']); ?> unita
            </div>
        </div>
        <h6>Utenti dello studio</h6>
        <?php $studioUsers = Studi::getUsers((int)$s['id']); ?>
        <?php if ($studioUsers): ?>
        <table class="table table-sm">
            <thead><tr><th>Nome</th><th>Email</th><th>Ruolo</th><th>Azioni</th></tr></thead>
            <tbody>
            <?php foreach ($studioUsers as $su): ?>
            <tr>
                <td><?php echo h($su['name']); ?></td>
                <td><?php echo h($su['email']); ?></td>
                <td>
                    <form method="post" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="azione" value="aggiorna_ruolo">
                        <input type="hidden" name="studio_id" value="<?php echo (int)$s['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo (int)$su['user_id']; ?>">
                        <select name="ruolo_id" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                            <?php foreach ($ruoli as $r): ?>
                            <option value="<?php echo (int)$r['id']; ?>" <?php echo (int)($su['ruolo_id'] ?? 0)===(int)$r['id']?'selected':''; ?>><?php echo h($r['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="post" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="azione" value="rimuovi_utente">
                        <input type="hidden" name="studio_id" value="<?php echo (int)$s['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo (int)$su['user_id']; ?>">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Rimuovere?')">Rimuovi</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="text-muted small">Nessun utente assegnato.</p><?php endif; ?>

        <form method="post" class="row g-2 align-items-end">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="azione" value="aggiungi_utente">
            <input type="hidden" name="studio_id" value="<?php echo (int)$s['id']; ?>">
            <div class="col-auto">
                <select name="user_id" class="form-select form-select-sm" required>
                    <option value="">Utente...</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo (int)$u['id']; ?>"><?php echo h($u['name'] . ' (' . $u['email'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="ruolo_id" class="form-select form-select-sm">
                    <?php foreach ($ruoli as $r): ?>
                    <option value="<?php echo (int)$r['id']; ?>"><?php echo h($r['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Aggiungi</button></div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<div class="col-md-4">
<h4>Nuovo studio</h4>
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="azione" value="crea">
    <div class="mb-2"><label class="form-label">Nome studio*</label><input type="text" name="nome" class="form-control form-control-sm" required></div>
    <div class="mb-2"><label class="form-label">Amministratore</label><input type="text" name="nome_amministratore" class="form-control form-control-sm"></div>
    <div class="mb-2"><label class="form-label">P.IVA</label><input type="text" name="partita_iva" class="form-control form-control-sm"></div>
    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-sm"></div>
    <div class="mb-2"><label class="form-label">PEC</label><input type="text" name="pec" class="form-control form-control-sm"></div>
    <div class="mb-2"><label class="form-label">Telefono</label><input type="text" name="telefono" class="form-control form-control-sm"></div>
    <div class="mb-2"><label class="form-label">Indirizzo</label><input type="text" name="indirizzo" class="form-control form-control-sm"></div>
    <div class="row mb-2">
        <div class="col-6"><label class="form-label">Comune</label><input type="text" name="comune" class="form-control form-control-sm"></div>
        <div class="col-3"><label class="form-label">Prov</label><input type="text" name="provincia" class="form-control form-control-sm" maxlength="2"></div>
        <div class="col-3"><label class="form-label">CAP</label><input type="text" name="cap" class="form-control form-control-sm" maxlength="5"></div>
    </div>
    <div class="mb-2"><label class="form-label">C.F.</label><input type="text" name="codice_fiscale" class="form-control form-control-sm"></div>
    <hr>
    <h6>Piano e limiti</h6>
    <div class="mb-2"><label class="form-label">Piano</label>
        <select name="piano" class="form-select form-select-sm">
            <option value="free">Free</option>
            <option value="base">Base</option>
            <option value="pro">Pro</option>
            <option value="enterprise">Enterprise</option>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Max condomini</label><input type="number" name="max_condomini" class="form-control form-control-sm" value="5"></div>
    <div class="mb-2"><label class="form-label">Max unita</label><input type="number" name="max_unita" class="form-control form-control-sm" value="50"></div>
    <div class="mb-2"><label class="form-label">Max storage MB</label><input type="number" name="max_storage_mb" class="form-control form-control-sm" value="500"></div>
    <button class="btn btn-primary btn-sm">Crea studio</button>
</form>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
