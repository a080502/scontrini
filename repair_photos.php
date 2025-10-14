<?php
// Script per riparare i riferimenti alle foto nel database
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 RIPARAZIONE FOTO DATABASE\n";
echo "============================\n\n";

try {
    $db = Database::getInstance();
    
    // 1. Verifica struttura tabella
    echo "1. Verifica struttura tabella scontrini...\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM scontrini");
    
    $has_foto = false;
    $has_foto_scontrino = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'foto') $has_foto = true;
        if ($col['Field'] === 'foto_scontrino') $has_foto_scontrino = true;
    }
    
    echo "   - Campo 'foto': " . ($has_foto ? "✅ PRESENTE" : "❌ ASSENTE") . "\n";
    echo "   - Campo 'foto_scontrino': " . ($has_foto_scontrino ? "✅ PRESENTE" : "❌ ASSENTE") . "\n\n";
    
    // 2. Se esiste sia foto che foto_scontrino, migra i dati
    if ($has_foto && $has_foto_scontrino) {
        echo "2. Migrazione da foto_scontrino a foto...\n";
        
        $to_migrate = $db->fetchAll("
            SELECT id, foto_scontrino 
            FROM scontrini 
            WHERE foto_scontrino IS NOT NULL 
            AND foto_scontrino != '' 
            AND (foto IS NULL OR foto = '')
        ");
        
        echo "   Trovati " . count($to_migrate) . " record da migrare\n";
        
        foreach ($to_migrate as $record) {
            $updated = $db->query("UPDATE scontrini SET foto = ? WHERE id = ?", [
                $record['foto_scontrino'], 
                $record['id']
            ]);
            
            if ($updated) {
                echo "   ✅ Migrato record ID: {$record['id']}\n";
            } else {
                echo "   ❌ Errore migrazione record ID: {$record['id']}\n";
            }
        }
    }
    
    // 3. Cerca foto orfane (file esistenti ma non in database)
    echo "\n3. Ricerca foto orfane...\n";
    
    $photo_files = [];
    $upload_dir = 'uploads/scontrini';
    
    if (is_dir($upload_dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($upload_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $relative_path = str_replace('\\', '/', $file->getPath() . '/' . $file->getFilename());
                    $photo_files[] = $relative_path;
                }
            }
        }
    }
    
    echo "   Trovati " . count($photo_files) . " file foto\n";
    
    // Trova ID scontrino dai nomi file
    $orphaned = 0;
    $repaired = 0;
    
    foreach ($photo_files as $file_path) {
        $filename = basename($file_path);
        
        // Estrae ID scontrino dal nome file
        if (preg_match('/scontrino_(\d+)_/', $filename, $matches)) {
            $scontrino_id = (int)$matches[1];
            
            // Verifica se esiste record con foto vuota
            $scontrino = $db->fetchOne("
                SELECT id, foto 
                FROM scontrini 
                WHERE id = ? AND (foto IS NULL OR foto = '')
            ", [$scontrino_id]);
            
            if ($scontrino) {
                // Aggiorna con il percorso corretto
                $updated = $db->query("UPDATE scontrini SET foto = ? WHERE id = ?", [
                    $file_path, 
                    $scontrino_id
                ]);
                
                if ($updated) {
                    echo "   🔧 Riparato: ID $scontrino_id -> $file_path\n";
                    $repaired++;
                } else {
                    echo "   ❌ Errore riparazione: ID $scontrino_id\n";
                }
            }
        } else {
            $orphaned++;
        }
    }
    
    echo "\n📊 RISULTATI:\n";
    echo "   - Foto riparate: $repaired\n";
    echo "   - Foto orfane: $orphaned\n";
    
    // 4. Verifica finale
    echo "\n4. Verifica finale...\n";
    $with_photos = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE foto IS NOT NULL AND foto != ''")['count'];
    $without_photos = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE foto IS NULL OR foto = ''")['count'];
    
    echo "   - Scontrini con foto: $with_photos\n";
    echo "   - Scontrini senza foto: $without_photos\n";
    
    echo "\n✅ RIPARAZIONE COMPLETATA!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>