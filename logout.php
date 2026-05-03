<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Auth.php';

logout_user();
// Dopo il logout torniamo alla home
header('Location: /');
exit;
