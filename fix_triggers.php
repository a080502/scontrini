<?php
/**
 * Script rapido per rimuovere trigger problematici
 * Usa le credenziali dal config.php se esiste
 */

echo "🧹 RIMOZIONE TRIGGER PROBLEMATICI\n";
echo "===============================\n\n";

// Prova a usare config.php se esiste
if (file_exists('config.php')) {
    echo "📁 Caricamento configurazione da config.php...\n";
    require_once 'config.php';
    
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $database = defined('DB_NAME') ? DB_NAME : '';
    $username = defined('DB_USER') ? DB_USER : '';
    $password = defined('DB_PASS') ? DB_PASS : '';
    
    if (empty($database) || empty($username)) {
        echo "❌ Configurazione database incompleta in config.php\n";
        echo "💡 Usa clean_database.php per inserire credenziali manualmente\n";
        exit(1);
    }
    
    echo "✅ Configurazione caricata: $username@$host/$database\n\n";
} else {
    echo "❌ File config.php non trovato\n";
    echo "💡 Esegui prima l'installazione o usa clean_database.php\n";
    exit(1);
}

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connessione database riuscita\n\n";
} catch (PDOException $e) {
    echo "❌ Errore connessione: " . $e->getMessage() . "\n";
    exit(1);
}

// Rimuovi trigger problematici
echo "🗑️ Rimozione trigger con sintassi DELIMITER...\n";
$triggers_to_remove = [
    'tr_scontrini_insert',
    'tr_scontrini_update'
];

$removed = 0;
foreach ($triggers_to_remove as $trigger) {
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS `$trigger`");
        echo "✅ Trigger '$trigger' rimosso\n";
        $removed++;
    } catch (PDOException $e) {
        echo "⚠️ Trigger '$trigger': " . $e->getMessage() . "\n";
    }
}

echo "\n📋 Verifica trigger rimanenti...\n";
try {
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll();
    if (empty($triggers)) {
        echo "✅ Nessun trigger presente - database pulito\n";
    } else {
        echo "ℹ️ Trigger rimanenti:\n";
        foreach ($triggers as $trigger) {
            echo "   - " . $trigger['Trigger'] . " su " . $trigger['Table'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "⚠️ Errore controllo trigger: " . $e->getMessage() . "\n";
}

echo "\n🎯 RISULTATO\n";
echo "============\n";
if ($removed > 0) {
    echo "✅ $removed trigger problematici rimossi\n";
    echo "✅ Il database è ora compatibile con l'installer\n";
    echo "✅ Puoi procedere con una nuova installazione\n\n";
    
    echo "💡 PROSSIMI PASSI:\n";
    echo "1. Rimuovi installation.lock se presente\n";
    echo "2. Accedi al sistema via web per reinstallare\n";
    echo "3. Oppure usa: php install/cli_installer.php\n\n";
    
    echo "⚠️ Per installare trigger opzionali in futuro:\n";
    echo "   mysql -u $username -p $database < install/triggers_optional.sql\n";
} else {
    echo "ℹ️ Nessun trigger problematico trovato\n";
    echo "ℹ️ Il database sembra già pulito\n";
}

?>