<?php
// API di debug per testare l'autocomplete senza autenticazione
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Dati di esempio per il test
$sample_data = [
    ['value' => 'Pizza Margherita', 'label' => 'Pizza Margherita (usato 15 volte)', 'count' => 15],
    ['value' => 'Pasta Carbonara', 'label' => 'Pasta Carbonara (usato 12 volte)', 'count' => 12],
    ['value' => 'Caffè Espresso', 'label' => 'Caffè Espresso (usato 25 volte)', 'count' => 25],
    ['value' => 'Gelato Vaniglia', 'label' => 'Gelato Vaniglia (usato 8 volte)', 'count' => 8],
    ['value' => 'Birra Media', 'label' => 'Birra Media (usato 20 volte)', 'count' => 20],
    ['value' => 'Panino Prosciutto', 'label' => 'Panino Prosciutto (usato 18 volte)', 'count' => 18],
    ['value' => 'Coca Cola', 'label' => 'Coca Cola (usato 30 volte)', 'count' => 30],
    ['value' => 'Acqua Naturale', 'label' => 'Acqua Naturale (usato 22 volte)', 'count' => 22],
    ['value' => 'Vino Rosso', 'label' => 'Vino Rosso (usato 10 volte)', 'count' => 10],
    ['value' => 'Cornetto Crema', 'label' => 'Cornetto Crema (usato 14 volte)', 'count' => 14]
];

try {
    $query = $_GET['q'] ?? '';
    
    if (empty($query)) {
        // Restituisci i più popolari ordinati per count
        usort($sample_data, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        $results = array_slice($sample_data, 0, 10);
    } else {
        // Filtra per query
        $results = array_filter($sample_data, function($item) use ($query) {
            return stripos($item['value'], $query) !== false;
        });
        
        // Riordina array per rimuovere buchi negli indici
        $results = array_values($results);
        
        // Limita a 10 risultati
        $results = array_slice($results, 0, 10);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'query' => $query,
        'debug' => 'API di test senza autenticazione - ' . count($results) . ' risultati trovati'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => 'Errore nel debug API'
    ]);
}
?>