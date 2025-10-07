#!/usr/bin/env php
<?php
/**
 * Installer CLI per il Sistema Gestione Scontrini
 * Permette l'installazione automatica da linea di comando
 */

// Verifica se l'installazione è già stata effettuata
if (file_exists('installation.lock')) {
    echo "❌ Il sistema è già installato. Per reinstallare, elimina il file 'installation.lock'.\n";
    exit(1);
}

echo "🚀 Installer CLI - Sistema Gestione Scontrini\n";
echo str_repeat("=", 50) . "\n\n";

// Configurazione da input
$config = [];

// Input database
echo "📊 CONFIGURAZIONE DATABASE\n";
echo str_repeat("-", 25) . "\n";

$config['db_host'] = readline("Host database [localhost]: ") ?: 'localhost';
$config['db_name'] = readline("Nome database [scontrini_db]: ") ?: 'scontrini_db';
$config['db_user'] = readline("Username database: ");
$config['db_pass'] = readline("Password database: ");

if (empty($config['db_user'])) {
    echo "❌ Username database obbligatorio.\n";
    exit(1);
}

// Test connessione database
echo "\n🔍 Test connessione database...\n";
try {
    $dsn = "mysql:host={$config['db_host']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connessione riuscita\n";
    
    // Crea database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '{$config['db_name']}' creato/verificato\n";
    
} catch (PDOException $e) {
    echo "❌ Errore connessione database: " . $e->getMessage() . "\n";
    exit(1);
}

// Dati amministratore
echo "\n👤 UTENTE AMMINISTRATORE\n";
echo str_repeat("-", 22) . "\n";

$config['admin_nome'] = readline("Nome amministratore: ");
$config['admin_cognome'] = readline("Cognome amministratore: ");
$config['admin_username'] = readline("Username amministratore: ");
$config['admin_email'] = readline("Email amministratore [opzionale]: ");

// Password con conferma
do {
    $config['admin_password'] = readline("Password amministratore (min 8 caratteri): ");
    if (strlen($config['admin_password']) < 8) {
        echo "⚠️  Password troppo corta (minimo 8 caratteri)\n";
        continue;
    }
    $confirm_password = readline("Conferma password: ");
    if ($config['admin_password'] !== $confirm_password) {
        echo "⚠️  Le password non coincidono\n";
    }
} while ($config['admin_password'] !== $confirm_password || strlen($config['admin_password']) < 8);

// Dati di esempio
echo "\n📊 DATI DI ESEMPIO\n";
echo str_repeat("-", 15) . "\n";
$install_sample = strtolower(readline("Installare dati di esempio? [s/N]: ")) === 's';

// Riepilogo
echo "\n📋 RIEPILOGO CONFIGURAZIONE\n";
echo str_repeat("-", 25) . "\n";
echo "Database: {$config['db_host']}/{$config['db_name']}\n";
echo "Username DB: {$config['db_user']}\n";
echo "Amministratore: {$config['admin_nome']} {$config['admin_cognome']} ({$config['admin_username']})\n";
echo "Dati esempio: " . ($install_sample ? "Sì" : "No") . "\n";

$confirm = strtolower(readline("\n🤔 Procedere con l'installazione? [s/N]: "));
if ($confirm !== 's') {
    echo "❌ Installazione annullata.\n";
    exit(0);
}

echo "\n🚀 AVVIO INSTALLAZIONE\n";
echo str_repeat("-", 20) . "\n";

try {
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
        require_once 'includes/bootstrap.php';
        $db = Database::getInstance();
        installSampleDataCLI($db);
        echo "   ✅ Dati di esempio installati\n";
    }
    
    // 4. Crea utente amministratore
    echo "4️⃣ Creazione utente amministratore...\n";
    if (!isset($db)) {
        require_once 'includes/bootstrap.php';
        $db = Database::getInstance();
    }
    
    $hashed_password = password_hash($config['admin_password'], PASSWORD_DEFAULT);
    $db->query(
        "INSERT INTO utenti (nome, cognome, username, password, email, ruolo, attivo) VALUES (?, ?, ?, ?, ?, 'admin', 1)",
        [$config['admin_nome'], $config['admin_cognome'], $config['admin_username'], $hashed_password, $config['admin_email']]
    );
    echo "   ✅ Utente amministratore creato\n";
    
    // 5. Crea file di lock
    echo "5️⃣ Finalizzazione installazione...\n";
    $lock_content = json_encode([
        'installed_at' => date('Y-m-d H:i:s'),
        'version' => '2.0.0',
        'installer_ip' => 'CLI',
        'installer_type' => 'command_line'
    ]);
    file_put_contents('installation.lock', $lock_content);
    echo "   ✅ File di lock creato\n";
    
    echo "\n🎉 INSTALLAZIONE COMPLETATA!\n";
    echo str_repeat("=", 30) . "\n";
    echo "✅ Sistema installato e configurato correttamente\n";
    echo "🌐 Accedi tramite browser alla pagina di login\n";
    echo "👤 Username: {$config['admin_username']}\n";
    echo "🔒 Password: (quella inserita)\n\n";
    echo "📝 Per testare l'installazione: php install/test_installation.php\n";
    
} catch (Exception $e) {
    echo "❌ Errore durante l'installazione: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Aggiorna il file di configurazione
 */
function updateConfigFile($host, $name, $user, $pass) {
    $config = "<?php
/**
 * File di configurazione - Generato automaticamente (CLI)
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

    file_put_contents('config.php', $config);
}

/**
 * Crea le tabelle del database
 */
function createDatabaseTables($host, $name, $user, $pass) {
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $schema_file = 'install/database_schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("File schema database non trovato: $schema_file");
    }
    
    $sql = file_get_contents($schema_file);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(DELIMITER|START TRANSACTION|COMMIT)/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
}

/**
 * Installa i dati di esempio (versione CLI)
 */
function installSampleDataCLI($db) {
    // Filiali
    $filiali = [
        ['nome' => 'Filiale Centro', 'indirizzo' => 'Via Roma 123, Milano', 'telefono' => '02-1234567'],
        ['nome' => 'Filiale Nord', 'indirizzo' => 'Via Garibaldi 456, Milano', 'telefono' => '02-7654321'],
        ['nome' => 'Filiale Sud', 'indirizzo' => 'Corso Buenos Aires 789, Milano', 'telefono' => '02-9876543']
    ];
    
    foreach ($filiali as $filiale) {
        $db->query(
            "INSERT INTO filiali (nome, indirizzo, telefono, attiva) VALUES (?, ?, ?, 1)",
            [$filiale['nome'], $filiale['indirizzo'], $filiale['telefono']]
        );
    }
    
    // Scontrini di esempio
    $start_date = new DateTime('-1 year');
    $end_date = new DateTime();
    
    for ($i = 0; $i < 100; $i++) {
        $random_date = randomDateBetween($start_date, $end_date);
        $lordo = mt_rand(1000, 50000) / 100;
        $netto = $lordo * 0.85;
        $da_versare = mt_rand(0, 1) ? $lordo * 0.1 : null;
        
        $db->query(
            "INSERT INTO scontrini (numero, data, lordo, netto, da_versare, filiale_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                'SC' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                $random_date->format('Y-m-d'),
                $lordo,
                $netto,
                $da_versare,
                mt_rand(1, 3),
                $random_date->format('Y-m-d H:i:s')
            ]
        );
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