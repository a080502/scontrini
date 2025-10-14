#!/usr/bin/env php
<?php
/**
 * Quick Installer CLI - Installazione rapida con opzioni skip
 * Permette installazione veloce con configurazioni di default
 */

// Verifica se l'installazione è già stata effettuata
if (file_exists('../installation.lock')) {
    echo "❌ Il sistema è già installato. Per reinstallare, elimina il file 'installation.lock'.\n";
    exit(1);
}

echo "⚡ Quick Installer - Sistema Gestione Scontrini\n";
echo str_repeat("=", 50) . "\n\n";

// Verifica argomenti per installazione automatica
$auto_install = in_array('--auto', $argv) || in_array('-a', $argv);
$skip_sample = in_array('--skip-sample', $argv) || in_array('-s', $argv);
$default_admin = in_array('--default-admin', $argv) || in_array('-d', $argv);
$help = in_array('--help', $argv) || in_array('-h', $argv);

if ($help) {
    echo "🆘 AIUTO - Opzioni disponibili:\n";
    echo "  --auto, -a          Installazione automatica con valori di default\n";
    echo "  --skip-sample, -s   Salta l'installazione dei dati di esempio\n";
    echo "  --default-admin, -d Crea amministratore di default (admin/password123)\n";
    echo "  --help, -h          Mostra questo aiuto\n\n";
    echo "Esempio installazione completa automatica:\n";
    echo "  php quick_installer.php --auto --skip-sample --default-admin\n\n";
    exit(0);
}

// Configurazione di default per installazione automatica
if ($auto_install) {
    echo "🚀 MODALITÀ AUTOMATICA ATTIVATA\n";
    echo "Usando configurazioni di default...\n\n";
    
    $config = [
        'db_host' => 'localhost',
        'db_name' => 'scontrini_db',
        'db_user' => 'root',
        'db_pass' => '',
        'admin_nome' => 'Admin',
        'admin_cognome' => 'Sistema',
        'admin_username' => 'admin',
        'admin_password' => 'password123',
        'admin_email' => 'admin@sistema.local'
    ];
    
    $install_sample = !$skip_sample;
    
    echo "📋 CONFIGURAZIONE AUTOMATICA:\n";
    echo "Database: {$config['db_host']}/{$config['db_name']}\n";
    echo "Username DB: {$config['db_user']}\n";
    echo "Admin: {$config['admin_username']}/password123\n";
    echo "Dati esempio: " . ($install_sample ? "Sì" : "No") . "\n\n";
    
} else {
    // Modalità interattiva
    $config = [];

    echo "📊 CONFIGURAZIONE DATABASE\n";
    echo str_repeat("-", 25) . "\n";

    $config['db_host'] = readline("Host database [localhost]: ") ?: 'localhost';
    $config['db_name'] = readline("Nome database [scontrini_db]: ") ?: 'scontrini_db';
    $config['db_user'] = readline("Username database [root]: ") ?: 'root';
    $config['db_pass'] = readline("Password database [vuota]: ");

    if ($default_admin) {
        echo "\n🤖 UTENTE AMMINISTRATORE (DEFAULT)\n";
        echo "Usando admin/password123\n";
        $config['admin_nome'] = 'Admin';
        $config['admin_cognome'] = 'Sistema';
        $config['admin_username'] = 'admin';
        $config['admin_password'] = 'password123';
        $config['admin_email'] = 'admin@sistema.local';
    } else {
        echo "\n👤 UTENTE AMMINISTRATORE\n";
        echo str_repeat("-", 22) . "\n";

        $config['admin_nome'] = readline("Nome amministratore [Admin]: ") ?: 'Admin';
        $config['admin_cognome'] = readline("Cognome amministratore [Sistema]: ") ?: 'Sistema';
        $config['admin_username'] = readline("Username amministratore [admin]: ") ?: 'admin';
        $config['admin_email'] = readline("Email amministratore [admin@sistema.local]: ") ?: 'admin@sistema.local';

        // Password con conferma
        do {
            $config['admin_password'] = readline("Password amministratore [password123]: ") ?: 'password123';
            if (strlen($config['admin_password']) < 8) {
                echo "⚠️  Password troppo corta (minimo 8 caratteri)\n";
                continue;
            }
            if ($config['admin_password'] === 'password123') {
                $confirm_password = 'password123';
            } else {
                $confirm_password = readline("Conferma password: ");
            }
            if ($config['admin_password'] !== $confirm_password) {
                echo "⚠️  Le password non coincidono\n";
            }
        } while ($config['admin_password'] !== $confirm_password || strlen($config['admin_password']) < 8);
    }

    // Dati di esempio
    if (!$skip_sample) {
        echo "\n📊 DATI DI ESEMPIO\n";
        echo str_repeat("-", 15) . "\n";
        $install_sample = strtolower(readline("Installare dati di esempio? [s/N]: ")) === 's';
    } else {
        $install_sample = false;
        echo "\n⏭️  Dati di esempio saltati\n";
    }

    // Riepilogo
    echo "\n📋 RIEPILOGO CONFIGURAZIONE\n";
    echo str_repeat("-", 25) . "\n";
    echo "Database: {$config['db_host']}/{$config['db_name']}\n";
    echo "Username DB: {$config['db_user']}\n";
    echo "Amministratore: {$config['admin_nome']} {$config['admin_cognome']} ({$config['admin_username']})\n";
    echo "Dati esempio: " . ($install_sample ? "Sì" : "No") . "\n";

    if (!$auto_install) {
        $confirm = strtolower(readline("\n🤔 Procedere con l'installazione? [s/N]: "));
        if ($confirm !== 's') {
            echo "❌ Installazione annullata.\n";
            exit(0);
        }
    }
}

echo "\n🚀 AVVIO INSTALLAZIONE\n";
echo str_repeat("-", 20) . "\n";

try {
    // Test connessione database
    echo "0️⃣ Test connessione database...\n";
    $dsn = "mysql:host={$config['db_host']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "   ✅ Connessione riuscita\n";
    
    // Crea database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "   ✅ Database '{$config['db_name']}' creato/verificato\n";

    // 1. Aggiorna config.php
    echo "1️⃣ Creazione file di configurazione...\n";
    updateConfigFile($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    echo "   ✅ config.php creato\n";
    
    // 2. Crea tabelle
    echo "2️⃣ Creazione tabelle database...\n";
    createDatabaseTables($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    echo "   ✅ Tabelle create\n";
    
    // 3. Installa dati di esempio
    if ($install_sample) {
        echo "3️⃣ Installazione dati di esempio...\n";
        require_once '../config.php';
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        installSampleDataCLI($pdo);
        echo "   ✅ Dati di esempio installati\n";
    } else {
        echo "3️⃣ Dati di esempio saltati (installazione pulita)\n";
    }
    
    // 4. Crea utente amministratore
    echo "4️⃣ Creazione utente amministratore...\n";
    if (!isset($pdo)) {
        require_once '../config.php';
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    // Verifica se l'utente esiste già
    $stmt = $pdo->prepare("SELECT id FROM utenti WHERE username = ?");
    $stmt->execute([$config['admin_username']]);
    if ($stmt->fetch()) {
        echo "   ⚠️  Username '{$config['admin_username']}' già esistente - skip creazione\n";
    } else {
        $hashed_password = password_hash($config['admin_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, username, password, email, ruolo, attivo) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
        $stmt->execute([$config['admin_nome'], $config['admin_cognome'], $config['admin_username'], $hashed_password, $config['admin_email']]);
        echo "   ✅ Utente amministratore creato\n";
    }
    
    // 5. Crea file di lock
    echo "5️⃣ Finalizzazione installazione...\n";
    $lock_content = json_encode([
        'installed_at' => date('Y-m-d H:i:s'),
        'version' => '2.0.0',
        'installer_ip' => 'CLI-QUICK',
        'installer_type' => 'quick_command_line',
        'auto_install' => $auto_install,
        'skip_sample' => $skip_sample,
        'default_admin' => $default_admin || ($auto_install)
    ]);
    file_put_contents('../installation.lock', $lock_content);
    echo "   ✅ File di lock creato\n";
    
    echo "\n🎉 INSTALLAZIONE COMPLETATA!\n";
    echo str_repeat("=", 30) . "\n";
    echo "✅ Sistema installato e configurato correttamente\n";
    echo "🌐 Accedi tramite browser alla pagina di login\n";
    echo "👤 Username: {$config['admin_username']}\n";
    echo "🔒 Password: {$config['admin_password']}\n\n";
    
    if ($auto_install || $default_admin) {
        echo "⚠️  IMPORTANTE: Cambia la password di default dopo il primo accesso!\n\n";
    }
    
    echo "📝 Per testare l'installazione: php test_installation.php\n";
    
} catch (Exception $e) {
    echo "❌ Errore durante l'installazione: " . $e->getMessage() . "\n";
    
    // Cleanup in caso di errore
    if (file_exists('../config.php')) {
        unlink('../config.php');
        echo "🧹 File di configurazione rimosso\n";
    }
    if (file_exists('../installation.lock')) {
        unlink('../installation.lock');
        echo "🧹 File di lock rimosso\n";
    }
    
    exit(1);
}

/**
 * Aggiorna il file di configurazione
 */
function updateConfigFile($host, $name, $user, $pass) {
    $config = "<?php
/**
 * File di configurazione - Generato automaticamente (Quick CLI)
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

    file_put_contents('../config.php', $config);
}

/**
 * Crea le tabelle del database
 */
function createDatabaseTables($host, $name, $user, $pass) {
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $schema_file = 'database_schema.sql';
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
    
    // Rimuovi transazioni manuali
    $sql = preg_replace('/^(START TRANSACTION|COMMIT);?\s*$/m', '', $sql);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "   ⚠️  Warning SQL: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}

/**
 * Installa i dati di esempio (versione CLI)
 */
function installSampleDataCLI($pdo) {
    // Filiali
    $filiali = [
        ['nome' => 'Filiale Centro', 'indirizzo' => 'Via Roma 123, Milano', 'telefono' => '02-1234567'],
        ['nome' => 'Filiale Nord', 'indirizzo' => 'Via Garibaldi 456, Milano', 'telefono' => '02-7654321'],
        ['nome' => 'Filiale Sud', 'indirizzo' => 'Corso Buenos Aires 789, Milano', 'telefono' => '02-9876543']
    ];
    
    foreach ($filiali as $filiale) {
        $stmt = $pdo->prepare("INSERT INTO filiali (nome, indirizzo, telefono, attiva) VALUES (?, ?, ?, 1)");
        $stmt->execute([$filiale['nome'], $filiale['indirizzo'], $filiale['telefono']]);
    }
    
    // Scontrini di esempio
    $start_date = new DateTime('-1 year');
    $end_date = new DateTime();
    
    for ($i = 0; $i < 100; $i++) {
        $random_date = randomDateBetween($start_date, $end_date);
        $lordo = mt_rand(1000, 50000) / 100;
        $netto = $lordo * 0.85;
        $da_versare = mt_rand(0, 1) ? $lordo * 0.1 : null;
        
        $stmt = $pdo->prepare("INSERT INTO scontrini (numero, data, lordo, netto, da_versare, filiale_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'SC' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
            $random_date->format('Y-m-d'),
            $lordo,
            $netto,
            $da_versare,
            mt_rand(1, 3),
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
?>