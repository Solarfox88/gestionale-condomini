<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Preventivi.php';
require_login();
require_admin();

$esercizioId = isset($_GET['esercizio_id']) ? (int)$_GET['esercizio_id'] : 0;
if ($esercizioId <= 0) { echo 'Specificare esercizio_id.'; exit; }
$esercizio = Esercizi::find($esercizioId);
if (!$esercizio) { echo 'Esercizio non trovato.'; exit; }
$cond = get_condominio((int)$esercizio['condominio_id']);

$confronto = Preventivi::confronto($esercizioId);
$consuntivo = Preventivi::consuntivo($esercizioId);

$totPrevEntrate = 0; $totPrevUscite = 0;
$totConsEntrate = 0; $totConsUscite = 0;
foreach ($confronto as $c) {
    if ($c['tipo'] === 'entrata') { $totPrevEntrate += (float)$c['prev_importo']; }
    else { $totPrevUscite += (float)$c['prev_importo']; }
}
foreach ($consuntivo as $c) {
    if ($c['tipo'] === 'entrata') { $totConsEntrate += (float)$c['totale']; }
    else { $totConsUscite += (float)$c['totale']; }
}

$printTitle = 'Bilancio Consuntivo';
$printSubtitle = 'Esercizio: ' . h($esercizio['nome']);
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<table class="table" style="margin:15px 0;">
<tr><th style="width:180px;">Esercizio</th><td><?php echo h($esercizio['nome']); ?> (<?php echo date('d/m/Y', strtotime($esercizio['data_inizio'])); ?> &ndash; <?php echo date('d/m/Y', strtotime($esercizio['data_fine'])); ?>)</td></tr>
<tr><th>Stato</th><td><?php echo h(ucfirst($esercizio['stato'])); ?></td></tr>
</table>

<?php if (!empty($confronto)): ?>
<div class="section-title">Confronto Preventivo / Consuntivo</div>
<table class="table">
<thead><tr><th>Categoria</th><th>Tipo</th><th class="text-end">Preventivo</th><th class="text-end">Consuntivo</th><th class="text-end">Scostamento</th></tr></thead>
<tbody>
<?php foreach ($confronto as $c):
    $consVal = 0;
    foreach ($consuntivo as $cv) { if (($cv['categoria_nome'] ?? '') === ($c['categoria_nome'] ?? '')) { $consVal = (float)$cv['totale']; break; } }
    $scost = $consVal - (float)$c['prev_importo'];
?>
<tr>
    <td><?php echo h($c['categoria_nome'] ?? 'Senza categoria'); ?></td>
    <td><?php echo h(ucfirst($c['tipo'])); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$c['prev_importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($consVal); ?></td>
    <td class="text-end" style="<?php echo $scost > 0 ? 'color:red' : ($scost < 0 ? 'color:green' : ''); ?>">&euro; <?php echo format_euro($scost); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr><td colspan="2"><strong>Totale Entrate</strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totPrevEntrate); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totConsEntrate); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totConsEntrate - $totPrevEntrate); ?></strong></td></tr>
<tr><td colspan="2"><strong>Totale Uscite</strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totPrevUscite); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totConsUscite); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totConsUscite - $totPrevUscite); ?></strong></td></tr>
<tr><td colspan="2"><strong>Saldo</strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totPrevEntrate - $totPrevUscite); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totConsEntrate - $totConsUscite); ?></strong></td>
    <td></td></tr>
</tfoot>
</table>
<?php else: ?>
<p><em>Nessun preventivo trovato per questo esercizio.</em></p>
<?php endif; ?>

<?php
$quadrature = Esercizi::quadrature($esercizioId);
?>
<div class="section-title">Quadrature Esercizio</div>
<table class="table">
<tr><th style="width:220px;">Entrate (movimenti)</th><td class="text-end">&euro; <?php echo format_euro((float)($quadrature['entrate'] ?? 0)); ?></td></tr>
<tr><th>Uscite (movimenti)</th><td class="text-end">&euro; <?php echo format_euro((float)($quadrature['uscite'] ?? 0)); ?></td></tr>
<tr><th>Saldo movimenti</th><td class="text-end"><strong>&euro; <?php echo format_euro((float)($quadrature['saldo'] ?? 0)); ?></strong></td></tr>
<tr><th>Rate emesse</th><td class="text-end">&euro; <?php echo format_euro((float)($quadrature['rate_totale'] ?? 0)); ?></td></tr>
<tr><th>Rate incassate</th><td class="text-end">&euro; <?php echo format_euro((float)($quadrature['rate_incassato'] ?? 0)); ?></td></tr>
<tr><th>Rate residuo</th><td class="text-end"><strong>&euro; <?php echo format_euro((float)($quadrature['rate_totale'] ?? 0) - (float)($quadrature['rate_incassato'] ?? 0)); ?></strong></td></tr>
</table>

<div style="margin-top:50px;">
    <div style="border-top:1px solid #000; width:250px; text-align:center; padding-top:5px;">L'Amministratore</div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
