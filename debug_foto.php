<?php
require_once 'includes/bootstrap.php';

header('Content-Type: text/plain');

echo "=== DEBUG FOTO SCONTRINI ===\n\n";

try {
    $db = Database::getInstance();
    
    echo "1. Verifica struttura tabella scontrini:\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM scontrini");
    
    $foto_columns = [];
    foreach ($columns as $col) {
        if (strpos($col['Field'], 'foto') !== false) {
            $foto_columns[] = $col['Field'];
            echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Default']}\n";
        }
    }
    
    echo "\n2. Scontrini con foto:\n";
    $scontrini_with_photos = $db->fetchAll("
        SELECT id, numero, foto 
        FROM scontrini 
        WHERE foto IS NOT NULL AND foto != '' 
        LIMIT 5
    ");
    
    foreach ($scontrini_with_photos as $scontrino) {
        echo "   - ID: {$scontrino['id']}, Numero: {$scontrino['numero']}\n";
        echo "     Foto: {$scontrino['foto']}\n";
        echo "     File exists: " . (file_exists($scontrino['foto']) ? 'YES' : 'NO') . "\n";
        if (file_exists($scontrino['foto'])) {
            $size = filesize($scontrino['foto']);
            echo "     Size: " . number_format($size / 1024, 2) . " KB\n";
        }
        echo "\n";
    }
    
    echo "3. Test ImageManager::getPhotoUrl():\n";
    require_once 'includes/image_manager.php';
    
    foreach ($scontrini_with_photos as $scontrino) {
        $url = ImageManager::getPhotoUrl($scontrino['foto']);
        echo "   - Foto: {$scontrino['foto']}\n";
        echo "     URL: $url\n";
        echo "     URL decoded: " . base64_decode(str_replace('view_photo.php?path=', '', urldecode($url))) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
}
?>