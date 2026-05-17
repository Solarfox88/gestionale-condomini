<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_login();

$userId = (int)$_SESSION['user']['id'];
global $pdo;

$condIds = [];
$stmt = $pdo->prepare("SELECT condominio_id FROM condomini_users WHERE user_id = :uid");
$stmt->execute(['uid' => $userId]);
$condIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$assemblee = [];
if ($condIds) {
    $ph = implode(',', array_fill(0, count($condIds), '?'));
    $stmtA = $pdo->prepare("SELECT a.*, c.nome AS condominio_nome FROM assemblee a JOIN condomini c ON c.id=a.condominio_id WHERE a.condominio_id IN ($ph) ORDER BY a.data_prima_convocazione DESC");
    $stmtA->execute($condIds);
    $assemblee = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}

$detailId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$detail = null; $odgItems = [];
if ($detailId) {
    $detail = Assemblee::find($detailId);
    if ($detail && !in_array((int)$detail['condominio_id'], array_map('intval', $condIds))) {
        $detail = null;
    }
    if ($detail) {
        $odg = $detail['ordine_giorno'] ?? '';
        $odgItems = array_filter(array_map('trim', preg_split('/\r?\n/', $odg)));
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Assemblee</h2>

<?php if ($detail): ?>
<div class="card mb-4">
<div class="card-header d-flex justify-content-between">
    <strong><?php echo h($detail['titolo']); ?></strong>
    <?php echo stato_badge($detail['stato']); ?>
</div>
<div class="card-body">
    <table class="table table-sm">
        <tr><th style="width:200px;">Condominio</th><td><?php echo h($detail['condominio_nome'] ?? ''); ?></td></tr>
        <tr><th>Prima convocazione</th><td><?php echo !empty($detail['data_prima_convocazione']) ? date('d/m/Y H:i', strtotime($detail['data_prima_convocazione'])) : '-'; ?></td></tr>
        <?php if (!empty($detail['data_seconda_convocazione'])): ?>
        <tr><th>Seconda convocazione</th><td><?php echo date('d/m/Y H:i', strtotime($detail['data_seconda_convocazione'])); ?></td></tr>
        <?php endif; ?>
        <tr><th>Luogo</th><td><?php echo h($detail['luogo'] ?? ''); ?></td></tr>
        <tr><th>Stato</th><td><?php echo stato_badge($detail['stato']); ?></td></tr>
    </table>

    <?php if ($odgItems): ?>
    <h5>Ordine del giorno</h5>
    <ol>
    <?php foreach ($odgItems as $item): ?>
        <li><?php echo h($item); ?></li>
    <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <?php if (!empty($detail['verbale'])): ?>
    <h5>Verbale</h5>
    <div class="border p-3 bg-light"><?php echo nl2br(h($detail['verbale'])); ?></div>
    <?php endif; ?>
</div>
</div>
<a href="assemblee.php" class="btn btn-secondary mb-3">Torna alla lista</a>
<?php endif; ?>

<h4><?php echo $detail ? 'Tutte le assemblee' : ''; ?></h4>
<?php if (empty($assemblee)): ?>
<div class="alert alert-info">Nessuna assemblea trovata.</div>
<?php else: ?>
<table class="table table-bordered table-striped">
<thead><tr><th>Condominio</th><th>Titolo</th><th>Data</th><th>Luogo</th><th>Stato</th><th></th></tr></thead>
<tbody>
<?php foreach ($assemblee as $a): ?>
<tr>
    <td><?php echo h($a['condominio_nome']); ?></td>
    <td><?php echo h($a['titolo']); ?></td>
    <td><?php echo !empty($a['data_prima_convocazione']) ? date('d/m/Y H:i', strtotime($a['data_prima_convocazione'])) : '-'; ?></td>
    <td><?php echo h($a['luogo'] ?? ''); ?></td>
    <td><?php echo stato_badge($a['stato']); ?></td>
    <td><a href="?id=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-outline-primary">Dettaglio</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
