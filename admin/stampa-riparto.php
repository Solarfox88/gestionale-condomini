<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$riparto = Riparti::find($id);
if (!$riparto) { echo 'Riparto non trovato.'; exit; }
$cond = get_condominio((int)$riparto['condominio_id']);
$dettaglio = Riparti::getDettaglio($id);

$totaleQuote = 0; $totaleEffettivo = 0; $totaleMillesimi = 0;
foreach ($dettaglio as $d) {
    $totaleQuote += (float)$d['importo'];
    $totaleEffettivo += Riparti::importoEffettivo($d);
    if (!$d['esclusa']) $totaleMillesimi += (float)$d['millesimi'];
}

$printTitle = 'Riparto Spese';
$printSubtitle = h($riparto['descrizione']);
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<table class="table" style="margin:15px 0;">
<tr><th style="width:180px;">Esercizio</th><td><?php echo h($riparto['esercizio_nome']); ?></td></tr>
<tr><th>Tipo millesimi</th><td><?php echo h($riparto['tipo_millesimi']); ?><?php if ($riparto['tabella_nome']): ?> (<?php echo h($riparto['tabella_nome']); ?>)<?php endif; ?></td></tr>
<tr><th>Tipo spesa</th><td><?php echo h(ucfirst($riparto['tipo_spesa'])); ?></td></tr>
<?php if ($riparto['categoria_nome']): ?><tr><th>Categoria</th><td><?php echo h($riparto['categoria_nome']); ?></td></tr><?php endif; ?>
<?php if ($riparto['scala_filtro']): ?><tr><th>Filtro scala</th><td><?php echo h($riparto['scala_filtro']); ?></td></tr><?php endif; ?>
<tr><th>Importo totale</th><td><strong>&euro; <?php echo format_euro((float)$riparto['importo_totale']); ?></strong></td></tr>
<tr><th>Stato</th><td><?php echo h(ucfirst(str_replace('_', ' ', $riparto['stato']))); ?></td></tr>
</table>

<div class="section-title">Dettaglio Quote</div>
<table class="table">
<thead>
<tr><th>Scala</th><th>Piano</th><th>Int.</th><th class="text-end">Millesimi</th><th class="text-end">Quota calcolata</th><th class="text-end">Rettifica</th><th class="text-end">Quota effettiva</th><th>Esclusa</th></tr>
</thead>
<tbody>
<?php foreach ($dettaglio as $d): $eff = Riparti::importoEffettivo($d); ?>
<tr<?php echo $d['esclusa'] ? ' style="text-decoration:line-through; color:#999;"' : ''; ?>>
    <td><?php echo h($d['scala'] ?? ''); ?></td>
    <td><?php echo h($d['piano'] ?? ''); ?></td>
    <td><?php echo h($d['interno'] ?? ''); ?></td>
    <td class="text-end"><?php echo number_format((float)$d['millesimi'], 4, ',', '.'); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$d['importo']); ?></td>
    <td class="text-end"><?php echo $d['importo_rettificato'] !== null ? '&euro; ' . format_euro((float)$d['importo_rettificato']) : '-'; ?></td>
    <td class="text-end">&euro; <?php echo format_euro($eff); ?></td>
    <td><?php echo $d['esclusa'] ? 'Si' : 'No'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr>
    <td colspan="3"><strong>Totale</strong></td>
    <td class="text-end"><strong><?php echo number_format($totaleMillesimi, 4, ',', '.'); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totaleQuote); ?></strong></td>
    <td></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totaleEffettivo); ?></strong></td>
    <td></td>
</tr>
</tfoot>
</table>

<?php
$differenza = (float)$riparto['importo_totale'] - $totaleEffettivo;
if (abs($differenza) > 0.01): ?>
<p style="color:red;"><strong>Attenzione:</strong> Differenza tra importo totale e somma quote: &euro; <?php echo format_euro($differenza); ?></p>
<?php endif; ?>

<div style="margin-top:50px;">
    <div style="border-top:1px solid #000; width:250px; text-align:center; padding-top:5px;">L'Amministratore</div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
