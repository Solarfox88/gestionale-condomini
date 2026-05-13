<?php
// intestazione HTML comune a tutte le pagine
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionale Condomini</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">Condomini Gestionale</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/condomini.php">Condomini</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/unita.php">Unità</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/persone.php">Persone</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/documenti.php">Documenti</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/esercizi.php">Esercizi</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/movimenti.php">Movimenti</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/millesimi.php">Millesimi</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/riparti.php">Riparti</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/rate.php">Rate</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/ticket.php">Ticket</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/assemblee.php">Assemblee</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/utenti.php">Utenti</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/area-condomino/dashboard.php">Area Condòmino</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item"><a class="nav-link" href="/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="/register.php">Registrati</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
