<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Comunicazioni.php';
require_login();

$userId = (int)$_SESSION['user']['id'];
global $pdo;

$stmt = $pdo->prepare("SELECT DISTINCT c.id, c.oggetto, c.corpo, c.tipo, c.inviata_at, co.nome AS condominio_nome, cd.stato AS dest_stato, cd.letto_at
                        FROM comunicazioni_destinatari cd
                        JOIN comunicazioni c ON c.id = cd.comunicazione_id
                        JOIN condomini co ON co.id = c.condominio_id
                        JOIN condomini_users cu ON cu.condominio_id = c.condominio_id
                        WHERE cu.user_id = :uid AND c.stato = 'inviata'
                        ORDER BY c.inviata_at DESC");
$stmt->execute(['uid' => $userId]);
$comunicazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as read
if (!empty($_GET['read'])) {
    $cid = (int)$_GET['read'];
    $pdo->prepare("UPDATE comunicazioni_destinatari cd
                    JOIN comunicazioni c ON c.id = cd.comunicazione_id
                    JOIN condomini_users cu ON cu.condominio_id = c.condominio_id
                    SET cd.letto_at = NOW(), cd.stato = 'letto'
                    WHERE cd.comunicazione_id = :cid AND cu.user_id = :uid AND cd.letto_at IS NULL")
        ->execute(['cid' => $cid, 'uid' => $userId]);
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Le mie comunicazioni</h2>

<?php if (!empty($_GET['read'])): ?>
<?php
$detailStmt = $pdo->prepare("SELECT c.*, co.nome AS condominio_nome FROM comunicazioni c JOIN condomini co ON co.id = c.condominio_id WHERE c.id = :id");
$detailStmt->execute(['id' => (int)$_GET['read']]);
$detail = $detailStmt->fetch(PDO::FETCH_ASSOC);
if ($detail):
?>
<div class="card mb-4">
<div class="card-header">
    <strong><?php echo h($detail['oggetto']); ?></strong>
    <span class="badge bg-secondary"><?php echo h(ucfirst($detail['tipo'])); ?></span>
</div>
<div class="card-body">
    <p class="text-muted mb-2"><small>Da: <?php echo h($detail['condominio_nome']); ?> — <?php echo $detail['inviata_at'] ? date('d/m/Y H:i', strtotime($detail['inviata_at'])) : ''; ?></small></p>
    <div><?php echo nl2br(h($detail['corpo'])); ?></div>
</div>
</div>
<a href="comunicazioni.php" class="btn btn-secondary mb-3">Torna alla lista</a>
<?php endif; ?>
<?php endif; ?>

<table class="table table-striped">
<thead><tr><th>Data</th><th>Condominio</th><th>Oggetto</th><th>Tipo</th><th>Stato</th><th>Azione</th></tr></thead>
<tbody>
<?php if (empty($comunicazioni)): ?>
<tr><td colspan="6" class="text-center text-muted">Nessuna comunicazione ricevuta.</td></tr>
<?php endif; ?>
<?php foreach ($comunicazioni as $c): ?>
<tr class="<?php echo empty($c['letto_at']) ? 'fw-bold' : ''; ?>">
    <td><?php echo $c['inviata_at'] ? date('d/m/Y', strtotime($c['inviata_at'])) : ''; ?></td>
    <td><?php echo h($c['condominio_nome']); ?></td>
    <td><?php echo h($c['oggetto']); ?></td>
    <td><span class="badge bg-secondary"><?php echo h(ucfirst($c['tipo'])); ?></span></td>
    <td><?php echo empty($c['letto_at']) ? '<span class="badge bg-primary">Nuova</span>' : '<span class="badge bg-light text-dark">Letta</span>'; ?></td>
    <td><a href="?read=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-outline-primary">Leggi</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
