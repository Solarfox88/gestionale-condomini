<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_admin();
global $pdo;

$tipo = $_GET['tipo'] ?? '';
$condFilter = isset($_GET['condominio_id']) ? (int)$_GET['condominio_id'] : 0;

$exportTypes = [
    'condomini' => 'Condomini',
    'unita' => 'Unita immobiliari',
    'persone' => 'Persone',
    'associazioni' => 'Associazioni unita/persone',
    'movimenti' => 'Movimenti',
    'rate' => 'Rate',
    'pagamenti' => 'Pagamenti',
    'morosita' => 'Morosita (rate scadute)',
    'documenti' => 'Documenti (metadata)',
];

if ($tipo && isset($exportTypes[$tipo])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_' . $tipo . '_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    switch ($tipo) {
        case 'condomini':
            fputcsv($out, ['id','nome','codice_fiscale','indirizzo','comune','provincia','cap','iban','banca','email','pec','note','status']);
            $rows = $pdo->query("SELECT id,nome,codice_fiscale,indirizzo,comune,provincia,cap,iban,banca,email,pec,note,status FROM condomini ORDER BY nome")->fetchAll(PDO::FETCH_NUM);
            foreach ($rows as $r) fputcsv($out, $r);
            break;

        case 'unita':
            fputcsv($out, ['id','condominio_id','condominio_nome','scala','piano','interno','subalterno','descrizione','mq','millesimi_proprieta','millesimi_scale','millesimi_ascensore','millesimi_riscaldamento']);
            $q = "SELECT ui.id, ui.condominio_id, c.nome, ui.scala, ui.piano, ui.interno, ui.subalterno, ui.descrizione, ui.mq, ui.millesimi_proprieta, ui.millesimi_scale, ui.millesimi_ascensore, ui.millesimi_riscaldamento FROM unita_immobiliari ui JOIN condomini c ON c.id=ui.condominio_id";
            if ($condFilter) $q .= " WHERE ui.condominio_id=$condFilter";
            $q .= " ORDER BY c.nome, ui.scala, ui.piano, ui.interno";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'persone':
            fputcsv($out, ['id','nome','cognome','ragione_sociale','tipo','codice_fiscale','partita_iva','email','pec','telefono','indirizzo','note']);
            foreach ($pdo->query("SELECT id,nome,cognome,ragione_sociale,tipo,codice_fiscale,partita_iva,email,pec,telefono,indirizzo,note FROM persone ORDER BY cognome,nome")->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'associazioni':
            fputcsv($out, ['id','unita_id','condominio_nome','scala','piano','interno','persona_id','persona_nome','persona_cognome','ruolo','percentuale','data_inizio','data_fine']);
            $q = "SELECT up.id, up.unita_id, c.nome, ui.scala, ui.piano, ui.interno, up.persona_id, p.nome, p.cognome, up.ruolo, up.percentuale, up.data_inizio, up.data_fine FROM unita_persone up JOIN unita_immobiliari ui ON ui.id=up.unita_id JOIN condomini c ON c.id=ui.condominio_id JOIN persone p ON p.id=up.persona_id";
            if ($condFilter) $q .= " WHERE ui.condominio_id=$condFilter";
            $q .= " ORDER BY c.nome, ui.scala, up.persona_id";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'movimenti':
            fputcsv($out, ['id','condominio_nome','esercizio_nome','tipo','descrizione','importo','data_movimento','metodo_pagamento','riferimento','categoria_nome']);
            $q = "SELECT m.id, c.nome, e.nome, m.tipo, m.descrizione, m.importo, m.data_movimento, m.metodo_pagamento, m.riferimento, COALESCE(cs.nome,'') FROM movimenti m JOIN condomini c ON c.id=m.condominio_id LEFT JOIN esercizi e ON e.id=m.esercizio_id LEFT JOIN categorie_spesa cs ON cs.id=m.categoria_id";
            if ($condFilter) $q .= " WHERE m.condominio_id=$condFilter";
            $q .= " ORDER BY m.data_movimento DESC";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'rate':
            fputcsv($out, ['id','condominio_nome','esercizio_nome','unita_scala','unita_piano','unita_interno','descrizione','importo','scadenza','stato']);
            $q = "SELECT r.id, c.nome, COALESCE(e.nome,''), ui.scala, ui.piano, ui.interno, r.descrizione, r.importo, r.scadenza, r.stato FROM rate r JOIN condomini c ON c.id=r.condominio_id LEFT JOIN esercizi e ON e.id=r.esercizio_id JOIN unita_immobiliari ui ON ui.id=r.unita_id";
            if ($condFilter) $q .= " WHERE r.condominio_id=$condFilter";
            $q .= " ORDER BY r.scadenza DESC";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'pagamenti':
            fputcsv($out, ['id','rata_id','rata_descrizione','condominio_nome','importo','data_pagamento','metodo','riferimento','note']);
            $q = "SELECT pg.id, pg.rata_id, r.descrizione, c.nome, pg.importo, pg.data_pagamento, pg.metodo, pg.riferimento, pg.note FROM pagamenti pg JOIN rate r ON r.id=pg.rata_id JOIN condomini c ON c.id=r.condominio_id";
            if ($condFilter) $q .= " WHERE r.condominio_id=$condFilter";
            $q .= " ORDER BY pg.data_pagamento DESC";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'morosita':
            fputcsv($out, ['condominio','unita','persona','rata','importo','pagato','residuo','scadenza','giorni_ritardo']);
            $q = "SELECT c.nome, CONCAT(ui.scala,' ',ui.piano,' ',ui.interno), CONCAT(p.cognome,' ',p.nome), r.descrizione, r.importo, COALESCE((SELECT SUM(importo) FROM pagamenti WHERE rata_id=r.id),0), r.importo - COALESCE((SELECT SUM(importo) FROM pagamenti WHERE rata_id=r.id),0), r.scadenza, DATEDIFF(CURDATE(), r.scadenza) FROM rate r JOIN condomini c ON c.id=r.condominio_id JOIN unita_immobiliari ui ON ui.id=r.unita_id LEFT JOIN unita_persone up ON up.unita_id=ui.id AND up.ruolo='proprietario' LEFT JOIN persone p ON p.id=up.persona_id WHERE r.stato='scaduta'";
            if ($condFilter) $q .= " AND r.condominio_id=$condFilter";
            $q .= " ORDER BY r.scadenza ASC";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;

        case 'documenti':
            fputcsv($out, ['id','condominio_nome','titolo','descrizione','categoria','visibility','file_originale','mime_type','utente_upload','data_upload']);
            $q = "SELECT d.id, c.nome, d.titolo, d.descrizione, d.categoria, d.visibility, d.original_name, d.mime_type, u.name, d.created_at FROM documenti d LEFT JOIN condomini c ON c.id=d.condominio_id LEFT JOIN users u ON u.id=d.uploaded_by";
            if ($condFilter) $q .= " WHERE d.condominio_id=$condFilter";
            $q .= " ORDER BY d.created_at DESC";
            foreach ($pdo->query($q)->fetchAll(PDO::FETCH_NUM) as $r) fputcsv($out, $r);
            break;
    }
    fclose($out);
    audit_log('export', $tipo, null, "Export CSV $tipo" . ($condFilter ? " cond=$condFilter" : ''));
    exit;
}

$condomini = $pdo->query("SELECT id, nome FROM condomini ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<h2>Export dati</h2>
<p class="text-muted">Esporta i dati del gestionale in formato CSV compatibile con Excel/LibreOffice.</p>

<div class="row mb-3">
<div class="col-md-4">
    <label class="form-label">Filtro condominio (opzionale)</label>
    <select id="condFilter" class="form-select">
        <option value="">Tutti i condomini</option>
        <?php foreach ($condomini as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['nome']); ?></option><?php endforeach; ?>
    </select>
</div>
</div>

<div class="row">
<?php foreach ($exportTypes as $key => $label): ?>
<div class="col-md-4 mb-3">
    <div class="card h-100">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo h($label); ?></h5>
            <div class="mt-auto">
                <a href="#" class="btn btn-success export-btn" data-tipo="<?php echo h($key); ?>">Esporta CSV</a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.export-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const tipo = this.dataset.tipo;
        const cond = document.getElementById('condFilter').value;
        let url = '?tipo=' + tipo;
        if (cond) url += '&condominio_id=' + cond;
        window.location.href = url;
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
