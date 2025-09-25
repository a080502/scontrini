<?php
// Cattura tutti gli errori e previeni output HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Buffer di output per catturare eventuali errori
ob_start();

try {
    require_once '../includes/bootstrap.php';
} catch (Exception $e) {
    // Pulisci buffer e restituisci errore JSON
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Errore caricamento bootstrap: ' . $e->getMessage()]);
    exit;
}

// Pulisci eventuali output non desiderati e imposta header JSON
ob_clean();
header('Content-Type: application/json');

// Debug: verifica se le classi sono caricate
if (!class_exists('Auth')) {
    echo json_encode(['success' => false, 'error' => 'Classe Auth non trovata']);
    exit;
}

if (!class_exists('Database')) {
    echo json_encode(['success' => false, 'error' => 'Classe Database non trovata']);
    exit;
}

// Verifica autenticazione
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato', 'debug' => 'Utente non autenticato']);
    exit;
}

try {
    $db = Database::getInstance();
    $current_user = Auth::getCurrentUser();
    $query = $_GET['q'] ?? '';
    
    // Se non c'è query di ricerca, ritorna nomi più utilizzati
    if (empty($query)) {
        // Prepara filtri per permessi utente
        $where_conditions = [];
        $params = [];
        
        if (Auth::isAdmin()) {
            // Admin vede tutti i nomi
        } elseif (Auth::isResponsabile()) {
            // Responsabile vede solo nomi della sua filiale
            $where_conditions[] = "filiale_id = ?";
            $params[] = $current_user['filiale_id'];
        } else {
            // Utente normale vede solo i suoi nomi
            $where_conditions[] = "utente_id = ?";
            $params[] = $current_user['id'];
        }
        
        $where_clause = empty($where_conditions) ? "1=1" : implode(" AND ", $where_conditions);
        
        // Recupera i 10 nomi più utilizzati
        $nomi = $db->fetchAll("
            SELECT 
                nome,
                COUNT(*) as count_usage,
                MAX(data_scontrino) as last_used
            FROM scontrini 
            WHERE {$where_clause}
            GROUP BY nome 
            ORDER BY count_usage DESC, last_used DESC
            LIMIT 10
        ", $params);
        
    } else {
        // Ricerca con filtro testo
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'nomi' => []]);
            exit;
        }
        
        // Prepara filtri per permessi utente + ricerca
        $where_conditions = [];
        $params = [];
        
        if (Auth::isAdmin()) {
            // Admin vede tutti i nomi
        } elseif (Auth::isResponsabile()) {
            // Responsabile vede solo nomi della sua filiale
            $where_conditions[] = "filiale_id = ?";
            $params[] = $current_user['filiale_id'];
        } else {
            // Utente normale vede solo i suoi nomi
            $where_conditions[] = "utente_id = ?";
            $params[] = $current_user['id'];
        }
        
        // Aggiungi filtro di ricerca
        $where_conditions[] = "nome LIKE ?";
        $params[] = "%{$query}%";
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Recupera nomi che corrispondono alla ricerca
        $nomi = $db->fetchAll("
            SELECT 
                nome,
                COUNT(*) as count_usage,
                MAX(data_scontrino) as last_used
            FROM scontrini 
            WHERE {$where_clause}
            GROUP BY nome 
            ORDER BY count_usage DESC, last_used DESC
            LIMIT 15
        ", $params);
    }
    
    // Formatta risultati per l'autocomplete
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
        'nomi' => $suggestions
    ]);
    
} catch (Exception $e) {
    // Assicurati che non ci sia output HTML prima dell'errore JSON
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore server: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Error $e) {
    // Cattura anche errori fatali PHP
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore PHP: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

// Assicurati che non ci siano output aggiuntivi
exit;
?>