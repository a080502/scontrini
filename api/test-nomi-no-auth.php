<?php
// API temporanea senza autenticazione per test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Inizio test...\n";

try {
    echo "1. Caricamento config...\n";
    require_once '../config.php';
    echo "2. Config OK\n";
    
    echo "3. Caricamento database...\n";
    require_once '../includes/database.php';
    echo "4. Database class OK\n";
    
    echo "5. Caricamento auth...\n";
    require_once '../includes/auth.php';
    echo "6. Auth class OK\n";
    
    echo "7. Caricamento utils...\n";
    require_once '../includes/utils.php';
    echo "8. Utils class OK\n";
    
    echo "9. Test bootstrap completo...\n";
    require_once '../includes/bootstrap.php';
    echo "10. Bootstrap OK\n";
    
} catch (Exception $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Riga: " . $e->getLine() . "\n";
    exit;
} catch (Error $e) {
    echo "ERRORE FATALE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Riga: " . $e->getLine() . "\n";
    exit;
}

// Se arriviamo qui, proviamo il database
try {
    echo "11. Test istanza database...\n";
    $db = Database::getInstance();
    echo "12. Database instance OK\n";
    
    // Query molto semplice
    echo "13. Test query SELECT...\n";
    $result = $db->fetchAll("SELECT numero FROM scontrini LIMIT 3");
    echo "14. Query OK - trovati " . count($result) . " risultati\n";
    
    foreach ($result as $row) {
        echo "Nome: " . $row['nome'] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERRORE DATABASE: " . $e->getMessage() . "\n";
    exit;
}

echo "TUTTI I TEST COMPLETATI CON SUCCESSO!\n";
?>