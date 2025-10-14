<?php
// Test API senza autenticazione per vedere se il problema è nell'Auth
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test 1: Include config diretto
    require_once '../config.php';
    echo "Config OK\n";
    
    // Test 2: Include database
    require_once '../includes/database.php';
    echo "Database class OK\n";
    
    // Test 3: Prova a creare istanza database
    $db = Database::getInstance();
    echo "Database instance OK\n";
    
    // Test 4: Query semplice
    $result = $db->fetchAll("SELECT COUNT(*) as count FROM scontrini LIMIT 1");
    echo "Query OK\n";
    
    echo json_encode([
        'success' => true,
        'message' => 'Tutti i test OK',
        'scontrini_count' => $result[0]['count'] ?? 0
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Fatal Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>