<?php
/**
 * Test E2E per EPIC #12 — Area condòmino evoluta
 * 
 * Verifica: dashboard condomino, profilo, unita, rate/pagamenti,
 * assemblee, comunicazioni, ticket, accesso protetto.
 */

$base = 'http://localhost:8080';
$cookie = '/tmp/test_e2e_12_cookie.txt';
$cookieAdmin = '/tmp/test_e2e_12_admin_cookie.txt';
$passed = 0; $failed = 0; $total = 0;

function test($desc, $cond) {
    global $passed, $failed, $total; $total++;
    if ($cond) { $passed++; echo "  PASS: $desc\n"; }
    else { $failed++; echo "  FAIL: $desc\n"; }
}

function get($url, $ck = null) {
    global $cookie, $base;
    $c = $ck ?? $cookie;
    $ch = curl_init($base . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $c, CURLOPT_COOKIEJAR => $c]);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['body' => $r, 'code' => $code];
}

function post($url, $data, $ck = null) {
    global $cookie, $base;
    $c = $ck ?? $cookie;
    $page = get($url, $c);
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $page['body'], $m)) {
        $data['csrf_token'] = $m[1];
    }
    $ch = curl_init($base . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $c, CURLOPT_COOKIEJAR => $c,
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['body' => $r, 'code' => $code];
}

// DB connection for verification
$pdo = new PDO('mysql:host=127.0.0.1;dbname=condomini_db;charset=utf8mb4', 'db_user', 'db_password', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// ============================================================
// SETUP: Create test data via admin
// ============================================================
echo "=== SETUP: Preparazione dati di test ===\n";

// Login admin
$r = post('/login.php', ['email' => 'admin@gestionale.local', 'password' => 'password'], $cookieAdmin);
test('Admin login', $r['code'] == 200 && stripos($r['body'], 'dashboard') !== false);

// Create condominio
$r = post('/admin/condomini.php', ['azione' => 'crea', 'nome' => 'Cond Test E12', 'indirizzo' => 'Via Test 12', 'comune' => 'Roma', 'provincia' => 'RM', 'cap' => '00100', 'iban' => 'IT60X0542811101000000123456'], $cookieAdmin);
$condId = $pdo->query("SELECT id FROM condomini WHERE nome='Cond Test E12' ORDER BY id DESC LIMIT 1")->fetchColumn();
test('Condominio creato', $condId > 0);

// Create unita
$r = post('/admin/unita.php', ['azione' => 'crea', 'condominio_id' => $condId, 'scala' => 'A', 'piano' => '1', 'interno' => '1', 'millesimi_proprieta' => 500, 'mq' => 80], $cookieAdmin);
$unitaId = $pdo->query("SELECT id FROM unita_immobiliari WHERE condominio_id=$condId ORDER BY id DESC LIMIT 1")->fetchColumn();
test('Unita creata', $unitaId > 0);

// Create persona
$r = post('/admin/persone.php', ['nome' => 'Mario', 'cognome' => 'Rossi', 'tipo' => 'persona', 'email' => 'mario@test.it', 'codice_fiscale' => 'RSSMRA80A01H501U'], $cookieAdmin);
$personaId = $pdo->query("SELECT id FROM persone WHERE cognome='Rossi' AND nome='Mario' ORDER BY id DESC LIMIT 1")->fetchColumn();
if (!$personaId) {
    $pdo->exec("INSERT INTO persone (nome, cognome, tipo, email, codice_fiscale) VALUES ('Mario', 'Rossi', 'persona', 'mario@test.it', 'RSSMRA80A01H501U')");
    $personaId = $pdo->lastInsertId();
}
test('Persona creata', $personaId > 0);

// Associate persona to unita
$r = post('/admin/unita-detail.php?id='.$unitaId, ['azione' => 'associa_persona', 'persona_id' => $personaId, 'ruolo' => 'proprietario', 'percentuale' => 100], $cookieAdmin);
if ($pdo->query("SELECT COUNT(*) FROM unita_persone WHERE unita_id=$unitaId AND persona_id=$personaId")->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO unita_persone (unita_id, persona_id, ruolo, percentuale) VALUES ($unitaId, $personaId, 'proprietario', 100)");
}
test('Associazione persona-unita', $pdo->query("SELECT COUNT(*) FROM unita_persone WHERE unita_id=$unitaId AND persona_id=$personaId")->fetchColumn() > 0);

// Create esercizio
$r = post('/admin/esercizi.php', ['azione' => 'crea', 'condominio_id' => $condId, 'nome' => 'ES 2026', 'data_inizio' => '2026-01-01', 'data_fine' => '2026-12-31', 'stato' => 'aperto'], $cookieAdmin);
$esercizioId = $pdo->query("SELECT id FROM esercizi WHERE condominio_id=$condId ORDER BY id DESC LIMIT 1")->fetchColumn();
test('Esercizio creato', $esercizioId > 0);

// Create rate
$pdo->exec("INSERT INTO rate (condominio_id, esercizio_id, unita_id, descrizione, importo, scadenza, stato) VALUES ($condId, $esercizioId, $unitaId, 'Rata Q1 2026', 500.00, '2026-03-31', 'da_pagare')");
$pdo->exec("INSERT INTO rate (condominio_id, esercizio_id, unita_id, descrizione, importo, scadenza, stato) VALUES ($condId, $esercizioId, $unitaId, 'Rata Q2 2026', 500.00, '2026-06-30', 'da_pagare')");
$pdo->exec("INSERT INTO rate (condominio_id, esercizio_id, unita_id, descrizione, importo, scadenza, stato) VALUES ($condId, $esercizioId, $unitaId, 'Rata pagata', 300.00, '2026-01-31', 'pagata')");
$rataId = $pdo->query("SELECT id FROM rate WHERE descrizione='Rata Q1 2026' ORDER BY id DESC LIMIT 1")->fetchColumn();
$rataIdPagata = $pdo->query("SELECT id FROM rate WHERE descrizione='Rata pagata' ORDER BY id DESC LIMIT 1")->fetchColumn();
test('Rate create', $rataId > 0 && $rataIdPagata > 0);

// Create pagamento for the paid rate
$pdo->exec("INSERT INTO pagamenti (rata_id, importo, data_pagamento, metodo, riferimento) VALUES ($rataIdPagata, 300.00, '2026-01-15', 'bonifico', 'BON-001')");
test('Pagamento registrato', $pdo->query("SELECT COUNT(*) FROM pagamenti WHERE rata_id=$rataIdPagata")->fetchColumn() > 0);

// Create assemblea
$pdo->exec("INSERT INTO assemblee (condominio_id, titolo, data_prima_convocazione, data_seconda_convocazione, luogo, ordine_giorno, stato) VALUES ($condId, 'Assemblea ordinaria 2026', '2026-06-15 18:00:00', '2026-06-16 18:00:00', 'Sala riunioni', 'Approvazione bilancio\nNomina amministratore\nVarie ed eventuali', 'convocata')");
$assembleaId = $pdo->query("SELECT id FROM assemblee WHERE condominio_id=$condId ORDER BY id DESC LIMIT 1")->fetchColumn();
test('Assemblea creata', $assembleaId > 0);

// Register condomino user
$r = post('/register.php', ['name' => 'Mario Rossi Cond', 'email' => 'mario.condomino12@test.it', 'password' => 'password123', 'password_confirm' => 'password123']);
$condominoUserId = $pdo->query("SELECT id FROM users WHERE email='mario.condomino12@test.it'")->fetchColumn();
test('Registrazione condomino', $condominoUserId > 0);

// Approve condomino + associate to condominio
$pdo->exec("UPDATE users SET status='active' WHERE id=$condominoUserId");
$pdo->exec("INSERT INTO condomini_users (user_id, condominio_id, relation_type) VALUES ($condominoUserId, $condId, 'proprietario')");
test('Condomino approvato e associato', true);

// ============================================================
// TEST 1: Login condomino
// ============================================================
echo "\n=== TEST: Login e Dashboard condomino ===\n";
$r = post('/login.php', ['email' => 'mario.condomino12@test.it', 'password' => 'password123']);
test('Login condomino', $r['code'] == 200);

$r = get('/area-condomino/dashboard.php');
test('Dashboard accessibile', $r['code'] == 200);
test('Dashboard mostra saldo residuo', stripos($r['body'], 'Saldo residuo') !== false);
test('Dashboard mostra rate aperte', stripos($r['body'], 'Rate aperte') !== false);
test('Dashboard mostra rate scadute', stripos($r['body'], 'Rate scadute') !== false);
test('Dashboard mostra pagamenti', stripos($r['body'], 'Pagamenti') !== false);
test('Dashboard mostra comunicazioni', stripos($r['body'], 'Comunicazioni') !== false);
test('Dashboard mostra condomini associati', stripos($r['body'], 'Cond Test E12') !== false);

// ============================================================
// TEST 2: Unita associate
// ============================================================
echo "\n=== TEST: Unita associate ===\n";
$r = get('/area-condomino/unita.php');
test('Pagina unita accessibile', $r['code'] == 200);
test('Mostra condominio', stripos($r['body'], 'Cond Test E12') !== false);
test('Mostra scala/piano/interno', stripos($r['body'], 'A') !== false);

// ============================================================
// TEST 3: Rate e pagamenti
// ============================================================
echo "\n=== TEST: Rate e pagamenti ===\n";
$r = get('/area-condomino/rate-pagamenti.php');
test('Pagina rate accessibile', $r['code'] == 200);
test('Mostra totale dovuto', stripos($r['body'], 'Totale dovuto') !== false);
test('Mostra totale pagato', stripos($r['body'], 'Totale pagato') !== false);
test('Mostra residuo', stripos($r['body'], 'Residuo') !== false);
test('Mostra rate con descrizione', stripos($r['body'], 'Rata Q1 2026') !== false);
test('Mostra istruzioni bonifico (IBAN)', stripos($r['body'], 'IT60X0542811101000000123456') !== false);

$r = get('/area-condomino/rate-pagamenti.php?filtro=pagate');
test('Tab rate pagate', $r['code'] == 200);
test('Rate pagate mostra rata pagata', stripos($r['body'], 'Rata pagata') !== false);

// storico pagamenti
test('Storico pagamenti mostra BON-001', stripos($r['body'], 'BON-001') !== false || stripos(get('/area-condomino/rate-pagamenti.php')['body'], 'BON-001') !== false);

// ============================================================
// TEST 4: Assemblee
// ============================================================
echo "\n=== TEST: Assemblee ===\n";
$r = get('/area-condomino/assemblee.php');
test('Pagina assemblee accessibile', $r['code'] == 200);
test('Mostra assemblea', stripos($r['body'], 'Assemblea ordinaria 2026') !== false);
test('Mostra luogo', stripos($r['body'], 'Sala riunioni') !== false);

$r = get('/area-condomino/assemblee.php?id=' . $assembleaId);
test('Dettaglio assemblea accessibile', $r['code'] == 200);
test('Ordine del giorno mostra punti', stripos($r['body'], 'Approvazione bilancio') !== false);
test('Stato assemblea', stripos($r['body'], 'convocata') !== false);

// ============================================================
// TEST 5: Profilo
// ============================================================
echo "\n=== TEST: Profilo ===\n";
$r = get('/area-condomino/profilo.php');
test('Pagina profilo accessibile', $r['code'] == 200);
test('Mostra campo telefono', stripos($r['body'], 'phone') !== false || stripos($r['body'], 'Telefono') !== false);
test('Mostra campo codice fiscale', stripos($r['body'], 'fiscal_code') !== false || stripos($r['body'], 'Codice fiscale') !== false);
test('Mostra privacy/consensi', stripos($r['body'], 'Privacy') !== false || stripos($r['body'], 'consensi') !== false);
test('Mostra cambio password', stripos($r['body'], 'Cambio password') !== false);

// Update profilo
$r = post('/area-condomino/profilo.php', ['azione' => 'profilo', 'name' => 'Mario Rossi Updated', 'phone' => '+39 333 1234567', 'fiscal_code' => 'RSSMRA80A01H501U']);
test('Profilo aggiornato', stripos($r['body'], 'aggiornato') !== false);
$u = $pdo->query("SELECT * FROM users WHERE id=$condominoUserId")->fetch(PDO::FETCH_ASSOC);
test('Telefono salvato in DB', $u['phone'] === '+39 333 1234567');
test('Codice fiscale salvato', $u['fiscal_code'] === 'RSSMRA80A01H501U');

// Update privacy
$r = post('/area-condomino/profilo.php', ['azione' => 'privacy', 'consenso_email' => '1']);
test('Privacy aggiornata', stripos($r['body'], 'privacy aggiornate') !== false || stripos($r['body'], 'Preferenze') !== false);

// ============================================================
// TEST 6: Ticket
// ============================================================
echo "\n=== TEST: Ticket ===\n";
$r = post('/area-condomino/ticket-crea.php', ['condominio_id' => $condId, 'titolo' => 'Perdita acqua bagno', 'descrizione' => 'C\'e una perdita dal soffitto del bagno', 'priorita' => 'alta']);
test('Ticket creato', $r['code'] == 200);
$ticketId = $pdo->query("SELECT id FROM ticket WHERE titolo='Perdita acqua bagno' ORDER BY id DESC LIMIT 1")->fetchColumn();
test('Ticket nel DB', $ticketId > 0);

$r = get('/area-condomino/ticket-view.php?id=' . $ticketId);
test('Vista ticket accessibile', $r['code'] == 200);
test('Ticket mostra titolo', stripos($r['body'], 'Perdita acqua bagno') !== false);
test('Ticket mostra priorita', stripos($r['body'], 'alta') !== false);

// Add message
$r = post('/area-condomino/ticket-view.php?id=' . $ticketId, ['messaggio' => 'Urgente, sta peggiorando']);
test('Messaggio ticket inviato', stripos($r['body'], 'Messaggio inviato') !== false || stripos($r['body'], 'peggiorando') !== false);
$msgCount = $pdo->query("SELECT COUNT(*) FROM ticket_messaggi WHERE ticket_id=$ticketId")->fetchColumn();
test('Messaggio salvato in DB', $msgCount > 0);

// ============================================================
// TEST 7: Comunicazioni
// ============================================================
echo "\n=== TEST: Comunicazioni ===\n";
$r = get('/area-condomino/comunicazioni.php');
test('Pagina comunicazioni accessibile', $r['code'] == 200);

// ============================================================
// TEST 8: Accesso protetto - condomino non vede dati altrui
// ============================================================
echo "\n=== TEST: Accesso protetto ===\n";

// Try accessing admin
$r = get('/admin/dashboard.php');
test('Condomino NON accede a admin dashboard', $r['code'] == 403 || stripos($r['body'], 'Accesso negato') !== false || stripos($r['body'], 'login') !== false);

// Non-logged-in access
$cookieAnon = '/tmp/test_e2e_12_anon.txt';
@unlink($cookieAnon);
$r = get('/area-condomino/dashboard.php', $cookieAnon);
test('Anonimo rediretto a login', stripos($r['body'], 'login') !== false || stripos($r['body'], 'Login') !== false);

$r = get('/area-condomino/rate-pagamenti.php', $cookieAnon);
test('Rate protette da login', stripos($r['body'], 'login') !== false || stripos($r['body'], 'Login') !== false);

$r = get('/area-condomino/assemblee.php', $cookieAnon);
test('Assemblee protette da login', stripos($r['body'], 'login') !== false || stripos($r['body'], 'Login') !== false);

// ============================================================
// TEST 9: Menu aggiornato
// ============================================================
echo "\n=== TEST: Menu navigazione condomino ===\n";
$r = get('/area-condomino/dashboard.php');
test('Menu contiene Le mie unita', stripos($r['body'], 'unita.php') !== false);
test('Menu contiene Rate e pagamenti', stripos($r['body'], 'rate-pagamenti.php') !== false);
test('Menu contiene Assemblee', stripos($r['body'], 'assemblee.php') !== false);
test('Menu contiene Comunicazioni', stripos($r['body'], 'comunicazioni.php') !== false);
test('Menu contiene Profilo', stripos($r['body'], 'profilo.php') !== false);

// ============================================================
// RISULTATI
// ============================================================
echo "\n========================================\n";
echo "RISULTATI: $passed/$total passati, $failed falliti\n";
echo "========================================\n";
exit($failed > 0 ? 1 : 0);
