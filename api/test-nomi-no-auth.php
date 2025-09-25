<?php
// API temporanea senza autenticazione per test
error_reporting(E_ALL);
ini_set('display_errors', 0);

ob_start();

try {
    require_once '../includes/bootstrap.php';
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Errore bootstrap: ' . $e->getMessage()]);
    exit;
}

ob_clean();
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $query = $_GET['q'] ?? '';
    
    // Query semplice senza controlli di autenticazione
    if (empty($query)) {
        // Recupera i 10 nomi più utilizzati
        $nomi = $db->fetchAll("
            SELECT 
                nome,
                COUNT(*) as count_usage,
                MAX(data_scontrino) as last_used
            FROM scontrini 
            GROUP BY nome 
            ORDER BY count_usage DESC, last_used DESC
            LIMIT 10
        ");
    } else {
        // Ricerca con filtro testo
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'nomi' => []]);
            exit;
        }
        
        $nomi = $db->fetchAll("
            SELECT 
                nome,
                COUNT(*) as count_usage,
                MAX(data_scontrino) as last_used
            FROM scontrini 
            WHERE nome LIKE ?
            GROUP BY nome 
            ORDER BY count_usage DESC, last_used DESC
            LIMIT 15
        ", ["%{$query}%"]);
    }
    
    // Formatta risultati
    $suggestions = [];
    foreach ($nomi as $nome_data) {
        $suggestions[] = [
            'value' => $nome_data['nome'],
            'label' => $nome_data['nome'] . ' (' . $nome_data['count_usage'] . ' volte)',
            'count' => $nome_data['count_usage'],
            'last_used' => $nome_data['last_used']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'nomi' => $suggestions,
        'debug' => 'API senza auth - ' . count($suggestions) . ' risultati'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
exit;
?>