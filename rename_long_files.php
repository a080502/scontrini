<?php
/**
 * Script per rinominare file con nomi troppo lunghi
 */
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 RINOMINA FILE FOTO TROPPO LUNGHI\n";
echo "===================================\n\n";

try {
    $db = Database::getInstance();
    $renamed = 0;
    $errors = 0;
    
    // Trova tutti i record con foto
    $scontrini_with_photos = $db->fetchAll("
        SELECT id, numero, foto 
        FROM scontrini 
        WHERE foto IS NOT NULL AND foto != ''
    ");
    
    echo "Trovati " . count($scontrini_with_photos) . " scontrini con foto\n\n";
    
    foreach ($scontrini_with_photos as $scontrino) {
        $old_path = $scontrino['foto'];
        $filename = basename($old_path);
        
        // Controlla se il nome è troppo lungo (oltre 200 caratteri)
        if (strlen($filename) > 200) {
            echo "File troppo lungo: $filename\n";
            echo "Lunghezza: " . strlen($filename) . " caratteri\n";
            
            // Estrae informazioni dal nome file
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $scontrino_id = $scontrino['id'];
            
            // Genera nuovo nome compatto
            $timestamp = date('Ymd_His');
            $unique_id = substr(uniqid(), -8);
            $new_filename = "scontrino_{$scontrino_id}_{$timestamp}_{$unique_id}.{$extension}";
            
            $directory = dirname($old_path);
            $new_path = $directory . '/' . $new_filename;
            
            echo "Nuovo nome: $new_filename\n";
            echo "Lunghezza: " . strlen($new_filename) . " caratteri\n";
            
            // Rinomina il file fisico
            if (file_exists($old_path)) {
                if (rename($old_path, $new_path)) {
                    // Aggiorna il database
                    $updated = $db->query("UPDATE scontrini SET foto = ? WHERE id = ?", [
                        $new_path, 
                        $scontrino_id
                    ]);
                    
                    if ($updated) {
                        echo "✅ Rinominato con successo!\n";
                        $renamed++;
                    } else {
                        echo "❌ Errore aggiornamento database\n";
                        // Ripristina il nome originale
                        rename($new_path, $old_path);
                        $errors++;
                    }
                } else {
                    echo "❌ Errore rinomina file\n";
                    $errors++;
                }
            } else {
                echo "❌ File originale non esiste\n";
                $errors++;
            }
            
            echo "\n";
        }
    }
    
    echo "\n📊 RISULTATI:\n";
    echo "   - File rinominati: $renamed\n";
    echo "   - Errori: $errors\n";
    
    if ($renamed > 0) {
        echo "\n✅ RINOMINA COMPLETATA!\n";
        echo "I file ora hanno nomi più corti e le foto dovrebbero funzionare meglio.\n";
    } else {
        echo "\n✅ NESSUN FILE DA RINOMINARE\n";
        echo "Tutti i file hanno già nomi di lunghezza accettabile.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>