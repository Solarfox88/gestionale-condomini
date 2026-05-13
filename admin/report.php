<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/Documenti.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Pagamenti.php';
require_login();
require_admin();

$tipo = $_GET['tipo'] ?? '';
$export = isset($_GET['export']) && $_GET['export'] === 'csv';

if ($export && $tipo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $tipo . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");

    switch ($tipo) {
        case 'condomini':
            $data = get_condomini();
            fputcsv($out, ['ID','Nome','Indirizzo','Comune','Provincia','CAP','CF','Email','Stato']);
            foreach ($data as $r) fputcsv($out, [$r['id'],$r['nome'],$r['indirizzo'] ?? '',$r['comune'] ?? '',$r['provincia'] ?? '',$r['cap'] ?? '',$r['codice_fiscale'] ?? '',$r['email'] ?? '',$r['status'] ?? '']);
            break;
        case 'unita':
            $data = get_unita();
            fputcsv($out, ['ID','Condominio','Scala','Piano','Interno','MQ','Mill.Prop.','Mill.Scale','Mill.Asc.','Mill.Risc.','Stato']);
            foreach ($data as $r) fputcsv($out, [$r['id'],$r['condominio_nome'],$r['scala'] ?? '',$r['piano'] ?? '',$r['interno'] ?? '',$r['mq'] ?? '',$r['millesimi_proprieta'],$r['millesimi_scale'],$r['millesimi_ascensore'],$r['millesimi_riscaldamento'],$r['status'] ?? '']);
            break;
        case 'persone':
            $data = get_persone();
            fputcsv($out, ['ID','Cognome','Nome','Tipo','CF','P.IVA','Email','Telefono']);
            foreach ($data as $r) fputcsv($out, [$r['id'],$r['cognome'],$r['nome'],$r['tipo'],$r['codice_fiscale'] ?? '',$r['partita_iva'] ?? '',$r['email'] ?? '',$r['telefono'] ?? '']);
            break;
        case 'movimenti':
            $data = Movimenti::all();
            fputcsv($out, ['ID','Condominio','Esercizio','Tipo','Categoria','Descrizione','Importo','Data','Metodo']);
            foreach ($data as $r) fputcsv($out, [$r['id'],$r['condominio_nome'],$r['esercizio_nome'],$r['tipo'],$r['categoria_nome'] ?? '',$r['descrizione'],$r['importo'],$r['data_movimento'],$r['metodo_pagamento'] ?? '']);
            break;
        case 'rate':
            $data = Rate::all();
            fputcsv($out, ['ID','Condominio','Esercizio','Unita','Descrizione','Importo','Scadenza','Stato']);
            foreach ($data as $r) fputcsv($out, [$r['id'],$r['condominio_nome'],$r['esercizio_nome'],trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? '')),$r['descrizione'],$r['importo'],$r['scadenza'],$r['stato']]);
            break;
        case 'pagamenti':
            $data = Pagamenti::all();
            fputcsv($out, ['ID','Condominio','Rata','Unita','Persona','Importo','Data','Metodo','Riferimento']);
            foreach ($data as $r) fputcsv($out, [$r['id'],$r['condominio_nome'],$r['rata_descrizione'],trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? '')),trim(($r['persona_cognome'] ?? '').' '.($r['persona_nome'] ?? '')),$r['importo'],$r['data_pagamento'],$r['metodo'] ?? '',$r['riferimento'] ?? '']);
            break;
    }
    fclose($out);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>
<h2>Report ed esportazioni</h2>
<p>Seleziona il tipo di report da generare. Puoi esportare in CSV o stampare direttamente.</p>

<div class="row">
<?php
$reports = [
    'condomini' => ['Condomini', 'bg-primary', 'Elenco completo condomini'],
    'unita' => ['Unita immobiliari', 'bg-info', 'Elenco unita con millesimi'],
    'persone' => ['Persone', 'bg-success', 'Anagrafica persone/aziende/fornitori'],
    'movimenti' => ['Movimenti', 'bg-warning', 'Prima nota / movimenti contabili'],
    'rate' => ['Rate', 'bg-danger', 'Elenco rate con stato'],
    'pagamenti' => ['Pagamenti', 'bg-dark', 'Storico pagamenti registrati'],
];
foreach ($reports as $key => $info): ?>
<div class="col-md-4 mb-3">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><?php echo $info[0]; ?></h5>
            <p class="card-text text-muted"><?php echo $info[2]; ?></p>
            <a href="?tipo=<?php echo $key; ?>&export=csv" class="btn btn-sm btn-success">Esporta CSV</a>
            <a href="report-view.php?tipo=<?php echo $key; ?>" class="btn btn-sm btn-outline-primary">Visualizza</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
