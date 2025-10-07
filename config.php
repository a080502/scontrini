<?php
/**
 * File di configurazione - Generato automaticamente durante l'installazione
 */

// Configurazione Database
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'scontrini_db');
define('DB_USER', 'denis');
define('DB_PASS', 'denis');

// Configurazione Applicazione
define('APP_NAME', 'Sistema Gestione Scontrini');
define('APP_VERSION', '2.0.0');
define('SITE_NAME', 'Gestione Scontrini Fiscali');

// Configurazione Sicurezza
define('SESSION_TIMEOUT', 3600);
define('SESSION_LIFETIME', 1800);
define('SESSION_SECRET', 'f8de858f35e914ed2e7c3ce102be8d58');

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

// Imposta locale italiano
setlocale(LC_MONETARY, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
setlocale(LC_NUMERIC, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
?>