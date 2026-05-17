<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Rate.php';
require_login();
require_admin();

Rate::aggiornaScadute();

$condId = isset($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : null;

global $pdo;
$sql = "SELECT r.id, r.descrizione, r.importo, r.scadenza, r.stato,
    c.nome AS condominio_nome, c.id AS condominio_id,
    ui.scala, ui.piano, ui.interno,
    p.nome AS p_nome, p.cognome AS p_cognome, p.ragione_sociale,
    COALESCE((SELECT SUM(pg.importo) FROM pagamenti pg WHERE pg.rata_id = r.id), 0) AS pagato,
    DATEDIFF(CURDATE(), r.scadenza) AS giorni_ritardo
    FROM rate r
    JOIN condomini c ON c.id = r.condominio_id
    JOIN unita_immobiliari ui ON ui.id = r.unita_id
    LEFT JOIN unita_persone up ON up.unita_id = ui.id AND up.ruolo = 'proprietario'
    LEFT JOIN persone p ON p.id = up.persona_id
    WHERE r.stato IN ('scaduta','parziale','da_pagare') AND r.scadenza < CURDATE()";
$params = [];
if ($condId) { $sql .= ' AND r.condominio_id = :cid'; $params['cid'] = $condId; }
$sql .= ' ORDER BY c.nome, ui.scala, ui.piano, ui.interno, r.scadenza';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$morosi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totaleDovuto = 0; $totalePagato = 0;
foreach ($morosi as $m) { $totaleDovuto += (float)$m['importo']; $totalePagato += (float)$m['pagato']; }

$cond = $condId ? get_condominio($condId) : null;

$printTitle = 'Elenco Morosi';
$printSubtitle = $cond ? h($cond['nome']) : 'Tutti i condomini';
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<p>Situazione morosi alla data del <strong><?php echo date('d/m/Y'); ?></strong></p>

<?php if (!empty($morosi)): ?>
<table class="table">
<thead>
<tr>
    <?php if (!$condId): ?><th>Condominio</th><?php endif; ?>
    <th>Unita</th><th>Proprietario</th><th>Descrizione</th><th>Scadenza</th>
    <th class="text-end">Dovuto</th><th class="text-end">Pagato</th><th class="text-end">Residuo</th><th class="text-end">Gg.</th>
</tr>
</thead>
<tbody>
<?php foreach ($morosi as $m): $res = (float)$m['importo'] - (float)$m['pagato']; ?>
<tr>
    <?php if (!$condId): ?><td><?php echo h($m['condominio_nome']); ?></td><?php endif; ?>
    <td>Sc.<?php echo h($m['scala'] ?? ''); ?> P.<?php echo h($m['piano'] ?? ''); ?> Int.<?php echo h($m['interno'] ?? ''); ?></td>
    <td><?php echo h($m['ragione_sociale'] ?: (($m['p_cognome'] ?? '') . ' ' . ($m['p_nome'] ?? ''))); ?></td>
    <td><?php echo h($m['descrizione']); ?></td>
    <td><?php echo date('d/m/Y', strtotime($m['scadenza'])); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['importo']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro((float)$m['pagato']); ?></td>
    <td class="text-end">&euro; <?php echo format_euro($res); ?></td>
    <td class="text-end"><?php echo (int)$m['giorni_ritardo']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="print-totals">
<tr>
    <td colspan="<?php echo $condId ? 4 : 5; ?>"><strong>Totale (<?php echo count($morosi); ?> rate scadute)</strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totaleDovuto); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totalePagato); ?></strong></td>
    <td class="text-end"><strong>&euro; <?php echo format_euro($totaleDovuto - $totalePagato); ?></strong></td>
    <td></td>
</tr>
</tfoot>
</table>
<?php else: ?>
<p><em>Nessuna rata scaduta. Nessun moroso alla data odierna.</em></p>
<?php endif; ?>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
