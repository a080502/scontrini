<?php
// Debug version of view_photo.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/installation_check.php';
requireBootstrap();
require_once 'includes/image_manager.php';

// Solo utenti autenticati possono vedere le foto
Auth::requireLogin();

echo "DEBUG VIEW PHOTO<br>";

// Decodifica il percorso dalla query string
$encoded_path = $_GET['path'] ?? '';
echo "Encoded path: " . htmlspecialchars($encoded_path) . "<br>";

if (empty($encoded_path)) {
    echo "ERROR: Empty encoded path<br>";
    exit;
}

$photo_path = base64_decode($encoded_path);
echo "Decoded path: " . htmlspecialchars($photo_path) . "<br>";

// Validazioni di sicurezza
if (empty($photo_path)) {
    echo "ERROR: Empty photo path after decode<br>";
    exit;
}

if (!file_exists($photo_path)) {
    echo "ERROR: File does not exist: " . htmlspecialchars($photo_path) . "<br>";
    exit;
}

echo "File exists: YES<br>";
echo "File size: " . filesize($photo_path) . " bytes<br>";

// Verifica che il percorso sia nella directory uploads
$real_path = realpath($photo_path);
$upload_dir = realpath('uploads/scontrini');

echo "Real path: " . htmlspecialchars($real_path ?: 'NULL') . "<br>";
echo "Upload dir: " . htmlspecialchars($upload_dir ?: 'NULL') . "<br>";

if (!$real_path || !$upload_dir || strpos($real_path, $upload_dir) !== 0) {
    echo "ERROR: Security check failed<br>";
    echo "Real path starts with upload dir: " . (($real_path && $upload_dir && strpos($real_path, $upload_dir) === 0) ? 'YES' : 'NO') . "<br>";
    exit;
}

// Verifica che sia un'immagine valida
if (!ImageManager::isValidImage($photo_path)) {
    echo "ERROR: Invalid image according to ImageManager<br>";
    exit;
}

// Ottieni informazioni sull'immagine
$image_info = getimagesize($photo_path);
if ($image_info === false) {
    echo "ERROR: getimagesize failed<br>";
    exit;
}

echo "Image info: " . print_r($image_info, true) . "<br>";

// Estrae l'ID dello scontrino dal nome del file per verificare i permessi
$filename = basename($photo_path);
echo "Filename: " . htmlspecialchars($filename) . "<br>";

if (preg_match('/scontrino_(\d+)_/', $filename, $matches)) {
    $scontrino_id = (int)$matches[1];
    echo "Extracted scontrino ID: $scontrino_id<br>";
    
    // Verifica che l'utente abbia accesso a questo scontrino
    $db = Database::getInstance();
    $current_user = Auth::getCurrentUser();
    
    echo "Current user: " . print_r($current_user, true) . "<br>";
    echo "Is admin: " . (Auth::isAdmin() ? 'YES' : 'NO') . "<br>";
    
    // Query per verificare l'accesso allo scontrino
    if (Auth::isAdmin()) {
        // Admin può vedere tutto
        $scontrino = $db->fetchOne("SELECT id FROM scontrini WHERE id = ?", [$scontrino_id]);
    } elseif (Auth::isResponsabile()) {
        // Responsabile può vedere scontrini della sua filiale
        $scontrino = $db->fetchOne("
            SELECT id FROM scontrini 
            WHERE id = ? AND filiale_id = ?
        ", [$scontrino_id, $current_user['filiale_id']]);
    } else {
        // Utente normale può vedere solo i suoi scontrini
        $scontrino = $db->fetchOne("
            SELECT id FROM scontrini 
            WHERE id = ? AND utente_id = ?
        ", [$scontrino_id, $current_user['id']]);
    }
    
    echo "Scontrino found: " . ($scontrino ? 'YES' : 'NO') . "<br>";
    
    if (!$scontrino) {
        echo "ERROR: No permission for this photo<br>";
        exit;
    }
}

echo "All checks passed!<br>";
echo "Thumbnail requested: " . (isset($_GET['thumbnail']) && $_GET['thumbnail'] == '1' ? 'YES' : 'NO') . "<br>";

echo "<br><strong>Everything OK - would serve image now</strong><br>";
echo "<a href='?path=" . urlencode($encoded_path) . "&serve=1'>Click to serve actual image</a><br>";

if (isset($_GET['serve'])) {
    echo "<br>Serving image...<br>";
    
    // Imposta gli header per l'immagine
    header('Content-Type: ' . $image_info['mime']);
    header('Content-Length: ' . filesize($photo_path));
    
    // Servi l'immagine
    readfile($photo_path);
}
?>