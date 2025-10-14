<?php
require_once 'includes/installation_check.php';
requireBootstrap();
require_once 'includes/image_manager.php';
Auth::requireLogin();

// Controllo automatico per dispositivi mobili (solo se non � forzata la versione desktop)
if (!isset($_GET['force_desktop'])) {
    Utils::smartRedirect('aggiungi.php', 'aggiungi-mobile.php');
}

// Redirect automatico alla versione mobile se necessario
if (Utils::isMobileDevice() && !isset($_GET['force_desktop'])) {
    Utils::redirect('aggiungi-mobile.php');
}

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();
$error = '';
$success = '';
$warning = '';

// Per responsabili e admin, recupera la lista utenti disponibili
$available_users = Auth::getAvailableUsersForReceipts();

if ($_POST) {
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $data = $_POST['data'] ?? '';
    $lordo = Utils::safeFloat($_POST['lordo'] ?? '');
    $da_versare = Utils::safeFloat($_POST['da_versare'] ?? '');
    $note = Utils::sanitizeString($_POST['note'] ?? '');
    $selected_user_id = isset($_POST['utente_id']) ? (int)$_POST['utente_id'] : null;
    
    // Gestione file foto scontrino
    $foto_uploaded = false;
    $foto_path = null;
    $foto_mime_type = null;
    $foto_size = null;
    
    // Gestione coordinate GPS se presenti
    $gps_data = null;
    if (!empty($_POST['gps_latitude']) && !empty($_POST['gps_longitude'])) {
        $gps_data = [
            'latitude' => (float)$_POST['gps_latitude'],
            'longitude' => (float)$_POST['gps_longitude'],
            'accuracy' => !empty($_POST['gps_accuracy']) ? (float)$_POST['gps_accuracy'] : null
        ];
    }
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto_uploaded = true;
    }
    
    // Se da_versare � vuoto e lordo non � zero, usa l'importo lordo
    if (empty($_POST['da_versare']) && $lordo != 0) {
        $da_versare = $lordo;
    }
    
    // Determina l'utente e la filiale per lo scontrino
    $target_user_id = $current_user['id'];
    $target_filiale_id = $current_user['filiale_id'];
    
    // Se � responsabile o admin e ha selezionato un utente specifico
    if ((Auth::isResponsabile() || Auth::isAdmin()) && $selected_user_id) {
        // Verifica che l'utente selezionato sia autorizzato
        $selected_user = null;
        foreach ($available_users as $user) {
            if ($user['id'] == $selected_user_id) {
                $selected_user = $user;
                break;
            }
        }
        
        if ($selected_user) {
            // Ottieni i dettagli completi dell'utente selezionato
            $user_details = $db->fetchOne("
                SELECT id, filiale_id FROM utenti WHERE id = ? AND attivo = 1
            ", [$selected_user_id]);
            
            if ($user_details) {
                $target_user_id = $user_details['id'];
                $target_filiale_id = $user_details['filiale_id'];
            }
        }
    }
    
    // Validazione
    if (empty($nome)) {
        $error = 'Il nome dello scontrino � obbligatorio';
    } elseif (empty($data)) {
        $error = 'La data dello scontrino � obbligatoria';
    } elseif ($lordo == 0) {
        $error = 'L\'importo lordo non pu� essere zero';
    } elseif ($lordo > 0 && $da_versare > $lordo) {
        $error = 'L\'importo da versare non pu� essere maggiore dell\'importo lordo';
    } elseif ($lordo < 0 && $da_versare < $lordo) {
        $error = 'L\'importo da versare non pu� essere inferiore all\'importo lordo (per scontrini negativi)';
    } else {
        // Warning non bloccante per importi negativi
        if ($lordo < 0) {
            $warning = '?? Attenzione: stai inserendo uno scontrino con importo negativo (rimborso/nota di credito)';
        }
        
        try {
            // Calcola il netto (se non specificato, uguale al lordo)
            $netto = $lordo; // In futuro potresti implementare una logica per il calcolo delle tasse

            
            // Genera numero progressivo univoco per lo scontrino
            $stmt = $db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 3) AS UNSIGNED)), 0) + 1 AS next_number FROM scontrini WHERE numero LIKE 'SC%'");
            $next_number = $stmt->fetch()['next_number'];
            $numero_scontrino = 'SC' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
            
            // Inserisci lo scontrino con numero progressivo e nome persona separati
            $db->query("
                INSERT INTO scontrini (numero, nome_persona, data, lordo, netto, da_versare, note, utente_id, filiale_id, foto, gps_coords) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$numero_scontrino, $nome, $data, $lordo, $netto, $da_versare, $note, $target_user_id, $target_filiale_id, $foto_path, 
                $gps_data ? json_encode($gps_data) : null
            ]);
            
            $scontrino_id = $db->lastInsertId();
            
            // Gestisce l'upload della foto se presente
            if ($foto_uploaded) {
                // Prepara info utente per nome file
                $user_info = [
                    'username' => $current_user['username'],
                    'nome' => $current_user['nome']
                ];
                
                $upload_result = ImageManager::saveScontrinoPhoto($_FILES['foto'], $scontrino_id, $user_info, $gps_data);
                
                if ($upload_result['success']) {
                    // Aggiorna lo scontrino con i dati della foto
                    $db->query("
                        UPDATE scontrini 
                        SET foto = ?
                        WHERE id = ?
                    ", [$upload_result['path'], $scontrino_id]);
                } else {
                    // Se l'upload fallisce, mostra un warning ma non bloccare il salvataggio
                    $upload_warning = 'Scontrino salvato ma foto non caricata: ' . $upload_result['error'];
                    $warning = $warning ? $warning . ' | ' . $upload_warning : $upload_warning;
                }
            }
            
            $success_message = 'Scontrino aggiunto con successo!';
            if ($warning) {
                $success_message .= ' ' . $warning;
            }
            if ($target_user_id !== $current_user['id']) {
                // Trova il nome dell'utente per il messaggio
                $target_user_name = '';
                foreach ($available_users as $user) {
                    if ($user['id'] == $target_user_id) {
                        $target_user_name = $user['nome'];
                        break;
                    }
                }
                $success_message = "Scontrino aggiunto con successo per l'utente: " . $target_user_name;
                if ($warning) {
                    $success_message .= ' ' . $warning;
                }
            }
            
            Utils::setFlashMessage('success', $success_message . ' Ora puoi aggiungere i dettagli degli articoli.');
            Utils::redirect('modifica.php?id=' . $scontrino_id);
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
}

$page_title = 'Aggiungi Scontrino - ' . SITE_NAME;
$page_header = 'Aggiungi Nuovo Scontrino';

ob_start();
?>

<?php if (Auth::isAdmin() || Auth::isResponsabile()): ?>
<div class="d-flex justify-content-end mb-3">
    <a href="import-excel.php" class="btn btn-success">
        <i class="fas fa-file-excel me-2"></i>Importa da Excel
    </a>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($warning): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($warning); ?>
    </div>
    <?php endif; ?>
    
    <?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
    <div class="form-group user-selection-field">
        <label for="utente_id"><i class="fas fa-user"></i> Associa Scontrino a Utente</label>
        <select id="utente_id" name="utente_id" class="form-control">
            <option value="">-- Seleziona utente (o lascia vuoto per te stesso) --</option>
            <?php foreach ($available_users as $user): ?>
                <option value="<?php echo $user['id']; ?>" 
                        <?php echo (isset($selected_user_id) && $selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($user['nome'] . ' (' . $user['username'] . ')'); ?>
                    <?php if (Auth::isAdmin()): ?>
                        - <?php echo htmlspecialchars($user['filiale_nome']); ?>
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">
            <?php if (Auth::isAdmin()): ?>
                Come admin, puoi associare lo scontrino a qualsiasi utente di qualsiasi filiale
            <?php else: ?>
                Come responsabile, puoi associare lo scontrino agli utenti della tua filiale
            <?php endif; ?>
        </small>
    </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label for="nome"><i class="fas fa-tag"></i> Nome Scontrino *</label>
        <div class="autocomplete">
            <input type="text" id="nome" name="nome" required 
                   value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                   placeholder="Es: Acquisto materiali ufficio">
        </div>
        <small class="text-muted">Inizia a digitare per vedere i suggerimenti dai nomi precedenti</small>
    </div>
    
    <div class="form-group">
        <label for="data"><i class="fas fa-calendar"></i> Data Scontrino *</label>
        <input type="date" id="data" name="data" required
               value="<?php echo htmlspecialchars($data ?? date('Y-m-d')); ?>">
    </div>
    
    <div class="form-group">
        <label for="lordo"><i class="fas fa-euro-sign"></i> Importo Lordo *</label>
        <input type="text" id="lordo" name="lordo" required
               pattern="-?[0-9]+([,\.][0-9]{1,2})?"
               value="<?php echo htmlspecialchars($lordo ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Importo totale dello scontrino (es: 123,45 oppure -50,00 per rimborsi/note di credito)</small>
        <div id="lordo-warning" class="alert alert-warning mt-2" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Attenzione:</strong> Stai inserendo un importo negativo (rimborso/nota di credito)
        </div>
    </div>
    
    <div class="form-group">
        <label for="da_versare"><i class="fas fa-hand-holding-usd"></i> Importo da Versare</label>
        <input type="text" id="da_versare" name="da_versare"
               pattern="-?[0-9]*([,\.][0-9]{1,2})?"
               value="<?php echo htmlspecialchars($da_versare ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Importo che deve essere versato (lascia vuoto se uguale all'importo lordo, oppure inserisci 0 se nulla da versare)</small>
    </div>
    
    <div class="form-group">
        <label for="note"><i class="fas fa-sticky-note"></i> Note (opzionale)</label>
        <textarea id="note" name="note" rows="3" 
                  placeholder="Note aggiuntive..."><?php echo htmlspecialchars($note ?? ''); ?></textarea>
    </div>
    
    <div class="form-group">
        <label for="foto"><i class="fas fa-camera"></i> Foto Scontrino (opzionale)</label>
        <input type="file" id="foto" name="foto" 
               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
               class="form-control">
        <small class="text-muted">
            Formati supportati: JPG, PNG, GIF, WebP. Dimensione massima: 5MB.<br>
            L'immagine sara' ridimensionata automaticamente se troppo grande.
        </small>
        <div id="foto-preview" style="margin-top: 10px; display: none;">
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
    </div>
    
    <!-- Sezione Dettagli Articoli Scontrino -->
    <div class="card mt-4" id="scontrino-dettagli-section" style="display: none;">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-list-ul me-2"></i>Dettagli Articoli
                <small class="text-muted ms-2">(Opzionale - Aggiungi dopo aver salvato lo scontrino)</small>
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Funzionalit� avanzata:</strong> Dopo aver salvato lo scontrino, potrai aggiungere i dettagli degli articoli 
                con possibilit� di importazione da Excel.
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" width="80">N�</th>
                            <th width="120">Codice Art.</th>
                            <th>Descrizione Materiale</th>
                            <th class="text-end" width="100">Qt�</th>
                            <th class="text-end" width="120">Prezzo Unit.</th>
                            <th class="text-end" width="120">Totale</th>
                            <th class="text-center" width="100">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="dettagli-tbody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox mb-2 d-block" style="font-size: 2rem;"></i>
                                I dettagli articoli saranno disponibili dopo aver salvato lo scontrino.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary" id="btn-aggiungi-dettaglio" disabled>
                        <i class="fas fa-plus me-2"></i>Aggiungi Dettaglio
                    </button>
                    <button type="button" class="btn btn-success ms-2" id="btn-import-excel" disabled>
                        <i class="fas fa-file-excel me-2"></i>Import Excel
                    </button>
                </div>
                <div class="col-md-8">
                    <div class="row text-end">
                        <div class="col-4">
                            <strong>Articoli:</strong><br>
                            <span id="totale-articoli" class="badge bg-primary">0</span>
                        </div>
                        <div class="col-4">
                            <strong>Qt� Totale:</strong><br>
                            <span id="totale-qta" class="badge bg-info">0</span>
                        </div>
                        <div class="col-4">
                            <strong>Importo Totale:</strong><br>
                            <span id="totale-importo" class="badge bg-success">� 0,00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-save"></i> Salva Scontrino
        </button>
        <a href="index.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
    </div>
    
    <!-- Link per passare alla versione mobile -->
    <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
        <a href="aggiungi-mobile.php?force_mobile=1" style="color: #6c757d; font-size: 14px; text-decoration: none;">
              📱 Passa alla versione mobile   📱
        </a>
    </div>
</form>

<?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectUtente = document.getElementById('utente_id');
    const pageHeader = document.querySelector('h1');
    const originalHeader = pageHeader.textContent;
    
    selectUtente.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const userName = selectedOption.textContent.split(' (')[0];
            pageHeader.textContent = 'Aggiungi Scontrino per: ' + userName;
            pageHeader.className = 'text-primary';
        } else {
            pageHeader.textContent = originalHeader;
            pageHeader.className = '';
        }
    });
});
</script>
<?php endif; ?>

<script>
// Warning per importo negativo in tempo reale
document.getElementById('lordo').addEventListener('input', function(e) {
    const value = parseFloat(e.target.value.replace(',', '.'));
    const warningDiv = document.getElementById('lordo-warning');
    
    if (value < 0) {
        warningDiv.style.display = 'block';
    } else {
        warningDiv.style.display = 'none';
    }
});

// Gestione anteprima foto
document.getElementById('foto').addEventListener('change', function(e) {
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

// Funzioni GPS
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
        maximumAge: 60000 // Cache per 1 minuto
    };
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            // Salva coordinate nei campi hidden
            document.getElementById('gps_latitude').value = lat;
            document.getElementById('gps_longitude').value = lng;
            document.getElementById('gps_accuracy').value = accuracy;
            
            showGpsMessage(`Posizione acquisita (�${Math.round(accuracy)}m)`, 'success');
            
            console.log('GPS coords:', { lat, lng, accuracy });
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
    
    // Colori basati sul tipo
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
    
    // Nascondi dopo 5 secondi per messaggi di successo
    if (type === 'success') {
        setTimeout(() => {
            gpsStatus.style.display = 'none';
        }, 5000);
    }
}

function clearFotoPreview() {
    document.getElementById('foto').value = '';
    document.getElementById('foto-preview').style.display = 'none';
}

// Drag and drop per la foto
const fotoInput = document.getElementById('foto');
const fotoLabel = fotoInput.parentElement;

fotoLabel.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.backgroundColor = '#f8f9fa';
    this.style.borderColor = '#007bff';
});

fotoLabel.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.style.backgroundColor = '';
    this.style.borderColor = '';
});

fotoLabel.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.backgroundColor = '';
    this.style.borderColor = '';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fotoInput.files = files;
        fotoInput.dispatchEvent(new Event('change'));
    }
});

// Inizializzazione dettagli scontrino (se siamo in modalit� modifica)
document.addEventListener('DOMContentLoaded', function() {
    // Nota: I dettagli saranno abilitati solo dopo il salvataggio dello scontrino
    // In modalit� aggiunta, la sezione rimane disabilitata
});
</script>

<!-- Includi il JavaScript per la gestione dettagli scontrino -->
<script src="assets/js/scontrino-dettagli.js"></script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>