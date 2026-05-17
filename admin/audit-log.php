<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_admin();
global $pdo;

$filterUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filterAction = $_GET['action'] ?? '';
$filterEntity = $_GET['entity_type'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where = ['1=1']; $params = [];

if ($filterUser) { $where[] = 'al.user_id = :uid'; $params['uid'] = $filterUser; }
if ($filterAction) { $where[] = 'al.action = :act'; $params['act'] = $filterAction; }
if ($filterEntity) { $where[] = 'al.entity_type = :ent'; $params['ent'] = $filterEntity; }
if ($filterDateFrom) { $where[] = 'al.created_at >= :df'; $params['df'] = $filterDateFrom . ' 00:00:00'; }
if ($filterDateTo) { $where[] = 'al.created_at <= :dt'; $params['dt'] = $filterDateTo . ' 23:59:59'; }

$whereStr = implode(' AND ', $where);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM audit_logs al WHERE $whereStr");
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT al.*, u.name as user_name, u.email as user_email FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id WHERE $whereStr ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$entities = $pdo->query("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h2>Audit Log <small class="text-muted">(<?php echo number_format($totalRows); ?> eventi)</small></h2>

<form method="get" class="row g-2 mb-3 align-items-end">
    <div class="col-md-2">
        <label class="form-label">Utente</label>
        <select name="user_id" class="form-select form-select-sm">
            <option value="">Tutti</option>
            <?php foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>" <?php echo $filterUser==(int)$u['id']?'selected':''; ?>><?php echo h($u['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Azione</label>
        <select name="action" class="form-select form-select-sm">
            <option value="">Tutte</option>
            <?php foreach ($actions as $a): ?>
            <option value="<?php echo h($a); ?>" <?php echo $filterAction===$a?'selected':''; ?>><?php echo h($a); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Entita</label>
        <select name="entity_type" class="form-select form-select-sm">
            <option value="">Tutte</option>
            <?php foreach ($entities as $e): ?>
            <option value="<?php echo h($e); ?>" <?php echo $filterEntity===$e?'selected':''; ?>><?php echo h($e); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Da</label>
        <input type="date" name="date_from" value="<?php echo h($filterDateFrom); ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-2">
        <label class="form-label">A</label>
        <input type="date" name="date_to" value="<?php echo h($filterDateTo); ?>" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary btn-sm">Filtra</button>
        <a href="audit-log.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
</form>

<div class="table-responsive">
<table class="table table-sm table-striped">
<thead>
<tr><th>Data/ora</th><th>Utente</th><th>Azione</th><th>Entita</th><th>ID</th><th>Dettagli</th><th>IP</th></tr>
</thead>
<tbody>
<?php foreach ($logs as $l): ?>
<tr>
    <td><small><?php echo h($l['created_at']); ?></small></td>
    <td><?php echo h($l['user_name'] ?? 'Sistema'); ?></td>
    <td><?php echo action_badge($l['action']); ?></td>
    <td><?php echo h($l['entity_type'] ?? ''); ?></td>
    <td><?php echo $l['entity_id'] ? (int)$l['entity_id'] : ''; ?></td>
    <td><small><?php echo h(mb_substr($l['details'] ?? '', 0, 120)); ?></small></td>
    <td><small><?php echo h($l['ip_address'] ?? ''); ?></small></td>
</tr>
<?php endforeach; ?>
<?php if (!$logs): ?><tr><td colspan="7" class="text-center text-muted">Nessun evento trovato.</td></tr><?php endif; ?>
</tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<nav><ul class="pagination pagination-sm">
<?php for ($p = 1; $p <= $totalPages; $p++): ?>
<li class="page-item <?php echo $p==$page?'active':''; ?>">
    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$p])); ?>"><?php echo $p; ?></a>
</li>
<?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php
function action_badge(string $action): string {
    $colors = [
        'login' => 'bg-success', 'logout' => 'bg-secondary',
        'create' => 'bg-primary', 'update' => 'bg-info',
        'delete' => 'bg-danger', 'soft_delete' => 'bg-warning text-dark',
        'restore' => 'bg-success', 'archive' => 'bg-dark',
        'upload' => 'bg-info', 'download' => 'bg-secondary',
        'export' => 'bg-success', 'import' => 'bg-primary',
        'backup' => 'bg-dark', 'approve' => 'bg-success',
        'close' => 'bg-dark', 'reopen' => 'bg-warning text-dark',
        'send' => 'bg-primary', 'stato' => 'bg-info',
    ];
    $cls = $colors[$action] ?? 'bg-secondary';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($action) . '</span>';
}
include __DIR__ . '/../includes/footer.php';
?>
