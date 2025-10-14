<?php
// Test caricamento bootstrap
header('Content-Type: application/json');

try {
    echo json_encode(['step' => 'Iniziando test bootstrap']);
    
    // Test 1: carica config
    require_once '../config.php';
    echo json_encode(['step' => 'Config caricato']);
    
    // Test 2: carica bootstrap
    require_once '../includes/bootstrap.php';
    echo json_encode(['step' => 'Bootstrap caricato']);
    
    // Test 3: verifica classi
    $classes = [
        'Database' => class_exists('Database'),
        'Auth' => class_exists('Auth'),
        'Utils' => class_exists('Utils')
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Bootstrap test completato',
        'classes' => $classes,
        'constants' => [
            'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'non definito',
            'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'non definito'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>