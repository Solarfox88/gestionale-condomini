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
$condId = isset($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : 0;

global $pdo;

if ($unitaId > 0) {
    $stmtU = $pdo->prepare("SELECT ui.*, c.id AS cond_id, c.nome AS cond_nome FROM unita_immobiliari ui JOIN condomini c ON c.id = ui.condominio_id WHERE ui.id = :id");
    $stmtU->execute(['id' => $unitaId]);
    $unita = $stmtU->fetch(PDO::FETCH_ASSOC);
    if (!$unita) { echo 'Unita non trovata.'; exit; }
    $condId = (int)$unita['cond_id'];
    $filterSql = 'AND r.unita_id = :uid';
    $filterParams = ['uid' => $unitaId];
} else {
    $filterSql = '';
    $filterParams = [];
    $unita = null;
}

if ($condId <= 0) { echo 'Specificare condominio_id o unita_id.'; exit; }
$cond = get_condominio($condId);
if (!$cond) { echo 'Condominio non trovato.'; exit; }

$sql = "SELECT r.id, r.descrizione, r.importo, r.scadenza, r.stato,
    ui.scala, ui.piano, ui.interno,
    COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id = r.id), 0) AS pagato,
    DATEDIFF(CURDATE(), r.scadenza) AS giorni_ritardo
    FROM rate r
    JOIN unita_immobiliari ui ON ui.id = r.unita_id
    WHERE r.condominio_id = :cid AND r.stato IN ('scaduta','parziale','da_pagare') AND r.scadenza < CURDATE()
    $filterSql
    ORDER BY r.scadenza ASC";
$params = array_merge(['cid' => $condId], $filterParams);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$morosi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totaleDovuto = 0; $totalePagato = 0;
foreach ($morosi as $m) { $totaleDovuto += (float)$m['importo']; $totalePagato += (float)$m['pagato']; }
$residuo = $totaleDovuto - $totalePagato;

// Get persona for unita
$persona = null;
if ($unita) {
    $stmtP = $pdo->prepare("SELECT p.nome, p.cognome, p.ragione_sociale, p.indirizzo
        FROM persone p JOIN unita_persone up ON up.persona_id = p.id
        WHERE up.unita_id = :uid AND up.ruolo = 'proprietario' LIMIT 1");
    $stmtP->execute(['uid' => $unitaId]);
    $persona = $stmtP->fetch(PDO::FETCH_ASSOC);
}

$printTitle = 'Sollecito di Pagamento';
$printSubtitle = $unita ? 'Unita Sc.' . h($unita['scala'] ?? '') . ' P.' . h($unita['piano'] ?? '') . ' Int.' . h($unita['interno'] ?? '') : 'Tutti i morosi';
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<?php if ($persona): ?>
<p style="margin-top:20px;">
    <strong>Spett.le</strong><br>
    <?php echo h($persona['ragione_sociale'] ?: ($persona['cognome'] . ' ' . $persona['nome'])); ?><br>
    <?php if (!empty($persona['indirizzo'])): ?><?php echo h($persona['indirizzo']); ?><br><?php endif; ?>
</p>
<?php endif; ?>

<p style="margin-top:15px;">
    Gentile Condomino/a,<br>
    con la presente Le comunichiamo che, alla data odierna, risultano scadute e non saldate le seguenti rate condominiali:
</p>

<?php if (!empty($morosi)): ?>
<table class="table">
<thead><tr><th>Unita</th><th>Descrizione</th><th class="text-end">Dovuto</th><th class="text-end">Pagato</th><th class="text-end">Residuo</th><th>Scadenza</th><th class="text-end">Gg. ritardo</th></tr></thead>
<tbody>
<?php foreach ($morosi as $m): $res = (float)$m['importo'] - (float)$m['pagato']; ?>
<tr>
    <td>Sc.<?php echo h($m['scala'] ?? ''); ?> P.<?php echo h($m['piano'] ?? ''); ?> Int.<?php echo h($m['interno'] ?? ''); ?></td>
    <td><?php echo h($m['descrizione']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['pagato']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($res); ?></td>
    <td><?php echo date('d/m/Y', strtotime($m['scadenza'])); ?></td>
    <td class="text-end"><?php echo (int)$m['giorni_ritardo']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr><td colspan="2"><strong>Totale</strong></td><td class="text-end"><strong>&euro; <?php echo format_euro($totaleDovuto); ?></strong></td><td class="text-end"><strong>&euro; <?php echo format_euro($totalePagato); ?></strong></td><td class="text-end"><strong>&euro; <?php echo format_euro($residuo); ?></strong></td><td colspan="2"></td></tr>
</tfoot>
</table>
<?php else: ?>
<p><em>Nessuna rata scaduta trovata.</em></p>
<?php endif; ?>

<p>La invitiamo a provvedere al pagamento entro e non oltre <strong>15 giorni</strong> dal ricevimento della presente comunicazione.</p>

<?php if (!empty($cond['iban'])): ?>
<p><strong>Coordinate bancarie:</strong><br>
IBAN: <?php echo h($cond['iban']); ?>
<?php if (!empty($cond['banca'])): ?><br>Banca: <?php echo h($cond['banca']); ?><?php endif; ?>
</p>
<?php endif; ?>

<p>Distinti saluti,</p>
<div style="margin-top:50px;">
    <div style="border-top:1px solid #000; width:250px; text-align:center; padding-top:5px;">L'Amministratore</div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
