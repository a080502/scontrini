<?php
/**
 * Helper per la verifica dello stato di installazione
 * Questo file deve essere incluso PRIMA di bootstrap.php in ogni pagina
 */

/**
 * Verifica se il sistema è installato e reindirizza se necessario
 */
function checkInstallationStatus($auto_redirect = true) {
    $installation_completed = file_exists(__DIR__ . '/../installation.lock');
    $current_script = basename($_SERVER['PHP_SELF']);
    
    // Se il sistema non è installato
    if (!$installation_completed && $auto_redirect) {
        // Pagine che possono essere accessibili senza installazione
        $allowed_pages = ['login.php', 'install.php'];
        
        if (!in_array($current_script, $allowed_pages)) {
            // Reindirizza solo se non abbiamo già inviato output
            if (!headers_sent()) {
                header('Location: login.php');
                exit();
            }
        }
    }
    
    return $installation_completed;
}

/**
 * Include bootstrap.php solo se il sistema è installato
 */
function requireBootstrap() {
    $installation_completed = checkInstallationStatus();
    
    if ($installation_completed) {
        require_once __DIR__ . '/bootstrap.php';
    } else {
        // Se non installato, carica solo le utilità base se esistono
        $utils_path = __DIR__ . '/utils.php';
        if (file_exists($utils_path)) {
            require_once $utils_path;
        }
    }
    
    return $installation_completed;
}