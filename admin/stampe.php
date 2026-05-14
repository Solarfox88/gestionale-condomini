<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Assemblee.php';
require_once __DIR__ . '/../app/Esercizi.php';
require_once __DIR__ . '/../app/Riparti.php';
require_login();
require_admin();

$condomini = get_condomini();
$assemblee = Assemblee::all();

include __DIR__ . '/../includes/header.php';
?>
<h2>Stampe e Report</h2>
<p class="text-muted">Seleziona il tipo di stampa da generare. Ogni documento si apre in formato HTML print-ready: usa il pulsante "Stampa / PDF" per stampare o salvare come PDF dal browser.</p>

<div class="row g-4 mt-2">

<!-- Scheda Condominio -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Scheda Condominio</h5>
        <p class="card-text text-muted">Anagrafica, unita, intestatari, riepilogo contabile.</p>
        <form method="get" action="stampa-condominio.php">
            <div class="mb-2">
                <select name="id" class="form-select form-select-sm" required>
                    <option value="">Seleziona condominio...</option>
                    <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo h($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary" target="_blank">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Convocazione Assemblea -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Convocazione Assemblea</h5>
        <p class="card-text text-muted">Lettera convocazione con OdG e destinatari.</p>
        <form method="get" action="stampa-convocazione.php">
            <div class="mb-2">
                <select name="id" class="form-select form-select-sm" required>
                    <option value="">Seleziona assemblea...</option>
                    <?php foreach ($assemblee as $a): ?>
                    <option value="<?php echo $a['id']; ?>"><?php echo h($a['titolo']); ?> (<?php echo date('d/m/Y', strtotime($a['prima_convocazione'])); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Verbale Assemblea -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Verbale Assemblea</h5>
        <p class="card-text text-muted">Verbale con presenze, deleghe, millesimi e OdG.</p>
        <form method="get" action="stampa-verbale.php">
            <div class="mb-2">
                <select name="id" class="form-select form-select-sm" required>
                    <option value="">Seleziona assemblea...</option>
                    <?php foreach ($assemblee as $a): ?>
                    <option value="<?php echo $a['id']; ?>"><?php echo h($a['titolo']); ?> (<?php echo date('d/m/Y', strtotime($a['prima_convocazione'])); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Bilancio Preventivo -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Bilancio Preventivo</h5>
        <p class="card-text text-muted">Stampa preventivo con voci, entrate, uscite e saldo.</p>
        <form method="get" action="stampa-preventivo.php">
            <div class="mb-2">
                <select name="id" class="form-select form-select-sm" required id="sel-preventivo">
                    <option value="">Seleziona preventivo...</option>
                    <?php
                    global $pdo;
                    $stmtPrev = $pdo->query("SELECT pv.id, pv.titolo, e.nome AS es_nome FROM preventivi pv JOIN esercizi e ON e.id = pv.esercizio_id ORDER BY pv.id DESC");
                    foreach ($stmtPrev->fetchAll(PDO::FETCH_ASSOC) as $pv): ?>
                    <option value="<?php echo $pv['id']; ?>"><?php echo h($pv['titolo']); ?> (<?php echo h($pv['es_nome']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Bilancio Consuntivo -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Bilancio Consuntivo</h5>
        <p class="card-text text-muted">Confronto preventivo/consuntivo con scostamenti e quadrature.</p>
        <form method="get" action="stampa-consuntivo.php">
            <div class="mb-2">
                <select name="esercizio_id" class="form-select form-select-sm" required>
                    <option value="">Seleziona esercizio...</option>
                    <?php
                    $stmtEs = $pdo->query("SELECT id, nome FROM esercizi ORDER BY data_inizio DESC");
                    foreach ($stmtEs->fetchAll(PDO::FETCH_ASSOC) as $es): ?>
                    <option value="<?php echo $es['id']; ?>"><?php echo h($es['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Riparto Spese -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Riparto Spese</h5>
        <p class="card-text text-muted">Tabella riparto con millesimi, quote, rettifiche ed esclusioni.</p>
        <form method="get" action="stampa-riparto.php">
            <div class="mb-2">
                <select name="id" class="form-select form-select-sm" required>
                    <option value="">Seleziona riparto...</option>
                    <?php
                    $stmtRip = $pdo->query("SELECT rip.id, rip.descrizione, c.nome AS cond_nome FROM riparti rip JOIN condomini c ON c.id = rip.condominio_id ORDER BY rip.id DESC");
                    foreach ($stmtRip->fetchAll(PDO::FETCH_ASSOC) as $rip): ?>
                    <option value="<?php echo $rip['id']; ?>"><?php echo h($rip['descrizione']); ?> — <?php echo h($rip['cond_nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Sollecito Morosita -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Sollecito Morosita</h5>
        <p class="card-text text-muted">Lettera sollecito pagamento con dettaglio rate scadute e IBAN.</p>
        <form method="get" action="stampa-sollecito.php">
            <div class="mb-2">
                <select name="condominio_id" class="form-select form-select-sm" required id="sel-sollecito-cond">
                    <option value="">Seleziona condominio...</option>
                    <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo h($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <input type="number" name="unita_id" class="form-control form-control-sm" placeholder="ID unita (opz., per singola unita)">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Elenco Morosi -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Elenco Morosi</h5>
        <p class="card-text text-muted">Elenco completo rate scadute con residui e giorni di ritardo.</p>
        <form method="get" action="stampa-morosi.php">
            <div class="mb-2">
                <select name="condominio_id" class="form-select form-select-sm">
                    <option value="">Tutti i condomini</option>
                    <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo h($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Situazione Contabile Unita -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Situazione Contabile</h5>
        <p class="card-text text-muted">Estratto conto unita: rate, pagamenti, intestatari, saldo.</p>
        <form method="get" action="stampa-situazione.php">
            <div class="mb-2">
                <input type="number" name="unita_id" class="form-control form-control-sm" placeholder="ID unita immobiliare" required>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Registro Documenti -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Registro Documenti</h5>
        <p class="card-text text-muted">Elenco documenti caricati con filtri per condominio e categoria.</p>
        <form method="get" action="stampa-documenti.php">
            <div class="mb-2">
                <select name="condominio_id" class="form-select form-select-sm">
                    <option value="">Tutti i condomini</option>
                    <?php foreach ($condomini as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo h($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Tutte le categorie</option>
                    <?php foreach (categorie_documenti() as $cat): ?>
                    <option value="<?php echo h($cat); ?>"><?php echo h(ucfirst($cat)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

<!-- Ricevuta Pagamento -->
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title">Ricevuta Pagamento</h5>
        <p class="card-text text-muted">Ricevuta singolo pagamento con dati intestatario e rata.</p>
        <form method="get" action="stampa-ricevuta.php">
            <div class="mb-2">
                <input type="number" name="id" class="form-control form-control-sm" placeholder="ID pagamento" required>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Genera</button>
        </form>
    </div>
</div>
</div>

</div><!-- /row -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
