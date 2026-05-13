<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Persone.php';
require_login();
require_admin();

$msg = $_GET['msg'] ?? '';
if ($msg === 'eliminata') $msg = 'Persona eliminata.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    if ($nome !== '' && $cognome !== '') {
        if (create_persona($_POST)) {
            $msg = 'Persona creata con successo.';
            audit_log('create', 'persone', null, $cognome . ' ' . $nome);
        } else {
            $msg = 'Errore nella creazione della persona.';
        }
    } else {
        $msg = 'Nome e cognome sono obbligatori.';
    }
}

$search = trim($_GET['q'] ?? '');
$persone = get_persone();
if ($search !== '') {
    $persone = array_filter($persone, function($p) use ($search) {
        $hay = strtolower($p['nome'].' '.$p['cognome'].' '.($p['codice_fiscale'] ?? '').' '.($p['email'] ?? '').' '.($p['ragione_sociale'] ?? ''));
        return str_contains($hay, strtolower($search));
    });
}
include __DIR__ . '/../includes/header.php';
?>
<h2>Anagrafiche Persone</h2>
<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><input name="q" class="form-control" placeholder="Cerca per nome, cognome, CF, email..." value="<?php echo h($search); ?>"></div>
        <div class="col-auto"><button class="btn btn-primary">Cerca</button></div>
        <?php if ($search): ?><div class="col-auto"><a href="persone.php" class="btn btn-secondary">Reset</a></div><?php endif; ?>
    </div>
</form>

<div class="row">
    <div class="col-md-8">
        <h4>Elenco Persone (<?php echo count($persone); ?>)</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>ID</th><th>Cognome</th><th>Nome</th><th>Tipo</th><th>Codice Fiscale</th><th>Email</th><th>Azioni</th></tr>
            </thead>
            <tbody>
                <?php foreach ($persone as $p): ?>
                <tr>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><?php echo h($p['cognome']); ?></td>
                    <td><?php echo h($p['nome']); ?></td>
                    <td><?php echo h($p['tipo']); ?></td>
                    <td><?php echo h($p['codice_fiscale'] ?? ''); ?></td>
                    <td><?php echo h($p['email'] ?? ''); ?></td>
                    <td><a href="persona-detail.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-primary">Dettaglio</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        <h4>Aggiungi Persona</h4>
        <form method="post">
            <?php echo csrf_field(); ?>
            <div class="mb-2"><label class="form-label">Nome*</label><input type="text" class="form-control" name="nome" required></div>
            <div class="mb-2"><label class="form-label">Cognome*</label><input type="text" class="form-control" name="cognome" required></div>
            <div class="mb-2"><label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="persona">Persona</option>
                    <option value="azienda">Azienda</option>
                    <option value="fornitore">Fornitore</option>
                </select>
            </div>
            <div class="mb-2"><label class="form-label">Ragione Sociale</label><input type="text" class="form-control" name="ragione_sociale"></div>
            <div class="mb-2"><label class="form-label">Codice Fiscale</label><input type="text" class="form-control" name="codice_fiscale"></div>
            <div class="mb-2"><label class="form-label">Partita IVA</label><input type="text" class="form-control" name="partita_iva"></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
            <div class="mb-2"><label class="form-label">PEC</label><input type="email" class="form-control" name="pec"></div>
            <div class="mb-2"><label class="form-label">Telefono</label><input type="text" class="form-control" name="telefono"></div>
            <div class="mb-2"><label class="form-label">Indirizzo</label><input type="text" class="form-control" name="indirizzo"></div>
            <div class="mb-2"><label class="form-label">Note</label><textarea class="form-control" name="note"></textarea></div>
            <button type="submit" class="btn btn-primary">Crea</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
