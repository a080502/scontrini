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
            
            // Se l'utente ha personalizzato le credenziali admin, aggiorna
            $admin_user = $_POST['admin_user'] ?? 'admin_sede';
            $admin_pass = $_POST['admin_pass'] ?? 'admin123';
            $admin_nome = $_POST['admin_nome'] ?? 'Admin Sede Centrale';
            
            if ($admin_user !== 'admin_sede' || $admin_pass !== 'admin123' || $admin_nome !== 'Admin Sede Centrale') {
                // Aggiorna l'admin principale con le credenziali personalizzate
                $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                
                $db->query("
                    UPDATE utenti 
                    SET username = ?, password = ?, nome = ? 
                    WHERE username = 'admin_sede'
                ", [$admin_user, $password_hash, $admin_nome]);
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
            <i class="fas fa-building fa-3x" style="color: #007bff; margin-bottom: 15px;"></i>
            <h1>Setup Sistema Multi-Filiale</h1>
            <p class="text-muted">Configurazione iniziale per gestione scontrini con filiali</p>
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
            <h3>Step 2: Creazione Sistema Multi-Filiale</h3>
            <p>Crea le tabelle necessarie, configura le filiali e crea utenti di esempio per ogni ruolo.</p>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Sistema Filiali:</strong> Verranno create 3 filiali di esempio e utenti per testare i diversi livelli di accesso.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="admin_user">Username Amministratore Principale</label>
                    <input type="text" id="admin_user" name="admin_user" value="admin_sede" required>
                    <small class="text-muted">Amministratore con accesso completo a tutte le filiali</small>
                </div>
                
                <div class="form-group">
                    <label for="admin_pass">Password Amministratore</label>
                    <input type="password" id="admin_pass" name="admin_pass" value="admin123" required>
                    <small class="text-muted">Cambia la password di default per sicurezza</small>
                </div>
                
                <div class="form-group">
                    <label for="admin_nome">Nome Completo</label>
                    <input type="text" id="admin_nome" name="admin_nome" value="Admin Sede Centrale" required>
                </div>
                
                <div class="card" style="background: #f8f9fa; margin: 20px 0;">
                    <h5>Utenti di Test che verranno creati:</h5>
                    <ul>
                        <li><strong>Responsabile Nord:</strong> resp_nord (Mario Bianchi) - Gestisce Filiale Nord</li>
                        <li><strong>Responsabile Sud:</strong> resp_sud (Anna Verdi) - Gestisce Filiale Sud</li>
                        <li><strong>Utente Nord:</strong> user_nord1 (Luca Rossi) - Vede solo i suoi scontrini</li>
                        <li><strong>Utente Sud:</strong> user_sud1 (Giuseppe Romano) - Vede solo i suoi scontrini</li>
                    </ul>
                    <small class="text-muted">Tutti con password: admin123</small>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-cogs"></i> Crea Sistema Multi-Filiale
                </button>
            </form>
        </div>

        <?php elseif ($step == 3): ?>
        <div class="card" style="text-align: center;">
            <h3 style="color: #28a745;">
                <i class="fas fa-check-circle"></i> Sistema Multi-Filiale Installato!
            </h3>
            <p>L'applicazione con sistema filiali è stata installata correttamente.</p>
            
            <div class="card" style="background: #e8f5e8; margin: 30px 0; text-align: left;">
                <h5>🏢 Filiali Create:</h5>
                <ul>
                    <li><strong>Sede Centrale</strong> - Milano (Responsabile: Admin)</li>
                    <li><strong>Filiale Nord</strong> - Torino (Responsabile: Mario Bianchi)</li>
                    <li><strong>Filiale Sud</strong> - Napoli (Responsabile: Anna Verdi)</li>
                </ul>
                
                <h5>👥 Utenti di Test:</h5>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Amministratori:</strong>
                        <ul>
                            <li>admin_sede / admin123</li>
                        </ul>
                        
                        <strong>Responsabili:</strong>
                        <ul>
                            <li>resp_nord / admin123</li>
                            <li>resp_sud / admin123</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Utenti:</strong>
                        <ul>
                            <li>user_nord1 / admin123</li>
                            <li>user_sud1 / admin123</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <strong>🔐 Livelli di Accesso:</strong><br>
                • <strong>Admin:</strong> Vede tutto, gestisce tutte le filiali<br>
                • <strong>Responsabile:</strong> Vede solo la propria filiale<br>
                • <strong>Utente:</strong> Vede solo i propri scontrini
            </div>
            
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Accedi all'Applicazione
            </a>
            
            <div style="margin-top: 30px; font-size: 12px; color: #6c757d;">
                <p><strong>Importante:</strong> Per sicurezza, elimina il file <code>setup.php</code> dopo aver completato l'installazione.</p>
                <p>Testa con diversi utenti per vedere come funzionano i controlli di accesso!</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>