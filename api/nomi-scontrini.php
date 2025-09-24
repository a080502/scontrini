<?php
require_once '../includes/bootstrap.php';

header('Content-Type: application/json');

// Verifica autenticazione
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Ottieni nomi unici degli scontrini per autocomplete
    $nomi = $db->fetchAll("
        SELECT DISTINCT nome 
        FROM scontrini 
        ORDER BY nome ASC
    ");
    
    $nomi_array = array_column($nomi, 'nome');
    
    echo json_encode([
        'success' => true,
        'nomi' => $nomi_array
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore server: ' . $e->getMessage()
    ]);
}
?>