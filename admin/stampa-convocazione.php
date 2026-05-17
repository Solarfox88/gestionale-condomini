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

global $pdo;
$stmtU = $pdo->prepare("SELECT ui.scala, ui.piano, ui.interno,
    p.nome, p.cognome, p.ragione_sociale,
    up.ruolo
    FROM unita_immobiliari ui
    JOIN unita_persone up ON up.unita_id = ui.id
    JOIN persone p ON p.id = up.persona_id
    WHERE ui.condominio_id = :cid AND ui.status = 'active'
    ORDER BY ui.scala, ui.piano, ui.interno, p.cognome");
$stmtU->execute(['cid' => (int)$cond['id']]);
$destinatari = $stmtU->fetchAll(PDO::FETCH_ASSOC);

$printTitle = 'Convocazione Assemblea';
$printSubtitle = h($assemblea['titolo']);
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<p style="margin-top:20px;">Gentile Condomino/a,</p>
<p>con la presente si comunica che e stata convocata l'assemblea condominiale con il seguente ordine:</p>

<table class="table" style="margin:15px 0;">
<tr><th style="width:180px;">Prima convocazione</th><td><?php echo date('d/m/Y H:i', strtotime($assemblea['data_prima_convocazione'])); ?></td></tr>
<?php if (!empty($assemblea['data_seconda_convocazione'])): ?>
<tr><th>Seconda convocazione</th><td><?php echo date('d/m/Y H:i', strtotime($assemblea['data_seconda_convocazione'])); ?></td></tr>
<?php endif; ?>
<tr><th>Luogo</th><td><?php echo h($assemblea['luogo'] ?? 'Da definire'); ?></td></tr>
</table>

<div class="section-title">Ordine del Giorno</div>
<?php
$odg = $assemblea['ordine_giorno'] ?? '';
$punti = array_filter(array_map('trim', preg_split('/\r?\n/', $odg)));
if (!empty($punti)): ?>
<ol>
<?php foreach ($punti as $punto): ?>
    <li><?php echo h($punto); ?></li>
<?php endforeach; ?>
</ol>
<?php else: ?>
<p><em><?php echo h($odg ?: 'Nessun punto specificato'); ?></em></p>
<?php endif; ?>

<?php if (!empty($destinatari)): ?>
<div class="section-title">Destinatari</div>
<table class="table">
<thead><tr><th>Unita</th><th>Nominativo</th><th>Ruolo</th></tr></thead>
<tbody>
<?php foreach ($destinatari as $d): ?>
<tr>
    <td>Sc.<?php echo h($d['scala'] ?? ''); ?> P.<?php echo h($d['piano'] ?? ''); ?> Int.<?php echo h($d['interno'] ?? ''); ?></td>
    <td><?php echo h($d['ragione_sociale'] ?: ($d['cognome'] . ' ' . $d['nome'])); ?></td>
    <td><?php echo h(ucfirst($d['ruolo'])); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<p style="margin-top:30px;">Si prega di voler partecipare personalmente o tramite delega scritta.</p>
<p>Distinti saluti,</p>

<div style="margin-top:50px;">
    <div style="border-top:1px solid #000; width:250px; text-align:center; padding-top:5px;">
        L'Amministratore
    </div>
</div>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
