<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Rate.php';
require_login();
require_admin();

Rate::aggiornaScadute();

$unitaId = isset($_GET['unita_id']) ? (int)$_GET['unita_id'] : 0;
if ($unitaId <= 0) { echo 'Specificare unita_id.'; exit; }

global $pdo;
$stmtU = $pdo->prepare("SELECT ui.*, c.nome AS cond_nome, c.id AS cond_id
    FROM unita_immobiliari ui JOIN condomini c ON c.id = ui.condominio_id WHERE ui.id = :id");
$stmtU->execute(['id' => $unitaId]);
$unita = $stmtU->fetch(PDO::FETCH_ASSOC);
if (!$unita) { echo 'Unita non trovata.'; exit; }
$cond = get_condominio((int)$unita['cond_id']);

// Persona proprietaria
$stmtP = $pdo->prepare("SELECT p.nome, p.cognome, p.ragione_sociale, up.ruolo, up.percentuale
    FROM persone p JOIN unita_persone up ON up.persona_id = p.id
    WHERE up.unita_id = :uid ORDER BY up.ruolo, p.cognome");
$stmtP->execute(['uid' => $unitaId]);
$persone = $stmtP->fetchAll(PDO::FETCH_ASSOC);

// Rate
$stmtR = $pdo->prepare("SELECT r.*, COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id = r.id), 0) AS pagato
    FROM rate r WHERE r.unita_id = :uid ORDER BY r.scadenza DESC");
$stmtR->execute(['uid' => $unitaId]);
$rate = $stmtR->fetchAll(PDO::FETCH_ASSOC);

$totaleDovuto = 0; $totalePagato = 0;
foreach ($rate as $r) { $totaleDovuto += (float)$r['importo']; $totalePagato += (float)$r['pagato']; }

// Pagamenti dettaglio
$stmtPg = $pdo->prepare("SELECT pg.*, r.descrizione AS rata_desc
    FROM pagamenti pg JOIN rate r ON r.id = pg.rata_id
    WHERE r.unita_id = :uid ORDER BY pg.data_pagamento DESC");
$stmtPg->execute(['uid' => $unitaId]);
$pagamenti = $stmtPg->fetchAll(PDO::FETCH_ASSOC);

$printTitle = 'Situazione Contabile';
$printSubtitle = 'Unita: Sc.' . h($unita['scala'] ?? '') . ' P.' . h($unita['piano'] ?? '') . ' Int.' . h($unita['interno'] ?? '');
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<div class="section-title">Dati Unita</div>
<table class="table">
<tr><th style="width:180px;">Scala / Piano / Interno</th><td><?php echo h($unita['scala'] ?? '-'); ?> / <?php echo h($unita['piano'] ?? '-'); ?> / <?php echo h($unita['interno'] ?? '-'); ?></td></tr>
<tr><th>Descrizione</th><td><?php echo h($unita['descrizione'] ?? '-'); ?></td></tr>
<tr><th>Superficie</th><td><?php echo $unita['mq'] ? $unita['mq'] . ' mq' : '-'; ?></td></tr>
<tr><th>Millesimi proprieta</th><td><?php echo number_format((float)$unita['millesimi_proprieta'], 4, ',', '.'); ?></td></tr>
</table>

<?php if (!empty($persone)): ?>
<div class="section-title">Intestatari</div>
<table class="table">
<thead><tr><th>Nominativo</th><th>Ruolo</th><th class="text-end">%</th></tr></thead>
<tbody>
<?php foreach ($persone as $p): ?>
<tr>
    <td><?php echo h($p['ragione_sociale'] ?: ($p['cognome'] . ' ' . $p['nome'])); ?></td>
    <td><?php echo h(ucfirst($p['ruolo'])); ?></td>
    <td class="text-end"><?php echo $p['percentuale'] ? number_format((float)$p['percentuale'], 2, ',', '.') . '%' : '-'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<div class="section-title">Riepilogo Rate</div>
<table class="table">
<thead><tr><th>Descrizione</th><th>Scadenza</th><th class="text-end">Dovuto</th><th class="text-end">Pagato</th><th class="text-end">Residuo</th><th>Stato</th></tr></thead>
<tbody>
<?php foreach ($rate as $r): $res = (float)$r['importo'] - (float)$r['pagato']; ?>
<tr>
    <td><?php echo h($r['descrizione']); ?></td>
    <td><?php echo date('d/m/Y', strtotime($r['scadenza'])); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$r['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$r['pagato']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($res); ?></td>
    <td><?php echo h(ucfirst(str_replace('_', ' ', $r['stato']))); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr><td colspan="2"><strong>Totale</strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totaleDovuto); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totalePagato); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totaleDovuto - $totalePagato); ?></strong></td>
    <td></td></tr>
</tfoot>
</table>

<?php if (!empty($pagamenti)): ?>
<div class="section-title">Storico Pagamenti</div>
<table class="table">
<thead><tr><th>Data</th><th>Rata</th><th class="text-end">Importo</th><th>Metodo</th><th>Rif.</th></tr></thead>
<tbody>
<?php foreach ($pagamenti as $pg): ?>
<tr>
    <td><?php echo date('d/m/Y', strtotime($pg['data_pagamento'])); ?></td>
    <td><?php echo h($pg['rata_desc']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$pg['importo']); ?></td>
    <td><?php echo h($pg['metodo'] ?? '-'); ?></td>
    <td><?php echo h($pg['riferimento'] ?? '-'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
