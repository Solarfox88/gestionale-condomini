<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();

$userId = (int)$_SESSION['user']['id'];
global $pdo;

$stmt = $pdo->prepare("SELECT ui.*, c.nome AS condominio_nome, c.indirizzo AS condominio_indirizzo,
                        up.ruolo, up.percentuale, up.data_inizio, up.data_fine,
                        p.nome AS persona_nome, p.cognome AS persona_cognome
                        FROM condomini_users cu
                        JOIN condomini c ON c.id = cu.condominio_id
                        JOIN unita_immobiliari ui ON ui.condominio_id = c.id
                        JOIN unita_persone up ON up.unita_id = ui.id
                        JOIN persone p ON p.id = up.persona_id
                        WHERE cu.user_id = :uid AND (up.data_fine IS NULL OR up.data_fine >= CURDATE())
                        ORDER BY c.nome, ui.scala, ui.piano, ui.interno");
$stmt->execute(['uid' => $userId]);
$unita = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h2>Le mie unita</h2>
<p class="text-muted">Unita immobiliari collegate al tuo profilo con ruoli e dati di dettaglio.</p>

<?php if (empty($unita)): ?>
<div class="alert alert-info">Nessuna unita associata. Contatta l'amministratore.</div>
<?php else: ?>
<div class="row">
<?php foreach ($unita as $u): ?>
<div class="col-md-6 mb-3">
    <div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong><?php echo h($u['condominio_nome']); ?> — Scala <?php echo h($u['scala']); ?> Piano <?php echo h($u['piano']); ?> Int. <?php echo h($u['interno']); ?></strong>
        <span class="badge bg-info"><?php echo h(ucfirst($u['ruolo'])); ?></span>
    </div>
    <div class="card-body">
        <table class="table table-sm mb-0">
            <tr><th style="width:140px;">Condominio</th><td><?php echo h($u['condominio_nome']); ?></td></tr>
            <tr><th>Indirizzo</th><td><?php echo h($u['condominio_indirizzo'] ?? ''); ?></td></tr>
            <tr><th>Scala / Piano</th><td><?php echo h($u['scala']); ?> / <?php echo h($u['piano']); ?></td></tr>
            <tr><th>Interno</th><td><?php echo h($u['interno']); ?></td></tr>
            <tr><th>MQ</th><td><?php echo $u['mq'] ? number_format((float)$u['mq'], 2, ',', '.') : '-'; ?></td></tr>
            <tr><th>Millesimi propr.</th><td><?php echo number_format((float)($u['millesimi_proprieta'] ?? 0), 2, ',', '.'); ?></td></tr>
            <tr><th>Intestatario</th><td><?php echo h(trim($u['persona_cognome'] . ' ' . $u['persona_nome'])); ?></td></tr>
            <tr><th>Ruolo</th><td><?php echo h(ucfirst($u['ruolo'])); ?> (<?php echo number_format((float)($u['percentuale'] ?? 0), 0); ?>%)</td></tr>
            <tr><th>Dal</th><td><?php echo $u['data_inizio'] ? date('d/m/Y', strtotime($u['data_inizio'])) : '-'; ?></td></tr>
            <?php if ($u['data_fine']): ?><tr><th>Al</th><td><?php echo date('d/m/Y', strtotime($u['data_fine'])); ?></td></tr><?php endif; ?>
        </table>
    </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
