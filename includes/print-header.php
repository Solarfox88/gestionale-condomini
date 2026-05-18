<?php
// Print-ready page header. Set $printTitle, $printSubtitle, $printCondominio before including.
$printTitle = $printTitle ?? 'Documento';
$printSubtitle = $printSubtitle ?? '';
$printCondominio = $printCondominio ?? null;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($printTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo url('/assets/css/print.css'); ?>">
</head>
<body>
<div class="print-page">
    <div class="print-btn-bar no-print">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><b>Stampa / PDF</b></button>
        <button class="btn btn-outline-secondary btn-sm" onclick="history.back()">Indietro</button>
    </div>
    <div class="print-header">
        <h1><?php echo htmlspecialchars($printTitle); ?></h1>
        <?php if ($printSubtitle): ?>
        <div class="print-subtitle"><?php echo htmlspecialchars($printSubtitle); ?></div>
        <?php endif; ?>
        <?php if ($printCondominio): ?>
        <div class="print-info">
            <?php echo htmlspecialchars($printCondominio['nome']); ?>
            <?php if (!empty($printCondominio['indirizzo'])): ?>
                &mdash; <?php echo htmlspecialchars($printCondominio['indirizzo']); ?>
                <?php if (!empty($printCondominio['cap'])): ?>, <?php echo htmlspecialchars($printCondominio['cap']); ?><?php endif; ?>
                <?php if (!empty($printCondominio['comune'])): ?> <?php echo htmlspecialchars($printCondominio['comune']); ?><?php endif; ?>
                <?php if (!empty($printCondominio['provincia'])): ?> (<?php echo htmlspecialchars($printCondominio['provincia']); ?>)<?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($printCondominio['codice_fiscale'])): ?>
                <br>C.F.: <?php echo htmlspecialchars($printCondominio['codice_fiscale']); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="print-info">Data stampa: <?php echo date('d/m/Y H:i'); ?></div>
    </div>
