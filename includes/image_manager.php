<?php

class ImageManager {
    const UPLOAD_DIR = 'uploads/scontrini';
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const MAX_WIDTH = 1920;
    const MAX_HEIGHT = 1920;
    const THUMBNAIL_WIDTH = 300;
    const THUMBNAIL_HEIGHT = 300;

    /**
     * Salva una foto di scontrino
     * @param array $file - File da $_FILES
     * @param int $scontrino_id - ID dello scontrino
     * @param array $user_info - Informazioni utente (nome, username)
     * @param array $gps_data - Coordinate GPS opzionali ['latitude', 'longitude', 'accuracy']
     * @return array - ['success' => bool, 'path' => string, 'error' => string]
     */
    public static function saveScontrinoPhoto($file, $scontrino_id, $user_info = null, $gps_data = null) {
        try {
            // Validazione file
            $validation = self::validateUploadedFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // Genera percorso organizzato per data
            $year = date('Y');
            $month = date('m');
            $upload_dir = self::UPLOAD_DIR . "/$year/$month";
            
            // Crea directory se non esiste
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    return ['success' => false, 'error' => 'Impossibile creare la directory di upload'];
                }
            }

            // Genera nome file semplificato ma unico
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $timestamp = date('Ymd_His'); // Formato più compatto
            
            // Nome file semplificato per evitare path troppo lunghi
            $filename_parts = [];
            $filename_parts[] = 'scontrino_' . $scontrino_id;
            
            // Aggiunge timestamp compatto
            $filename_parts[] = $timestamp;
            
            // Aggiunge coordinate GPS in formato compatto se disponibili
            if ($gps_data && isset($gps_data['latitude']) && isset($gps_data['longitude'])) {
                // Formato compatto per GPS: lat,lng con 4 decimali max
                $lat = round($gps_data['latitude'], 4);
                $lng = round($gps_data['longitude'], 4);
                $filename_parts[] = 'gps_' . str_replace(['.', '-'], ['d', 'n'], $lat) . '_' . str_replace(['.', '-'], ['d', 'n'], $lng);
            }
            
            // ID univoco finale più corto
            $filename_parts[] = substr(uniqid(), -8); // Solo ultimi 8 caratteri
            $filename_parts[] = uniqid();
            
            $filename = implode('_', $filename_parts) . '.' . $extension;
            $filepath = $upload_dir . '/' . $filename;

            // Elabora e salva l'immagine
            $processed = self::processImage($file['tmp_name'], $filepath, $file['type']);
            if (!$processed['success']) {
                return $processed;
            }

            return [
                'success' => true,
                'path' => $filepath,
                'filename' => $filename,
                'size' => filesize($filepath),
                'mime_type' => $file['type'],
                'gps_data' => $gps_data
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore durante il salvataggio: ' . $e->getMessage()];
        }
    }

    /**
     * Valida il file caricato
     */
    private static function validateUploadedFile($file) {
        // Controlla errori di upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File troppo grande (limite server)',
                UPLOAD_ERR_FORM_SIZE => 'File troppo grande (limite form)',
                UPLOAD_ERR_PARTIAL => 'Upload incompleto',
                UPLOAD_ERR_NO_FILE => 'Nessun file selezionato',
                UPLOAD_ERR_NO_TMP_DIR => 'Directory temporanea mancante',
                UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere su disco',
                UPLOAD_ERR_EXTENSION => 'Upload bloccato da estensione'
            ];
            return ['valid' => false, 'error' => $error_messages[$file['error']] ?? 'Errore sconosciuto'];
        }

        // Controlla dimensione
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'File troppo grande. Max ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
        }

        // Controlla tipo MIME
        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
            return ['valid' => false, 'error' => 'Tipo di file non consentito. Usa: JPG, PNG, GIF, WebP'];
        }

        // Controlla estensione
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return ['valid' => false, 'error' => 'Estensione file non consentita'];
        }

        // Verifica che sia davvero un'immagine
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return ['valid' => false, 'error' => 'File non è un\'immagine valida'];
        }

        return ['valid' => true];
    }

    /**
     * Elabora l'immagine (ridimensiona se necessario)
     */
    private static function processImage($source_path, $dest_path, $mime_type) {
        try {
            // Carica l'immagine sorgente
            switch ($mime_type) {
                case 'image/jpeg':
                case 'image/jpg':
                    $source_image = imagecreatefromjpeg($source_path);
                    break;
                case 'image/png':
                    $source_image = imagecreatefrompng($source_path);
                    break;
                case 'image/gif':
                    $source_image = imagecreatefromgif($source_path);
                    break;
                case 'image/webp':
                    $source_image = imagecreatefromwebp($source_path);
                    break;
                default:
                    return ['success' => false, 'error' => 'Formato immagine non supportato'];
            }

            if (!$source_image) {
                return ['success' => false, 'error' => 'Impossibile leggere l\'immagine'];
            }

            // Ottieni dimensioni originali
            $orig_width = imagesx($source_image);
            $orig_height = imagesy($source_image);

            // Calcola nuove dimensioni se necessario
            $new_width = $orig_width;
            $new_height = $orig_height;

            if ($orig_width > self::MAX_WIDTH || $orig_height > self::MAX_HEIGHT) {
                $ratio = min(self::MAX_WIDTH / $orig_width, self::MAX_HEIGHT / $orig_height);
                $new_width = intval($orig_width * $ratio);
                $new_height = intval($orig_height * $ratio);
            }

            // Crea nuova immagine se serve ridimensionare
            if ($new_width != $orig_width || $new_height != $orig_height) {
                $new_image = imagecreatetruecolor($new_width, $new_height);
                
                // Preserva trasparenza per PNG e GIF
                if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
                    imagealphablending($new_image, false);
                    imagesavealpha($new_image, true);
                    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                    imagefill($new_image, 0, 0, $transparent);
                }

                imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
                imagedestroy($source_image);
                $source_image = $new_image;
            }

            // Salva l'immagine elaborata
            $saved = false;
            switch ($mime_type) {
                case 'image/jpeg':
                case 'image/jpg':
                    $saved = imagejpeg($source_image, $dest_path, 85);
                    break;
                case 'image/png':
                    $saved = imagepng($source_image, $dest_path, 6);
                    break;
                case 'image/gif':
                    $saved = imagegif($source_image, $dest_path);
                    break;
                case 'image/webp':
                    $saved = imagewebp($source_image, $dest_path, 85);
                    break;
            }

            imagedestroy($source_image);

            if (!$saved) {
                return ['success' => false, 'error' => 'Impossibile salvare l\'immagine elaborata'];
            }

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore durante l\'elaborazione: ' . $e->getMessage()];
        }
    }

    /**
     * Elimina una foto di scontrino
     */
    public static function deleteScontrinoPhoto($photo_path) {
        if (!empty($photo_path) && file_exists($photo_path)) {
            return unlink($photo_path);
        }
        return true;
    }

    /**
     * Genera URL per visualizzare la foto
     */
    public static function getPhotoUrl($photo_path) {
        if (empty($photo_path) || !file_exists($photo_path)) {
            return null;
        }
        return 'view_photo.php?path=' . urlencode(base64_encode($photo_path));
    }

    /**
     * Verifica se un file è un'immagine valida
     */
    public static function isValidImage($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        $image_info = getimagesize($file_path);
        return $image_info !== false;
    }

    /**
     * Ottieni informazioni su un'immagine
     */
    public static function getImageInfo($file_path) {
        if (!file_exists($file_path)) {
            return null;
        }
        
        $image_info = getimagesize($file_path);
        if ($image_info === false) {
            return null;
        }

        return [
            'width' => $image_info[0],
            'height' => $image_info[1],
            'mime_type' => $image_info['mime'],
            'size' => filesize($file_path),
            'size_human' => self::formatFileSize(filesize($file_path))
        ];
    }

    /**
     * Formatta la dimensione del file in modo human-readable
     */
    private static function formatFileSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }
        return round($size, 2) . ' ' . $units[$index];
    }

    /**
     * Sanitizza una stringa per uso sicuro come nome file
     */
    private static function sanitizeForFilename($string) {
        // Rimuove caratteri speciali e sostituisce spazi
        $string = preg_replace('/[^a-zA-Z0-9\-_]/', '', $string);
        // Limita la lunghezza
        $string = substr($string, 0, 20);
        // Assicura che non sia vuoto
        return empty($string) ? 'user' : $string;
    }
}