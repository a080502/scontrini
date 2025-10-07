<?php
// Test del parser SQL migliorato
$sql = file_get_contents('install/database_schema.sql');

// Rimuovi commenti
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Pulisci e dividi gli statements
$sql = preg_replace('/\s+/', ' ', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

echo 'Statements trovati: ' . count($statements) . PHP_EOL;
foreach ($statements as $i => $stmt) {
    if (!empty($stmt)) {
        echo 'Statement ' . ($i+1) . ': ' . substr($stmt, 0, 50) . '...' . PHP_EOL;
    }
}

echo PHP_EOL . "Test completato con successo!" . PHP_EOL;
?>