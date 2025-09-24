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

// Configurazione Sicurezza
define('SESSION_TIMEOUT', 3600); // 1 ora in secondi

// Debug (metti false in produzione)
define('DEBUG_MODE', true);

/**
 * IMPORTANTE: 
 * Non modificare mai config.example.php con credenziali reali!
 * Usa sempre config.php per le credenziali vere.
 */
?>