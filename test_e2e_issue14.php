<?php
/**
 * Test E2E per EPIC #14 — Import/export, migrazione dati e backup
 */

$base = 'http://localhost:8080';
$cookie = '/tmp/test_e2e_14_cookie.txt';
$passed = 0; $failed = 0; $total = 0;

function test($desc, $cond) {
    global $passed, $failed, $total; $total++;
    if ($cond) { $passed++; echo "  PASS: $desc\n"; }
    else { $failed++; echo "  FAIL: $desc\n"; }
}

function get($url) {
    global $cookie, $base;
    $ch = curl_init($base . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookie, CURLOPT_COOKIEJAR => $cookie]);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['body' => $r, 'code' => $code];
}

function post($url, $data) {
    global $cookie, $base;
    $page = get($url);
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $page['body'], $m)) {
        $data['csrf_token'] = $m[1];
    }
    $ch = curl_init($base . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookie, CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['body' => $r, 'code' => $code];
}

function post_file($url, $fields, $filePath, $fileField = 'csv_file') {
    global $cookie, $base;
    $page = get($url);
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $page['body'], $m)) {
        $fields['csrf_token'] = $m[1];
    }
    $fields[$fileField] = new CURLFile($filePath, 'text/csv', basename($filePath));
    $ch = curl_init($base . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookie, CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $fields]);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['body' => $r, 'code' => $code];
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=condomini_db;charset=utf8mb4', 'db_user', 'db_password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ============================================================
// SETUP
// ============================================================
echo "=== SETUP ===\n";
$r = post('/login.php', ['email' => 'admin@gestionale.local', 'password' => 'password']);
test('Admin login', $r['code'] == 200 && stripos($r['body'], 'dashboard') !== false);

// ============================================================
// TEST 1: Pages accessible
// ============================================================
echo "\n=== TEST: Pagine accessibili ===\n";
$r = get('/admin/export.php');
test('Export page HTTP 200', $r['code'] == 200);
test('Export mostra tipi export', stripos($r['body'], 'Condomini') !== false && stripos($r['body'], 'Esporta CSV') !== false);

$r = get('/admin/import.php');
test('Import page HTTP 200', $r['code'] == 200);
test('Import mostra template', stripos($r['body'], 'Template CSV') !== false);
test('Import mostra form upload', stripos($r['body'], 'csv_file') !== false);

$r = get('/admin/backup.php');
test('Backup page HTTP 200', $r['code'] == 200);
test('Backup mostra statistiche DB', stripos($r['body'], 'Database') !== false);
test('Backup mostra pulsante SQL', stripos($r['body'], 'export_sql') !== false);
test('Backup mostra ripristino', stripos($r['body'], 'Ripristino') !== false);

// ============================================================
// TEST 2: Template CSV downloads
// ============================================================
echo "\n=== TEST: Download template CSV ===\n";
$templateTypes = ['condomini', 'unita', 'persone', 'associazioni', 'rate', 'movimenti'];
foreach ($templateTypes as $tpl) {
    $r = get('/admin/import.php?template=' . $tpl);
    test("Template $tpl scaricabile", $r['code'] == 200 && strlen($r['body']) > 10);
}

// ============================================================
// TEST 3: Export CSV
// ============================================================
echo "\n=== TEST: Export CSV ===\n";
$exportTypes = ['condomini', 'unita', 'persone', 'associazioni', 'movimenti', 'rate', 'pagamenti', 'morosita', 'documenti'];
foreach ($exportTypes as $exp) {
    $r = get('/admin/export.php?tipo=' . $exp);
    test("Export $exp CSV", $r['code'] == 200);
}

// ============================================================
// TEST 4: Import CSV - condomini
// ============================================================
echo "\n=== TEST: Import CSV condomini ===\n";
$csvFile = '/tmp/test_import_condomini.csv';
file_put_contents($csvFile, "nome,codice_fiscale,indirizzo,comune,provincia,cap,iban,banca,email,pec,note\n\"Cond Import Test\",\"12345678901\",\"Via Import 1\",\"Milano\",\"MI\",\"20100\",\"\",\"\",\"cond@test.it\",\"\",\"test import\"\n\"Cond Import Test 2\",\"\",\"Via Import 2\",\"Roma\",\"RM\",\"00100\",\"\",\"\",\"\",\"\",\"\"\n");
$r = post_file('/admin/import.php', ['tipo' => 'condomini'], $csvFile);
test('Import condomini eseguito', stripos($r['body'], 'importate') !== false);
$cnt = $pdo->query("SELECT COUNT(*) FROM condomini WHERE nome LIKE 'Cond Import Test%'")->fetchColumn();
test('Condomini importati nel DB', $cnt >= 2);

// ============================================================
// TEST 5: Import CSV - persone
// ============================================================
echo "\n=== TEST: Import CSV persone ===\n";
$csvFile2 = '/tmp/test_import_persone.csv';
file_put_contents($csvFile2, "nome,cognome,ragione_sociale,tipo,codice_fiscale,partita_iva,email,pec,telefono,indirizzo,note\n\"Luigi\",\"Bianchi\",\"\",\"persona\",\"BNCLGU80A01F205Z\",\"\",\"luigi@test.it\",\"\",\"+39 333 9999999\",\"Via Bianchi 5\",\"importato\"\n");
$r = post_file('/admin/import.php', ['tipo' => 'persone'], $csvFile2);
test('Import persone eseguito', stripos($r['body'], 'importate') !== false);
$cnt = $pdo->query("SELECT COUNT(*) FROM persone WHERE cognome='Bianchi' AND nome='Luigi'")->fetchColumn();
test('Persona importata nel DB', $cnt >= 1);

// ============================================================
// TEST 6: Import CSV - validation errors
// ============================================================
echo "\n=== TEST: Import CSV con errori ===\n";
$csvFile3 = '/tmp/test_import_errors.csv';
file_put_contents($csvFile3, "condominio_id,scala,piano,interno\n999999,A,1,1\n");
$r = post_file('/admin/import.php', ['tipo' => 'unita'], $csvFile3);
test('Import con errore mostra avviso', stripos($r['body'], 'non esiste') !== false || stripos($r['body'], 'errori') !== false || stripos($r['body'], 'fallito') !== false);

// ============================================================
// TEST 7: Import CSV - unita
// ============================================================
echo "\n=== TEST: Import CSV unita ===\n";
$condId = $pdo->query("SELECT id FROM condomini WHERE nome='Cond Import Test' LIMIT 1")->fetchColumn();
$csvFile4 = '/tmp/test_import_unita.csv';
file_put_contents($csvFile4, "condominio_id,scala,piano,interno,subalterno,descrizione,mq,millesimi_proprieta,millesimi_scale,millesimi_ascensore,millesimi_riscaldamento\n$condId,A,1,1,,Appartamento,80,500,400,300,600\n$condId,A,2,2,,Appartamento,60,500,600,700,400\n");
$r = post_file('/admin/import.php', ['tipo' => 'unita'], $csvFile4);
test('Import unita eseguito', stripos($r['body'], 'importate') !== false);
$cnt = $pdo->query("SELECT COUNT(*) FROM unita_immobiliari WHERE condominio_id=$condId")->fetchColumn();
test('Unita importate nel DB', $cnt >= 2);

// ============================================================
// TEST 8: Backup SQL
// ============================================================
echo "\n=== TEST: Backup SQL ===\n";
$r = get('/admin/backup.php?azione=export_sql');
test('Backup SQL scaricabile', $r['code'] == 200 && strlen($r['body']) > 100);
test('Backup contiene CREATE TABLE', stripos($r['body'], 'CREATE TABLE') !== false);
test('Backup contiene INSERT', stripos($r['body'], 'INSERT INTO') !== false);
test('Backup contiene tabella condomini', stripos($r['body'], 'condomini') !== false);

// ============================================================
// TEST 9: Export con filtro condominio
// ============================================================
echo "\n=== TEST: Export con filtro ===\n";
$r = get('/admin/export.php?tipo=unita&condominio_id=' . $condId);
test('Export filtrato per condominio', $r['code'] == 200);

// ============================================================
// TEST 10: Menu aggiornato
// ============================================================
echo "\n=== TEST: Menu Strumenti ===\n";
$r = get('/admin/dashboard.php');
test('Menu contiene Import', stripos($r['body'], 'import.php') !== false);
test('Menu contiene Export', stripos($r['body'], 'export.php') !== false);
test('Menu contiene Backup', stripos($r['body'], 'backup.php') !== false);
test('Menu contiene Strumenti', stripos($r['body'], 'Strumenti') !== false);

// ============================================================
// TEST 11: Accesso protetto
// ============================================================
echo "\n=== TEST: Accesso protetto ===\n";
$cookieAnon = '/tmp/test_e2e_14_anon.txt';
@unlink($cookieAnon);
$ch = curl_init($base . '/admin/import.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => $cookieAnon, CURLOPT_COOKIEJAR => $cookieAnon]);
$body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
test('Import protetto da login', $code == 403 || stripos($body, 'login') !== false || stripos($body, 'Accesso negato') !== false);

$ch = curl_init($base . '/admin/backup.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => $cookieAnon, CURLOPT_COOKIEJAR => $cookieAnon]);
$body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
test('Backup protetto da login', $code == 403 || stripos($body, 'login') !== false || stripos($body, 'Accesso negato') !== false);

// ============================================================
// RISULTATI
// ============================================================
echo "\n========================================\n";
echo "RISULTATI: $passed/$total passati, $failed falliti\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
