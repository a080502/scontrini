<?php
session_start();

// --- Logica per Testare la Connessione DB (AJAX) ---
if (isset($_POST['action']) && $_POST['action'] === 'test_db_connection') {
    header('Content-Type: application/json');

    $db_host = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
    $db_username = isset($_POST['db_username']) ? trim($_POST['db_username']) : '';
    $db_password = isset($_POST['db_password']) ? trim($_POST['db_password']) : '';
    $db_name = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';

    $response = ['success' => false, 'message' => 'Dati di connessione incompleti.'];

    if (!empty($db_host) && !empty($db_username) && !empty($db_name)) {
        // Tentativo di connessione
        $conn_test = @new mysqli($db_host, $db_username, $db_password, $db_name);

        if ($conn_test->connect_error) {
            $response['message'] = "Connessione fallita: " . htmlspecialchars($conn_test->connect_error);
        } else {
            $response['success'] = true;
            $response['message'] = "Connessione al database riuscita!";
            $conn_test->close();
        }
    }
    echo json_encode($response);
    exit();
}

// --- Controllo di Sicurezza ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/bootstrap.php';

// Solo super può accedere
if (!Auth::isSuper()) {
    header("Location: index.php");
    exit();
}

// --- Gestione File di Configurazione ---
$configFile = 'config.php';
$config = [
    'DB_HOST' => DB_HOST,
    'DB_USER' => DB_USER,
    'DB_PASS' => DB_PASS,
    'DB_NAME' => DB_NAME,
    'SITE_NAME' => SITE_NAME
];

$message = '';
$message_type = '';
$perform_delayed_redirect = false;

// --- Gestione Salvataggio Configurazione Database ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_db_config']) && !isset($_POST['action'])) {
    
    $new_db_host = isset($_POST['db_host']) ? trim($_POST['db_host']) : $config['DB_HOST'];
    $new_db_username = isset($_POST['db_username']) ? trim($_POST['db_username']) : $config['DB_USER'];
    $new_db_password = isset($_POST['db_password']) ? trim($_POST['db_password']) : '';
    $new_db_name = isset($_POST['db_name']) ? trim($_POST['db_name']) : $config['DB_NAME'];
    $new_site_name = isset($_POST['site_name']) ? trim($_POST['site_name']) : $config['SITE_NAME'];
    
    // Se la password è vuota, mantieni quella esistente
    if (empty($new_db_password)) {
        $new_db_password = $config['DB_PASS'];
    }
    
    // Verifica la connessione prima di salvare
    $conn_test = @new mysqli($new_db_host, $new_db_username, $new_db_password, $new_db_name);
    
    if ($conn_test->connect_error) {
        $message = "Impossibile salvare: connessione al database fallita. " . htmlspecialchars($conn_test->connect_error);
        $message_type = 'danger';
    } else {
        $conn_test->close();
        
        // Genera il nuovo file config.php
        $config_content = "<?php\n";
        $config_content .= "// Configurazione Database\n";
        $config_content .= "define('DB_HOST', " . var_export($new_db_host, true) . ");\n";
        $config_content .= "define('DB_USER', " . var_export($new_db_username, true) . ");\n";
        $config_content .= "define('DB_PASS', " . var_export($new_db_password, true) . ");\n";
        $config_content .= "define('DB_NAME', " . var_export($new_db_name, true) . ");\n\n";
        $config_content .= "// Configurazione Applicazione\n";
        $config_content .= "define('SITE_NAME', " . var_export($new_site_name, true) . ");\n";
        $config_content .= "define('SESSION_LIFETIME', 10800); // 3 ore in secondi\n\n";
        $config_content .= "// Inizializza sessione\n";
        $config_content .= "if (session_status() === PHP_SESSION_NONE) {\n";
        $config_content .= "    session_start();\n";
        $config_content .= "}\n";
        
        // Salva il file
        if (file_put_contents($configFile, $config_content) !== false) {
            $message = "Configurazione database salvata con successo! Sarai reindirizzato alla dashboard tra 3 secondi.";
            $message_type = 'success';
            $perform_delayed_redirect = true;
            
            // Aggiorna i valori in memoria
            $config['DB_HOST'] = $new_db_host;
            $config['DB_USER'] = $new_db_username;
            $config['DB_PASS'] = $new_db_password;
            $config['DB_NAME'] = $new_db_name;
            $config['SITE_NAME'] = $new_site_name;
        } else {
            $message = "Errore nel salvataggio del file di configurazione. Controlla i permessi del file '$configFile'.";
            $message_type = 'danger';
        }
    }
}

// Gestione operazioni sui backup del logo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if ($_POST['action'] === 'restore_backup') {
        $backupFile = $_POST['backup_file'] ?? '';
        $backupPath = 'uploads/loghi/' . $backupFile;
        $currentLogoJpg = 'uploads/loghi/logo.jpg';
        $currentLogoPng = 'uploads/loghi/logo.png';
        
        if (preg_match('/^logo_backup_\d+\.(jpg|png)$/', $backupFile) && file_exists($backupPath)) {
            $currentLogo = file_exists($currentLogoJpg) ? $currentLogoJpg : (file_exists($currentLogoPng) ? $currentLogoPng : null);
            
            if ($currentLogo && file_exists($currentLogo)) {
                $ext = pathinfo($currentLogo, PATHINFO_EXTENSION);
                $backupCounter = 1;
                $newBackupPath = 'uploads/loghi/logo_backup_' . time() . '_' . $backupCounter . '.' . $ext;
                
                while (file_exists($newBackupPath)) {
                    $backupCounter++;
                    $newBackupPath = 'uploads/loghi/logo_backup_' . time() . '_' . $backupCounter . '.' . $ext;
                }
                
                if (rename($currentLogo, $newBackupPath)) {
                    $response['message'] = "Logo attuale salvato come backup. ";
                }
            }
            
            $backupExt = pathinfo($backupPath, PATHINFO_EXTENSION);
            $newLogoPath = 'uploads/loghi/logo.' . $backupExt;
            
            if (copy($backupPath, $newLogoPath)) {
                $response['success'] = true;
                $response['message'] .= "Backup ripristinato con successo!";
            } else {
                $response['message'] = "Errore durante il ripristino del backup.";
            }
        } else {
            $response['message'] = "File di backup non valido o inesistente.";
        }
        
        echo json_encode($response);
        exit();
    }
    
    if ($_POST['action'] === 'delete_backup') {
        $backupFile = $_POST['backup_file'] ?? '';
        $backupPath = 'uploads/loghi/' . $backupFile;
        
        if (preg_match('/^logo_backup_\d+\.(jpg|png)$/', $backupFile) && file_exists($backupPath)) {
            if (unlink($backupPath)) {
                $response['success'] = true;
                $response['message'] = "Backup eliminato con successo!";
            } else {
                $response['message'] = "Errore durante l'eliminazione del backup.";
            }
        } else {
            $response['message'] = "File di backup non valido o inesistente.";
        }
        
        echo json_encode($response);
        exit();
    }
}

// --- Gestione Upload Logo ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_logo']) && !isset($_POST['action'])) {
    
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/loghi/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png'];
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileType = $_FILES['company_logo']['type'];
        $fileExt = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $fileSize = $_FILES['company_logo']['size'];
        
        if (!in_array($fileType, $allowedTypes) || !in_array($fileExt, $allowedExtensions)) {
            $message = "Formato immagine non supportato. Usa JPG o PNG.";
            $message_type = 'danger';
        } elseif ($fileSize > $maxSize) {
            $message = "File troppo grande. Dimensione massima: 2MB.";
            $message_type = 'danger';
        } else {
            $finalExt = ($fileExt === 'jpeg') ? 'jpg' : $fileExt;
            $newFilePath = $uploadDir . 'logo.' . $finalExt;
            
            $existingLogo = null;
            if (file_exists($uploadDir . 'logo.jpg')) {
                $existingLogo = $uploadDir . 'logo.jpg';
            } elseif (file_exists($uploadDir . 'logo.png')) {
                $existingLogo = $uploadDir . 'logo.png';
            }
            
            if ($existingLogo) {
                $backupExt = pathinfo($existingLogo, PATHINFO_EXTENSION);
                $backupCounter = 1;
                $backupPath = $uploadDir . 'logo_backup_' . time() . '_' . $backupCounter . '.' . $backupExt;
                
                while (file_exists($backupPath)) {
                    $backupCounter++;
                    $backupPath = $uploadDir . 'logo_backup_' . time() . '_' . $backupCounter . '.' . $backupExt;
                }
                
                if (rename($existingLogo, $backupPath)) {
                    $message = "Logo precedente salvato come backup. ";
                }
            }
            
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $newFilePath)) {
                $message .= "Nuovo logo caricato con successo!";
                $message_type = 'success';
            } else {
                $message = "Errore durante il caricamento dell'immagine.";
                $message_type = 'danger';
            }
        }
    }
}

// Recupera i backup disponibili
$backupFiles = [];
if (is_dir('uploads/loghi/')) {
    $files = scandir('uploads/loghi/');
    foreach ($files as $file) {
        if (preg_match('/^logo_backup_\d+\.(jpg|png)$/', $file)) {
            $backupFiles[] = $file;
        }
    }
    rsort($backupFiles);
}

// Determina quale logo è attualmente in uso
$currentLogo = null;
if (file_exists('uploads/loghi/logo.jpg')) {
    $currentLogo = 'uploads/loghi/logo.jpg';
} elseif (file_exists('uploads/loghi/logo.png')) {
    $currentLogo = 'uploads/loghi/logo.png';
}

$page_title = 'Configurazione Server';
require_once 'includes/layout.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">🔧 Configurazione Server</h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Configurazione Database -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">⚙️ Configurazione Database</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>Attenzione:</strong> 
                La modifica di questi dati influisce direttamente sulla connessione al database. 
                Assicurati di inserire dati corretti per evitare malfunzionamenti.
            </div>

            <form id="dbConfigForm" action="configurazione_server.php" method="POST">
                <input type="hidden" name="save_db_config" value="1">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_host" class="form-label fw-bold">Host Database:</label>
                        <input type="text" 
                               class="form-control" 
                               id="db_host" 
                               name="db_host" 
                               value="<?php echo htmlspecialchars($config['DB_HOST']); ?>" 
                               required>
                        <small class="form-text text-muted">Es: localhost, 127.0.0.1</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="db_name" class="form-label fw-bold">Nome Database:</label>
                        <input type="text" 
                               class="form-control" 
                               id="db_name" 
                               name="db_name" 
                               value="<?php echo htmlspecialchars($config['DB_NAME']); ?>" 
                               required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_username" class="form-label fw-bold">Username Database:</label>
                        <input type="text" 
                               class="form-control" 
                               id="db_username" 
                               name="db_username" 
                               value="<?php echo htmlspecialchars($config['DB_USER']); ?>" 
                               required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="db_password" class="form-label fw-bold">
                            Password Database:
                            <small class="text-muted">(lascia vuoto per non modificare)</small>
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="db_password" 
                               name="db_password" 
                               placeholder="••••••••">
                        <small class="form-text text-muted">La password verrà aggiornata solo se inserisci un nuovo valore</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="site_name" class="form-label fw-bold">Nome Sito:</label>
                    <input type="text" 
                           class="form-control" 
                           id="site_name" 
                           name="site_name" 
                           value="<?php echo htmlspecialchars($config['SITE_NAME']); ?>" 
                           required>
                    <small class="form-text text-muted">Nome che appare nella navbar</small>
                </div>

                <div id="db_test_result" class="mb-3"></div>

                <div class="d-flex gap-2">
                    <button type="button" id="test_connection_btn" class="btn btn-info">
                        <i class="fas fa-plug"></i> Test Connessione
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salva Configurazione Database
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logo Aziendale -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">🖼️ Logo Aziendale</h5>
        </div>
        <div class="card-body">
            <form action="configurazione_server.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_logo" value="1">
                
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Logo Attuale:</label>
                        <div class="logo-preview-container">
                            <?php if ($currentLogo && file_exists($currentLogo)): ?>
                                <img src="<?php echo htmlspecialchars($currentLogo); ?>?v=<?php echo time(); ?>" 
                                     alt="Logo Aziendale" 
                                     id="logo-preview"
                                     class="img-fluid">
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Dimensione: <?php echo round(filesize($currentLogo) / 1024, 1); ?>KB
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="logo-preview-placeholder">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                    <div class="mt-2">Nessun logo caricato</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <label for="company_logo" class="form-label fw-bold">Carica Nuovo Logo:</label>
                        <div class="file-upload-area" onclick="document.getElementById('company_logo').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                            <div class="mt-2">
                                <strong>Clicca per selezionare</strong> o trascina qui il file
                            </div>
                            <small class="text-muted">
                                Formati supportati: JPG, PNG<br>
                                Dimensione massima: 2MB<br>
                                <strong>Nome file:</strong> Salvato come "logo.jpg" o "logo.png"
                            </small>
                        </div>
                        <input type="file" 
                               class="form-control d-none" 
                               id="company_logo" 
                               name="company_logo" 
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        
                        <div class="mt-3" id="file-info" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="file-name"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="clearFileSelection()">
                                    <i class="fas fa-times"></i> Rimuovi
                                </button>
                            </div>
                        </div>

                        <div class="form-text mt-3">
                            <strong>Raccomandazioni:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Dimensioni ottimali: 150x50px (o proporzioni simili)</li>
                                <li>Formato PNG con sfondo trasparente per risultati migliori</li>
                                <li>L'immagine apparirà nella navbar accanto al titolo</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salva Logo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Backup Loghi -->
    <?php if (!empty($backupFiles)): ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">📦 Backup Logo Disponibili</h5>
        </div>
        <div class="card-body">
            <div class="backup-files-container">
                <?php foreach ($backupFiles as $backupFile): 
                    $backupPath = 'uploads/loghi/' . $backupFile;
                    $fileSize = round(filesize($backupPath) / 1024, 1);
                    $fileTime = date('d/m/Y H:i', filemtime($backupPath));
                ?>
                <div class="backup-item d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($backupPath); ?>?v=<?php echo time(); ?>" 
                             alt="Backup" 
                             class="backup-thumb me-3">
                        <div>
                            <strong><?php echo htmlspecialchars($backupFile); ?></strong><br>
                            <small class="text-muted"><?php echo $fileTime; ?> • <?php echo $fileSize; ?>KB</small>
                        </div>
                    </div>
                    <div>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary me-1" 
                                onclick="restoreBackup('<?php echo htmlspecialchars($backupFile); ?>')">
                            <i class="fas fa-undo"></i> Ripristina
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger" 
                                onclick="deleteBackup('<?php echo htmlspecialchars($backupFile); ?>')">
                            <i class="fas fa-trash"></i> Elimina
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center mb-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alla Dashboard
        </a>
    </div>
</div>

<style>
.logo-preview-container {
    max-width: 250px;
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    text-align: center;
    background-color: #f8f9fa;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.logo-preview-container img {
    max-width: 100%;
    max-height: 120px;
    object-fit: contain;
}
.logo-preview-placeholder {
    color: #6c757d;
    padding: 1rem;
}
.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 2rem;
    text-align: center;
    background-color: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}
.file-upload-area:hover {
    border-color: #0d6efd;
    background-color: #e7f3ff;
}
.backup-thumb {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
.backup-files-container {
    max-height: 400px;
    overflow-y: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test connessione database
    const testConnectionBtn = document.getElementById('test_connection_btn');
    const dbTestResultDiv = document.getElementById('db_test_result');
    
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function() {
            const dbHost = document.getElementById('db_host').value;
            const dbUsername = document.getElementById('db_username').value;
            const dbPassword = document.getElementById('db_password').value;
            const dbName = document.getElementById('db_name').value;

            const formData = new FormData();
            formData.append('action', 'test_db_connection');
            formData.append('db_host', dbHost);
            formData.append('db_username', dbUsername);
            formData.append('db_password', dbPassword);
            formData.append('db_name', dbName);

            dbTestResultDiv.innerHTML = '<div class="alert alert-info" role="alert"><i class="fas fa-spinner fa-spin"></i> Test in corso...</div>';

            fetch('configurazione_server.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let alertClass = data.success ? 'alert-success' : 'alert-danger';
                let icon = data.success ? 'fa-check-circle' : 'fa-times-circle';
                dbTestResultDiv.innerHTML = '<div class="alert ' + alertClass + '" role="alert"><i class="fas ' + icon + '"></i> ' + data.message + '</div>';
            })
            .catch(error => {
                dbTestResultDiv.innerHTML = '<div class="alert alert-danger" role="alert"><i class="fas fa-times-circle"></i> Errore durante il test della connessione.</div>';
                console.error('Errore:', error);
            });
        });
    }

    <?php if ($perform_delayed_redirect): ?>
    console.log('Redirect ritardato attivato. Reindirizzamento tra 3 secondi a index.php.');
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 3000);
    <?php endif; ?>

    // Gestione upload logo
    const logoUpload = document.getElementById('company_logo');
    const uploadArea = document.querySelector('.file-upload-area');
    
    if (logoUpload) {
        logoUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Formato file non supportato. Utilizzare JPG o PNG.');
                    e.target.value = '';
                    return;
                }
                
                if (file.size > 2 * 1024 * 1024) {
                    alert('Il file è troppo grande. Dimensione massima: 2MB.');
                    e.target.value = '';
                    return;
                }
                
                const sizeKB = Math.round(file.size / 1024);
                fileName.textContent = `📁 ${file.name} (${sizeKB}KB)`;
                fileInfo.style.display = 'block';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logo-preview');
                    if (preview) {
                        preview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Drag & drop
    if (uploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function() {
                uploadArea.style.borderColor = '#0d6efd';
                uploadArea.style.backgroundColor = '#e7f3ff';
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function() {
                uploadArea.style.borderColor = '';
                uploadArea.style.backgroundColor = '';
            });
        });
        
        uploadArea.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                logoUpload.files = files;
                logoUpload.dispatchEvent(new Event('change'));
            }
        });
    }
});

function clearFileSelection() {
    document.getElementById('company_logo').value = '';
    document.getElementById('file-info').style.display = 'none';
}

function restoreBackup(backupFile) {
    if (confirm(`Sei sicuro di voler ripristinare il backup "${backupFile}"?`)) {
        const formData = new FormData();
        formData.append('action', 'restore_backup');
        formData.append('backup_file', backupFile);

        fetch('configurazione_server.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            alert('Errore durante il ripristino del backup.');
            console.error('Errore:', error);
        });
    }
}

function deleteBackup(backupFile) {
    if (confirm(`Sei sicuro di voler eliminare il backup "${backupFile}"?`)) {
        const formData = new FormData();
        formData.append('action', 'delete_backup');
        formData.append('backup_file', backupFile);

        fetch('configurazione_server.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            alert('Errore durante l\'eliminazione del backup.');
            console.error('Errore:', error);
        });
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>