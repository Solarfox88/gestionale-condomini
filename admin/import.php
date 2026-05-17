<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_admin();
global $pdo;

$msg = ''; $errors = []; $imported = 0; $importType = '';

$templates = [
    'condomini' => ['nome*','codice_fiscale','indirizzo','comune','provincia','cap','iban','banca','email','pec','note'],
    'unita' => ['condominio_id*','scala','piano','interno','subalterno','descrizione','mq','millesimi_proprieta','millesimi_scale','millesimi_ascensore','millesimi_riscaldamento'],
    'persone' => ['nome*','cognome*','ragione_sociale','tipo(persona/azienda/fornitore)*','codice_fiscale','partita_iva','email','pec','telefono','indirizzo','note'],
    'associazioni' => ['unita_id*','persona_id*','ruolo(proprietario/comproprietario/inquilino/usufruttuario/delegato/altro)*','percentuale*','data_inizio(YYYY-MM-DD)','data_fine(YYYY-MM-DD)'],
    'rate' => ['condominio_id*','esercizio_id','unita_id*','descrizione*','importo*','scadenza(YYYY-MM-DD)*','stato(da_pagare/parziale/pagata/scaduta)'],
    'movimenti' => ['condominio_id*','esercizio_id','tipo(entrata/uscita)*','descrizione*','importo*','data_movimento(YYYY-MM-DD)*','metodo_pagamento','riferimento','categoria_id'],
];

// Download template
if (isset($_GET['template']) && isset($templates[$_GET['template']])) {
    $tpl = $_GET['template'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_' . $tpl . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, $templates[$tpl]);
    fclose($out);
    exit;
}

// Process import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $importType = $_POST['tipo'] ?? '';
    if (!isset($templates[$importType])) {
        $msg = 'Tipo import non valido.';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Errore caricamento file.';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            $msg = 'Impossibile leggere il file.';
        } else {
            $header = fgetcsv($handle);
            if (!$header) { $msg = 'File vuoto.'; fclose($handle); }
            else {
                $header = array_map('trim', $header);
                $header = array_map(function($h) { return preg_replace('/^\x{FEFF}/u', '', $h); }, $header);
                $lineNum = 1;

                $pdo->beginTransaction();
                try {
                    while (($row = fgetcsv($handle)) !== false) {
                        $lineNum++;
                        if (count($row) === 1 && trim($row[0]) === '') continue;
                        $data = [];
                        foreach ($header as $i => $col) {
                            $cleanCol = preg_replace('/\(.*\)/', '', $col);
                            $cleanCol = rtrim($cleanCol, '*');
                            $data[$cleanCol] = isset($row[$i]) ? trim($row[$i]) : '';
                        }

                        $rowErrors = validateRow($importType, $data, $lineNum, $pdo);
                        if ($rowErrors) {
                            $errors = array_merge($errors, $rowErrors);
                            continue;
                        }

                        insertRow($importType, $data, $pdo);
                        $imported++;
                    }

                    if ($errors && $imported === 0) {
                        $pdo->rollBack();
                        $msg = "Import fallito: nessuna riga valida.";
                    } elseif ($errors) {
                        $pdo->commit();
                        $msg = "$imported righe importate con " . count($errors) . " errori.";
                    } else {
                        $pdo->commit();
                        $msg = "$imported righe importate con successo.";
                    }
                    audit_log('import', $importType, null, "Import CSV $importType: $imported righe");
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $msg = "Errore database: " . $e->getMessage();
                }
                fclose($handle);
            }
        }
    }
}

function validateRow(string $type, array $data, int $line, PDO $pdo): array {
    $errs = [];
    switch ($type) {
        case 'condomini':
            if (empty($data['nome'])) $errs[] = "Riga $line: nome obbligatorio";
            break;
        case 'unita':
            if (empty($data['condominio_id'])) $errs[] = "Riga $line: condominio_id obbligatorio";
            elseif (!$pdo->query("SELECT id FROM condomini WHERE id=".(int)$data['condominio_id'])->fetchColumn()) $errs[] = "Riga $line: condominio_id {$data['condominio_id']} non esiste";
            break;
        case 'persone':
            if (empty($data['nome']) && empty($data['ragione_sociale'])) $errs[] = "Riga $line: nome o ragione_sociale obbligatorio";
            if (empty($data['tipo'])) $data['tipo'] = 'persona';
            if (!in_array($data['tipo'], ['persona','azienda','fornitore'])) $errs[] = "Riga $line: tipo deve essere persona/azienda/fornitore";
            break;
        case 'associazioni':
            if (empty($data['unita_id'])) $errs[] = "Riga $line: unita_id obbligatorio";
            if (empty($data['persona_id'])) $errs[] = "Riga $line: persona_id obbligatorio";
            break;
        case 'rate':
            if (empty($data['condominio_id'])) $errs[] = "Riga $line: condominio_id obbligatorio";
            if (empty($data['unita_id'])) $errs[] = "Riga $line: unita_id obbligatorio";
            if (empty($data['importo'])) $errs[] = "Riga $line: importo obbligatorio";
            if (empty($data['scadenza'])) $errs[] = "Riga $line: scadenza obbligatoria";
            break;
        case 'movimenti':
            if (empty($data['condominio_id'])) $errs[] = "Riga $line: condominio_id obbligatorio";
            if (empty($data['importo'])) $errs[] = "Riga $line: importo obbligatorio";
            if (empty($data['data_movimento'])) $errs[] = "Riga $line: data_movimento obbligatoria";
            if (!empty($data['tipo']) && !in_array($data['tipo'], ['entrata','uscita'])) $errs[] = "Riga $line: tipo deve essere entrata/uscita";
            break;
    }
    return $errs;
}

function insertRow(string $type, array $data, PDO $pdo): void {
    switch ($type) {
        case 'condomini':
            $stmt = $pdo->prepare("INSERT INTO condomini (nome, codice_fiscale, indirizzo, comune, provincia, cap, iban, banca, email, pec, note) VALUES (:nome, :cf, :ind, :com, :prov, :cap, :iban, :banca, :email, :pec, :note)");
            $stmt->execute(['nome'=>$data['nome'], 'cf'=>$data['codice_fiscale']??null, 'ind'=>$data['indirizzo']??null, 'com'=>$data['comune']??null, 'prov'=>$data['provincia']??null, 'cap'=>$data['cap']??null, 'iban'=>$data['iban']??null, 'banca'=>$data['banca']??null, 'email'=>$data['email']??null, 'pec'=>$data['pec']??null, 'note'=>$data['note']??null]);
            break;
        case 'unita':
            $stmt = $pdo->prepare("INSERT INTO unita_immobiliari (condominio_id, scala, piano, interno, subalterno, descrizione, mq, millesimi_proprieta, millesimi_scale, millesimi_ascensore, millesimi_riscaldamento) VALUES (:cid, :sc, :pi, :int, :sub, :desc, :mq, :mp, :ms, :ma, :mr)");
            $stmt->execute(['cid'=>(int)$data['condominio_id'], 'sc'=>$data['scala']??null, 'pi'=>$data['piano']??null, 'int'=>$data['interno']??null, 'sub'=>$data['subalterno']??null, 'desc'=>$data['descrizione']??null, 'mq'=>$data['mq']?:null, 'mp'=>$data['millesimi_proprieta']?:0, 'ms'=>$data['millesimi_scale']?:0, 'ma'=>$data['millesimi_ascensore']?:0, 'mr'=>$data['millesimi_riscaldamento']?:0]);
            break;
        case 'persone':
            $tipo = $data['tipo'] ?: 'persona';
            $stmt = $pdo->prepare("INSERT INTO persone (nome, cognome, ragione_sociale, tipo, codice_fiscale, partita_iva, email, pec, telefono, indirizzo, note) VALUES (:nome, :cog, :rs, :tipo, :cf, :piva, :email, :pec, :tel, :ind, :note)");
            $stmt->execute(['nome'=>$data['nome']??'', 'cog'=>$data['cognome']??'', 'rs'=>$data['ragione_sociale']??null, 'tipo'=>$tipo, 'cf'=>$data['codice_fiscale']??null, 'piva'=>$data['partita_iva']??null, 'email'=>$data['email']??null, 'pec'=>$data['pec']??null, 'tel'=>$data['telefono']??null, 'ind'=>$data['indirizzo']??null, 'note'=>$data['note']??null]);
            break;
        case 'associazioni':
            $stmt = $pdo->prepare("INSERT INTO unita_persone (unita_id, persona_id, ruolo, percentuale, data_inizio, data_fine) VALUES (:uid, :pid, :ruolo, :perc, :di, :df)");
            $stmt->execute(['uid'=>(int)$data['unita_id'], 'pid'=>(int)$data['persona_id'], 'ruolo'=>$data['ruolo']?:'proprietario', 'perc'=>$data['percentuale']?:100, 'di'=>$data['data_inizio']?:null, 'df'=>$data['data_fine']?:null]);
            break;
        case 'rate':
            $stmt = $pdo->prepare("INSERT INTO rate (condominio_id, esercizio_id, unita_id, descrizione, importo, scadenza, stato) VALUES (:cid, :eid, :uid, :desc, :imp, :scad, :stato)");
            $stmt->execute(['cid'=>(int)$data['condominio_id'], 'eid'=>$data['esercizio_id']?:null, 'uid'=>(int)$data['unita_id'], 'desc'=>$data['descrizione']??'', 'imp'=>(float)$data['importo'], 'scad'=>$data['scadenza'], 'stato'=>$data['stato']?:'da_pagare']);
            break;
        case 'movimenti':
            $stmt = $pdo->prepare("INSERT INTO movimenti (condominio_id, esercizio_id, tipo, descrizione, importo, data_movimento, metodo_pagamento, riferimento, categoria_id) VALUES (:cid, :eid, :tipo, :desc, :imp, :data, :met, :rif, :cat)");
            $stmt->execute(['cid'=>(int)$data['condominio_id'], 'eid'=>$data['esercizio_id']?:null, 'tipo'=>$data['tipo']?:'uscita', 'desc'=>$data['descrizione']??'', 'imp'=>(float)$data['importo'], 'data'=>$data['data_movimento'], 'met'=>$data['metodo_pagamento']??null, 'rif'=>$data['riferimento']??null, 'cat'=>$data['categoria_id']?:null]);
            break;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Import dati da CSV</h2>
<p class="text-muted">Importa dati nel gestionale da file CSV. Scarica prima il template per il formato corretto.</p>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $errors ? 'warning' : ($imported ? 'success' : 'danger'); ?>">
    <?php echo h($msg); ?>
    <?php if ($imported > 0): ?><br><strong><?php echo $imported; ?> righe importate.</strong><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong>Errori rilevati (<?php echo count($errors); ?>):</strong>
    <ul class="mb-0"><?php foreach (array_slice($errors, 0, 20) as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?>
    <?php if (count($errors) > 20): ?><li>... e altri <?php echo count($errors) - 20; ?> errori</li><?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row mb-4">
<div class="col-md-6">
    <div class="card">
    <div class="card-header"><strong>Template CSV scaricabili</strong></div>
    <div class="card-body">
    <table class="table table-sm mb-0">
    <?php foreach ($templates as $key => $cols): ?>
    <tr>
        <td><?php echo ucfirst(h($key)); ?></td>
        <td><a href="?template=<?php echo h($key); ?>" class="btn btn-sm btn-outline-primary">Scarica template</a></td>
        <td><small class="text-muted"><?php echo count($cols); ?> colonne</small></td>
    </tr>
    <?php endforeach; ?>
    </table>
    </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card">
    <div class="card-header"><strong>Carica file CSV</strong></div>
    <div class="card-body">
    <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label">Tipo di import*</label>
            <select name="tipo" class="form-select" required>
                <option value="">-- Seleziona --</option>
                <?php foreach ($templates as $key => $cols): ?>
                <option value="<?php echo h($key); ?>" <?php echo $importType === $key ? 'selected' : ''; ?>><?php echo ucfirst(h($key)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">File CSV*</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
            <small class="text-muted">UTF-8 con separatore virgola. Prima riga = intestazioni.</small>
        </div>
        <button class="btn btn-primary">Importa</button>
    </form>
    </div>
    </div>
</div>
</div>

<div class="alert alert-info">
    <strong>Note sull'import:</strong>
    <ul class="mb-0">
        <li>I campi con * sono obbligatori</li>
        <li>I valori tra parentesi indicano i valori ammessi (es. tipo: persona/azienda/fornitore)</li>
        <li>Le date devono essere in formato YYYY-MM-DD</li>
        <li>L'import e transazionale: se ci sono solo errori, nessuna riga viene importata</li>
        <li>Se ci sono righe valide e righe con errori, le valide vengono importate e gli errori segnalati</li>
    </ul>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
