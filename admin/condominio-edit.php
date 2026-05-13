<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

// Recupera l'ID del condominio
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$condominio = get_condominio($id);
if (!$condominio) {
    echo 'Condominio non trovato.';
    exit;
}

// gestione aggiornamento
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $data = [
        'nome' => trim($_POST['nome'] ?? $condominio['nome']),
        'codice_fiscale' => $_POST['codice_fiscale'] ?? null,
        'indirizzo' => $_POST['indirizzo'] ?? null,
        'comune' => $_POST['comune'] ?? null,
        'provincia' => $_POST['provincia'] ?? null,
        'cap' => $_POST['cap'] ?? null,
        'iban' => $_POST['iban'] ?? null,
        'banca' => $_POST['banca'] ?? null,
        'email' => $_POST['email'] ?? null,
        'pec' => $_POST['pec'] ?? null,
        'note' => $_POST['note'] ?? null,
        'status' => $_POST['status'] ?? 'active'
    ];
    if ($data['nome'] !== '') {
        if (update_condominio($id, $data)) {
            $msg = 'Condominio aggiornato con successo.';
            // aggiorna l'oggetto condominio
            $condominio = get_condominio($id);
        } else {
            $msg = 'Errore durante l\'aggiornamento.';
        }
    } else {
        $msg = 'Il campo nome è obbligatorio.';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Modifica Condominio</h2>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-2">
        <label class="form-label">Nome</label>
        <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($condominio['nome']); ?>" required>
    </div>
    <div class="mb-2">
        <label class="form-label">Codice Fiscale</label>
        <input type="text" class="form-control" name="codice_fiscale" value="<?php echo htmlspecialchars($condominio['codice_fiscale']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Indirizzo</label>
        <input type="text" class="form-control" name="indirizzo" value="<?php echo htmlspecialchars($condominio['indirizzo']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Comune</label>
        <input type="text" class="form-control" name="comune" value="<?php echo htmlspecialchars($condominio['comune']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Provincia</label>
        <input type="text" class="form-control" name="provincia" value="<?php echo htmlspecialchars($condominio['provincia']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">CAP</label>
        <input type="text" class="form-control" name="cap" value="<?php echo htmlspecialchars($condominio['cap']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">IBAN</label>
        <input type="text" class="form-control" name="iban" value="<?php echo htmlspecialchars($condominio['iban']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Banca</label>
        <input type="text" class="form-control" name="banca" value="<?php echo htmlspecialchars($condominio['banca']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($condominio['email']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">PEC</label>
        <input type="email" class="form-control" name="pec" value="<?php echo htmlspecialchars($condominio['pec']); ?>">
    </div>
    <div class="mb-2">
        <label class="form-label">Note</label>
        <textarea class="form-control" name="note"><?php echo htmlspecialchars($condominio['note']); ?></textarea>
    </div>
    <div class="mb-2">
        <label class="form-label">Stato</label>
        <select name="status" class="form-select">
            <option value="active" <?php echo $condominio['status'] === 'active' ? 'selected' : ''; ?>>Attivo</option>
            <option value="inactive" <?php echo $condominio['status'] === 'inactive' ? 'selected' : ''; ?>>Inattivo</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Salva</button>
    <a href="/admin/condomini.php" class="btn btn-secondary">Indietro</a>
</form>

<?php include __DIR__ . '/../includes/footer.php';
