<?php
require_once 'includes/installation_check.php';
requireBootstrap();
require_once 'includes/image_manager.php';
Auth::requireLogin();

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();
$error = '';
$success = '';

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
    
    if (isset($_FILES['foto_scontrino']) && $_FILES['foto_scontrino']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto_uploaded = true;
    }
    
    // Se da_versare è vuoto o zero, usa l'importo lordo
    if ($da_versare <= 0) {
        $da_versare = $lordo;
    }
    
    // Determina l'utente e la filiale per lo scontrino
    $target_user_id = $current_user['id'];
    $target_filiale_id = $current_user['filiale_id'];
    
    // Se è responsabile o admin e ha selezionato un utente specifico
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
        $error = 'Il nome dello scontrino è obbligatorio';
    } elseif (empty($data)) {
        $error = 'La data dello scontrino è obbligatoria';
    } elseif ($lordo <= 0) {
        $error = 'L\'importo lordo deve essere maggiore di zero';
    } elseif ($da_versare < 0) {
        $error = 'L\'importo da versare non può essere negativo';
    } elseif ($da_versare > $lordo) {
        $error = 'L\'importo da versare non può essere maggiore dell\'importo lordo';
    } else {
        try {
            // Calcola il netto (se non specificato, uguale al lordo)
            $netto = $lordo; // In futuro potresti implementare una logica per il calcolo delle tasse
            
            // Inserisci lo scontrino associandolo all'utente e filiale determinati
            $db->query("
                INSERT INTO scontrini (numero, data, lordo, netto, da_versare, note, utente_id, filiale_id, foto, gps_coords) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$nome, $data, $lordo, $netto, $da_versare, $note, $target_user_id, $target_filiale_id, $foto_path, 
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
                
                $upload_result = ImageManager::saveScontrinoPhoto($_FILES['foto_scontrino'], $scontrino_id, $user_info, $gps_data);
                
                if ($upload_result['success']) {
                    // Aggiorna lo scontrino con i dati della foto
                    $db->query("
                        UPDATE scontrini 
                        SET foto = ?
                        WHERE id = ?
                    ", [$upload_result['path'], $scontrino_id]);
                } else {
                    // Se l'upload fallisce, mostra un warning ma non bloccare il salvataggio
                    $warning = 'Scontrino salvato ma foto non caricata: ' . $upload_result['error'];
                }
            }
            
            $success_message = 'Scontrino aggiunto con successo!';
            if (isset($warning)) {
                $success_message .= ' ⚠️ ' . $warning;
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
                if (isset($warning)) {
                    $success_message .= ' ⚠️ ' . $warning;
                }
            }
            
            Utils::setFlashMessage('success', $success_message);
            Utils::redirect('index.php');
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Aggiungi Scontrino - <?php echo APP_NAME; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.4;
        }
        
        .mobile-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .mobile-header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: 600;
        }
        
        .container {
            padding: 15px;
            max-width: 100%;
        }
        
        .mobile-form {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .form-group label .required {
            color: #dc3545;
            font-size: 18px;
            margin-left: 3px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control:invalid {
            border-color: #dc3545;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-primary:hover, .btn-primary:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover, .btn-secondary:active {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .button-group .btn {
            flex: 1;
            min-width: 140px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .user-selector {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .user-selector label {
            color: #1976d2;
            font-weight: 700;
        }
        
        .help-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }
        
        .amount-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 480px) {
            .amount-inputs {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .container {
                padding: 10px;
            }
            
            .mobile-form {
                padding: 15px;
            }
        }
        
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 8px;
        }
        
        .quick-amount {
            padding: 8px;
            background: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .quick-amount:hover, .quick-amount:active {
            background: #007bff;
            color: white;
            transform: scale(1.05);
        }
        
        .floating-save {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50px;
            padding: 15px 25px;
            font-size: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .floating-save {
                bottom: 10px;
                right: 10px;
                left: 10px;
                border-radius: 8px;
                position: fixed;
            }
        }
        
        /* Stili per gestione foto */
        .file-input {
            padding: 12px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input:focus {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .foto-preview {
            margin-top: 15px;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .foto-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 2px solid #dee2e6;
        }
        
        .btn-clear-foto {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-clear-foto:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        /* Migliore supporto touch per file input */
        @media (pointer: coarse) {
            .file-input {
                padding: 20px;
                font-size: 18px;
            }
        }
        
        /* Stili per status GPS */
        .gps-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .gps-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .gps-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .gps-status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .gps-status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="mobile-header">
        <h1>📝 Aggiungi Scontrino</h1>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
        <div class="alert alert-danger">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <!-- Link per versione desktop -->
        <div style="text-align: center; margin-bottom: 15px;">
            <a href="aggiungi.php?force_desktop=1" style="color: #6c757d; font-size: 14px; text-decoration: none;">
                🖥️ Versione Desktop
            </a>
        </div>
        
        <form method="POST" class="mobile-form" enctype="multipart/form-data">
            <?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
            <div class="user-selector">
                <label for="utente_id">👤 Utente</label>
                <select id="utente_id" name="utente_id" class="form-control">
                    <option value="">Me stesso</option>
                    <?php foreach ($available_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo (isset($selected_user_id) && $selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome">🏷️ Nome <span class="required">*</span></label>
                <input type="text" 
                       id="nome" 
                       name="nome" 
                       class="form-control"
                       placeholder="Es: Cena cliente, Materiali..." 
                       value="<?php echo htmlspecialchars($nome ?? ''); ?>" 
                       required 
                       autocomplete="off">
                <div class="help-text">Descrivi brevemente lo scontrino</div>
            </div>
            
            <div class="form-group">
                <label for="data">📅 Data <span class="required">*</span></label>
                <input type="date" 
                       id="data" 
                       name="data" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($data ?? date('Y-m-d')); ?>" 
                       required>
            </div>
            
            <div class="amount-inputs">
                <div class="form-group">
                    <label for="lordo">💰 Importo <span class="required">*</span></label>
                    <input type="number" 
                           id="lordo" 
                           name="lordo" 
                           class="form-control"
                           placeholder="0.00" 
                           step="0.01" 
                           min="0.01"
                           value="<?php echo htmlspecialchars($lordo ?? ''); ?>" 
                           required>
                    <div class="quick-amounts">
                        <div class="quick-amount" onclick="setAmount('lordo', '10')">10€</div>
                        <div class="quick-amount" onclick="setAmount('lordo', '20')">20€</div>
                        <div class="quick-amount" onclick="setAmount('lordo', '50')">50€</div>
                        <div class="quick-amount" onclick="setAmount('lordo', '100')">100€</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="da_versare">💸 Da Versare</label>
                    <input type="number" 
                           id="da_versare" 
                           name="da_versare" 
                           class="form-control"
                           placeholder="Uguale all'importo" 
                           step="0.01" 
                           min="0"
                           value="<?php echo htmlspecialchars($da_versare ?? ''); ?>">
                    <div class="help-text">Lascia vuoto se uguale all'importo</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="note">📝 Note</label>
                <textarea id="note" 
                          name="note" 
                          class="form-control"
                          placeholder="Note aggiuntive (opzionale)..."
                          rows="3"><?php echo htmlspecialchars($note ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="foto_scontrino">📷 Foto Scontrino</label>
                <input type="file" 
                       id="foto_scontrino" 
                       name="foto_scontrino" 
                       class="form-control file-input"
                       accept="image/*"
                       capture="environment">
                <div class="help-text">Scatta o seleziona una foto dello scontrino (opzionale)</div>
                
                <div id="foto-preview" class="foto-preview" style="display: none;">
                    <img id="preview-img" alt="Anteprima foto">
                    <button type="button" onclick="clearFoto()" class="btn-clear-foto">❌ Rimuovi</button>
                </div>
            </div>
            
            <!-- Campi hidden per coordinate GPS -->
            <input type="hidden" id="gps_latitude" name="gps_latitude">
            <input type="hidden" id="gps_longitude" name="gps_longitude">
            <input type="hidden" id="gps_accuracy" name="gps_accuracy">
            
            <div id="gps-status" class="gps-status" style="display: none;">
                <div id="gps-message"></div>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    💾 Salva
                </button>
                <a href="index.php" class="btn btn-secondary">
                    ❌ Annulla
                </a>
            </div>
        </form>
        
        <!-- Link per passare alla versione desktop -->
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #dee2e6;">
            <a href="aggiungi.php?force_desktop=1" style="color: #6c757d; font-size: 14px; text-decoration: none;">
                💻 Passa alla versione desktop
            </a>
        </div>
    </div>

    <script>
        // Auto-copia dell'importo lordo in "da versare"
        document.getElementById('lordo').addEventListener('input', function() {
            const daVersare = document.getElementById('da_versare');
            if (!daVersare.value) {
                daVersare.value = this.value;
            }
        });
        
        // Funzione per impostare importi rapidi
        function setAmount(fieldId, amount) {
            document.getElementById(fieldId).value = amount;
            // Se stiamo impostando il lordo e da_versare è vuoto, copialo
            if (fieldId === 'lordo') {
                const daVersare = document.getElementById('da_versare');
                if (!daVersare.value) {
                    daVersare.value = amount;
                }
            }
        }
        
        // Vibrazione tattile su dispositivi mobili (se supportata)
        function vibrate() {
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        }
        
        // Aggiungi vibrazione ai pulsanti
        document.querySelectorAll('.btn, .quick-amount').forEach(element => {
            element.addEventListener('click', vibrate);
        });
        
        // Auto-focus sul primo campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nome').focus();
        });
        
        // Suggerimenti nome scontrino (simulazione autocomplete)
        const nomiComuni = [
            'Cena cliente',
            'Pranzo lavoro', 
            'Materiali ufficio',
            'Benzina',
            'Parcheggio',
            'Taxi',
            'Hotel',
            'Formazione',
            'Software',
            'Telefono'
        ];
        
        document.getElementById('nome').addEventListener('input', function() {
            // Implementazione base di suggerimenti
            // In una versione completa potresti fare una chiamata AJAX per ottenere nomi già usati
        });
        
        // Gestione foto
        document.getElementById('foto_scontrino').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('foto-preview');
            const previewImg = document.getElementById('preview-img');
            
            if (file) {
                // Validazione dimensione (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File troppo grande! Dimensione massima: 5MB');
                    e.target.value = '';
                    return;
                }
                
                // Validazione tipo file
                if (!file.type.startsWith('image/')) {
                    alert('Seleziona solo file immagine!');
                    e.target.value = '';
                    return;
                }
                
                // Mostra anteprima
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Scroll verso l'anteprima
                    preview.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                };
                reader.readAsDataURL(file);
                
                vibrate(); // Feedback tattile
                
                // Acquisisci coordinate GPS quando viene selezionata una foto
                getCurrentLocationMobile();
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Funzioni GPS per mobile
        function getCurrentLocationMobile() {
            if (!navigator.geolocation) {
                showGpsMessageMobile('📍 GPS non supportato', 'warning');
                return;
            }
            
            showGpsMessageMobile('📡 Acquisizione posizione...', 'info');
            
            const options = {
                enableHighAccuracy: true,
                timeout: 15000, // Timeout più lungo per mobile
                maximumAge: 30000 // Cache per 30 secondi
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
                    
                    showGpsMessageMobile(`📍 Posizione acquisita (±${Math.round(accuracy)}m)`, 'success');
                    vibrate(); // Feedback tattile per successo
                },
                function(error) {
                    let message = '⚠️ Errore GPS: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message += 'Permesso negato';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message += 'Non disponibile';
                            break;
                        case error.TIMEOUT:
                            message += 'Timeout';
                            break;
                        default:
                            message += 'Sconosciuto';
                            break;
                    }
                    showGpsMessageMobile(message, 'error');
                },
                options
            );
        }
        
        function showGpsMessageMobile(message, type) {
            const gpsStatus = document.getElementById('gps-status');
            const gpsMessage = document.getElementById('gps-message');
            
            gpsMessage.textContent = message;
            gpsStatus.className = 'gps-status ' + type;
            gpsStatus.style.display = 'block';
            
            // Nascondi dopo 4 secondi per messaggi di successo
            if (type === 'success') {
                setTimeout(() => {
                    gpsStatus.style.display = 'none';
                }, 4000);
            }
        }
        
        // Funzione per cancellare la foto
        function clearFoto() {
            document.getElementById('foto_scontrino').value = '';
            document.getElementById('foto-preview').style.display = 'none';
            vibrate();
        }
        
        // Migliora l'UX del file input su mobile
        document.getElementById('foto_scontrino').addEventListener('click', function() {
            vibrate();
        });
        
        // Gestisci il drag and drop se supportato
        const fotoInput = document.getElementById('foto_scontrino');
        const formGroup = fotoInput.closest('.form-group');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            formGroup.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            formGroup.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            formGroup.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            formGroup.style.background = '#e3f2fd';
            formGroup.style.borderColor = '#2196f3';
        }
        
        function unhighlight(e) {
            formGroup.style.background = '';
            formGroup.style.borderColor = '';
        }
        
        formGroup.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fotoInput.files = files;
                fotoInput.dispatchEvent(new Event('change'));
            }
        }
    </script>
</body>
</html>