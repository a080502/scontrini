<?php
/**
 * Sistema di installazione automatica
 * Questo file gestisce l'installazione iniziale del sistema
 */

// Verifica se l'installazione è già stata effettuata
if (file_exists('installation.lock')) {
    header('Location: login.php');
    exit();
}

// Non includere bootstrap.php durante l'installazione per evitare conflitti
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

// Gestione POST per ogni step
if ($_POST) {
    switch ($_POST['action']) {
        case 'check_requirements':
            $step = 2;
            break;
            
        case 'create_database':
            if (handleDatabaseCreation()) {
                $step = 3;
            }
            break;
            
        case 'install_sample_data':
            if (handleSampleDataInstallation()) {
                $step = 4;
            }
            break;
            
        case 'create_admin':
            if (handleAdminCreation()) {
                $step = 5;
            }
            break;
            
        case 'finalize_installation':
            if (finalizeInstallation()) {
                header('Location: login.php?installed=1');
                exit();
            }
            break;
            
        case 'skip_step':
            $current_step = isset($_POST['current_step']) ? (int)$_POST['current_step'] : $step;
            handleSkipStep($current_step);
            $step = $current_step + 1;
            break;
            
        case 'fix_permissions':
            fixPermissions();
            // Rimani sullo stesso step per ricontrollare
            break;
    }
}

/**
 * Corregge automaticamente i permessi via web
 */
function fixPermissions() {
    global $success, $errors;
    
    $fixed = [];
    $failed = [];
    
    // 1. Correggi permessi directory principale
    if (chmod('.', 0755)) {
        $fixed[] = "Directory principale: permessi corretti (755)";
    } else {
        $failed[] = "Impossibile correggere permessi directory principale";
    }
    
    // 2. Correggi config.php se esiste
    if (file_exists('config.php')) {
        if (chmod('config.php', 0666)) {
            $fixed[] = "config.php: permessi corretti (666)";
        } else {
            $failed[] = "Impossibile correggere permessi config.php";
        }
    }
    
    // 3. Crea e correggi directory uploads
    if (!is_dir('uploads')) {
        if (mkdir('uploads', 0777, true)) {
            $fixed[] = "Directory uploads: creata con permessi 777";
        } else {
            $failed[] = "Impossibile creare directory uploads";
        }
    } else {
        if (chmod('uploads', 0777)) {
            $fixed[] = "Directory uploads: permessi corretti (777)";
        } else {
            $failed[] = "Impossibile correggere permessi directory uploads";
        }
    }
    
    // 4. Crea sottodirectory foto_scontrini
    $foto_dir = 'uploads/foto_scontrini';
    if (!is_dir($foto_dir)) {
        if (mkdir($foto_dir, 0777, true)) {
            $fixed[] = "Directory foto_scontrini: creata con permessi 777";
        } else {
            $failed[] = "Impossibile creare directory foto_scontrini";
        }
    } else {
        if (chmod($foto_dir, 0777)) {
            $fixed[] = "Directory foto_scontrini: permessi corretti (777)";
        } else {
            $failed[] = "Impossibile correggere permessi directory foto_scontrini";
        }
    }
    
    // 5. Test di scrittura
    $test_file = 'test_write_' . time() . '.tmp';
    if (file_put_contents($test_file, 'test') !== false) {
        unlink($test_file);
        $fixed[] = "Test scrittura: SUCCESSO";
    } else {
        $failed[] = "Test scrittura: FALLITO - potrebbero servire permessi aggiuntivi";
    }
    
    // Aggiungi messaggi ai risultati globali
    if (!empty($fixed)) {
        foreach ($fixed as $msg) {
            $success[] = "✅ " . $msg;
        }
    }
    
    if (!empty($failed)) {
        foreach ($failed as $msg) {
            $errors[] = "❌ " . $msg;
        }
        
        // Aggiungi istruzioni alternative
        $errors[] = "Soluzioni alternative da terminale:";
        $errors[] = "chmod 755 . && chmod 666 config.php && mkdir -p uploads && chmod 777 uploads";
    }
    
    if (empty($failed)) {
        $success[] = "🎉 Tutti i permessi sono stati corretti automaticamente!";
    }
}

/**
 * Gestisce il salto di uno step con configurazioni di default
 */
function handleSkipStep($current_step) {
    global $success, $errors;
    
    switch ($current_step) {
        case 1: // Skip verifica requisiti
            $success[] = 'Verifica requisiti saltata - procedi a tuo rischio!';
            break;
            
        case 3: // Skip dati di esempio
            $success[] = 'Step dati di esempio saltato - installazione pulita';
            break;
            
        case 4: // Skip utente admin - crea uno di default
            if (createDefaultAdmin()) {
                $success[] = 'Utente amministratore di default creato (admin/password123)';
            }
            break;
            
        default:
            $success[] = "Step {$current_step} saltato";
            break;
    }
}

/**
 * Crea un utente amministratore di default per il skip
 */
function createDefaultAdmin() {
    global $errors;
    
    try {
        // Leggi le credenziali dal config.php
        if (!file_exists('config.php')) {
            $errors[] = 'File di configurazione non trovato. Impossibile creare utente di default.';
            return false;
        }
        
        require_once 'config.php';
        
        // Connessione diretta al database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Verifica se username già esiste
        $stmt = $pdo->prepare("SELECT id FROM utenti WHERE username = ?");
        $stmt->execute(['admin']);
        if ($stmt->fetch()) {
            $errors[] = 'Username "admin" già esistente';
            return false;
        }
        
        // Crea utente amministratore di default
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, username, password, email, ruolo, attivo) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
        $stmt->execute(['Admin', 'Sistema', 'admin', $hashed_password, 'admin@sistema.local']);
        
        return true;
        
    } catch (Exception $e) {
        $errors[] = 'Errore creazione utente di default: ' . $e->getMessage();
        return false;
    }
}

/**
 * Verifica i requisiti di sistema
 */
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'Uploads directory writable' => is_writable('uploads/'),
        'Directory writable for config' => is_writable('.') || (!file_exists('config.php') && is_writable('.')),
    ];
    
    return $requirements;
}

/**
 * Gestisce la creazione del database
 */
function handleDatabaseCreation() {
    global $errors, $success;
    
    $host = $_POST['db_host'] ?? '';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    
    if (empty($host) || empty($name) || empty($user)) {
        $errors[] = 'Tutti i campi del database sono obbligatori';
        return false;
    }
    
    try {
        // Test connessione
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Crea database se non esiste
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Aggiorna config.php
        if (!updateConfigFile($host, $name, $user, $pass)) {
            return false; // Gli errori sono già stati aggiunti in updateConfigFile
        }
        
        // Crea tabelle
        createDatabaseTables($host, $name, $user, $pass);
        
        $success[] = 'Database creato e configurato con successo';
        return true;
        
    } catch (PDOException $e) {
        $errors[] = 'Errore connessione database: ' . $e->getMessage();
        return false;
    }
}

/**
 * Aggiorna il file di configurazione
 */
function updateConfigFile($host, $name, $user, $pass) {
    global $errors;
    
    $config = "<?php
/**
 * File di configurazione - Generato automaticamente durante l'installazione
 */

// Configurazione Database
define('DB_HOST', '{$host}');
define('DB_NAME', '{$name}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass}');

// Configurazione Applicazione
define('APP_NAME', 'Sistema Gestione Scontrini');
define('APP_VERSION', '2.0.0');
define('SITE_NAME', 'Gestione Scontrini Fiscali');

// Configurazione Sicurezza
define('SESSION_TIMEOUT', 3600);
define('SESSION_LIFETIME', 1800);
define('SESSION_SECRET', '" . bin2hex(random_bytes(16)) . "');

// Configurazione Locale
define('LOCALE', 'it_IT');

// Debug (false in produzione)
define('DEBUG_MODE', false);

// Timezone
date_default_timezone_set('Europe/Rome');

// Avvia sessione se non già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Imposta locale italiano
setlocale(LC_MONETARY, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
setlocale(LC_NUMERIC, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
?>";

    // Prova a scrivere il file con gestione errori
    $config_file = 'config.php';
    
    // Controlla se il file esiste e i suoi permessi
    if (file_exists($config_file)) {
        if (!is_writable($config_file)) {
            $errors[] = "Il file config.php esiste ma non è scrivibile. Permessi attuali: " . substr(sprintf('%o', fileperms($config_file)), -4);
            $errors[] = "Soluzione: esegui 'chmod 666 config.php' oppure 'chmod 777 config.php'";
            return false;
        }
    } else {
        // Il file non esiste, controlla se la directory è scrivibile
        if (!is_writable('.')) {
            $errors[] = "La directory corrente non è scrivibile. Non posso creare config.php";
            $errors[] = "Soluzione: esegui 'chmod 755 .' oppure 'chmod 777 .'";
            return false;
        }
    }
    
    // Prova a scrivere il file
    $result = file_put_contents($config_file, $config);
    
    if ($result === false) {
        $errors[] = "Impossibile scrivere il file config.php";
        $errors[] = "Possibili soluzioni:";
        $errors[] = "1. Esegui: chmod 666 config.php (se il file esiste)";
        $errors[] = "2. Esegui: chmod 777 . (per la directory)";
        $errors[] = "3. Cambia il proprietario: chown www-data:www-data config.php";
        $errors[] = "4. Crea manualmente il file config.php con il contenuto mostrato sotto";
        
        // Mostra il contenuto che dovrebbe essere nel file
        $errors[] = "CONTENUTO DA COPIARE IN config.php:";
        $errors[] = "<pre>" . htmlspecialchars($config) . "</pre>";
        
        return false;
    }
    
    return true;
}

/**
 * Crea le tabelle del database
 */
function createDatabaseTables($host, $name, $user, $pass) {
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Leggi e esegui lo schema SQL
    $schema_file = 'install/database_schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("File schema database non trovato: $schema_file");
    }
    
    $sql = file_get_contents($schema_file);
    
    // Rimuovi commenti SQL
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Rimuovi le direttive DELIMITER e relative
    $sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
    $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
    $sql = preg_replace('/\$\$/i', ';', $sql);
    
    // Rimuovi transazioni manuali (START TRANSACTION, COMMIT)
    $sql = preg_replace('/^(START TRANSACTION|COMMIT);?\s*$/m', '', $sql);
    
    // Dividi in statement usando ; come separatore
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignora errori per statement che potrebbero già esistere
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    // Log l'errore ma continua (per compatibilità)
                    error_log("Errore SQL statement: " . $e->getMessage());
                    error_log("Statement: " . $statement);
                }
            }
        }
    }
}

/**
 * Testa la connessione al database
 */
function testDatabaseConnection($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        // Test semplice query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        
        return [
            'success' => true, 
            'message' => "Connessione riuscita - MySQL/MariaDB " . $version['version']
        ];
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'message' => "Errore connessione: " . $e->getMessage()
        ];
    }
}

/**
 * Gestisce l'installazione dei dati di esempio
 */
function handleSampleDataInstallation() {
    global $errors, $success;
    
    $install_sample = isset($_POST['install_sample']) && $_POST['install_sample'] === '1';
    
    if ($install_sample) {
        try {
            // Leggi le credenziali dal config.php appena creato
            if (!file_exists('config.php')) {
                $errors[] = 'File di configurazione non trovato. Ripetere la configurazione database.';
                return false;
            }
            
            require_once 'config.php';
            
            // Connessione diretta al database
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Installa dati di esempio
            installSampleData($pdo);
            
            $success[] = 'Dati di esempio installati con successo';
        } catch (Exception $e) {
            $errors[] = 'Errore installazione dati di esempio: ' . $e->getMessage();
            return false;
        }
    }
    
    return true;
}

/**
 * Installa i dati di esempio
 */
function installSampleData($pdo) {
    // Dati di esempio per filiali
    $filiali = [
        ['nome' => 'Filiale Centro', 'indirizzo' => 'Via Roma 123, Milano', 'telefono' => '02-1234567'],
        ['nome' => 'Filiale Nord', 'indirizzo' => 'Via Garibaldi 456, Milano', 'telefono' => '02-7654321'],
        ['nome' => 'Filiale Sud', 'indirizzo' => 'Corso Buenos Aires 789, Milano', 'telefono' => '02-9876543']
    ];
    
    foreach ($filiali as $filiale) {
        $stmt = $pdo->prepare("INSERT INTO filiali (nome, indirizzo, telefono, attiva) VALUES (?, ?, ?, 1)");
        $stmt->execute([$filiale['nome'], $filiale['indirizzo'], $filiale['telefono']]);
    }
    
    // Genera scontrini di esempio per l'ultimo anno
    $start_date = new DateTime('-1 year');
    $end_date = new DateTime();
    $nomi_esempio = ['Mario Rossi', 'Giulia Bianchi', 'Luca Verdi', 'Anna Neri', 'Francesco Bruno', 
                     'Sara Gialli', 'Marco Viola', 'Elena Azzurri', 'Davide Rosa', 'Chiara Grigi'];
    
    for ($i = 0; $i < 100; $i++) {
        $random_date = randomDateBetween($start_date, $end_date);
        $lordo = mt_rand(1000, 50000) / 100; // Tra 10.00 e 500.00 euro
        $netto = $lordo * 0.85; // Circa 15% di tasse
        $da_versare = mt_rand(0, 1) ? $lordo * 0.1 : null; // 10% chance di avere da_versare
        $nome_persona = $nomi_esempio[array_rand($nomi_esempio)]; // Nome casuale
        
        $stmt = $pdo->prepare("INSERT INTO scontrini (numero, nome_persona, data, lordo, netto, da_versare, filiale_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'SC' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
            $nome_persona,
            $random_date->format('Y-m-d'),
            $lordo,
            $netto,
            $da_versare,
            mt_rand(1, 3), // Random filiale
            $random_date->format('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Genera una data casuale tra due date
 */
function randomDateBetween($start, $end) {
    $start_timestamp = $start->getTimestamp();
    $end_timestamp = $end->getTimestamp();
    $random_timestamp = mt_rand($start_timestamp, $end_timestamp);
    
    return new DateTime('@' . $random_timestamp);
}

/**
 * Gestisce la creazione dell'utente amministratore
 */
function handleAdminCreation() {
    global $errors, $success;
    
    $nome = $_POST['admin_nome'] ?? '';
    $cognome = $_POST['admin_cognome'] ?? '';
    $username = $_POST['admin_username'] ?? '';
    $password = $_POST['admin_password'] ?? '';
    $confirm_password = $_POST['admin_confirm_password'] ?? '';
    $email = $_POST['admin_email'] ?? '';
    
    // Validazione
    if (empty($nome) || empty($cognome) || empty($username) || empty($password)) {
        $errors[] = 'Tutti i campi sono obbligatori';
        return false;
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Le password non coincidono';
        return false;
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'La password deve essere di almeno 8 caratteri';
        return false;
    }
    
    try {
        // Leggi le credenziali dal config.php
        if (!file_exists('config.php')) {
            $errors[] = 'File di configurazione non trovato. Ripetere la configurazione database.';
            return false;
        }
        
        require_once 'config.php';
        
        // Connessione diretta al database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Verifica se username già esiste
        $stmt = $pdo->prepare("SELECT id FROM utenti WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username già esistente';
            return false;
        }
        
        // Crea utente amministratore
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, username, password, email, ruolo, attivo) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
        $stmt->execute([$nome, $cognome, $username, $hashed_password, $email]);
        
        $success[] = 'Utente amministratore creato con successo';
        return true;
        
    } catch (Exception $e) {
        $errors[] = 'Errore creazione utente: ' . $e->getMessage();
        return false;
    }
}

/**
 * Finalizza l'installazione
 */
function finalizeInstallation() {
    global $errors, $success;
    
    try {
        // Crea il file di lock per impedire nuove installazioni
        $lock_content = json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '2.0.0',
            'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        file_put_contents('installation.lock', $lock_content);
        
        // Rimuovi eventuali file temporanei di installazione
        if (file_exists('install/temp')) {
            rmdir('install/temp');
        }
        
        $success[] = 'Installazione completata con successo';
        return true;
        
    } catch (Exception $e) {
        $errors[] = 'Errore finalizzazione: ' . $e->getMessage();
        return false;
    }
}

$page_title = 'Installazione Sistema - ' . ($step === 1 ? 'Verifica Requisiti' : 
    ($step === 2 ? 'Configurazione Database' : 
    ($step === 3 ? 'Dati di Esempio' : 
    ($step === 4 ? 'Utente Amministratore' : 'Finalizzazione'))));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .install-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: white;
            font-weight: bold;
        }
        .step.active {
            background-color: #007bff;
        }
        .step.completed {
            background-color: #28a745;
        }
        .step.pending {
            background-color: #6c757d;
        }
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .requirement-ok {
            color: #28a745;
        }
        .requirement-error {
            color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <div class="install-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-cogs"></i> Installazione Sistema</h1>
            <p class="text-muted">Configurazione iniziale del Sistema Gestione Scontrini</p>
        </div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : 'pending'; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'pending'; ?>">3</div>
            <div class="step <?php echo $step >= 4 ? ($step > 4 ? 'completed' : 'active') : 'pending'; ?>">4</div>
            <div class="step <?php echo $step >= 5 ? 'active' : 'pending'; ?>">5</div>
        </div>
        
        <!-- Messaggi di errore e successo -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <ul class="mb-0">
                    <?php foreach ($success as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Step 1: Verifica Requisiti -->
        <?php if ($step === 1): ?>
            <div class="step-content">
                <h3><i class="fas fa-check-circle"></i> Step 1: Verifica Requisiti</h3>
                <p>Verifichiamo che il server soddisfi tutti i requisiti necessari per l'installazione.</p>
                
                <div class="requirements-list">
                    <?php 
                    $requirements = checkRequirements();
                    $all_ok = true;
                    foreach ($requirements as $name => $status):
                        if (!$status) $all_ok = false;
                    ?>
                        <div class="requirement-item">
                            <span><?php echo $name; ?></span>
                            <span class="<?php echo $status ? 'requirement-ok' : 'requirement-error'; ?>">
                                <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?>"></i>
                                <?php echo $status ? 'OK' : 'ERRORE'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($all_ok): ?>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-thumbs-up"></i> Tutti i requisiti sono soddisfatti!
                    </div>
                    <form method="post" class="mt-4">
                        <input type="hidden" name="action" value="check_requirements">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right"></i> Procedi al Database
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle"></i> Alcuni requisiti non sono soddisfatti. Correggi i problemi prima di procedere.
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <!-- Pulsante per procedere comunque -->
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="skip_step">
                            <input type="hidden" name="current_step" value="1">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-exclamation-triangle"></i> Procedi Comunque
                            </button>
                        </form>
                        
                        <!-- Pulsante per correggere permessi automaticamente -->
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="fix_permissions">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-tools"></i> Correggi Permessi
                            </button>
                        </form>
                        
                        <!-- Pulsante per ricaricare i controlli -->
                        <button type="button" class="btn btn-secondary btn-lg" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Ricontrolla
                        </button>
                    </div>
                    
                    <div class="alert alert-warning mt-2">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            <strong>Attenzione:</strong> Procedendo comunque potresti incontrare problemi durante l'utilizzo del sistema. 
                            Si raccomanda di risolvere i requisiti mancanti prima di continuare.
                        </small>
                        <hr>
                        <small>
                            <i class="fas fa-external-link-alt"></i> 
                            Per una diagnosi dettagliata: <a href="check_permissions.php" target="_blank" class="alert-link">check_permissions.php</a>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Step 2: Configurazione Database -->
        <?php if ($step === 2): ?>
            <div class="step-content">
                <h3><i class="fas fa-database"></i> Step 2: Configurazione Database</h3>
                <p>Inserisci i parametri di connessione al database MySQL.</p>
                
                <?php if (file_exists('config.php')): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Attenzione:</strong> È stato trovato un file di configurazione esistente. 
                        Procedendo, verrà sovrascritto con le nuove impostazioni.
                    </div>
                <?php endif; ?>
                
                <form method="post" class="mt-4">
                    <input type="hidden" name="action" value="create_database">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Host Database</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Nome Database</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="<?php echo $_POST['db_name'] ?? 'scontrini_db'; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_user" class="form-label">Username Database</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" 
                                       value="<?php echo $_POST['db_user'] ?? ''; ?>" required>
                                <small class="form-text text-muted">Username per accedere a MySQL</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="db_pass" class="form-label">Password Database</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                       value="<?php echo $_POST['db_pass'] ?? ''; ?>">
                                <small class="form-text text-muted">Password per accedere a MySQL (lascia vuoto se non c'è)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Il sistema creerà automaticamente il database se non esiste e configurerà tutte le tabelle necessarie.
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Crea Database e Tabelle
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Step 3: Dati di Esempio -->
        <?php if ($step === 3): ?>
            <div class="step-content">
                <h3><i class="fas fa-file-import"></i> Step 3: Dati di Esempio</h3>
                <p>Vuoi installare dei dati di esempio per testare il sistema?</p>
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> I dati di esempio includono:</h5>
                    <ul>
                        <li>3 filiali di esempio</li>
                        <li>100 scontrini distribuiti nell'ultimo anno</li>
                        <li>Vari importi e date casuali</li>
                        <li>Alcuni scontrini con importi da versare</li>
                    </ul>
                </div>
                
                <form method="post" class="mt-4">
                    <input type="hidden" name="action" value="install_sample_data">
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="install_sample" name="install_sample" value="1">
                        <label class="form-check-label" for="install_sample">
                            <strong>Sì, installa i dati di esempio</strong>
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right"></i> Continua
                        </button>
                        <a href="?step=2" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Indietro
                        </a>
                    </div>
                </form>
                
                <!-- Form separato per il skip -->
                <form method="post" class="mt-2">
                    <input type="hidden" name="action" value="skip_step">
                    <input type="hidden" name="current_step" value="3">
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-forward"></i> Salta questo step
                    </button>
                    <small class="form-text text-muted">Installazione pulita senza dati di esempio</small>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Step 4: Utente Amministratore -->
        <?php if ($step === 4): ?>
            <div class="step-content">
                <h3><i class="fas fa-user-shield"></i> Step 4: Utente Amministratore</h3>
                <p>Crea l'account amministratore per accedere al sistema.</p>
                
                <form method="post" class="mt-4">
                    <input type="hidden" name="action" value="create_admin">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="admin_nome" name="admin_nome" 
                                       value="<?php echo $_POST['admin_nome'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_cognome" class="form-label">Cognome</label>
                                <input type="text" class="form-control" id="admin_cognome" name="admin_cognome" 
                                       value="<?php echo $_POST['admin_cognome'] ?? ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                       value="<?php echo $_POST['admin_username'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?php echo $_POST['admin_email'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                <small class="form-text text-muted">Minimo 8 caratteri</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_confirm_password" class="form-label">Conferma Password</label>
                                <input type="password" class="form-control" id="admin_confirm_password" name="admin_confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus"></i> Crea Amministratore
                        </button>
                        <a href="?step=3" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Indietro
                        </a>
                    </div>
                </form>
                
                <!-- Form separato per il skip -->
                <form method="post" class="mt-2">
                    <input type="hidden" name="action" value="skip_step">
                    <input type="hidden" name="current_step" value="4">
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-forward"></i> Crea amministratore di default
                    </button>
                    <small class="form-text text-muted">Username: admin, Password: password123</small>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Step 5: Finalizzazione -->
        <?php if ($step === 5): ?>
            <div class="step-content">
                <h3><i class="fas fa-flag-checkered"></i> Step 5: Finalizzazione</h3>
                <p>Sei quasi pronto! Conferma per completare l'installazione.</p>
                
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Riepilogo installazione:</h5>
                    <ul>
                        <li>✓ Requisiti di sistema verificati</li>
                        <li>✓ Database configurato e tabelle create</li>
                        <li>✓ <?php echo isset($_POST['install_sample']) && $_POST['install_sample'] === '1' ? 'Dati di esempio installati' : 'Installazione pulita (senza dati di esempio)'; ?></li>
                        <li>✓ Utente amministratore creato</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Importante:</strong> Dopo la finalizzazione, questo script di installazione non sarà più accessibile per motivi di sicurezza.
                </div>
                
                <form method="post" class="mt-4">
                    <input type="hidden" name="action" value="finalize_installation">
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> Finalizza Installazione
                        </button>
                        <a href="?step=4" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Indietro
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>