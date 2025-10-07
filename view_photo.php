<?php
// Abilita error reporting per debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disabilitato per le immagini

require_once 'includes/installation_check.php';
requireBootstrap();
require_once 'includes/image_manager.php';

// Solo utenti autenticati possono vedere le foto
Auth::requireLogin();

// Decodifica il percorso dalla query string
$encoded_path = $_GET['path'] ?? '';
if (empty($encoded_path)) {
    http_response_code(404);
    die('Foto non trovata');
}

try {
    $photo_path = base64_decode($encoded_path);

    // Validazioni di sicurezza
    if (empty($photo_path) || !file_exists($photo_path)) {
        http_response_code(404);
        die('Foto non trovata');
    }

    // Verifica che il percorso sia nella directory uploads
    $real_path = realpath($photo_path);
    $upload_dir = realpath('uploads/scontrini');
    if (!$real_path || !$upload_dir || strpos($real_path, $upload_dir) !== 0) {
        http_response_code(403);
        die('Accesso non autorizzato');
    }

    // Verifica che sia un'immagine valida
    if (!ImageManager::isValidImage($photo_path)) {
        http_response_code(404);
        die('File non valido');
    }

    // Ottieni informazioni sull'immagine
    $image_info = getimagesize($photo_path);
    if ($image_info === false) {
        http_response_code(404);
        die('Immagine non valida');
    }

    // Estrae l'ID dello scontrino dal nome del file per verificare i permessi
    $filename = basename($photo_path);
    if (preg_match('/scontrino_(\d+)_/', $filename, $matches)) {
        $scontrino_id = (int)$matches[1];
        
        // Verifica che l'utente abbia accesso a questo scontrino
        $db = Database::getInstance();
        $current_user = Auth::getCurrentUser();
        
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
        
        if (!$scontrino) {
            http_response_code(403);
            die('Non hai i permessi per vedere questa foto');
        }
    }

    // Imposta gli header per l'immagine
    header('Content-Type: ' . $image_info['mime']);
    header('Content-Length: ' . filesize($photo_path));
    header('Cache-Control: private, max-age=3600');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($photo_path)) . ' GMT');

    // Se è richiesta una miniatura, per ora serviamo l'originale
    // TODO: Implementare generazione thumbnail sicura
    if (isset($_GET['thumbnail']) && $_GET['thumbnail'] == '1') {
        // Per ora serviamo l'immagine originale
        readfile($photo_path);
    } else {
        // Servi l'immagine originale
        readfile($photo_path);
    }

} catch (Exception $e) {
    // Log error but don't show details to user
    error_log("Error in view_photo.php: " . $e->getMessage());
    http_response_code(500);
    die('Errore interno del server');
}
?>

/**
 * Genera una miniatura dell'immagine
 */
function generateThumbnail($source_path, $mime_type) {
    $thumb_width = 200;
    $thumb_height = 200;
    
    try {
        // Carica l'immagine sorgente
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source = imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($source_path);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($source_path);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        $orig_width = imagesx($source);
        $orig_height = imagesy($source);
        
        // Calcola dimensioni mantenendo le proporzioni
        $ratio = min($thumb_width / $orig_width, $thumb_height / $orig_height);
        $new_width = intval($orig_width * $ratio);
        $new_height = intval($orig_height * $ratio);
        
        // Crea la miniatura
        $thumbnail = imagecreatetruecolor($new_width, $new_height);
        
        // Preserva trasparenza per PNG
        if ($mime_type == 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        
        // Output della miniatura
        ob_start();
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($thumbnail, null, 75);
                break;
            case 'image/png':
                imagepng($thumbnail, null, 6);
                break;
            case 'image/gif':
                imagegif($thumbnail);
                break;
            case 'image/webp':
                imagewebp($thumbnail, null, 75);
                break;
        }
        $image_data = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return $image_data;
        
    } catch (Exception $e) {
        return false;
    }
}
?>