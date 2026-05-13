<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Condomini.php';
require_once __DIR__ . '/../app/Documenti.php';
require_login();
require_admin();

include __DIR__ . '/../includes/header.php';

// Count condomini and documents
$condomini = get_condomini();
$numCondomini = count($condomini);
$documenti = get_documenti();
$numDocumenti = count($documenti);
?>

<h1>Dashboard Amministratore</h1>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Condomini</h5>
                <p class="card-text"><?php echo $numCondomini; ?></p>
                <a href="condomini.php" class="btn btn-primary">Gestisci condomini</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Documenti</h5>
                <p class="card-text"><?php echo $numDocumenti; ?></p>
                <a href="documenti.php" class="btn btn-primary">Gestisci documenti</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
