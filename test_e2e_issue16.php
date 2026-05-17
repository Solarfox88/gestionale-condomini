<?php
/**
 * Test E2E — EPIC #16: Audit legale, tracciabilità, blocchi e cancellazione logica
 */
$base = 'http://localhost:8080';
$passed = 0; $failed = 0;
$cookie = '';

function req(string $method, string $url, array $post = [], bool $followRedirect = true): array {
    global $base, $cookie;
    $ch = curl_init($base . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if (!$followRedirect) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    else curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    if (preg_match('/Set-Cookie:\s*PHPSESSID=([^;\s]+)/i', $headers, $m)) {
        $cookie = 'PHPSESSID=' . $m[1];
    }
    return ['code' => $httpCode, 'body' => $body, 'headers' => $headers];
}

function assert_test(bool $condition, string $name): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "  PASS: $name\n"; }
    else { $failed++; echo "  FAIL: $name\n"; }
}

function get_csrf(string $body): string {
    preg_match('/name="csrf_token" value="([^"]+)"/', $body, $m);
    return $m[1] ?? '';
}

// Connect to DB
$pdo = new PDO('mysql:host=127.0.0.1;dbname=condomini_db;charset=utf8mb4', 'db_user', 'db_password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== SETUP ===\n";
$r = req('GET', '/login.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/login.php', ['email' => 'admin@gestionale.local', 'password' => 'password', 'csrf_token' => $csrf]);
assert_test(strpos($r['body'], 'Dashboard') !== false || strpos($r['body'], 'dashboard') !== false || strpos($r['body'], 'Condomini') !== false, 'Admin login');

// Record the last audit log id before our tests
$lastLog = $pdo->query("SELECT COALESCE(MAX(id),0) FROM audit_logs")->fetchColumn();

echo "\n=== TEST: Pagina Audit Log ===\n";
$r = req('GET', '/admin/audit-log.php');
assert_test($r['code'] === 200, 'Audit log page HTTP 200');
assert_test(strpos($r['body'], 'Audit Log') !== false, 'Audit log title present');
assert_test(strpos($r['body'], 'Filtra') !== false, 'Filtro form present');
assert_test(strpos($r['body'], 'Azione') !== false, 'Colonna azione present');
assert_test(strpos($r['body'], 'Utente') !== false, 'Colonna utente present');

echo "\n=== TEST: Audit log registra login ===\n";
$loginLogs = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='login'")->fetchColumn();
assert_test($loginLogs >= 1, 'Login registrato in audit_logs');

echo "\n=== TEST: Audit log filtri ===\n";
$r = req('GET', '/admin/audit-log.php?action=login');
assert_test($r['code'] === 200, 'Filtro per azione funziona');
assert_test(strpos($r['body'], 'login') !== false, 'Risultati filtrati per login');

$r = req('GET', '/admin/audit-log.php?date_from=2020-01-01&date_to=2099-12-31');
assert_test($r['code'] === 200, 'Filtro per date funziona');

echo "\n=== TEST: Creazione condominio con audit ===\n";
$r = req('GET', '/admin/condomini.php');
$csrf = get_csrf($r['body']);
$before = $pdo->query("SELECT MAX(id) FROM audit_logs")->fetchColumn();
$r = req('POST', '/admin/condomini.php', [
    'csrf_token' => $csrf,
    'nome' => 'Cond Audit Test',
    'codice_fiscale' => '',
    'indirizzo' => 'Via Test 1',
    'comune' => 'Roma',
    'provincia' => 'RM',
    'cap' => '00100',
    'iban' => '', 'banca' => '', 'email' => '', 'pec' => '', 'note' => ''
]);
assert_test(strpos($r['body'], 'Condominio creato') !== false, 'Condominio creato');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action='create' AND entity_type='condomini' AND id > :b");
$stmt->execute(['b' => $before]);
assert_test((int)$stmt->fetchColumn() >= 1, 'Creazione condominio registrata in audit');

echo "\n=== TEST: Modifica condominio con audit ===\n";
$condId = (int)$pdo->query("SELECT id FROM condomini WHERE nome='Cond Audit Test'")->fetchColumn();
$r = req('GET', '/admin/condominio-edit.php?id=' . $condId);
$csrf = get_csrf($r['body']);
$before = $pdo->query("SELECT MAX(id) FROM audit_logs")->fetchColumn();
$r = req('POST', '/admin/condominio-edit.php?id=' . $condId, [
    'csrf_token' => $csrf,
    'nome' => 'Cond Audit Test Modified',
    'codice_fiscale' => '', 'indirizzo' => 'Via Test 1', 'comune' => 'Roma',
    'provincia' => 'RM', 'cap' => '00100', 'iban' => '', 'banca' => '',
    'email' => '', 'pec' => '', 'note' => '', 'status' => 'active'
]);
assert_test(strpos($r['body'], 'aggiornato') !== false, 'Condominio aggiornato');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action='update' AND entity_type='condomini' AND id > :b");
$stmt->execute(['b' => $before]);
assert_test((int)$stmt->fetchColumn() >= 1, 'Modifica condominio registrata in audit');

echo "\n=== TEST: Soft delete movimenti ===\n";
// Create a test esercizio + movimento
$condId = (int)$pdo->query("SELECT id FROM condomini WHERE nome='Cond Audit Test Modified' ORDER BY id DESC LIMIT 1")->fetchColumn();
$pdo->exec("INSERT INTO esercizi (condominio_id, nome, data_inizio, data_fine, stato) VALUES ($condId, 'Es Audit 2026', '2026-01-01', '2026-12-31', 'aperto')");
$esId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO movimenti (esercizio_id, condominio_id, tipo, descrizione, importo, data_movimento) VALUES ($esId, $condId, 'entrata', 'Test mov audit', 100, '2026-06-01')");
$movId = (int)$pdo->lastInsertId();

$r = req('GET', '/admin/movimenti.php');
$csrf = get_csrf($r['body']);
$before = $pdo->query("SELECT MAX(id) FROM audit_logs")->fetchColumn();
$r = req('POST', '/admin/movimenti.php', [
    'csrf_token' => $csrf,
    'azione' => 'elimina',
    'id' => $movId
]);
assert_test(strpos($r['body'], 'archiviato') !== false, 'Movimento archiviato (non eliminato)');

// Verify soft delete
$stmt = $pdo->prepare("SELECT deleted_at FROM movimenti WHERE id = :id");
$stmt->execute(['id' => $movId]);
$deletedAt = $stmt->fetchColumn();
assert_test($deletedAt !== null && $deletedAt !== false, 'Movimento ha deleted_at impostato');

// Verify it's hidden from list
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movimenti WHERE id = :id AND deleted_at IS NULL");
$stmt->execute(['id' => $movId]);
assert_test((int)$stmt->fetchColumn() === 0, 'Movimento non visibile nelle query normali');

// Verify audit log
$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action='soft_delete' AND entity_type='movimenti' AND id > :b");
$stmt->execute(['b' => $before]);
assert_test((int)$stmt->fetchColumn() >= 1, 'Soft delete movimenti registrato in audit');

echo "\n=== TEST: Soft delete rate ===\n";
$pdo->exec("INSERT INTO unita_immobiliari (condominio_id, interno, millesimi_proprieta) VALUES ($condId, 'A1', 500)");
$unitaId = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO rate (esercizio_id, condominio_id, unita_id, descrizione, importo, scadenza, stato) VALUES ($esId, $condId, $unitaId, 'Rata test audit', 200, '2026-07-01', 'da_pagare')");
$rataId = (int)$pdo->lastInsertId();

$r = req('GET', '/admin/rate.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/rate.php', [
    'csrf_token' => $csrf,
    'azione' => 'elimina',
    'id' => $rataId
]);

$stmt = $pdo->prepare("SELECT deleted_at FROM rate WHERE id = :id");
$stmt->execute(['id' => $rataId]);
$deletedAt = $stmt->fetchColumn();
assert_test($deletedAt !== null && $deletedAt !== false, 'Rata soft-deleted con deleted_at');

echo "\n=== TEST: Soft delete pagamenti ===\n";
// Re-insert a rata for pagamento test
$pdo->exec("INSERT INTO rate (esercizio_id, condominio_id, unita_id, descrizione, importo, scadenza, stato) VALUES ($esId, $condId, $unitaId, 'Rata pag test', 300, '2026-08-01', 'da_pagare')");
$rataId2 = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO pagamenti (rata_id, data_pagamento, importo, metodo) VALUES ($rataId2, '2026-08-01', 300, 'bonifico')");
$pagId = (int)$pdo->lastInsertId();

$r = req('GET', '/admin/pagamenti.php');
$csrf = get_csrf($r['body']);
$before = $pdo->query("SELECT MAX(id) FROM audit_logs")->fetchColumn();
$r = req('POST', '/admin/pagamenti.php', [
    'csrf_token' => $csrf,
    'azione' => 'elimina',
    'id' => $pagId
]);
assert_test(strpos($r['body'], 'archiviato') !== false, 'Pagamento archiviato');

$stmt = $pdo->prepare("SELECT deleted_at FROM pagamenti WHERE id = :id");
$stmt->execute(['id' => $pagId]);
$deletedAt = $stmt->fetchColumn();
assert_test($deletedAt !== null && $deletedAt !== false, 'Pagamento soft-deleted con deleted_at');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action='soft_delete' AND entity_type='pagamenti' AND id > :b");
$stmt->execute(['b' => $before]);
assert_test((int)$stmt->fetchColumn() >= 1, 'Soft delete pagamenti registrato in audit');

echo "\n=== TEST: Blocco esercizio chiuso ===\n";
$pdo->prepare("UPDATE esercizi SET stato='chiuso' WHERE id=:id")->execute(['id' => $esId]);
$pdo->exec("INSERT INTO movimenti (esercizio_id, condominio_id, tipo, descrizione, importo, data_movimento) VALUES ($esId, $condId, 'uscita', 'Mov bloccato', 50, '2026-06-15')");
$movBlocked = (int)$pdo->lastInsertId();

$r = req('GET', '/admin/movimenti.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/movimenti.php', [
    'csrf_token' => $csrf,
    'azione' => 'elimina',
    'id' => $movBlocked
]);
assert_test(strpos($r['body'], 'chiuso') !== false || strpos($r['body'], 'Impossibile') !== false, 'Eliminazione bloccata su esercizio chiuso');

$stmt = $pdo->prepare("SELECT deleted_at FROM movimenti WHERE id = :id");
$stmt->execute(['id' => $movBlocked]);
$delAt = $stmt->fetchColumn();
assert_test($delAt === null || $delAt === false, 'Movimento su esercizio chiuso non cancellato');

echo "\n=== TEST: Approvazione utente con audit ===\n";
$r = req('GET', '/admin/utenti.php');
$csrf = get_csrf($r['body']);
// Get a user id
$testUser = $pdo->query("SELECT id FROM users WHERE role != 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($testUser) {
    $before = $pdo->query("SELECT MAX(id) FROM audit_logs")->fetchColumn();
    $r = req('POST', '/admin/utenti.php', [
        'csrf_token' => $csrf,
        'user_id' => $testUser,
        'role' => 'condomino',
        'status' => 'active'
    ]);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action='update' AND entity_type='users' AND id > :b");
    $stmt->execute(['b' => $before]);
    assert_test((int)$stmt->fetchColumn() >= 1, 'Aggiornamento utente registrato in audit');
} else {
    assert_test(true, 'Aggiornamento utente registrato in audit (skip: nessun utente non-admin)');
}

echo "\n=== TEST: Schema DB aggiornato ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM movimenti LIKE 'deleted_at'")->fetchAll();
assert_test(count($cols) === 1, 'movimenti ha colonna deleted_at');

$cols = $pdo->query("SHOW COLUMNS FROM rate LIKE 'deleted_at'")->fetchAll();
assert_test(count($cols) === 1, 'rate ha colonna deleted_at');

$cols = $pdo->query("SHOW COLUMNS FROM pagamenti LIKE 'deleted_at'")->fetchAll();
assert_test(count($cols) === 1, 'pagamenti ha colonna deleted_at');

$cols = $pdo->query("SHOW COLUMNS FROM documenti LIKE 'deleted_at'")->fetchAll();
assert_test(count($cols) === 1, 'documenti ha colonna deleted_at');

$cols = $pdo->query("SHOW COLUMNS FROM persone LIKE 'deleted_at'")->fetchAll();
assert_test(count($cols) === 1, 'persone ha colonna deleted_at');

$cols = $pdo->query("SHOW COLUMNS FROM unita_immobiliari LIKE 'deleted_at'")->fetchAll();
assert_test(count($cols) === 1, 'unita_immobiliari ha colonna deleted_at');

echo "\n=== TEST: Menu Audit Log ===\n";
$r = req('GET', '/admin/dashboard.php');
assert_test(strpos($r['body'], 'audit-log.php') !== false, 'Menu contiene link Audit Log');

echo "\n=== TEST: Accesso protetto ===\n";
$savedCookie = $cookie;
$cookie = '';
$r = req('GET', '/admin/audit-log.php');
assert_test($r['code'] === 403 || strpos($r['body'], 'negato') !== false || strpos($r['body'], 'login') !== false || strpos($r['body'], 'Login') !== false, 'Audit log protetto da login');
$cookie = $savedCookie;

echo "\n=== TEST: Audit log viewer con eventi recenti ===\n";
$r = req('GET', '/admin/audit-log.php');
assert_test(strpos($r['body'], 'create') !== false || strpos($r['body'], 'soft_delete') !== false, 'Audit log mostra eventi recenti');
assert_test(strpos($r['body'], 'condomini') !== false || strpos($r['body'], 'movimenti') !== false, 'Audit log mostra entita');

echo "\n=== TEST: Filtro per entity_type ===\n";
$r = req('GET', '/admin/audit-log.php?entity_type=condomini');
assert_test($r['code'] === 200, 'Filtro per entity_type funziona');

// Cleanup
$pdo->exec("DELETE FROM movimenti WHERE condominio_id = $condId");
$pdo->exec("DELETE FROM pagamenti WHERE rata_id IN (SELECT id FROM rate WHERE condominio_id = $condId)");
$pdo->exec("DELETE FROM rate WHERE condominio_id = $condId");
$pdo->exec("DELETE FROM unita_immobiliari WHERE condominio_id = $condId");
$pdo->exec("DELETE FROM esercizi WHERE condominio_id = $condId");
$pdo->exec("DELETE FROM condomini WHERE id = $condId");

echo "\n========================================\n";
echo "RISULTATI: $passed/$" . ($passed+$failed) . " passati, $failed falliti\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
