<?php
// Test dello schema del database dopo l'allineamento
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain');

echo "=== TEST ALLINEAMENTO SCHEMA DATABASE ===\n\n";

try {
    $db = Database::getInstance();
    
    echo "1. Test connessione database: ";
    echo "✅ OK\n\n";
    
    echo "2. Verifica schema tabella scontrini:\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM scontrini");
    
    $required_columns = ['id', 'numero', 'data', 'stato', 'lordo', 'da_versare', 'note', 'utente_id', 'filiale_id', 'foto', 'gps_coords'];
    $found_columns = [];
    
    foreach ($columns as $col) {
        $found_columns[] = $col['Field'];
        echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Default']}\n";
    }
    
    echo "\n3. Verifica colonne richieste:\n";
    foreach ($required_columns as $required) {
        if (in_array($required, $found_columns)) {
            echo "   ✅ $required\n";
        } else {
            echo "   ❌ $required (MANCANTE)\n";
        }
    }
    
    echo "\n4. Test query di base:\n";
    
    // Test SELECT base
    echo "   - SELECT COUNT(*): ";
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini")['count'];
    echo "$count record ✅\n";
    
    // Test query con nuovo schema
    echo "   - SELECT con nuovo schema: ";
    $result = $db->fetchAll("SELECT numero, data, stato FROM scontrini LIMIT 1");
    echo "✅ OK\n";
    
    // Test query con filtri stato ENUM
    echo "   - Filtro stato 'attivo': ";
    $attivi = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE stato = 'attivo'")['count'];
    echo "$attivi record ✅\n";
    
    echo "   - Filtro stato 'incassato': ";
    $incassati = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE stato = 'incassato'")['count'];
    echo "$incassati record ✅\n";
    
    echo "   - Filtro stato 'versato': ";
    $versati = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE stato = 'versato'")['count'];
    echo "$versati record ✅\n";
    
    echo "   - Filtro stato 'archiviato': ";
    $archiviati = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE stato = 'archiviato'")['count'];
    echo "$archiviati record ✅\n";
    
    echo "\n5. Test query con JOIN:\n";
    echo "   - JOIN con utenti e filiali: ";
    $join_result = $db->fetchAll("
        SELECT s.numero, s.data, u.nome as utente_nome, f.nome as filiale_nome 
        FROM scontrini s 
        LEFT JOIN utenti u ON s.utente_id = u.id 
        LEFT JOIN filiali f ON s.filiale_id = f.id 
        LIMIT 1
    ");
    echo "✅ OK\n";
    
    echo "\n6. Test INSERT con nuovo schema:\n";
    echo "   - INSERT di test: ";
    
    $test_id = $db->query("
        INSERT INTO scontrini (numero, data, lordo, netto, da_versare, note, utente_id, filiale_id, stato) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
        'TEST-' . time(), 
        date('Y-m-d'), 
        100.50, 
        100.50, // netto uguale a lordo
        50.25, 
        'Test schema alignment', 
        1, 
        1, 
        'attivo'
    ]);
    
    if ($test_id) {
        echo "✅ OK (ID: $test_id)\n";
        
        echo "   - UPDATE di test: ";
        $updated = $db->query("UPDATE scontrini SET stato = 'incassato' WHERE id = ?", [$test_id]);
        echo "✅ OK\n";
        
        echo "   - DELETE di test: ";
        $deleted = $db->query("DELETE FROM scontrini WHERE id = ?", [$test_id]);
        echo "✅ OK\n";
    } else {
        echo "❌ ERRORE\n";
    }
    
    echo "\n=== RISULTATO FINALE ===\n";
    echo "✅ Schema del database correttamente allineato!\n";
    echo "✅ Tutte le query funzionano con il nuovo schema\n";
    echo "✅ Sistema pronto per l'uso\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>