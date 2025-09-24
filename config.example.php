<?php
/**
 * File di configurazione template
 * 
 * ISTRUZIONI:
 * 1. Copia questo file come 'config.php'
 * 2. Modifica i valori con le tue credenziali database
 * 3. Il file config.php sarà ignorato da git per sicurezza
 */

// Configurazione Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_database');
define('DB_USER', 'username_database');
define('DB_PASS', 'password_database');

// Configurazione Applicazione
define('APP_NAME', 'Sistema Gestione Scontrini');
define('APP_VERSION', '2.0.0');
define('SITE_NAME', 'Gestione Scontrini Fiscali'); // Compatibilità con vecchio codice

// Configurazione Sicurezza
define('SESSION_TIMEOUT', 3600); // 1 ora in secondi
define('SESSION_LIFETIME', 1800); // 30 minuti - Compatibilità
define('SESSION_SECRET', 'your-secret-key-here');

// Configurazione Locale
define('LOCALE', 'it_IT');

// Debug (metti false in produzione)
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

/**
 * IMPORTANTE: 
 * Non modificare mai config.example.php con credenziali reali!
 * Usa sempre config.php per le credenziali vere.
 */
?>
