<?php
/**
 * Script di test per verificare l'installazione
 * Da eseguire dopo l'installazione per verificare che tutto sia configurato correttamente
 */

// Controllo se l'installazione è stata completata
if (!file_exists('../installation.lock')) {
    die("❌ Installazione non completata. Eseguire prima l'installazione.\n");
}

echo "🔍 Test del Sistema di Installazione\n";
echo "=====================================\n\n";

// Test 1: Verifica file di configurazione
echo "1️⃣ Verifica file di configurazione...\n";
if (file_exists('../config.php')) {
    require_once '../config.php';
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        echo "   ✅ config.php presente e configurato\n";
    } else {
        echo "   ❌ config.php presente ma configurazione incompleta\n";
    }
} else {
    echo "   ❌ config.php non trovato\n";
}

// Test 2: Verifica connessione database
echo "\n2️⃣ Verifica connessione database...\n";
try {
    require_once '../includes/database.php';
    $db = Database::getInstance();
    echo "   ✅ Connessione al database riuscita\n";
    
    // Test 3: Verifica tabelle
    echo "\n3️⃣ Verifica presenza tabelle...\n";
    $tables = ['filiali', 'utenti', 'scontrini', 'log_attivita', 'sessioni'];
    foreach ($tables as $table) {
        $result = $db->fetchOne("SHOW TABLES LIKE ?", [$table]);
        if ($result) {
            echo "   ✅ Tabella '$table' presente\n";
        } else {
            echo "   ❌ Tabella '$table' mancante\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Errore connessione database: " . $e->getMessage() . "\n";
}

// Test 4: Verifica utente amministratore
echo "\n4️⃣ Verifica utente amministratore...\n";
try {
    $admin = $db->fetchOne("SELECT * FROM utenti WHERE ruolo = 'admin' LIMIT 1");
    if ($admin) {
        echo "   ✅ Utente amministratore trovato: " . $admin['username'] . "\n";
    } else {
        echo "   ❌ Nessun utente amministratore trovato\n";
    }
} catch (Exception $e) {
    echo "   ❌ Errore verifica utente: " . $e->getMessage() . "\n";
}

// Test 5: Verifica permessi directory
echo "\n5️⃣ Verifica permessi directory...\n";
$directories = [
    '../uploads/' => 'Directory uploads',
    '../uploads/scontrini/' => 'Directory scontrini'
];

foreach ($directories as $dir => $desc) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "   ✅ $desc scrivibile\n";
        } else {
            echo "   ⚠️  $desc presente ma non scrivibile\n";
        }
    } else {
        echo "   ❌ $desc non trovata\n";
    }
}

// Test 6: Verifica estensioni PHP
echo "\n6️⃣ Verifica estensioni PHP...\n";
$extensions = [
    'pdo' => 'PDO',
    'pdo_mysql' => 'PDO MySQL',
    'gd' => 'GD',
    'mbstring' => 'mbstring'
];

foreach ($extensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "   ✅ Estensione $desc caricata\n";
    } else {
        echo "   ❌ Estensione $desc non caricata\n";
    }
}

// Test 7: Verifica dati di esempio (se installati)
echo "\n7️⃣ Verifica dati di esempio...\n";
try {
    $filiali_count = $db->fetchOne("SELECT COUNT(*) as count FROM filiali")['count'];
    $scontrini_count = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini")['count'];
    
    if ($filiali_count > 0) {
        echo "   ✅ Trovate $filiali_count filiali\n";
    } else {
        echo "   ℹ️  Nessuna filiale trovata (installazione pulita)\n";
    }
    
    if ($scontrini_count > 0) {
        echo "   ✅ Trovati $scontrini_count scontrini di esempio\n";
    } else {
        echo "   ℹ️  Nessuno scontrino trovato (installazione pulita)\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Errore verifica dati: " . $e->getMessage() . "\n";
}

// Test 8: Verifica file di lock
echo "\n8️⃣ Verifica file di lock installazione...\n";
if (file_exists('../installation.lock')) {
    $lock_data = json_decode(file_get_contents('../installation.lock'), true);
    if ($lock_data) {
        echo "   ✅ File di lock presente e valido\n";
        echo "      📅 Installato il: " . $lock_data['installed_at'] . "\n";
        echo "      🏷️  Versione: " . $lock_data['version'] . "\n";
    } else {
        echo "   ⚠️  File di lock presente ma formato non valido\n";
    }
} else {
    echo "   ❌ File di lock mancante\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 Test completato!\n";
echo "ℹ️  Se tutti i test sono passati (✅), l'installazione è corretta.\n";
echo "ℹ️  Per problemi (❌), consultare la documentazione di troubleshooting.\n";
?>