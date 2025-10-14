<?php
// Test diretto per verificare il problema delle foto
require_once 'includes/image_manager.php';

echo "=== TEST URL FOTO ===\n\n";

// Test con un file esistente
$test_file = 'uploads/scontrini/2025/10/scontrino_10_1759758006_68e3c6b67edef.jpg';

echo "1. File test: $test_file\n";
echo "2. File exists: " . (file_exists($test_file) ? 'YES' : 'NO') . "\n";

if (file_exists($test_file)) {
    echo "3. File size: " . number_format(filesize($test_file) / 1024, 2) . " KB\n";
    
    $url = ImageManager::getPhotoUrl($test_file);
    echo "4. Generated URL: $url\n";
    
    // Decodifica il path per verificare
    $encoded = str_replace('view_photo.php?path=', '', $url);
    $decoded_path = base64_decode(urldecode($encoded));
    echo "5. Decoded path: $decoded_path\n";
    echo "6. Decoded file exists: " . (file_exists($decoded_path) ? 'YES' : 'NO') . "\n";
    
    // Test thumbnail URL
    $thumb_url = $url . '&thumbnail=1';
    echo "7. Thumbnail URL: $thumb_url\n";
    
    // Simula richiesta
    echo "\n=== SIMULATING REQUEST ===\n";
    $_GET['path'] = urldecode($encoded);
    $_GET['thumbnail'] = '1';
    
    echo "GET path: " . $_GET['path'] . "\n";
    echo "GET thumbnail: " . $_GET['thumbnail'] . "\n";
    
    $photo_path = base64_decode($_GET['path']);
    echo "Final photo path: $photo_path\n";
    echo "Final file exists: " . (file_exists($photo_path) ? 'YES' : 'NO') . "\n";
}
?>