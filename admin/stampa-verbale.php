<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$assemblea = Assemblee::find($id);
if (!$assemblea) { echo 'Assemblea non trovata.'; exit; }
$cond = get_condominio((int)$assemblea['condominio_id']);
$presenze = Assemblee::getPresenze($id);
$totMillPresenti = Assemblee::totaleMillesimiPresenti($id);

global $pdo;
$stmtTotMill = $pdo->prepare("SELECT COALESCE(SUM(millesimi_proprieta),0) FROM unita_immobiliari WHERE condominio_id = :cid AND status = 'active'");
$stmtTotMill->execute(['cid' => (int)$cond['id']]);
$totMillCondominio = (float)$stmtTotMill->fetchColumn();
$quorum = $totMillCondominio > 0 ? round($totMillPresenti / $totMillCondominio * 100, 2) : 0;

$printTitle = 'Verbale di Assemblea';
$printSubtitle = h($assemblea['titolo']);
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<table class="table" style="margin:15px 0;">
<tr><th style="width:180px;">Data assemblea</th><td><?php echo date('d/m/Y H:i', strtotime($assemblea['data_prima_convocazione'])); ?></td></tr>
<?php if (!empty($assemblea['data_seconda_convocazione'])): ?>
<tr><th>Seconda convocazione</th><td><?php echo date('d/m/Y H:i', strtotime($assemblea['data_seconda_convocazione'])); ?></td></tr>
<?php endif; ?>
<tr><th>Luogo</th><td><?php echo h($assemblea['luogo'] ?? 'N/D'); ?></td></tr>
<tr><th>Stato</th><td><?php echo h(ucfirst(str_replace('_', ' ', $assemblea['stato']))); ?></td></tr>
</table>

<?php if (!empty($presenze)): ?>
<div class="section-title">Presenze e Deleghe</div>
<table class="table">
<thead><tr><th>Nominativo</th><th>Unita</th><th>Presente</th><th>Delegato da</th><th class="text-end">Millesimi</th></tr></thead>
<tbody>
<?php foreach ($presenze as $pr): ?>
<tr>
    <td><?php echo h(($pr['cognome'] ?? '') . ' ' . ($pr['nome'] ?? '')); ?></td>
    <td><?php echo h(($pr['scala'] ?? '') . '/' . ($pr['piano'] ?? '') . '/' . ($pr['interno'] ?? '')); ?></td>
    <td><?php echo $pr['presente'] ? 'Si' : 'No'; ?></td>
    <td><?php echo h($pr['delegato_cognome'] ?? '-'); ?></td>
    <td class="text-end"><?php echo number_format((float)$pr['millesimi'], 2, ',', '.'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr><td colspan="4"><strong>Totale millesimi presenti</strong></td><td class="text-end"><strong><?php echo number_format($totMillPresenti, 2, ',', '.'); ?> / <?php echo number_format($totMillCondominio, 2, ',', '.'); ?> (<?php echo $quorum; ?>%)</strong></td></tr>
</tfoot>
</table>
<?php endif; ?>

<div class="section-title">Ordine del Giorno e Delibere</div>
<?php
$odg = $assemblea['ordine_giorno'] ?? '';
$punti = array_filter(array_map('trim', preg_split('/\r?\n/', $odg)));
if (!empty($punti)): ?>
<ol>
<?php foreach ($punti as $punto): ?>
    <li style="margin-bottom:10px;"><?php echo h($punto); ?></li>
<?php endforeach; ?>
</ol>
<?php else: ?>
<p><em><?php echo h($odg ?: 'Nessun punto specificato'); ?></em></p>
<?php endif; ?>

<?php if (!empty($assemblea['note'])): ?>
<div class="section-title">Note</div>
<p><?php echo nl2br(h($assemblea['note'])); ?></p>
<?php endif; ?>

<div style="margin-top:60px; display:flex; justify-content:space-between;">
    <div style="border-top:1px solid #000; width:200px; text-align:center; padding-top:5px;">Il Presidente</div>
    <div style="border-top:1px solid #000; width:200px; text-align:center; padding-top:5px;">Il Segretario</div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
