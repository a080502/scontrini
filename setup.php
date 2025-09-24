<?php
require_once 'config.php';

// Solo per setup iniziale - da eliminare dopo l'installazione
if (file_exists('setup_completed.lock')) {
    die('Setup già completato. Elimina il file setup_completed.lock per ripetere l\'installazione.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_POST) {
    if ($step == 1) {
        // Test connessione database
        $host = $_POST['db_host'] ?? 'localhost';
        $name = $_POST['db_name'] ?? 'scontrini_db';
        $user = $_POST['db_user'] ?? 'root';
        $pass = $_POST['db_pass'] ?? '';
        
        try {
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Crea database se non esiste
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Aggiorna config.php con i parametri
            $config_content = "<?php
// Configurazione database
define('DB_HOST', '$host');
define('DB_NAME', '$name');
define('DB_USER', '$user');
define('DB_PASS', '$pass');

// Configurazione sessioni
define('SESSION_LIFETIME', 1800); // 30 minuti
define('SESSION_SECRET', 'dev-secret-key-123');

// Configurazione generale
define('SITE_NAME', 'Gestione Scontrini Fiscali');
define('LOCALE', 'it_IT');

// Timezone
date_default_timezone_set('Europe/Rome');

// Avvia sessione se non già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Imposta locale italiano per formatting numeri
setlocale(LC_MONETARY, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
setlocale(LC_NUMERIC, 'it_IT.UTF-8', 'it_IT', 'Italian_Italy.1252');
?>";
            
            file_put_contents('config.php', $config_content);
            
            header('Location: setup.php?step=2');
            exit;
            
        } catch (Exception $e) {
            $error = 'Errore connessione database: ' . $e->getMessage();
        }
    } elseif ($step == 2) {
        // Crea tabelle e utente admin
        try {
            require_once 'includes/database.php';
            
            $db = Database::getInstance();
            $db->initializeDatabase();
            
            // Crea utente admin personalizzato se richiesto
            $admin_user = $_POST['admin_user'] ?? 'admin';
            $admin_pass = $_POST['admin_pass'] ?? 'admin123';
            $admin_nome = $_POST['admin_nome'] ?? 'Amministratore';
            
            if ($admin_user !== 'admin') {
                $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                
                // Rimuovi admin di default
                $db->query("DELETE FROM utenti WHERE username = 'admin'");
                
                // Inserisci nuovo admin
                $db->query("
                    INSERT INTO utenti (username, password, nome, ruolo) 
                    VALUES (?, ?, ?, ?)
                ", [$admin_user, $password_hash, $admin_nome, 'admin']);
            }
            
            // Crea file lock per impedire ripetizioni
            file_put_contents('setup_completed.lock', date('Y-m-d H:i:s'));
            
            header('Location: setup.php?step=3');
            exit;
            
        } catch (Exception $e) {
            $error = 'Errore inizializzazione database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Gestione Scontrini Fiscali</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="content-container">
        <div style="text-align: center; margin-bottom: 40px;">
            <i class="fas fa-receipt fa-3x" style="color: #007bff; margin-bottom: 15px;"></i>
            <h1>Setup Gestione Scontrini Fiscali</h1>
            <p class="text-muted">Configurazione iniziale dell'applicazione</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
        <div class="card">
            <h3>Step 1: Configurazione Database</h3>
            <p>Configura i parametri di connessione al database MySQL.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Host Database</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Nome Database</label>
                    <input type="text" id="db_name" name="db_name" value="scontrini_db" required>
                    <small class="text-muted">Il database sarà creato automaticamente se non esiste</small>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Username Database</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Password Database</label>
                    <input type="password" id="db_pass" name="db_pass" placeholder="Lascia vuoto se non hai password">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-database"></i> Testa Connessione e Continua
                </button>
            </form>
        </div>

        <?php elseif ($step == 2): ?>
        <div class="card">
            <h3>Step 2: Creazione Tabelle e Utente Admin</h3>
            <p>Crea le tabelle necessarie e configura l'utente amministratore.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="admin_user">Username Amministratore</label>
                    <input type="text" id="admin_user" name="admin_user" value="admin" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_pass">Password Amministratore</label>
                    <input type="password" id="admin_pass" name="admin_pass" value="admin123" required>
                    <small class="text-muted">Cambia la password di default per sicurezza</small>
                </div>
                
                <div class="form-group">
                    <label for="admin_nome">Nome Completo</label>
                    <input type="text" id="admin_nome" name="admin_nome" value="Amministratore" required>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-cogs"></i> Crea Tabelle e Utente
                </button>
            </form>
        </div>

        <?php elseif ($step == 3): ?>
        <div class="card" style="text-align: center;">
            <h3 style="color: #28a745;">
                <i class="fas fa-check-circle"></i> Setup Completato!
            </h3>
            <p>L'applicazione è stata installata correttamente.</p>
            
            <div style="margin: 30px 0;">
                <p><strong>Credenziali di accesso:</strong></p>
                <p>Usa le credenziali amministratore configurate nel step precedente</p>
            </div>
            
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Accedi all'Applicazione
            </a>
            
            <div style="margin-top: 20px; font-size: 12px; color: #6c757d;">
                <p><strong>Importante:</strong> Per sicurezza, elimina il file <code>setup.php</code> dopo aver completato l'installazione.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>