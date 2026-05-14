<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_once __DIR__ . '/../app/Rate.php';
require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cond = get_condominio($id);
if (!$cond) { echo 'Condominio non trovato.'; exit; }

global $pdo;

// Unita
$stmtU = $pdo->prepare("SELECT ui.*, p.nome AS p_nome, p.cognome AS p_cognome, p.ragione_sociale, up.ruolo, up.percentuale
    FROM unita_immobiliari ui
    LEFT JOIN unita_persone up ON up.unita_id = ui.id
    LEFT JOIN persone p ON p.id = up.persona_id
    WHERE ui.condominio_id = :cid
    ORDER BY ui.scala, ui.piano, ui.interno, p.cognome");
$stmtU->execute(['cid' => $id]);
$unita = $stmtU->fetchAll(PDO::FETCH_ASSOC);

$totMillProp = 0; $totMq = 0; $countUnita = 0;
$lastUnitaId = null;
foreach ($unita as $u) {
    if ($u['id'] !== $lastUnitaId) {
        $totMillProp += (float)$u['millesimi_proprieta'];
        $totMq += (float)($u['mq'] ?? 0);
        $countUnita++;
        $lastUnitaId = $u['id'];
    }
}

// Saldo movimenti
$saldo = Movimenti::saldoCondominio($id);

// Rate scadute
Rate::aggiornaScadute();
$stmtMor = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(r.importo) - SUM(COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id = r.id),0)),0) AS residuo
    FROM rate r WHERE r.condominio_id = :cid AND r.stato IN ('scaduta','parziale') AND r.scadenza < CURDATE()");
$stmtMor->execute(['cid' => $id]);
$morosita = $stmtMor->fetch(PDO::FETCH_ASSOC);

// Esercizi
$stmtEs = $pdo->prepare("SELECT * FROM esercizi WHERE condominio_id = :cid ORDER BY data_inizio DESC LIMIT 5");
$stmtEs->execute(['cid' => $id]);
$esercizi = $stmtEs->fetchAll(PDO::FETCH_ASSOC);

$printTitle = 'Scheda Condominio';
$printSubtitle = '';
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<div class="section-title">Dati Anagrafici</div>
<table class="table">
<tr><th style="width:180px;">Nome</th><td><?php echo h($cond['nome']); ?></td></tr>
<tr><th>Codice Fiscale</th><td><?php echo h($cond['codice_fiscale'] ?? '-'); ?></td></tr>
<tr><th>Indirizzo</th><td><?php echo h($cond['indirizzo'] ?? ''); ?> <?php echo h($cond['cap'] ?? ''); ?> <?php echo h($cond['comune'] ?? ''); ?> <?php echo $cond['provincia'] ? '(' . h($cond['provincia']) . ')' : ''; ?></td></tr>
<tr><th>Email</th><td><?php echo h($cond['email'] ?? '-'); ?></td></tr>
<tr><th>PEC</th><td><?php echo h($cond['pec'] ?? '-'); ?></td></tr>
<tr><th>IBAN</th><td><?php echo h($cond['iban'] ?? '-'); ?></td></tr>
<tr><th>Banca</th><td><?php echo h($cond['banca'] ?? '-'); ?></td></tr>
<tr><th>Stato</th><td><?php echo h(ucfirst($cond['status'] ?? '')); ?></td></tr>
<?php if (!empty($cond['note'])): ?>
<tr><th>Note</th><td><?php echo nl2br(h($cond['note'])); ?></td></tr>
<?php endif; ?>
</table>

<div class="section-title">Riepilogo</div>
<table class="table">
<tr><th style="width:220px;">Unita immobiliari</th><td><?php echo $countUnita; ?></td></tr>
<tr><th>Superficie totale</th><td><?php echo $totMq > 0 ? number_format($totMq, 2, ',', '.') . ' mq' : 'N/D'; ?></td></tr>
<tr><th>Millesimi proprieta totali</th><td><?php echo number_format($totMillProp, 4, ',', '.'); ?></td></tr>
<tr><th>Saldo movimenti</th><td>&euro; <?php echo format_euro($saldo); ?></td></tr>
<tr><th>Rate scadute</th><td><?php echo (int)$morosita['cnt']; ?> (residuo &euro; <?php echo format_euro((float)$morosita['residuo']); ?>)</td></tr>
</table>

<div class="section-title">Unita Immobiliari</div>
<table class="table">
<thead><tr><th>Scala</th><th>Piano</th><th>Int.</th><th>MQ</th><th class="text-end">Mill. prop.</th><th>Intestatario</th><th>Ruolo</th></tr></thead>
<tbody>
<?php foreach ($unita as $u): ?>
<tr>
    <td><?php echo h($u['scala'] ?? ''); ?></td>
    <td><?php echo h($u['piano'] ?? ''); ?></td>
    <td><?php echo h($u['interno'] ?? ''); ?></td>
    <td><?php echo $u['mq'] ?: '-'; ?></td>
    <td class="text-end"><?php echo number_format((float)$u['millesimi_proprieta'], 4, ',', '.'); ?></td>
    <td><?php echo h($u['ragione_sociale'] ?: (($u['p_cognome'] ?? '-') . ' ' . ($u['p_nome'] ?? ''))); ?></td>
    <td><?php echo h(ucfirst($u['ruolo'] ?? '-')); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if (!empty($esercizi)): ?>
<div class="section-title">Esercizi Contabili</div>
<table class="table">
<thead><tr><th>Nome</th><th>Inizio</th><th>Fine</th><th>Stato</th></tr></thead>
<tbody>
<?php foreach ($esercizi as $e): ?>
<tr>
    <td><?php echo h($e['nome']); ?></td>
    <td><?php echo date('d/m/Y', strtotime($e['data_inizio'])); ?></td>
    <td><?php echo date('d/m/Y', strtotime($e['data_fine'])); ?></td>
    <td><?php echo h(ucfirst($e['stato'])); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
