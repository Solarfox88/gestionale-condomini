<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_login();
require_admin();

$condId = isset($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : null;
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

global $pdo;
$sql = "SELECT d.*, c.nome AS cond_nome, u.name AS user_nome
    FROM documenti d
    LEFT JOIN condomini c ON c.id = d.condominio_id
    LEFT JOIN users u ON u.id = d.uploaded_by
    WHERE 1=1";
$params = [];
if ($condId) { $sql .= ' AND d.condominio_id = :cid'; $params['cid'] = $condId; }
if ($categoria) { $sql .= ' AND d.categoria = :cat'; $params['cat'] = $categoria; }
$sql .= ' ORDER BY d.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cond = $condId ? get_condominio($condId) : null;

$printTitle = 'Registro Documenti';
$printSubtitle = ($cond ? h($cond['nome']) : 'Tutti i condomini') . ($categoria ? ' — Cat. ' . h(ucfirst($categoria)) : '');
$printCondominio = $cond;
include __DIR__ . '/../includes/print-header.php';
?>

<p>Totale documenti: <strong><?php echo count($docs); ?></strong></p>

<?php if (!empty($docs)): ?>
<table class="table">
<thead>
<tr>
    <th>#</th>
    <?php if (!$condId): ?><th>Condominio</th><?php endif; ?>
    <th>Titolo</th><th>Categoria</th><th>Visibilita</th><th>Data caricamento</th><th>Caricato da</th>
</tr>
</thead>
<tbody>
<?php $n = 0; foreach ($docs as $d): $n++; ?>
<tr>
    <td><?php echo $n; ?></td>
    <?php if (!$condId): ?><td><?php echo h($d['cond_nome'] ?? '-'); ?></td><?php endif; ?>
    <td><?php echo h($d['titolo']); ?></td>
    <td><?php echo h(ucfirst($d['categoria'] ?? '-')); ?></td>
    <td><?php echo h(ucfirst($d['visibilita'] ?? '-')); ?></td>
    <td><?php echo date('d/m/Y', strtotime($d['created_at'])); ?></td>
    <td><?php echo h($d['user_nome'] ?? '-'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p><em>Nessun documento trovato con i filtri specificati.</em></p>
<?php endif; ?>

<?php include __DIR__ . '/../includes/print-footer.php'; ?>
