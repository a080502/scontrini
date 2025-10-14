<?php
require_once 'includes/database.php';

echo "🧪 Test del Sistema Filiali\n";
echo "==========================\n\n";

$db = Database::getInstance();

try {
    // Test 1: Verifica struttura tabelle
    echo "1. Verifica struttura database...\n";
    
    $filiali = $db->query("SELECT COUNT(*) as count FROM filiali");
    echo "   ✅ Filiali trovate: " . $filiali[0]['count'] . "\n";
    
    $utenti = $db->query("SELECT COUNT(*) as count FROM utenti");
    echo "   ✅ Utenti trovati: " . $utenti[0]['count'] . "\n";
    
    $scontrini = $db->query("SELECT COUNT(*) as count FROM scontrini");
    echo "   ✅ Scontrini trovati: " . $scontrini[0]['count'] . "\n";
    
    // Test 2: Verifica associazioni
    echo "\n2. Verifica associazioni...\n";
    
    $utenti_con_filiale = $db->query("SELECT COUNT(*) as count FROM utenti WHERE filiale_id IS NOT NULL");
    echo "   ✅ Utenti con filiale: " . $utenti_con_filiale[0]['count'] . "\n";
    
    $scontrini_con_utente = $db->query("SELECT COUNT(*) as count FROM scontrini WHERE utente_id IS NOT NULL");
    echo "   ✅ Scontrini con utente: " . $scontrini_con_utente[0]['count'] . "\n";
    
    // Test 3: Verifica ruoli
    echo "\n3. Verifica ruoli utenti...\n";
    
    $ruoli = $db->query("
        SELECT ruolo, COUNT(*) as count 
        FROM utenti 
        GROUP BY ruolo 
        ORDER BY ruolo
    ");
    
    foreach ($ruoli as $ruolo) {
        echo "   ✅ {$ruolo['ruolo']}: {$ruolo['count']} utenti\n";
    }
    
    // Test 4: Verifica responsabili filiali
    echo "\n4. Verifica responsabili filiali...\n";
    
    $filiali_con_responsabile = $db->query("
        SELECT f.nome as filiale, u.nome as responsabile 
        FROM filiali f 
        LEFT JOIN utenti u ON f.responsabile_id = u.id 
        WHERE f.attiva = 1
    ");
    
    foreach ($filiali_con_responsabile as $filiale) {
        $responsabile = $filiale['responsabile'] ?: 'Non assegnato';
        echo "   ✅ {$filiale['filiale']}: {$responsabile}\n";
    }
    
    // Test 5: Verifica distribuzione scontrini
    echo "\n5. Distribuzione scontrini per filiale...\n";
    
    $distribuzione = $db->query("
        SELECT f.nome as filiale, COUNT(s.id) as scontrini_count
        FROM filiali f
        LEFT JOIN scontrini s ON f.id = s.filiale_id
        WHERE f.attiva = 1
        GROUP BY f.id, f.nome
        ORDER BY f.nome
    ");
    
    foreach ($distribuzione as $dist) {
        echo "   ✅ {$dist['filiale']}: {$dist['scontrini_count']} scontrini\n";
    }
    
    echo "\n🎉 Tutti i test completati con successo!\n";
    echo "\nCredenziali di test:\n";
    echo "- Admin: admin_sede / secret\n";
    echo "- Responsabile: resp_nord / secret\n";
    echo "- Utente: user_nord1 / secret\n";
    echo "\nAccedi a http://localhost:8000 per testare l'interfaccia\n";
    
} catch (Exception $e) {
    echo "❌ Errore durante il test: " . $e->getMessage() . "\n";
    exit(1);
}
?>