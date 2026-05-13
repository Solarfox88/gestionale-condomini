<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Documenti.php';
require_once __DIR__ . '/../app/Tickets.php';
require_login();

$userId = (int)$_SESSION['user']['id'];

global $pdo;
$stmt = $pdo->prepare("SELECT c.* FROM condomini c JOIN condomini_users cu ON cu.condominio_id = c.id WHERE cu.user_id = :uid ORDER BY c.nome");
$stmt->execute(['uid' => $userId]);
$condomini = $stmt->fetchAll(PDO::FETCH_ASSOC);

$documenti = [];
$myTickets = [];
foreach ($condomini as $c) {
    $docs = get_documenti((int)$c['id']);
    foreach ($docs as $d) {
        if ($d['visibility'] === 'pubblico' || $d['visibility'] === 'condominio') {
            $documenti[] = $d;
        }
    }
}

$stmtT = $pdo->prepare("SELECT t.*, c.nome AS condominio_nome FROM ticket t JOIN condomini c ON c.id = t.condominio_id WHERE t.aperto_da = :uid ORDER BY t.updated_at DESC LIMIT 20");
$stmtT->execute(['uid' => $userId]);
$myTickets = $stmtT->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h2>Area Condomino</h2>
<p>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong></p>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title"><?php echo count($condomini); ?></h5>
                <p class="card-text">Condomini associati</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5 class="card-title"><?php echo count($documenti); ?></h5>
                <p class="card-text">Documenti disponibili</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h5 class="card-title"><?php echo count($myTickets); ?></h5>
                <p class="card-text">I miei ticket</p>
            </div>
        </div>
    </div>
</div>

<?php if (count($condomini) > 0): ?>
<h4>I miei condomini</h4>
<table class="table table-bordered table-striped mb-4">
    <thead><tr><th>Nome</th><th>Indirizzo</th><th>Comune</th></tr></thead>
    <tbody>
        <?php foreach ($condomini as $c): ?>
            <tr>
                <td><?php echo htmlspecialchars($c['nome']); ?></td>
                <td><?php echo htmlspecialchars($c['indirizzo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($c['comune'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="alert alert-info">Non sei ancora associato a nessun condominio. Contatta l'amministratore.</div>
<?php endif; ?>

<?php if (count($documenti) > 0): ?>
<h4>Documenti disponibili</h4>
<table class="table table-bordered table-striped mb-4">
    <thead><tr><th>Titolo</th><th>Categoria</th><th>Data</th><th>Download</th></tr></thead>
    <tbody>
        <?php foreach ($documenti as $d): ?>
            <tr>
                <td><?php echo htmlspecialchars($d['titolo']); ?></td>
                <td><?php echo htmlspecialchars($d['categoria']); ?></td>
                <td><?php echo htmlspecialchars($d['created_at']); ?></td>
                <td><a href="/documenti_download.php?id=<?php echo (int)$d['id']; ?>" class="btn btn-sm btn-secondary">Scarica</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if (count($myTickets) > 0): ?>
<h4>I miei ticket</h4>
<table class="table table-bordered table-striped mb-4">
    <thead><tr><th>ID</th><th>Condominio</th><th>Titolo</th><th>Stato</th><th>Aggiornato</th></tr></thead>
    <tbody>
        <?php foreach ($myTickets as $t): ?>
            <tr>
                <td><?php echo (int)$t['id']; ?></td>
                <td><?php echo htmlspecialchars($t['condominio_nome']); ?></td>
                <td><?php echo htmlspecialchars($t['titolo']); ?></td>
                <td><?php echo htmlspecialchars($t['stato']); ?></td>
                <td><?php echo htmlspecialchars($t['updated_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h4>Apri un nuovo ticket</h4>
<?php if (count($condomini) > 0): ?>
<form method="post" action="/area-condomino/ticket-crea.php">
    <?php echo csrf_field(); ?>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">Condominio*</label>
            <select name="condominio_id" class="form-select" required>
                <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Titolo*</label>
            <input type="text" class="form-control" name="titolo" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Priorita</label>
            <select name="priorita" class="form-select">
                <option value="bassa">Bassa</option>
                <option value="media" selected>Media</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
            </select>
        </div>
        <div class="col-md-12">
            <label class="form-label">Descrizione*</label>
            <textarea class="form-control" name="descrizione" rows="3" required></textarea>
        </div>
        <div class="col-md-12"><button class="btn btn-primary">Invia Ticket</button></div>
    </div>
</form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php';
