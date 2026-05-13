<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

// gestione form creazione condominio
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $nome = trim($_POST['nome'] ?? '');
    if ($nome !== '') {
        $data = [
            'nome' => $nome,
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
            'status' => 'active',
        ];
        if (create_condominio($data)) {
            $msg = 'Condominio creato con successo.';
        } else {
            $msg = 'Errore nella creazione del condominio.';
        }
    } else {
        $msg = 'Il campo nome è obbligatorio.';
    }
}

$condomini = get_condomini();

include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Condomini</h2>
<?php if ($msg): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <h4>Elenco Condomini</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Comune</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($condomini as $cond): ?>
                    <tr>
                        <td><?php echo (int)$cond['id']; ?></td>
                        <td><?php echo htmlspecialchars($cond['nome']); ?></td>
                        <td><?php echo htmlspecialchars($cond['comune']); ?></td>
                        <td>
                            <a href="<?php echo url('/admin/condominio-detail.php?id=' . (int)$cond['id']); ?>" class="btn btn-sm btn-primary">Dettaglio</a>
                            <a href="<?php echo url('/admin/condominio-edit.php?id=' . (int)$cond['id']); ?>" class="btn btn-sm btn-secondary">Modifica</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <h4>Crea nuovo condominio</h4>
        <form method="post">
            <?php echo csrf_field(); ?>
            <div class="mb-2">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="nome" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Codice Fiscale</label>
                <input type="text" class="form-control" name="codice_fiscale">
            </div>
            <div class="mb-2">
                <label class="form-label">Indirizzo</label>
                <input type="text" class="form-control" name="indirizzo">
            </div>
            <div class="mb-2">
                <label class="form-label">Comune</label>
                <input type="text" class="form-control" name="comune">
            </div>
            <div class="mb-2">
                <label class="form-label">Provincia</label>
                <input type="text" class="form-control" name="provincia">
            </div>
            <div class="mb-2">
                <label class="form-label">CAP</label>
                <input type="text" class="form-control" name="cap">
            </div>
            <div class="mb-2">
                <label class="form-label">IBAN</label>
                <input type="text" class="form-control" name="iban">
            </div>
            <div class="mb-2">
                <label class="form-label">Banca</label>
                <input type="text" class="form-control" name="banca">
            </div>
            <div class="mb-2">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email">
            </div>
            <div class="mb-2">
                <label class="form-label">PEC</label>
                <input type="email" class="form-control" name="pec">
            </div>
            <div class="mb-2">
                <label class="form-label">Note</label>
                <textarea class="form-control" name="note"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Crea</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php';
