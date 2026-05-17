<?php
/**
 * Test E2E — EPIC #13: Multi-studio, tenant, ruoli granulari e SaaS readiness
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

$pdo = new PDO('mysql:host=127.0.0.1;dbname=condomini_db;charset=utf8mb4', 'db_user', 'db_password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== SETUP: Login admin ===\n";
$r = req('GET', '/login.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/login.php', ['email' => 'admin@gestionale.local', 'password' => 'password', 'csrf_token' => $csrf]);
assert_test(strpos($r['body'], 'Dashboard') !== false || strpos($r['body'], 'Condomini') !== false, 'Admin login');

echo "\n=== TEST: DB Schema ===\n";
$tables = ['studi', 'ruoli', 'permessi', 'ruolo_permessi', 'studio_users'];
foreach ($tables as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='condomini_db' AND table_name='$t'")->fetchColumn();
    assert_test((int)$c === 1, "Tabella $t esiste");
}

$cols = $pdo->query("SHOW COLUMNS FROM condomini LIKE 'studio_id'")->fetchAll();
assert_test(count($cols) === 1, 'Colonna studio_id su condomini');

echo "\n=== TEST: Ruoli seed ===\n";
$ruoliCount = (int)$pdo->query("SELECT COUNT(*) FROM ruoli WHERE is_system=1")->fetchColumn();
assert_test($ruoliCount >= 9, '9+ ruoli di sistema presenti');

$slugs = ['super_admin','admin_studio','collaboratore','contabile','tecnico','fornitore','condomino','inquilino','sola_lettura'];
foreach ($slugs as $slug) {
    $c = (int)$pdo->query("SELECT COUNT(*) FROM ruoli WHERE slug='$slug'")->fetchColumn();
    assert_test($c === 1, "Ruolo $slug esiste");
}

echo "\n=== TEST: Permessi seed ===\n";
$permCount = (int)$pdo->query("SELECT COUNT(*) FROM permessi")->fetchColumn();
assert_test($permCount >= 20, '20+ permessi presenti');

echo "\n=== TEST: Ruolo_permessi popolati ===\n";
$saId = (int)$pdo->query("SELECT id FROM ruoli WHERE slug='super_admin'")->fetchColumn();
$saPerms = (int)$pdo->query("SELECT COUNT(*) FROM ruolo_permessi WHERE ruolo_id=$saId")->fetchColumn();
assert_test($saPerms >= 20, 'Super admin ha 20+ permessi');

echo "\n=== TEST: Pagina Studi ===\n";
$r = req('GET', '/admin/studi.php');
assert_test($r['code'] === 200, 'Studi page HTTP 200');
assert_test(strpos($r['body'], 'Studi') !== false, 'Studi titolo presente');
assert_test(strpos($r['body'], 'Nuovo studio') !== false, 'Form creazione studio');

echo "\n=== TEST: Crea studio ===\n";
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/studi.php', [
    'csrf_token' => $csrf,
    'azione' => 'crea',
    'nome' => 'Studio Test E2E',
    'nome_amministratore' => 'Mario Rossi',
    'partita_iva' => '12345678901',
    'email' => 'studio@test.it',
    'pec' => '', 'telefono' => '0612345',
    'indirizzo' => 'Via Roma 1', 'comune' => 'Roma', 'provincia' => 'RM', 'cap' => '00100',
    'codice_fiscale' => '', 'piano' => 'base',
    'max_condomini' => '10', 'max_unita' => '100', 'max_storage_mb' => '1000'
]);
assert_test(strpos($r['body'], 'Studio creato') !== false, 'Studio creato con successo');

$studioId = (int)$pdo->query("SELECT id FROM studi WHERE nome='Studio Test E2E'")->fetchColumn();
assert_test($studioId > 0, 'Studio presente in DB');

$studio = $pdo->query("SELECT * FROM studi WHERE id=$studioId")->fetch(PDO::FETCH_ASSOC);
assert_test($studio['piano'] === 'base', 'Piano studio = base');
assert_test((int)$studio['max_condomini'] === 10, 'Max condomini = 10');

echo "\n=== TEST: Audit log studio creato ===\n";
$c = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE entity_type='studi' AND action='create'")->fetchColumn();
assert_test($c >= 1, 'Creazione studio registrata in audit');

echo "\n=== TEST: Aggiungi utente a studio ===\n";
$adminId = (int)$pdo->query("SELECT id FROM users WHERE email='admin@gestionale.local'")->fetchColumn();
$ruoloSA = (int)$pdo->query("SELECT id FROM ruoli WHERE slug='super_admin'")->fetchColumn();
$r = req('GET', '/admin/studi.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/studi.php', [
    'csrf_token' => $csrf,
    'azione' => 'aggiungi_utente',
    'studio_id' => $studioId,
    'user_id' => $adminId,
    'ruolo_id' => $ruoloSA,
]);
assert_test(strpos($r['body'], 'Utente aggiunto') !== false, 'Utente aggiunto allo studio');

$c = (int)$pdo->query("SELECT COUNT(*) FROM studio_users WHERE studio_id=$studioId AND user_id=$adminId")->fetchColumn();
assert_test($c === 1, 'Relazione studio_users creata');

echo "\n=== TEST: Aggiorna ruolo utente nello studio ===\n";
$ruoloAS = (int)$pdo->query("SELECT id FROM ruoli WHERE slug='admin_studio'")->fetchColumn();
$r = req('GET', '/admin/studi.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/studi.php', [
    'csrf_token' => $csrf,
    'azione' => 'aggiorna_ruolo',
    'studio_id' => $studioId,
    'user_id' => $adminId,
    'ruolo_id' => $ruoloAS,
]);
assert_test(strpos($r['body'], 'Ruolo aggiornato') !== false, 'Ruolo utente aggiornato');
$su = $pdo->query("SELECT ruolo_id FROM studio_users WHERE studio_id=$studioId AND user_id=$adminId")->fetch(PDO::FETCH_ASSOC);
assert_test((int)$su['ruolo_id'] === $ruoloAS, 'Ruolo corretto in DB');

echo "\n=== TEST: Pagina Ruoli ===\n";
$r = req('GET', '/admin/ruoli.php');
assert_test($r['code'] === 200, 'Ruoli page HTTP 200');
assert_test(strpos($r['body'], 'Ruoli') !== false, 'Ruoli titolo presente');
assert_test(strpos($r['body'], 'super_admin') !== false, 'Slug super_admin visibile');
assert_test(strpos($r['body'], 'Nuovo ruolo') !== false, 'Form nuovo ruolo presente');

echo "\n=== TEST: Crea ruolo custom ===\n";
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/ruoli.php', [
    'csrf_token' => $csrf,
    'azione' => 'crea',
    'nome' => 'Revisore Test',
    'slug' => 'revisore_test',
    'descrizione' => 'Ruolo test per revisori',
]);
assert_test(strpos($r['body'], 'Ruolo creato') !== false, 'Ruolo custom creato');
$customId = (int)$pdo->query("SELECT id FROM ruoli WHERE slug='revisore_test'")->fetchColumn();
assert_test($customId > 0, 'Ruolo custom in DB');

echo "\n=== TEST: Modifica permessi ruolo ===\n";
$perm1 = (int)$pdo->query("SELECT id FROM permessi WHERE modulo='condomini' AND azione='lettura'")->fetchColumn();
$perm2 = (int)$pdo->query("SELECT id FROM permessi WHERE modulo='documenti' AND azione='lettura'")->fetchColumn();
$r = req('GET', '/admin/ruoli.php?edit=' . $customId);
$csrf = get_csrf($r['body']);
assert_test(strpos($r['body'], 'Permessi') !== false, 'Matrice permessi visibile');

$r = req('POST', '/admin/ruoli.php', [
    'csrf_token' => $csrf,
    'azione' => 'permessi',
    'ruolo_id' => $customId,
    'permessi' => [$perm1, $perm2],
]);
assert_test(strpos($r['body'], 'Permessi aggiornati') !== false, 'Permessi salvati');
$rpCount = (int)$pdo->query("SELECT COUNT(*) FROM ruolo_permessi WHERE ruolo_id=$customId")->fetchColumn();
assert_test($rpCount === 2, '2 permessi assegnati al ruolo');

echo "\n=== TEST: Elimina ruolo custom ===\n";
$r = req('GET', '/admin/ruoli.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/ruoli.php', [
    'csrf_token' => $csrf,
    'azione' => 'elimina',
    'ruolo_id' => $customId,
]);
assert_test(strpos($r['body'], 'Ruolo eliminato') !== false, 'Ruolo custom eliminato');

echo "\n=== TEST: Ruoli sistema non eliminabili ===\n";
$r = req('GET', '/admin/ruoli.php');
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/ruoli.php', [
    'csrf_token' => $csrf,
    'azione' => 'elimina',
    'ruolo_id' => $saId,
]);
assert_test(strpos($r['body'], 'Impossibile') !== false, 'Ruoli sistema protetti');
$exists = (int)$pdo->query("SELECT COUNT(*) FROM ruoli WHERE id=$saId")->fetchColumn();
assert_test($exists === 1, 'Ruolo super_admin ancora presente');

echo "\n=== TEST: Funzioni permesso ===\n";
// Test user_can via direct DB check (admin legacy role always returns true)
// We'll check the function logic indirectly
$has = (int)$pdo->query("SELECT COUNT(*) FROM ruolo_permessi rp JOIN permessi p ON p.id=rp.permesso_id WHERE rp.ruolo_id=$ruoloAS AND p.modulo='condomini' AND p.azione='lettura'")->fetchColumn();
assert_test($has >= 1, 'admin_studio ha permesso condomini.lettura');

$hasStudi = (int)$pdo->query("SELECT COUNT(*) FROM ruolo_permessi rp JOIN permessi p ON p.id=rp.permesso_id WHERE rp.ruolo_id=$ruoloAS AND p.modulo='studi' AND p.azione='scrittura'")->fetchColumn();
assert_test($hasStudi === 0, 'admin_studio NON ha permesso studi.scrittura');

echo "\n=== TEST: Menu Amministrazione ===\n";
$r = req('GET', '/admin/dashboard.php');
assert_test(strpos($r['body'], 'Amministrazione') !== false, 'Dropdown Amministrazione nel menu');
assert_test(strpos($r['body'], 'studi.php') !== false, 'Link Studi nel menu');
assert_test(strpos($r['body'], 'ruoli.php') !== false, 'Link Ruoli nel menu');
assert_test(strpos($r['body'], 'audit-log.php') !== false, 'Link Audit Log nel menu');

echo "\n=== TEST: Accesso protetto studi ===\n";
$savedCookie = $cookie;
$cookie = '';
$r = req('GET', '/admin/studi.php');
assert_test($r['code'] === 403 || strpos($r['body'], 'negato') !== false || strpos($r['body'], 'login') !== false, 'Studi protetto da login');
$cookie = $savedCookie;

echo "\n=== TEST: Accesso protetto ruoli ===\n";
$cookie = '';
$r = req('GET', '/admin/ruoli.php');
assert_test($r['code'] === 403 || strpos($r['body'], 'negato') !== false || strpos($r['body'], 'login') !== false, 'Ruoli protetto da login');
$cookie = $savedCookie;

echo "\n=== TEST: SaaS readiness - piani e limiti ===\n";
$studio = $pdo->query("SELECT * FROM studi WHERE id=$studioId")->fetch(PDO::FETCH_ASSOC);
assert_test(in_array($studio['piano'], ['free','base','pro','enterprise']), 'Piano valido');
assert_test(in_array($studio['abbonamento_stato'], ['attivo','scaduto','sospeso','trial']), 'Stato abbonamento valido');
assert_test((int)$studio['max_condomini'] > 0, 'max_condomini impostato');
assert_test((int)$studio['max_unita'] > 0, 'max_unita impostato');
assert_test((int)$studio['max_storage_mb'] > 0, 'max_storage_mb impostato');

echo "\n=== TEST: Studio visualizza utilizzo ===\n";
$r = req('GET', '/admin/studi.php');
assert_test(strpos($r['body'], 'Studio Test E2E') !== false, 'Studio visibile nella lista');
assert_test(strpos($r['body'], 'Mario Rossi') !== false, 'Nome amministratore visibile');
assert_test(strpos($r['body'], 'base') !== false, 'Piano base visibile');

echo "\n=== TEST: Rimuovi utente da studio ===\n";
$csrf = get_csrf($r['body']);
$r = req('POST', '/admin/studi.php', [
    'csrf_token' => $csrf,
    'azione' => 'rimuovi_utente',
    'studio_id' => $studioId,
    'user_id' => $adminId,
]);
assert_test(strpos($r['body'], 'Utente rimosso') !== false, 'Utente rimosso dallo studio');
$c = (int)$pdo->query("SELECT COUNT(*) FROM studio_users WHERE studio_id=$studioId AND user_id=$adminId")->fetchColumn();
assert_test($c === 0, 'Relazione studio_users rimossa');

// Cleanup
$pdo->exec("DELETE FROM studio_users WHERE studio_id = $studioId");
$pdo->exec("DELETE FROM studi WHERE id = $studioId");
$pdo->exec("DELETE FROM ruoli WHERE slug = 'revisore_test'");

echo "\n========================================\n";
echo "RISULTATI: $passed/$" . ($passed + $failed) . " passati, $failed falliti\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
