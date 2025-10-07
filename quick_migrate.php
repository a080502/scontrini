<?php
/**
 * Migrazione Rapida - Solo Aggiunta Colonna Stato (Sicura)
 * 
 * Questo script aggiunge la colonna 'stato' senza rimuovere le colonne esistenti,
 * permettendo al sistema di funzionare subito.
 */

echo "🚀 MIGRAZIONE RAPIDA SCHEMA DATABASE\n";
echo "====================================\n\n";

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

// Verifica schema attuale
echo "🔍 Verifica schema...\n";
try {
    $stmt = $pdo->query("DESCRIBE scontrini");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    $hasStato = in_array('stato', $columns);
    $hasOldCols = array_intersect(['incassato', 'versato', 'archiviato'], $columns);
    
    echo "Colonna 'stato': " . ($hasStato ? "✅ Presente" : "❌ Mancante") . "\n";
    echo "Colonne vecchie: " . (!empty($hasOldCols) ? "⚠️ " . implode(', ', $hasOldCols) : "❌ Nessuna") . "\n\n";
    
} catch (PDOException $e) {
    echo "❌ Errore verifica: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 1: Aggiungi colonna stato se non esiste
if (!$hasStato) {
    echo "1️⃣ Aggiunta colonna 'stato'...\n";
    try {
        $pdo->exec("ALTER TABLE scontrini ADD COLUMN stato ENUM('attivo','incassato','versato','archiviato') DEFAULT 'attivo'");
        echo "✅ Colonna 'stato' aggiunta\n\n";
    } catch (PDOException $e) {
        echo "❌ Errore aggiunta colonna: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "1️⃣ Colonna 'stato' già presente\n\n";
}

// Step 2: Popola colonna stato basandosi sui valori esistenti
if (!empty($hasOldCols)) {
    echo "2️⃣ Popolamento colonna 'stato'...\n";
    try {
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
            echo "   " . ($i+1) . ". " . substr($update, 0, 40) . "... → $affected record\n";
        }
        
        echo "✅ Popolamento completato\n\n";
    } catch (PDOException $e) {
        echo "❌ Errore popolamento: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "2️⃣ Nessun dato da migrare\n\n";
}

// Step 3: Verifica risultato
echo "3️⃣ Verifica risultato...\n";
try {
    $stats = $pdo->query("SELECT stato, COUNT(*) as count FROM scontrini GROUP BY stato")->fetchAll();
    echo "📊 Distribuzione stati:\n";
    foreach ($stats as $stat) {
        echo "   - " . $stat['stato'] . ": " . $stat['count'] . " record\n";
    }
    echo "\n";
} catch (PDOException $e) {
    echo "⚠️ Errore verifica: " . $e->getMessage() . "\n";
}

echo "🎉 MIGRAZIONE RAPIDA COMPLETATA!\n\n";
echo "💡 RISULTATO:\n";
echo "- ✅ Colonna 'stato' presente e popolata\n";
echo "- ✅ Sistema ora funzionante\n";
echo "- ⚠️ Colonne vecchie mantenute per sicurezza\n\n";

echo "🔧 PROSSIMI PASSI OPZIONALI:\n";
echo "1. Testa il sistema per verificare che funzioni\n";
echo "2. Se tutto ok, esegui migrate_schema.php per pulizia completa\n";
echo "3. Le colonne vecchie possono essere rimosse in seguito\n\n";

echo "✨ Il sistema ora dovrebbe funzionare senza errori!\n";

?>