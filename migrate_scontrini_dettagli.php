<?php
/**
 * Script di migrazione per aggiungere la tabella scontrini_dettagli
 * Permette di gestire articoli dettagliati per ogni scontrino
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>🛍️ Migrazione Database - Aggiunta tabella scontrini_dettagli</h2>\n";
    echo "<p>Inizio migrazione per sistema articoli...</p>\n";
    
    // Verifica se la tabella esiste già
    $result = $pdo->query("SHOW TABLES LIKE 'scontrini_dettagli'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ La tabella scontrini_dettagli esiste già. Migrazione non necessaria.</p>\n";
        exit;
    }
    
    // Crea la tabella scontrini_dettagli
    echo "<p>📝 Creazione tabella scontrini_dettagli...</p>\n";
    $pdo->exec("
        CREATE TABLE scontrini_dettagli (
            id int(11) NOT NULL AUTO_INCREMENT,
            scontrino_id int(11) NOT NULL,
            numero_ordine int(11) NOT NULL DEFAULT 1,
            codice_articolo varchar(50) DEFAULT NULL,
            descrizione_materiale text NOT NULL,
            qta decimal(10,3) NOT NULL DEFAULT 1.000,
            prezzo_unitario decimal(10,2) NOT NULL DEFAULT 0.00,
            prezzo_totale decimal(10,2) NOT NULL DEFAULT 0.00,
            created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_scontrino (scontrino_id),
            KEY idx_numero_ordine (scontrino_id, numero_ordine),
            CONSTRAINT fk_dettagli_scontrino FOREIGN KEY (scontrino_id) REFERENCES scontrini (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Tabella scontrini_dettagli creata con successo.</p>\n";
    
    // Crea indici per performance
    echo "<p>🗂️ Creazione indici per performance...</p>\n";
    $pdo->exec("CREATE INDEX idx_codice_articolo ON scontrini_dettagli (codice_articolo)");
    $pdo->exec("CREATE INDEX idx_descrizione ON scontrini_dettagli (descrizione_materiale(100))");
    echo "<p>✅ Indici creati con successo.</p>\n";
    
    echo "<h3>🎉 Migrazione completata con successo!</h3>\n";
    echo "<p>Ora è possibile aggiungere articoli dettagliati agli scontrini.</p>\n";
    echo "<p><strong>Struttura tabella:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><strong>numero_ordine:</strong> Posizione dell'articolo nello scontrino</li>\n";
    echo "<li><strong>codice_articolo:</strong> Codice identificativo del prodotto</li>\n";
    echo "<li><strong>descrizione_materiale:</strong> Descrizione completa dell'articolo</li>\n";
    echo "<li><strong>qta:</strong> Quantità (supporta decimali)</li>\n";
    echo "<li><strong>prezzo_unitario:</strong> Prezzo per singola unità</li>\n";
    echo "<li><strong>prezzo_totale:</strong> Calcolato automaticamente (qta * prezzo_unitario)</li>\n";
    echo "</ul>\n";
    
} catch (PDOException $e) {
    echo "<h3>❌ Errore durante la migrazione:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>La migrazione è stata interrotta per sicurezza.</p>\n";
}

echo "<p><a href='aggiungi.php'>📝 Testa Aggiunta Scontrino</a> | ";
echo "<a href='install.php'>🚀 Torna all'Installazione</a></p>\n";
?>