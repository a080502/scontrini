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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore server: ' . $e->getMessage()
    ]);
}
?>