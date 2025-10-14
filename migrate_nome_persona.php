<?php
/**
 * Script di migrazione per aggiungere il campo nome_persona alla tabella scontrini
 * Risolve il problema del constraint UNIQUE sul numero che impedisce più scontrini per la stessa persona
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>🔧 Migrazione Database - Aggiunta campo nome_persona</h2>\n";
    echo "<p>Inizio migrazione...</p>\n";
    
    // Step 1: Verifica se il campo nome_persona esiste già
    $result = $pdo->query("SHOW COLUMNS FROM scontrini LIKE 'nome_persona'");
    if ($result->rowCount() > 0) {
        echo "<p>✅ Il campo nome_persona esiste già. Migrazione non necessaria.</p>\n";
        exit;
    }
    
    // Step 2: Aggiungi il campo nome_persona
    echo "<p>📝 Aggiunta campo nome_persona...</p>\n";
    $pdo->exec("ALTER TABLE scontrini ADD COLUMN nome_persona VARCHAR(100) DEFAULT NULL AFTER numero");
    echo "<p>✅ Campo nome_persona aggiunto con successo.</p>\n";
    
    // Step 3: Copia i dati dal campo numero al campo nome_persona per i record esistenti
    echo "<p>📋 Copia dati esistenti dal campo numero a nome_persona...</p>\n";
    $result = $pdo->exec("UPDATE scontrini SET nome_persona = numero WHERE numero NOT REGEXP '^SC[0-9]+$'");
    echo "<p>✅ Copiati dati per $result record.</p>\n";
    
    // Step 4: Genera numeri progressivi univoci per tutti gli scontrini
    echo "<p>🔢 Generazione numeri progressivi univoci...</p>\n";
    $pdo->exec("UPDATE scontrini SET numero = CONCAT('SC', LPAD(id, 6, '0'))");
    echo "<p>✅ Numeri progressivi generati con successo.</p>\n";
    
    // Step 5: Aggiungi indice per il campo nome_persona
    echo "<p>🗂️ Creazione indici per performance...</p>\n";
    $pdo->exec("CREATE INDEX idx_nome_persona ON scontrini (nome_persona)");
    
    // Step 6: Aggiorna l'indice di ricerca
    $pdo->exec("DROP INDEX IF EXISTS idx_scontrini_search ON scontrini");
    $pdo->exec("CREATE INDEX idx_scontrini_search ON scontrini (numero, nome_persona, data, stato)");
    echo "<p>✅ Indici creati con successo.</p>\n";
    
    echo "<h3>🎉 Migrazione completata con successo!</h3>\n";
    echo "<p>Ora è possibile aggiungere più scontrini per la stessa persona.</p>\n";
    echo "<p><strong>Prossimi passi:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Aggiornare il codice di aggiungi.php per usare nome_persona</li>\n";
    echo "<li>Aggiornare le query di ricerca</li>\n";
    echo "<li>Testare l'aggiunta di nuovi scontrini</li>\n";
    echo "</ul>\n";
    
} catch (PDOException $e) {
    echo "<h3>❌ Errore durante la migrazione:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>La migrazione è stata interrotta per sicurezza.</p>\n";
}

echo "<p><a href='check_permissions.php'>🔍 Verifica Permessi</a> | ";
echo "<a href='install.php'>🚀 Torna all'Installazione</a></p>\n";
?>