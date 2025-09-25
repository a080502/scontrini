<?php
// Test API super semplice per diagnosticare il problema
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'API base funziona',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>