<?php
/**
 * Script di migrazione database - Aggiunge campo da_versare
 * Usare questo file se hai già installato il sistema e devi aggiornare il database
 */

require_once 'includes/bootstrap.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>Migrazione Database - Aggiunta campo 'da_versare'</h2>\n";
    
    // Controlla se la colonna esiste già
    $columns = $db->query("SHOW COLUMNS FROM scontrini LIKE 'da_versare'");
    
    if (empty($columns)) {
        echo "<p>Aggiunta colonna 'da_versare' alla tabella scontrini...</p>\n";
        
        $db->query("
            ALTER TABLE scontrini 
            ADD COLUMN da_versare DECIMAL(10,2) DEFAULT NULL 
            AFTER lordo
        ");
        
        echo "<p style='color: green;'><strong>✅ Migrazione completata con successo!</strong></p>\n";
        echo "<p>La colonna 'da_versare' è stata aggiunta alla tabella scontrini.</p>\n";
        
        // Opzionalmente, popola i valori esistenti
        $updated = $db->query("UPDATE scontrini SET da_versare = lordo WHERE da_versare IS NULL");
        echo "<p>Aggiornati " . $db->getConnection()->rowCount() . " record esistenti.</p>\n";
        
    } else {
        echo "<p style='color: orange;'>⚠️ La colonna 'da_versare' esiste già nel database.</p>\n";
        echo "<p>Nessuna migrazione necessaria.</p>\n";
    }
    
    echo "<p><a href='index.php'>← Torna all'applicazione</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Errore durante la migrazione:</strong></p>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Controlla le credenziali del database in config.php</p>\n";
}
?>