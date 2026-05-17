<?php
// intestazione HTML comune a tutte le pagine
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionale Condomini</title>
    <link rel="stylesheet" href="<?php echo url('/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo url('/'); ?>">Condomini Gestionale</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/admin/dashboard.php'); ?>">Dashboard</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Anagrafiche</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/condomini.php'); ?>">Condomini</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/unita.php'); ?>">Unita immobiliari</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/persone.php'); ?>">Persone</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/documenti.php'); ?>">Documenti</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Contabilita</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/esercizi.php'); ?>">Esercizi</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/categorie-spesa.php'); ?>">Categorie spesa</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/movimenti.php'); ?>">Movimenti</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/millesimi.php'); ?>">Millesimi</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/riparti.php'); ?>">Riparti</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/preventivi.php'); ?>">Preventivi</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/consuntivi.php'); ?>">Consuntivi</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Pagamenti</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/rate.php'); ?>">Rate</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/pagamenti.php'); ?>">Pagamenti</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/morosita.php'); ?>">Morosita</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/admin/ticket.php'); ?>">Ticket</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/admin/assemblee.php'); ?>">Assemblee</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Comunicazioni</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/comunicazioni.php'); ?>">Comunicazioni</a></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/template-comunicazioni.php'); ?>">Template</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo url('/admin/impostazioni-email.php'); ?>">Impostazioni Email</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/admin/report.php'); ?>">Report</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/admin/stampe.php'); ?>">Stampe</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/admin/utenti.php'); ?>">Utenti</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/area-condomino/dashboard.php'); ?>">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/area-condomino/unita.php'); ?>">Le mie unita</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/area-condomino/rate-pagamenti.php'); ?>">Rate e pagamenti</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/area-condomino/assemblee.php'); ?>">Assemblee</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/area-condomino/comunicazioni.php'); ?>">Comunicazioni</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo url('/area-condomino/profilo.php'); ?>">Profilo</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item"><span class="nav-link text-light"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?></span></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('/logout.php'); ?>">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('/login.php'); ?>">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('/register.php'); ?>">Registrati</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
