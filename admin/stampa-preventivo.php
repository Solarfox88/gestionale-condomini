<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Preventivi.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preventivo = Preventivi::find($id);
if (!$preventivo) { echo 'Preventivo non trovato.'; exit; }
$voci = Preventivi::voci($id);
$totali = Preventivi::totali($id);
$esercizio = Esercizi::find((int)$preventivo['esercizio_id']);
$cond = get_condominio((int)$preventivo['condominio_id']);

$printTitle = 'Bilancio Preventivo';
$printSubtitle = h($preventivo['titolo']) . ' — Esercizio ' . h($esercizio['nome'] ?? '');
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<table class="table" style="margin:15px 0;">
<tr><th style="width:180px;">Esercizio</th><td><?php echo h($esercizio['nome'] ?? ''); ?> (<?php echo date('d/m/Y', strtotime($esercizio['data_inizio'])); ?> &ndash; <?php echo date('d/m/Y', strtotime($esercizio['data_fine'])); ?>)</td></tr>
<tr><th>Stato preventivo</th><td><?php echo h(ucfirst($preventivo['stato'])); ?></td></tr>
</table>

<div class="section-title">Voci di Bilancio Preventivo</div>
<table class="table">
<thead><tr><th>Categoria</th><th>Descrizione</th><th>Tipo</th><th class="text-end">Importo</th></tr></thead>
<tbody>
<?php foreach ($voci as $v): ?>
<tr>
    <td><?php echo h($v['categoria_nome'] ?? '-'); ?></td>
    <td><?php echo h($v['descrizione']); ?></td>
    <td><?php echo h(ucfirst($v['tipo'])); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$v['importo']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr><td colspan="3"><strong>Totale Entrate</strong></td><td class="text-end"><strong>&euro; <?php echo format_euro((float)$totali['entrate']); ?></strong></td></tr>
<tr><td colspan="3"><strong>Totale Uscite</strong></td><td class="text-end"><strong>&euro; <?php echo format_euro((float)$totali['uscite']); ?></strong></td></tr>
<tr><td colspan="3"><strong>Saldo</strong></td><td class="text-end"><strong>&euro; <?php echo format_euro((float)$totali['entrate'] - (float)$totali['uscite']); ?></strong></td></tr>
</tfoot>
</table>

<div style="margin-top:50px;">
    <div style="border-top:1px solid #000; width:250px; text-align:center; padding-top:5px;">L'Amministratore</div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
