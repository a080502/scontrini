<?php
/**
 * Script rapido per verificare e mostrare lo schema della tabella scontrini
 */

echo "🔍 VERIFICA SCHEMA TABELLA SCONTRINI\n";
echo "===================================\n\n";

// Prova a usare config.php se esiste
if (file_exists('config.php')) {
    require_once 'config.php';
    
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $database = defined('DB_NAME') ? DB_NAME : '';
    $username = defined('DB_USER') ? DB_USER : '';
    $password = defined('DB_PASS') ? DB_PASS : '';
    
    if (empty($database) || empty($username)) {
        echo "❌ Configurazione database incompleta\n";
        exit(1);
    }
    
    echo "📁 Database: $database\n\n";
} else {
    echo "❌ File config.php non trovato\n";
    exit(1);
}

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connessione riuscita\n\n";
} catch (PDOException $e) {
    echo "❌ Errore connessione: " . $e->getMessage() . "\n";
    exit(1);
}

// Mostra schema tabella scontrini
echo "📋 SCHEMA TABELLA SCONTRINI:\n";
echo "===========================\n";

try {
    $stmt = $pdo->query("DESCRIBE scontrini");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        $name = $column['Field'];
        $type = $column['Type'];
        $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ? "DEFAULT '{$column['Default']}'" : '';
        
        echo sprintf("%-20s %-30s %-10s %s\n", $name, $type, $null, $default);
    }
    
    echo "\n";
    
    // Controlli specifici
    $columnNames = array_column($columns, 'Field');
    
    echo "🔍 CONTROLLI SCHEMA:\n";
    echo "===================\n";
    
    $oldSchema = ['incassato', 'versato', 'archiviato'];
    $newSchema = ['stato'];
    
    $hasOldCols = array_intersect($oldSchema, $columnNames);
    $hasNewCols = array_intersect($newSchema, $columnNames);
    
    echo "Colonne vecchie presenti: " . (empty($hasOldCols) ? "❌ Nessuna" : "✅ " . implode(', ', $hasOldCols)) . "\n";
    echo "Colonne nuove presenti: " . (empty($hasNewCols) ? "❌ Nessuna" : "✅ " . implode(', ', $hasNewCols)) . "\n\n";
    
    if (!empty($hasOldCols) && !empty($hasNewCols)) {
        echo "⚠️ STATO MISTO - Presenti sia colonne vecchie che nuove\n";
        echo "💡 Esegui: php migrate_schema.php\n";
    } elseif (!empty($hasOldCols) && empty($hasNewCols)) {
        echo "📊 SCHEMA VECCHIO - Solo colonne boolean\n";
        echo "💡 Esegui: php migrate_schema.php\n";
    } elseif (empty($hasOldCols) && !empty($hasNewCols)) {
        echo "✅ SCHEMA NUOVO - Solo colonna stato ENUM\n";
        echo "💡 Tutto corretto!\n";
    } else {
        echo "❌ SCHEMA SCONOSCIUTO\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}

// Test query
echo "\n📊 TEST QUERY:\n";
echo "==============\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM scontrini");
    $count = $stmt->fetch()['count'];
    echo "✅ Scontrini totali: $count\n";
    
    // Test presenza colonne
    try {
        $pdo->query("SELECT stato FROM scontrini LIMIT 1");
        echo "✅ Colonna 'stato' accessibile\n";
    } catch (PDOException $e) {
        echo "❌ Colonna 'stato' non accessibile: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->query("SELECT incassato FROM scontrini LIMIT 1");
        echo "⚠️ Colonna 'incassato' ancora presente\n";
    } catch (PDOException $e) {
        echo "✅ Colonna 'incassato' non presente (corretto)\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Errore test: " . $e->getMessage() . "\n";
}

echo "\n✨ Verifica completata\n";

?>