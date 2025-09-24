<?php
// Configurazione database
define('DB_HOST', 'localhost');
define('DB_NAME', 'scontrini_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurazione sessioni
define('SESSION_LIFETIME', 1800); // 30 minuti
define('SESSION_SECRET', 'dev-secret-key-123');

// Configurazione generale
define('SITE_NAME', 'Gestione Scontrini Fiscali');
define('LOCALE', 'it_IT');

// Timezone
date_default_timezone_set('Europe/Rome');

// Avvia sessione se non già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Imposta locale italiano per formatting numeri
setlocale(LC_MONETARY, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
setlocale(LC_NUMERIC, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
?>