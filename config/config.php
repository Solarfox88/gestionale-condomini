<?php
// Configurazione principale del progetto.
// Impostare qui i parametri di connessione al database.

// Host del database (di solito localhost su servizi di hosting condiviso)
$host = 'localhost';
// Nome del database
$dbname = 'condomini_db';
// Utente del database
$user = 'db_user';
// Password del database
$password = 'db_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // In produzione è meglio non mostrare messaggi di errore dettagliati
    die('Impossibile connettersi al database: ' . $e->getMessage());
}

// Avvia la sessione se non già avviata
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definizione del percorso per la directory storage
define('STORAGE_PATH', __DIR__ . '/../storage');
