<?php
/**
 * INTERFACE WEB PER RINOMINARE FILE LUNGHI
 */
require_once 'includes/bootstrap.php';

// Verifica autenticazione admin
Auth::requireLogin();
$current_user = Auth::getCurrentUser();
if (!Auth::isAdmin()) {
    die('❌ Solo gli amministratori possono eseguire questo script');
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rinomina File Lunghi - Scontrini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4><i class="fas fa-file-signature"></i> Rinomina File Foto Lunghi</h4>
                </div>
                <div class="card-body">

<?php
if ($_POST && isset($_POST['rename'])) {
    echo '<div class="alert alert-info"><i class="fas fa-cog fa-spin"></i> Avvio rinomina...</div>';
    
    try {
        $db = Database::getInstance();
        $renamed = 0;
        $errors = 0;
        $checked = 0;
        
        // Trova tutti i record con foto
        $scontrini_with_photos = $db->fetchAll("
            SELECT id, numero, foto 
            FROM scontrini 
            WHERE foto IS NOT NULL AND foto != ''
        ");
        
        echo '<div class="alert alert-info">Trovati ' . count($scontrini_with_photos) . ' scontrini con foto da controllare</div>';
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h5>File da rinominare:</h5>';
        echo '<div class="list-group" style="max-height: 400px; overflow-y: auto;">';
        
        foreach ($scontrini_with_photos as $scontrino) {
            $old_path = $scontrino['foto'];
            $filename = basename($old_path);
            $checked++;
            
            // Controlla se il nome è troppo lungo (oltre 150 caratteri)
            if (strlen($filename) > 150) {
                echo '<div class="list-group-item">';
                echo '<h6 class="mb-1">Scontrino #' . $scontrino['id'] . '</h6>';
                echo '<p class="mb-1"><small class="text-muted">Lunghezza: ' . strlen($filename) . ' caratteri</small></p>';
                echo '<small class="text-break">' . htmlspecialchars($filename) . '</small>';
                
                // Estrae informazioni dal nome file
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $scontrino_id = $scontrino['id'];
                
                // Genera nuovo nome compatto
                $timestamp = date('Ymd_His');
                $unique_id = substr(uniqid(), -8);
                $new_filename = "scontrino_{$scontrino_id}_{$timestamp}_{$unique_id}.{$extension}";
                
                $directory = dirname($old_path);
                $new_path = $directory . '/' . $new_filename;
                
                echo '<hr>';
                echo '<p class="mb-1"><strong>Nuovo nome:</strong></p>';
                echo '<small class="text-success">' . htmlspecialchars($new_filename) . ' (' . strlen($new_filename) . ' caratteri)</small>';
                
                // Rinomina il file fisico
                if (file_exists($old_path)) {
                    if (rename($old_path, $new_path)) {
                        // Aggiorna il database
                        $updated = $db->query("UPDATE scontrini SET foto = ? WHERE id = ?", [
                            $new_path, 
                            $scontrino_id
                        ]);
                        
                        if ($updated) {
                            echo '<div class="badge bg-success mt-1">✅ Rinominato</div>';
                            $renamed++;
                        } else {
                            echo '<div class="badge bg-danger mt-1">❌ Errore DB</div>';
                            // Ripristina il nome originale
                            rename($new_path, $old_path);
                            $errors++;
                        }
                    } else {
                        echo '<div class="badge bg-danger mt-1">❌ Errore file</div>';
                        $errors++;
                    }
                } else {
                    echo '<div class="badge bg-warning mt-1">⚠️ File non esiste</div>';
                    $errors++;
                }
                
                echo '</div>';
            }
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<h5>Statistiche:</h5>';
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo '<p><strong>File controllati:</strong> ' . $checked . '</p>';
        echo '<p><strong>File rinominati:</strong> <span class="text-success">' . $renamed . '</span></p>';
        echo '<p><strong>Errori:</strong> <span class="text-danger">' . $errors . '</span></p>';
        echo '</div>';
        echo '</div>';
        
        if ($renamed > 0) {
            echo '<div class="alert alert-success mt-3">';
            echo '<h5>✅ Rinomina completata!</h5>';
            echo '<p>I file ora hanno nomi più corti. Le foto dovrebbero funzionare meglio.</p>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-info mt-3">';
            echo '<h5>ℹ️ Nessun file da rinominare</h5>';
            echo '<p>Tutti i file hanno già nomi di lunghezza accettabile.</p>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mt-3">';
        echo '<a href="lista.php" class="btn btn-primary">';
        echo '<i class="fas fa-list"></i> Torna alla Lista';
        echo '</a>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<h5>❌ Errore durante la rinomina:</h5>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    
} else {
    // Mostra form di conferma e preview
    ?>
    
    <div class="alert alert-warning">
        <h5><i class="fas fa-exclamation-triangle"></i> File con Nomi Troppo Lunghi</h5>
        <p>Alcuni file foto hanno nomi molto lunghi che possono causare problemi di visualizzazione. Questo script:</p>
        <ul>
            <li>Trova file con nomi oltre 150 caratteri</li>
            <li>Li rinomina con un formato più compatto</li>
            <li>Aggiorna i riferimenti nel database</li>
            <li>Mantiene l'ID scontrino per il collegamento</li>
        </ul>
        <p><strong>Nota:</strong> L'operazione è reversibile e sicura.</p>
    </div>
    
    <?php
    try {
        $db = Database::getInstance();
        $long_files = $db->fetchAll("
            SELECT id, numero, foto 
            FROM scontrini 
            WHERE foto IS NOT NULL AND foto != '' 
            AND LENGTH(foto) > 150
        ");
        
        if (count($long_files) > 0) {
            echo '<div class="alert alert-info">';
            echo '<h6>Trovati ' . count($long_files) . ' file da rinominare:</h6>';
            echo '<ul class="list-unstyled">';
            foreach (array_slice($long_files, 0, 5) as $file) {
                $filename = basename($file['foto']);
                echo '<li><small>• ' . htmlspecialchars(substr($filename, 0, 100)) . '... (' . strlen($filename) . ' char)</small></li>';
            }
            if (count($long_files) > 5) {
                echo '<li><small>... e altri ' . (count($long_files) - 5) . ' file</small></li>';
            }
            echo '</ul>';
            echo '</div>';
            
            echo '<form method="POST">';
            echo '<div class="d-grid gap-2">';
            echo '<button type="submit" name="rename" value="1" class="btn btn-info btn-lg">';
            echo '<i class="fas fa-file-signature"></i> Rinomina File Lunghi';
            echo '</button>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<div class="alert alert-success">';
            echo '<h5>✅ Nessun file da rinominare</h5>';
            echo '<p>Tutti i file hanno nomi di lunghezza accettabile.</p>';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<h5>❌ Errore controllo file:</h5>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
    
    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Annulla
        </a>
    </div>
    
<?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>