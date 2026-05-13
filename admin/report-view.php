<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Helpers.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/UnitaImmobiliari.php';
require_once __DIR__ . '/../app/Persone.php';
require_once __DIR__ . '/../app/Movimenti.php';
require_once __DIR__ . '/../app/Rate.php';
require_once __DIR__ . '/../app/Pagamenti.php';
require_login();
require_admin();

$tipo = $_GET['tipo'] ?? '';
$titolo = '';
$headers = [];
$rows = [];

switch ($tipo) {
    case 'condomini':
        $titolo = 'Report Condomini';
        $headers = ['ID','Nome','Indirizzo','Comune','Email','Stato'];
        foreach (get_condomini() as $r) $rows[] = [$r['id'], h($r['nome']), h($r['indirizzo'] ?? ''), h($r['comune'] ?? ''), h($r['email'] ?? ''), stato_badge($r['status'] ?? 'active')];
        break;
    case 'unita':
        $titolo = 'Report Unita Immobiliari';
        $headers = ['ID','Condominio','Scala','Piano','Interno','MQ','Mill.Prop.'];
        foreach (get_unita() as $r) $rows[] = [$r['id'], h($r['condominio_nome']), h($r['scala'] ?? ''), h($r['piano'] ?? ''), h($r['interno'] ?? ''), $r['mq'] ?? '-', number_format((float)$r['millesimi_proprieta'],4,',','.')];
        break;
    case 'persone':
        $titolo = 'Report Persone';
        $headers = ['ID','Cognome','Nome','Tipo','CF','Email','Telefono'];
        foreach (get_persone() as $r) $rows[] = [$r['id'], h($r['cognome']), h($r['nome']), h($r['tipo']), h($r['codice_fiscale'] ?? ''), h($r['email'] ?? ''), h($r['telefono'] ?? '')];
        break;
    case 'movimenti':
        $titolo = 'Report Movimenti';
        $headers = ['ID','Condominio','Esercizio','Tipo','Descrizione','Importo','Data'];
        foreach (Movimenti::all() as $r) $rows[] = [$r['id'], h($r['condominio_nome']), h($r['esercizio_nome']), $r['tipo']==='entrata' ? '<span class="text-success">Entrata</span>' : '<span class="text-danger">Uscita</span>', h($r['descrizione']), '&euro; '.format_euro((float)$r['importo']), h($r['data_movimento'])];
        break;
    case 'rate':
        $titolo = 'Report Rate';
        $headers = ['ID','Condominio','Unita','Descrizione','Importo','Scadenza','Stato'];
        foreach (Rate::all() as $r) $rows[] = [$r['id'], h($r['condominio_nome']), h(trim(($r['scala'] ?? '').' '.($r['piano'] ?? '').' '.($r['interno'] ?? ''))), h($r['descrizione']), '&euro; '.format_euro((float)$r['importo']), h($r['scadenza']), stato_badge($r['stato'])];
        break;
    case 'pagamenti':
        $titolo = 'Report Pagamenti';
        $headers = ['ID','Condominio','Rata','Persona','Importo','Data','Metodo'];
        foreach (Pagamenti::all() as $r) $rows[] = [$r['id'], h($r['condominio_nome']), h($r['rata_descrizione']), h(trim(($r['persona_cognome'] ?? '').' '.($r['persona_nome'] ?? ''))), '&euro; '.format_euro((float)$r['importo']), h($r['data_pagamento']), h($r['metodo'] ?? '')];
        break;
    default:
        header('Location: report.php');
        exit;
}

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($titolo); ?></h2>
    <div>
        <a href="report.php?tipo=<?php echo urlencode($tipo); ?>&export=csv" class="btn btn-success btn-sm">Esporta CSV</a>
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm">Stampa</button>
        <a href="report.php" class="btn btn-outline-secondary btn-sm">Indietro</a>
    </div>
</div>
<p>Totale record: <?php echo count($rows); ?></p>
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead><tr><?php foreach ($headers as $h): ?><th><?php echo $h; ?></th><?php endforeach; ?></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?><tr><?php foreach ($row as $cell): ?><td><?php echo $cell; ?></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody>
</table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
