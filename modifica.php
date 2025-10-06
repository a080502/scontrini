<?php
require_once 'includes/bootstrap.php';
require_once 'includes/image_manager.php';
Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    Utils::setFlashMessage('error', 'ID scontrino non valido');
    Utils::redirect('lista.php');
}

// Recupera scontrino
$scontrino = $db->fetchOne("SELECT * FROM scontrini WHERE id = ?", [$id]);

if (!$scontrino) {
    Utils::setFlashMessage('error', 'Scontrino non trovato');
    Utils::redirect('lista.php');
}

// Verifica che lo scontrino non sia archiviato
if ($scontrino['archiviato']) {
    Utils::setFlashMessage('error', 'Non puoi modificare uno scontrino archiviato');
    Utils::redirect('archivio.php');
}

$error = '';

if ($_POST) {
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $data_scontrino = $_POST['data_scontrino'] ?? '';
    $lordo = Utils::safeFloat($_POST['lordo'] ?? '');
    $da_versare = Utils::safeFloat($_POST['da_versare'] ?? '');
    $note = Utils::sanitizeString($_POST['note'] ?? '');
    
    // Gestione rimozione foto esistente
    $rimuovi_foto = isset($_POST['rimuovi_foto']) && $_POST['rimuovi_foto'] == '1';
    
    // Gestione upload nuova foto
    $foto_uploaded = false;
    if (isset($_FILES['foto_scontrino']) && $_FILES['foto_scontrino']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto_uploaded = true;
    }
    
    // Se da_versare è vuoto o zero, usa l'importo lordo
    if ($da_versare <= 0) {
        $da_versare = $lordo;
    }
    
    // Validazione
    if (empty($nome)) {
        $error = 'Il nome dello scontrino è obbligatorio';
    } elseif (empty($data_scontrino)) {
        $error = 'La data dello scontrino è obbligatoria';
    } elseif ($lordo <= 0) {
        $error = 'L\'importo lordo deve essere maggiore di zero';
    } elseif ($da_versare < 0) {
        $error = 'L\'importo da versare non può essere negativo';
    } elseif ($da_versare > $lordo) {
        $error = 'L\'importo da versare non può essere maggiore dell\'importo lordo';
    } else {
        try {
            // Gestione foto
            $foto_path_update = '';
            $foto_mime_type_update = '';
            $foto_size_update = '';
            
            // Se richiesta rimozione foto esistente
            if ($rimuovi_foto && !empty($scontrino['foto_scontrino'])) {
                ImageManager::deleteScontrinoPhoto($scontrino['foto_scontrino']);
                $foto_path_update = ', foto_scontrino = NULL, foto_mime_type = NULL, foto_size = NULL';
            }
            
            // Se caricata nuova foto
            if ($foto_uploaded) {
                // Rimuovi foto esistente se presente
                if (!empty($scontrino['foto_scontrino'])) {
                    ImageManager::deleteScontrinoPhoto($scontrino['foto_scontrino']);
                }
                
                // Prepara info utente per nome file
                $user_info = [
                    'username' => $current_user['username'],
                    'nome' => $current_user['nome']
                ];
                
                // Gestione coordinate GPS se presenti
                $gps_data = null;
                if (!empty($_POST['gps_latitude']) && !empty($_POST['gps_longitude'])) {
                    $gps_data = [
                        'latitude' => (float)$_POST['gps_latitude'],
                        'longitude' => (float)$_POST['gps_longitude'],
                        'accuracy' => !empty($_POST['gps_accuracy']) ? (float)$_POST['gps_accuracy'] : null
                    ];
                }
                
                $upload_result = ImageManager::saveScontrinoPhoto($_FILES['foto_scontrino'], $id, $user_info, $gps_data);
                
                if ($upload_result['success']) {
                    $foto_path_update = ', foto_scontrino = ?, foto_mime_type = ?, foto_size = ?';
                    $additional_params = [$upload_result['path'], $upload_result['mime_type'], $upload_result['size']];
                } else {
                    $error = 'Errore durante il caricamento della foto: ' . $upload_result['error'];
                }
            }
            
            if (empty($error)) {
                // Costruisci la query di update
                $base_query = "UPDATE scontrini SET nome = ?, data_scontrino = ?, lordo = ?, da_versare = ?, note = ?, updated_at = NOW()";
                $base_params = [$nome, $data_scontrino, $lordo, $da_versare, $note];
                
                if (!empty($foto_path_update)) {
                    $base_query .= $foto_path_update;
                    if (isset($additional_params)) {
                        $base_params = array_merge($base_params, $additional_params);
                    }
                }
                
                $base_query .= " WHERE id = ?";
                $base_params[] = $id;
                
                $db->query($base_query, $base_params);
                
                Utils::setFlashMessage('success', 'Scontrino modificato con successo!');
                Utils::redirect('lista.php');
            }
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
} else {
    // Pre-compila i campi con i dati esistenti
    $nome = $scontrino['nome'];
    $data_scontrino = $scontrino['data_scontrino'];
    $lordo = $scontrino['lordo'];
    $da_versare = $scontrino['da_versare'] ?? $scontrino['lordo'];
    $note = $scontrino['note'];
}

$page_title = 'Modifica Scontrino - ' . SITE_NAME;
$page_header = 'Modifica Scontrino';

ob_start();
?>

<div class="card">
    <h4>Stato Attuale</h4>
    <p><strong>Creato:</strong> <?php echo Utils::formatDateTime($scontrino['created_at']); ?></p>
    <?php if ($scontrino['updated_at'] !== $scontrino['created_at']): ?>
    <p><strong>Ultima modifica:</strong> <?php echo Utils::formatDateTime($scontrino['updated_at']); ?></p>
    <?php endif; ?>
    <p><strong>Stato:</strong>
        <?php if ($scontrino['archiviato']): ?>
            <span class="badge" style="background-color: #6c757d;">Archiviato</span>
        <?php elseif ($scontrino['versato']): ?>
            <span class="badge badge-success">Versato</span>
        <?php elseif ($scontrino['incassato']): ?>
            <span class="badge badge-success">Incassato</span>
        <?php else: ?>
            <span class="badge badge-warning">Da Incassare</span>
        <?php endif; ?>
    </p>
</div>

<form method="POST" enctype="multipart/form-data">
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label for="nome"><i class="fas fa-tag"></i> Nome Scontrino *</label>
        <div class="autocomplete">
            <input type="text" id="nome" name="nome" required 
                   value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                   placeholder="Es: Acquisto materiali ufficio">
        </div>
    </div>
    
    <div class="form-group">
        <label for="data_scontrino"><i class="fas fa-calendar"></i> Data Scontrino *</label>
        <input type="date" id="data_scontrino" name="data_scontrino" required
               value="<?php echo htmlspecialchars($data_scontrino ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="lordo"><i class="fas fa-euro-sign"></i> Importo Lordo *</label>
        <input type="text" id="lordo" name="lordo" required
               pattern="[0-9]+([,\\.][0-9]{1,2})?"
               value="<?php echo htmlspecialchars($lordo ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Importo totale dello scontrino (es: 123,45)</small>
    </div>
    
    <div class="form-group">
        <label for="da_versare"><i class="fas fa-hand-holding-usd"></i> Importo da Versare</label>
        <input type="text" id="da_versare" name="da_versare"
               pattern="[0-9]*([,\\.][0-9]{1,2})?"
               value="<?php echo htmlspecialchars($da_versare ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Importo che deve essere versato (lascia vuoto se uguale all'importo lordo)</small>
    </div>
    
    <div class="form-group">
        <label for="note"><i class="fas fa-sticky-note"></i> Note (opzionale)</label>
        <textarea id="note" name="note" rows="3" 
                  placeholder="Note aggiuntive..."><?php echo htmlspecialchars($note ?? ''); ?></textarea>
    </div>
    
    <div class="form-group">
        <label><i class="fas fa-camera"></i> Foto Scontrino</label>
        
        <?php if (!empty($scontrino['foto_scontrino']) && file_exists($scontrino['foto_scontrino'])): ?>
        <div class="foto-attuale" id="foto-attuale">
            <p><strong>Foto attuale:</strong></p>
            <div style="margin-bottom: 15px;">
                <a href="<?php echo ImageManager::getPhotoUrl($scontrino['foto_scontrino']); ?>" target="_blank">
                    <img src="<?php echo ImageManager::getPhotoUrl($scontrino['foto_scontrino']) . '&thumbnail=1'; ?>" 
                         style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;"
                         alt="Foto scontrino attuale">
                </a>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rimuovi_foto" name="rimuovi_foto" value="1">
                <label class="form-check-label" for="rimuovi_foto">
                    <i class="fas fa-trash text-danger"></i> Rimuovi foto attuale
                </label>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 15px;">
            <label for="foto_scontrino">
                <?php if (!empty($scontrino['foto_scontrino'])): ?>
                    <i class="fas fa-sync-alt"></i> Sostituisci con nuova foto
                <?php else: ?>
                    <i class="fas fa-plus"></i> Aggiungi foto
                <?php endif; ?>
            </label>
            <input type="file" id="foto_scontrino" name="foto_scontrino" 
                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                   class="form-control">
            <small class="text-muted">
                Formati supportati: JPG, PNG, GIF, WebP. Dimensione massima: 5MB.<br>
                L'immagine sarà automaticamente ridimensionata se troppo grande.
            </small>
        </div>
        
                <div id="foto-preview" style="margin-top: 10px; display: none;">
                    <p><strong>Anteprima nuova foto:</strong></p>
                    <img id="preview-img" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="button" onclick="clearFotoPreview()" style="margin-left: 10px;" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Rimuovi
                    </button>
                </div>
            </div>
            
            <!-- Campi hidden per coordinate GPS -->
            <input type="hidden" id="gps_latitude" name="gps_latitude">
            <input type="hidden" id="gps_longitude" name="gps_longitude">
            <input type="hidden" id="gps_accuracy" name="gps_accuracy">
            
            <div id="gps-status" style="margin-top: 10px; padding: 8px; border-radius: 4px; display: none;">
                <i class="fas fa-map-marker-alt"></i> <span id="gps-message"></span>
            </div>    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-warning btn-lg">
            <i class="fas fa-save"></i> Salva Modifiche
        </button>
        <a href="lista.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
    </div>
</form>

<script>
// Gestione anteprima foto
document.getElementById('foto_scontrino').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('foto-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (file) {
        // Validazione lato client
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!validTypes.includes(file.type)) {
            alert('Formato file non valido. Usa: JPG, PNG, GIF, WebP');
            e.target.value = '';
            return;
        }
        
        if (file.size > maxSize) {
            alert('File troppo grande. Dimensione massima: 5MB');
            e.target.value = '';
            return;
        }
        
        // Mostra anteprima
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        // Acquisisci coordinate GPS quando viene selezionata una foto
        getCurrentLocation();
    } else {
        preview.style.display = 'none';
    }
});

// Funzioni GPS (stesse di aggiungi.php)
function getCurrentLocation() {
    const gpsStatus = document.getElementById('gps-status');
    const gpsMessage = document.getElementById('gps-message');
    
    if (!navigator.geolocation) {
        showGpsMessage('Geolocalizzazione non supportata dal browser', 'warning');
        return;
    }
    
    showGpsMessage('Acquisizione posizione...', 'info');
    
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    };
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            document.getElementById('gps_latitude').value = lat;
            document.getElementById('gps_longitude').value = lng;
            document.getElementById('gps_accuracy').value = accuracy;
            
            showGpsMessage(`Posizione acquisita (±${Math.round(accuracy)}m)`, 'success');
        },
        function(error) {
            let message = 'Errore acquisizione posizione: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Permesso negato';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Posizione non disponibile';
                    break;
                case error.TIMEOUT:
                    message += 'Timeout';
                    break;
                default:
                    message += 'Errore sconosciuto';
                    break;
            }
            showGpsMessage(message, 'error');
        },
        options
    );
}

function showGpsMessage(message, type) {
    const gpsStatus = document.getElementById('gps-status');
    const gpsMessage = document.getElementById('gps-message');
    
    gpsMessage.textContent = message;
    gpsStatus.style.display = 'block';
    
    switch(type) {
        case 'success':
            gpsStatus.style.backgroundColor = '#d4edda';
            gpsStatus.style.color = '#155724';
            gpsStatus.style.borderColor = '#c3e6cb';
            break;
        case 'error':
            gpsStatus.style.backgroundColor = '#f8d7da';
            gpsStatus.style.color = '#721c24';
            gpsStatus.style.borderColor = '#f5c6cb';
            break;
        case 'warning':
            gpsStatus.style.backgroundColor = '#fff3cd';
            gpsStatus.style.color = '#856404';
            gpsStatus.style.borderColor = '#ffeaa7';
            break;
        case 'info':
            gpsStatus.style.backgroundColor = '#d1ecf1';
            gpsStatus.style.color = '#0c5460';
            gpsStatus.style.borderColor = '#bee5eb';
            break;
    }
    
    if (type === 'success') {
        setTimeout(() => {
            gpsStatus.style.display = 'none';
        }, 5000);
    }
}

function clearFotoPreview() {
    document.getElementById('foto_scontrino').value = '';
    document.getElementById('foto-preview').style.display = 'none';
}

// Gestione checkbox rimozione foto
document.getElementById('rimuovi_foto')?.addEventListener('change', function() {
    const fotoAttuale = document.getElementById('foto-attuale');
    const fotoInput = document.getElementById('foto_scontrino');
    
    if (this.checked) {
        fotoAttuale.style.opacity = '0.5';
        fotoAttuale.style.textDecoration = 'line-through';
    } else {
        fotoAttuale.style.opacity = '1';
        fotoAttuale.style.textDecoration = 'none';
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>