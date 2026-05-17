<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_admin();
global $pdo;

$msg = '';

// Backup SQL export
if (isset($_GET['azione']) && $_GET['azione'] === 'export_sql') {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_condomini_' . date('Ymd_His') . '.sql"');

    $out = fopen('php://output', 'w');
    fwrite($out, "-- Backup Gestionale Condomini\n");
    fwrite($out, "-- Data: " . date('Y-m-d H:i:s') . "\n");
    fwrite($out, "-- Generato automaticamente\n\n");
    fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($out, $createStmt[1] . ";\n\n");

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $cols = array_keys($rows[0]);
            foreach ($rows as $row) {
                $vals = [];
                foreach ($row as $v) {
                    if ($v === null) $vals[] = 'NULL';
                    else $vals[] = $pdo->quote($v);
                }
                fwrite($out, "INSERT INTO `$table` (" . implode(',', array_map(fn($c) => "`$c`", $cols)) . ") VALUES (" . implode(',', $vals) . ");\n");
            }
            fwrite($out, "\n");
        }
    }
    fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($out);
    audit_log('backup', 'database', null, 'Backup SQL esportato');
    exit;
}

// Backup storage (zip)
if (isset($_GET['azione']) && $_GET['azione'] === 'backup_storage') {
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) {
        $msg = 'Directory storage non trovata.';
    } elseif (!class_exists('ZipArchive')) {
        $msg = 'Estensione ZIP non disponibile sul server. Scaricare i file manualmente via FTP.';
    } else {
        $zipFile = sys_get_temp_dir() . '/backup_storage_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageDir, RecursiveDirectoryIterator::SKIP_DOTS));
            $count = 0;
            foreach ($files as $file) {
                if ($file->isFile() && $file->getFilename() !== '.htaccess') {
                    $relPath = substr($file->getPathname(), strlen($storageDir) + 1);
                    $zip->addFile($file->getPathname(), $relPath);
                    $count++;
                }
            }
            $zip->close();

            if ($count === 0) {
                $msg = 'Nessun documento nello storage.';
                @unlink($zipFile);
            } else {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="backup_documenti_' . date('Ymd_His') . '.zip"');
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
                @unlink($zipFile);
                audit_log('backup', 'storage', null, "Backup storage: $count file");
                exit;
            }
        } else {
            $msg = 'Errore creazione archivio ZIP.';
        }
    }
}

// Log backup
$backupLogs = [];
try {
    $stmtLog = $pdo->prepare("SELECT * FROM audit_log WHERE action='backup' ORDER BY created_at DESC LIMIT 20");
    $stmtLog->execute();
    $backupLogs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Stats
$dbSize = 0;
try {
    $stmtSize = $pdo->query("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()");
    $dbSize = (float)$stmtSize->fetchColumn();
} catch (Exception $e) {}

$storageSize = 0; $storageFiles = 0;
$storageDir = __DIR__ . '/../storage';
if (is_dir($storageDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getFilename() !== '.htaccess') {
            $storageSize += $f->getSize();
            $storageFiles++;
        }
    }
}

$tableRows = [];
try {
    $stmtTables = $pdo->query("SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_rows DESC");
    $tableRows = $stmtTables->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>
<h2>Backup e ripristino</h2>

<?php if ($msg): ?><div class="alert alert-info"><?php echo h($msg); ?></div><?php endif; ?>

<div class="row mb-4">
<div class="col-md-4"><div class="card text-bg-primary"><div class="card-body text-center"><h4><?php echo number_format($dbSize / 1024 / 1024, 2); ?> MB</h4><small>Database</small></div></div></div>
<div class="col-md-4"><div class="card text-bg-success"><div class="card-body text-center"><h4><?php echo number_format($storageSize / 1024 / 1024, 2); ?> MB</h4><small>Storage (<?php echo $storageFiles; ?> file)</small></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h4><?php echo count($tableRows); ?></h4><small>Tabelle</small></div></div></div>
</div>

<div class="row mb-4">
<div class="col-md-6">
    <div class="card">
    <div class="card-header"><strong>Backup database (SQL)</strong></div>
    <div class="card-body">
        <p>Esporta tutte le tabelle e i dati in un file SQL ripristinabile tramite phpMyAdmin o mysql CLI.</p>
        <a href="?azione=export_sql" class="btn btn-primary">Scarica backup SQL</a>
    </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card">
    <div class="card-header"><strong>Backup documenti (ZIP)</strong></div>
    <div class="card-body">
        <p>Scarica tutti i documenti caricati nel gestionale in un archivio ZIP.</p>
        <a href="?azione=backup_storage" class="btn btn-success">Scarica backup documenti</a>
        <?php if (!class_exists('ZipArchive')): ?><br><small class="text-warning">Estensione ZIP non disponibile — usare FTP.</small><?php endif; ?>
    </div>
    </div>
</div>
</div>

<div class="row mb-4">
<div class="col-md-12">
    <div class="card">
    <div class="card-header"><strong>Ripristino</strong></div>
    <div class="card-body">
        <h5>Istruzioni per il ripristino</h5>
        <ol>
            <li><strong>Database:</strong> Importare il file .sql tramite phpMyAdmin (cPanel &gt; phpMyAdmin &gt; Import) oppure da CLI: <code>mysql -u utente -p nome_db &lt; backup.sql</code></li>
            <li><strong>Documenti:</strong> Estrarre lo ZIP nella cartella <code>storage/</code> del gestionale</li>
            <li><strong>Configurazione:</strong> Verificare che <code>config/config.php</code> abbia i parametri DB corretti</li>
        </ol>
        <div class="alert alert-warning mb-0">
            <strong>Attenzione:</strong> Il ripristino del database sovrascrive tutti i dati esistenti. Eseguire sempre un backup prima del ripristino.
        </div>
    </div>
    </div>
</div>
</div>

<?php if ($tableRows): ?>
<div class="card mb-4">
<div class="card-header"><strong>Contenuto database</strong></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
<thead><tr><th>Tabella</th><th class="text-end">Righe (stimate)</th></tr></thead>
<tbody>
<?php foreach ($tableRows as $t): ?>
<tr><td><code><?php echo h($t['table_name']); ?></code></td><td class="text-end"><?php echo number_format((int)$t['table_rows']); ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php if ($backupLogs): ?>
<div class="card">
<div class="card-header"><strong>Log backup recenti</strong></div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
<thead><tr><th>Data</th><th>Azione</th><th>Dettagli</th></tr></thead>
<tbody>
<?php foreach ($backupLogs as $l): ?>
<tr><td><?php echo h($l['created_at']); ?></td><td><?php echo h($l['action']); ?></td><td><?php echo h($l['details'] ?? ''); ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
