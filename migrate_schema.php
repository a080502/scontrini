<?php
/**
 * Migrazione automatica schema database - Da colonne boolean a colonna stato ENUM
 * 
 * Questo script migra automaticamente i database esistenti dal vecchio schema
 * (incassato, versato, archiviato boolean) al nuovo schema (stato ENUM).
 */

echo "🔄 MIGRAZIONE SCHEMA DATABASE\n";
echo "============================\n\n";

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
        exit(1);
    }
    
    echo "✅ Configurazione caricata: $username@$host/$database\n\n";
} else {
    echo "❌ File config.php non trovato\n";
    echo "💡 Esegui prima l'installazione del sistema\n";
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

// 1. Verifica se la migrazione è necessaria
echo "🔍 Verifica schema attuale...\n";

try {
    $stmt = $pdo->query("DESCRIBE scontrini");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasOldSchema = in_array('incassato', $columns) && in_array('versato', $columns) && in_array('archiviato', $columns);
    $hasNewSchema = in_array('stato', $columns);
    
    if ($hasNewSchema && !$hasOldSchema) {
        echo "✅ Schema già aggiornato - nessuna migrazione necessaria\n";
        exit(0);
    }
    
    if (!$hasOldSchema) {
        echo "❌ Schema non riconosciuto - impossibile migrare\n";
        exit(1);
    }
    
    echo "📋 Schema rilevato: " . ($hasOldSchema ? "VECCHIO" : "NUOVO") . "\n";
    echo "🔄 Migrazione richiesta: " . ($hasOldSchema && !$hasNewSchema ? "SÌ" : "NO") . "\n\n";
    
} catch (PDOException $e) {
    echo "❌ Errore verifica schema: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Backup preventivo
echo "💾 Creazione backup preventivo...\n";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS scontrini_backup_" . date('Y_m_d_H_i_s') . " AS SELECT * FROM scontrini");
    echo "✅ Backup creato\n\n";
} catch (PDOException $e) {
    echo "⚠️ Errore backup: " . $e->getMessage() . "\n";
    echo "💡 Procedo comunque...\n\n";
}

// 3. Inizio migrazione
echo "🚀 Inizio migrazione schema...\n";

$pdo->beginTransaction();

try {
    // Aggiungi colonna stato se non esiste
    if (!$hasNewSchema) {
        echo "1️⃣ Aggiunta colonna 'stato'...\n";
        $pdo->exec("ALTER TABLE scontrini ADD COLUMN stato ENUM('attivo','incassato','versato','archiviato') DEFAULT 'attivo' AFTER utente_id");
        echo "✅ Colonna 'stato' aggiunta\n";
    }
    
    // Migra i dati esistenti
    echo "2️⃣ Migrazione dati esistenti...\n";
    
    // Aggiorna lo stato basandosi sui valori boolean esistenti
    $updates = [
        "UPDATE scontrini SET stato = 'archiviato' WHERE archiviato = 1",
        "UPDATE scontrini SET stato = 'versato' WHERE archiviato = 0 AND versato = 1",
        "UPDATE scontrini SET stato = 'incassato' WHERE archiviato = 0 AND versato = 0 AND incassato = 1",
        "UPDATE scontrini SET stato = 'attivo' WHERE archiviato = 0 AND versato = 0 AND incassato = 0"
    ];
    
    foreach ($updates as $i => $update) {
        $stmt = $pdo->prepare($update);
        $stmt->execute();
        $affected = $stmt->rowCount();
        echo "   " . ($i+1) . ". " . substr($update, 0, 50) . "... → $affected record aggiornati\n";
    }
    
    // Verifica migrazione dati
    $stats = $pdo->query("SELECT stato, COUNT(*) as count FROM scontrini GROUP BY stato")->fetchAll();
    echo "📊 Distribuzione stati:\n";
    foreach ($stats as $stat) {
        echo "   - " . $stat['stato'] . ": " . $stat['count'] . " record\n";
    }
    
    // Rimuovi colonne vecchie
    echo "3️⃣ Rimozione colonne obsolete...\n";
    $oldColumns = ['incassato', 'versato', 'archiviato'];
    foreach ($oldColumns as $column) {
        try {
            $pdo->exec("ALTER TABLE scontrini DROP COLUMN $column");
            echo "✅ Colonna '$column' rimossa\n";
        } catch (PDOException $e) {
            echo "⚠️ Errore rimozione '$column': " . $e->getMessage() . "\n";
        }
    }
    
    $pdo->commit();
    echo "\n🎉 MIGRAZIONE COMPLETATA CON SUCCESSO!\n\n";
    
} catch (PDOException $e) {
    $pdo->rollback();
    echo "❌ Errore migrazione: " . $e->getMessage() . "\n";
    echo "🔄 Rollback eseguito\n";
    exit(1);
}

// 4. Verifica finale
echo "🔍 Verifica finale schema...\n";
try {
    $stmt = $pdo->query("DESCRIBE scontrini");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasStato = in_array('stato', $finalColumns);
    $hasOldCols = array_intersect(['incassato', 'versato', 'archiviato'], $finalColumns);
    
    if ($hasStato && empty($hasOldCols)) {
        echo "✅ Schema migrato correttamente\n";
        echo "✅ Colonna 'stato' presente\n";
        echo "✅ Colonne obsolete rimosse\n";
    } else {
        echo "⚠️ Possibili problemi con la migrazione\n";
        echo "   - Colonna 'stato': " . ($hasStato ? "✅" : "❌") . "\n";
        echo "   - Colonne obsolete: " . (empty($hasOldCols) ? "✅ rimosse" : "⚠️ " . implode(', ', $hasOldCols)) . "\n";
    }
    
} catch (PDOException $e) {
    echo "⚠️ Errore verifica finale: " . $e->getMessage() . "\n";
}

echo "\n💡 PROSSIMI PASSI:\n";
echo "1. Testa il sistema per verificare che tutto funzioni\n";
echo "2. Se tutto ok, elimina le tabelle di backup\n";
echo "3. Il sistema ora usa il nuovo schema stato ENUM\n\n";

echo "✨ Migrazione completata!\n";

?>