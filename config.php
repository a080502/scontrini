<?php
/**
 * File di configurazione per sviluppo locale
 * Questo file contiene le credenziali reali del database
 */

// Configurazione Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'scontrini_db_TEST');
define('DB_USER', 'denis');
define('DB_PASS', 'a080502');

// Configurazione Applicazione
define('APP_NAME', 'Sistema Gestione Scontrini');
define('APP_VERSION', '2.0.0');
define('SITE_NAME', 'Gestione Scontrini Fiscali'); // Compatibilità con vecchio codice

// Configurazione Sicurezza
define('SESSION_TIMEOUT', 3600); // 1 ora in secondi
define('SESSION_LIFETIME', 1800); // 30 minuti - Compatibilità
define('SESSION_SECRET', 'dev-secret-key-123');

// Configurazione Locale
define('LOCALE', 'it_IT');

// Debug (metti false in produzione)
define('DEBUG_MODE', true);

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
