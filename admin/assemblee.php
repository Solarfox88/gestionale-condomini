<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_login();
require_admin();
include __DIR__ . '/../includes/header.php';
?>
<h2>Gestione Assemblee</h2>
<p>Sezione da completare. Qui potrai programmare le assemblee condominiali, gestire le presenze, le deleghe, i verbali e le votazioni.</p>
<?php include __DIR__ . '/../includes/footer.php';
