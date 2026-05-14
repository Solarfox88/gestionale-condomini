<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Rate.php';
require_login();
require_admin();

$pagamentoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pagamentoId <= 0) { echo 'Specificare id pagamento.'; exit; }

global $pdo;
$stmtPg = $pdo->prepare("SELECT pg.*, r.descrizione AS rata_desc, r.importo AS rata_importo, r.scadenza,
    r.condominio_id, r.unita_id,
    c.nome AS cond_nome,
    ui.scala, ui.piano, ui.interno
    FROM pagamenti pg
    JOIN rate r ON r.id = pg.rata_id
    JOIN condomini c ON c.id = r.condominio_id
    JOIN unita_immobiliari ui ON ui.id = r.unita_id
    WHERE pg.id = :id");
$stmtPg->execute(['id' => $pagamentoId]);
$pg = $stmtPg->fetch(PDO::FETCH_ASSOC);
if (!$pg) { echo 'Pagamento non trovato.'; exit; }

$cond = get_condominio((int)$pg['condominio_id']);

// Persona proprietaria
$stmtP = $pdo->prepare("SELECT p.nome, p.cognome, p.ragione_sociale, p.codice_fiscale
    FROM persone p JOIN unita_persone up ON up.persona_id = p.id
    WHERE up.unita_id = :uid AND up.ruolo = 'proprietario' LIMIT 1");
$stmtP->execute(['uid' => (int)$pg['unita_id']]);
$persona = $stmtP->fetch(PDO::FETCH_ASSOC);

$printTitle = 'Ricevuta di Pagamento';
$printSubtitle = 'N. ' . $pagamentoId . ' del ' . date('d/m/Y', strtotime($pg['data_pagamento']));
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<table class="table" style="margin:20px 0;">
<tr><th style="width:200px;">Ricevuta n.</th><td><strong><?php echo $pagamentoId; ?></strong></td></tr>
<tr><th>Data pagamento</th><td><?php echo date('d/m/Y', strtotime($pg['data_pagamento'])); ?></td></tr>
<tr><th>Unita</th><td>Scala <?php echo h($pg['scala'] ?? ''); ?>, Piano <?php echo h($pg['piano'] ?? ''); ?>, Interno <?php echo h($pg['interno'] ?? ''); ?></td></tr>
<?php if ($persona): ?>
<tr><th>Intestatario</th><td><?php echo h($persona['ragione_sociale'] ?: ($persona['cognome'] . ' ' . $persona['nome'])); ?>
    <?php if (!empty($persona['codice_fiscale'])): ?><br><small>C.F.: <?php echo h($persona['codice_fiscale']); ?></small><?php endif; ?></td></tr>
<?php endif; ?>
<tr><th>Rata di riferimento</th><td><?php echo h($pg['rata_desc']); ?> (scadenza <?php echo date('d/m/Y', strtotime($pg['scadenza'])); ?>)</td></tr>
<tr><th>Importo rata</th><td>&euro; <?php echo format_euro((float)$pg['rata_importo']); ?></td></tr>
</table>

<div style="border:2px solid #000; padding:15px; margin:20px 0; text-align:center;">
    <div style="font-size:12px; color:#666;">Importo pagato</div>
    <div style="font-size:24px; font-weight:bold;">&euro; <?php echo format_euro((float)$pg['importo']); ?></div>
</div>

<table class="table" style="margin:15px 0;">
<tr><th style="width:200px;">Metodo di pagamento</th><td><?php echo h(ucfirst($pg['metodo'] ?? 'N/D')); ?></td></tr>
<?php if (!empty($pg['riferimento'])): ?>
<tr><th>Riferimento</th><td><?php echo h($pg['riferimento']); ?></td></tr>
<?php endif; ?>
<?php if (!empty($pg['note'])): ?>
<tr><th>Note</th><td><?php echo h($pg['note']); ?></td></tr>
<?php endif; ?>
</table>

<p style="margin-top:20px; font-size:10px;">La presente ricevuta attesta l'avvenuto pagamento della somma indicata.</p>

<div style="margin-top:50px; display:flex; justify-content:space-between;">
    <div style="border-top:1px solid #000; width:200px; text-align:center; padding-top:5px;">L'Amministratore</div>
    <div style="border-top:1px solid #000; width:200px; text-align:center; padding-top:5px;">Il Condomino</div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
