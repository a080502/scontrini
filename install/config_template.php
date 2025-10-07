<?php
/**
 * File di configurazione - Template per l'installazione
 * Questo file viene usato come base durante l'installazione automatica
 */

// Configurazione Database - Verrà sostituita durante l'installazione
define('DB_HOST', 'localhost');
define('DB_NAME', 'scontrini_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Configurazione Applicazione
define('APP_NAME', 'Sistema Gestione Scontrini');
define('APP_VERSION', '2.0.0');
define('SITE_NAME', 'Gestione Scontrini Fiscali');

// Configurazione Sicurezza - Verrà generata durante l'installazione
define('SESSION_TIMEOUT', 3600); // 1 ora in secondi
define('SESSION_LIFETIME', 1800); // 30 minuti
define('SESSION_SECRET', 'your-secret-key-here');

// Configurazione Locale
define('LOCALE', 'it_IT');

// Debug (false in produzione)
define('DEBUG_MODE', false);

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