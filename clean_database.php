<?php
/**
 * Script di pulizia database - Rimuove trigger problematici e reinstalla schema pulito
 */

echo "🧹 PULIZIA DATABASE E REINSTALLAZIONE SCHEMA\n";
echo "===========================================\n\n";

// Chiedi le credenziali database
echo "Inserisci le credenziali del database da pulire:\n";
echo "Host [localhost]: ";
$host = trim(fgets(STDIN));
if (empty($host)) $host = 'localhost';

echo "Database: ";
$database = trim(fgets(STDIN));

echo "Username: ";
$username = trim(fgets(STDIN));

echo "Password: ";
$password = trim(fgets(STDIN));

echo "\n🔍 Connessione al database...\n";

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

// 1. Rimuovi trigger esistenti
echo "🗑️ Rimozione trigger problematici...\n";
$triggers_to_remove = ['tr_scontrini_insert', 'tr_scontrini_update'];

foreach ($triggers_to_remove as $trigger) {
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS `$trigger`");
        echo "✅ Trigger '$trigger' rimosso\n";
    } catch (PDOException $e) {
        echo "⚠️ Trigger '$trigger': " . $e->getMessage() . "\n";
    }
}

echo "\n📋 Verifica trigger rimanenti...\n";
try {
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll();
    if (empty($triggers)) {
        echo "✅ Nessun trigger presente\n";
    } else {
        echo "⚠️ Trigger presenti:\n";
        foreach ($triggers as $trigger) {
            echo "   - " . $trigger['Trigger'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "⚠️ Errore controllo trigger: " . $e->getMessage() . "\n";
}

echo "\n🔄 Reinstallazione schema pulito...\n";

// 2. Reinstalla schema pulito
$schema_file = 'install/database_schema.sql';
if (!file_exists($schema_file)) {
    echo "❌ File schema non trovato: $schema_file\n";
    exit(1);
}

$sql = file_get_contents($schema_file);

// Applica il nostro parser pulito
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
$sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
$sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
$sql = preg_replace('/\$\$/i', ';', $sql);
$sql = preg_replace('/^(START TRANSACTION|COMMIT);?\s*$/m', '', $sql);

$statements = array_filter(array_map('trim', explode(';', $sql)));

echo "📊 Statements da eseguire: " . count($statements) . "\n\n";

$pdo->beginTransaction();

try {
    foreach ($statements as $i => $statement) {
        if (!empty($statement)) {
            echo "Eseguendo statement " . ($i+1) . "... ";
            $pdo->exec($statement);
            echo "✅\n";
        }
    }
    
    $pdo->commit();
    echo "\n🎉 Schema reinstallato con successo!\n";
    
} catch (PDOException $e) {
    $pdo->rollback();
    echo "❌ Errore: " . $e->getMessage() . "\n";
    echo "Rollback eseguito.\n";
    exit(1);
}

echo "\n✅ PULIZIA COMPLETATA\n";
echo "Il database è ora pulito e pronto per l'uso.\n";
echo "Non ci sono più trigger problematici.\n\n";

echo "💡 Per installare i trigger opzionali in futuro:\n";
echo "   mysql -u $username -p $database < install/triggers_optional.sql\n";

?>