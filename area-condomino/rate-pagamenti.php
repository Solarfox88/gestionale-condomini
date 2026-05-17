<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Rate.php';
require_login();

$userId = (int)$_SESSION['user']['id'];
global $pdo;

Rate::aggiornaScadute();

$condIds = [];
$stmt = $pdo->prepare("SELECT condominio_id FROM condomini_users WHERE user_id = :uid");
$stmt->execute(['uid' => $userId]);
$condIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$filtro = $_GET['filtro'] ?? 'aperte';
$myRate = []; $myPagamenti = []; $totDovuto = 0; $totPagato = 0;

if ($condIds) {
    $ph = implode(',', array_fill(0, count($condIds), '?'));

    if ($filtro === 'pagate') {
        $stmtR = $pdo->prepare("SELECT r.*, c.nome AS condominio_nome, c.iban AS condominio_iban, ui.scala, ui.piano, ui.interno
                                FROM rate r JOIN condomini c ON c.id=r.condominio_id JOIN unita_immobiliari ui ON ui.id=r.unita_id
                                WHERE r.condominio_id IN ($ph) AND r.stato='pagata' ORDER BY r.scadenza DESC");
    } else {
        $stmtR = $pdo->prepare("SELECT r.*, c.nome AS condominio_nome, c.iban AS condominio_iban, ui.scala, ui.piano, ui.interno
                                FROM rate r JOIN condomini c ON c.id=r.condominio_id JOIN unita_immobiliari ui ON ui.id=r.unita_id
                                WHERE r.condominio_id IN ($ph) AND r.stato IN ('da_pagare','parziale','scaduta') ORDER BY r.scadenza ASC");
    }
    $stmtR->execute($condIds);
    $myRate = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    foreach ($myRate as &$r) {
        $stmtP = $pdo->prepare("SELECT SUM(importo) FROM pagamenti WHERE rata_id = :rid");
        $stmtP->execute(['rid' => $r['id']]);
        $r['pagato'] = (float)$stmtP->fetchColumn();
        $r['residuo'] = (float)$r['importo'] - $r['pagato'];
        $totDovuto += (float)$r['importo'];
        $totPagato += $r['pagato'];
    }
    unset($r);

    $stmtPag = $pdo->prepare("SELECT pg.*, r.descrizione AS rata_desc, c.nome AS condominio_nome
                              FROM pagamenti pg JOIN rate r ON r.id=pg.rata_id JOIN condomini c ON c.id=r.condominio_id
                              WHERE r.condominio_id IN ($ph) ORDER BY pg.data_pagamento DESC LIMIT 50");
    $stmtPag->execute($condIds);
    $myPagamenti = $stmtPag->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Rate e pagamenti</h2>

<div class="row mb-4">
    <div class="col-md-3"><div class="card text-bg-warning"><div class="card-body"><h5>&euro; <?php echo format_euro($totDovuto); ?></h5><p>Totale dovuto</p></div></div></div>
    <div class="col-md-3"><div class="card text-bg-success"><div class="card-body"><h5>&euro; <?php echo format_euro($totPagato); ?></h5><p>Totale pagato</p></div></div></div>
    <div class="col-md-3"><div class="card text-bg-danger"><div class="card-body"><h5>&euro; <?php echo format_euro($totDovuto - $totPagato); ?></h5><p>Residuo</p></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h5><?php echo count($myRate); ?></h5><p>Rate <?php echo $filtro === 'pagate' ? 'pagate' : 'aperte'; ?></p></div></div></div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?php echo $filtro !== 'pagate' ? 'active' : ''; ?>" href="?filtro=aperte">Rate aperte</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $filtro === 'pagate' ? 'active' : ''; ?>" href="?filtro=pagate">Rate pagate</a></li>
</ul>

<?php if (empty($myRate)): ?>
<div class="alert alert-success">Nessuna rata <?php echo $filtro === 'pagate' ? 'pagata' : 'da pagare'; ?>.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><th>Condominio</th><th>Unita</th><th>Descrizione</th><th class="text-end">Importo</th><th class="text-end">Pagato</th><th class="text-end">Residuo</th><th>Scadenza</th><th>Stato</th><th></th></tr></thead>
<tbody>
<?php foreach ($myRate as $r): ?>
<tr class="<?php echo $r['residuo'] > 0 && $filtro !== 'pagate' ? 'table-warning' : ''; ?>">
    <td><?php echo h($r['condominio_nome']); ?></td>
    <td><?php echo h(trim($r['scala'].' '.$r['piano'].' '.$r['interno'])); ?></td>
    <td><?php echo h($r['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$r['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($r['pagato']); ?></td>
    <td class="text-end fw-bold">&euro; <?php echo format_euro($r['residuo']); ?></td>
    <td><?php echo date('d/m/Y', strtotime($r['scadenza'])); ?></td>
    <td><?php echo stato_badge($r['stato']); ?></td>
    <td>
        <?php if ($r['residuo'] > 0 && !empty($r['condominio_iban'])): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bonificoModal<?php echo (int)$r['id']; ?>">Bonifico</button>
        <!-- Modal -->
        <div class="modal fade" id="bonificoModal<?php echo (int)$r['id']; ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Istruzioni bonifico</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p><strong>Beneficiario:</strong> <?php echo h($r['condominio_nome']); ?></p>
                <p><strong>IBAN:</strong> <code><?php echo h($r['condominio_iban']); ?></code></p>
                <p><strong>Importo:</strong> &euro; <?php echo format_euro($r['residuo']); ?></p>
                <p><strong>Causale suggerita:</strong><br><code>Rata <?php echo h($r['descrizione']); ?> - Unita <?php echo h(trim($r['scala'].' '.$r['piano'].' '.$r['interno'])); ?></code></p>
            </div>
        </div></div>
        </div>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<h4 class="mt-4">Storico pagamenti</h4>
<?php if (empty($myPagamenti)): ?>
<div class="alert alert-secondary">Nessun pagamento registrato.</div>
<?php else: ?>
<table class="table table-bordered table-sm">
<thead><tr><th>Data</th><th>Condominio</th><th>Rata</th><th class="text-end">Importo</th><th>Metodo</th><th>Riferimento</th></tr></thead>
<tbody>
<?php foreach ($myPagamenti as $p): ?>
<tr>
    <td><?php echo date('d/m/Y', strtotime($p['data_pagamento'])); ?></td>
    <td><?php echo h($p['condominio_nome']); ?></td>
    <td><?php echo h($p['rata_desc']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$p['importo']); ?></td>
    <td><?php echo h($p['metodo'] ?? ''); ?></td>
    <td><?php echo h($p['riferimento'] ?? ''); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
