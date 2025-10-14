<?php
/**
 * Aggiornamento schema database - Allineamento colonne
 * 
 * Questo script aggiunge le colonne mancanti per allineare
 * il database al codice esistente.
 */

echo "🔧 ALLINEAMENTO SCHEMA DATABASE\n";
echo "==============================\n\n";

// Carica configurazione
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

// Verifica colonne attuali
echo "🔍 Verifica colonne attuali...\n";
try {
    $stmt = $pdo->query("DESCRIBE scontrini");
    $columns = array_column($stmt->fetchAll(), 'Field');
    echo "Colonne presenti: " . implode(', ', $columns) . "\n\n";
} catch (PDOException $e) {
    echo "❌ Errore verifica: " . $e->getMessage() . "\n";
    exit(1);
}

// Colonne che dovrebbero esistere secondo il codice
$expectedColumns = [
    'nome' => 'varchar(50) NOT NULL',
    'data_scontrino' => 'date NOT NULL', 
    'foto_scontrino' => 'varchar(255) DEFAULT NULL',
    'foto_mime_type' => 'varchar(100) DEFAULT NULL',
    'foto_size' => 'int(11) DEFAULT NULL',
    'gps_latitude' => 'decimal(10,8) DEFAULT NULL',
    'gps_longitude' => 'decimal(11,8) DEFAULT NULL', 
    'gps_accuracy' => 'decimal(10,2) DEFAULT NULL',
    'gps_timestamp' => 'timestamp NULL DEFAULT NULL'
];

echo "🔄 Aggiunta colonne mancanti...\n";

foreach ($expectedColumns as $columnName => $columnDef) {
    if (!in_array($columnName, $columns)) {
        try {
            $sql = "ALTER TABLE scontrini ADD COLUMN $columnName $columnDef";
            $pdo->exec($sql);
            echo "✅ Aggiunta colonna: $columnName\n";
        } catch (PDOException $e) {
            echo "❌ Errore aggiunta $columnName: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ Colonna già presente: $columnName\n";
    }
}

// Migrazione dati dalle colonne vecchie a quelle nuove (se necessario)
echo "\n📊 Migrazione dati...\n";

try {
    // Se esiste 'numero' ma non 'nome', copia i dati
    if (in_array('numero', $columns) && !in_array('nome', $columns)) {
        echo "❌ Colonna 'nome' non può essere creata - conflitto con 'numero'\n";
    } else {
        // Aggiorna i dati da numero a nome se entrambi esistono
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM scontrini WHERE nome IS NULL AND numero IS NOT NULL");
        $nullCount = $stmt->fetch()['count'];
        
        if ($nullCount > 0) {
            $pdo->exec("UPDATE scontrini SET nome = numero WHERE nome IS NULL");
            echo "✅ Migrati $nullCount valori da 'numero' a 'nome'\n";
        }
    }
    
    // Migrazione data
    if (in_array('data', $columns) && in_array('data_scontrino', $columns)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM scontrini WHERE data_scontrino IS NULL AND data IS NOT NULL");
        $nullCount = $stmt->fetch()['count'];
        
        if ($nullCount > 0) {
            $pdo->exec("UPDATE scontrini SET data_scontrino = data WHERE data_scontrino IS NULL");
            echo "✅ Migrati $nullCount valori da 'data' a 'data_scontrino'\n";
        }
    }
    
    // Migrazione foto
    if (in_array('foto', $columns) && in_array('foto_scontrino', $columns)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM scontrini WHERE foto_scontrino IS NULL AND foto IS NOT NULL");
        $nullCount = $stmt->fetch()['count'];
        
        if ($nullCount > 0) {
            $pdo->exec("UPDATE scontrini SET foto_scontrino = foto WHERE foto_scontrino IS NULL");
            echo "✅ Migrati $nullCount valori da 'foto' a 'foto_scontrino'\n";
        }
    }
    
} catch (PDOException $e) {
    echo "⚠️ Errore migrazione dati: " . $e->getMessage() . "\n";
}

echo "\n🎉 ALLINEAMENTO COMPLETATO!\n\n";

// Verifica finale
echo "📋 SCHEMA FINALE:\n";
echo "=================\n";
try {
    $stmt = $pdo->query("DESCRIBE scontrini");
    $finalColumns = $stmt->fetchAll();
    
    foreach ($finalColumns as $column) {
        $name = $column['Field'];
        $type = $column['Type'];
        echo sprintf("%-20s %s\n", $name, $type);
    }
    
} catch (PDOException $e) {
    echo "⚠️ Errore verifica finale: " . $e->getMessage() . "\n";
}

echo "\n💡 Il database ora dovrebbe essere compatibile con il codice esistente!\n";

?>