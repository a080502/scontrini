<?php
/**
 * Script di debug per problemi di connessione database durante l'installazione
 * Utilizzare per diagnosticare problemi di connessione MySQL
 */

echo "🔍 Debug Connessione Database\n";
echo str_repeat("=", 40) . "\n\n";

// Verifica estensioni PHP
echo "📋 ESTENSIONI PHP\n";
echo str_repeat("-", 20) . "\n";
$extensions = ['pdo', 'pdo_mysql', 'mysqli'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? "✅" : "❌";
    echo "$status $ext\n";
}

echo "\n🔧 VERSIONI\n";
echo str_repeat("-", 10) . "\n";
echo "PHP: " . PHP_VERSION . "\n";

// Test connessione MySQL con diversi metodi
echo "\n🔌 TEST CONNESSIONI\n";
echo str_repeat("-", 20) . "\n";

// Parametri di default per test
$test_configs = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
];

// Se vengono passati parametri via GET
if (isset($_GET['host']) && isset($_GET['user'])) {
    $custom_config = [
        'host' => $_GET['host'],
        'user' => $_GET['user'],
        'pass' => $_GET['pass'] ?? ''
    ];
    array_unshift($test_configs, $custom_config);
    echo "🎯 Test con parametri personalizzati:\n";
    echo "   Host: {$custom_config['host']}\n";
    echo "   User: {$custom_config['user']}\n";
    echo "   Pass: " . (empty($custom_config['pass']) ? "(vuota)" : "***") . "\n\n";
}

foreach ($test_configs as $config) {
    echo "Tentativo: {$config['user']}@{$config['host']} ";
    echo (empty($config['pass']) ? "(senza password)" : "(con password)") . "\n";
    
    // Test con PDO
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "   ✅ PDO: Connessione riuscita\n";
        
        // Test creazione database
        try {
            $test_db = 'test_scontrini_' . date('YmdHis');
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$test_db`");
            $pdo->exec("DROP DATABASE `$test_db`");
            echo "   ✅ Permessi: Può creare/eliminare database\n";
        } catch (Exception $e) {
            echo "   ⚠️  Permessi: Non può creare database - " . $e->getMessage() . "\n";
        }
        
    } catch (PDOException $e) {
        echo "   ❌ PDO: " . $e->getMessage() . "\n";
    }
    
    // Test con MySQLi
    if (extension_loaded('mysqli')) {
        try {
            $mysqli = new mysqli($config['host'], $config['user'], $config['pass']);
            if ($mysqli->connect_error) {
                echo "   ❌ MySQLi: " . $mysqli->connect_error . "\n";
            } else {
                echo "   ✅ MySQLi: Connessione riuscita\n";
                $mysqli->close();
            }
        } catch (Exception $e) {
            echo "   ❌ MySQLi: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

echo "💡 SUGGERIMENTI\n";
echo str_repeat("-", 15) . "\n";
echo "1. Verifica che MySQL sia in esecuzione\n";
echo "2. Controlla le credenziali di accesso\n";
echo "3. Su XAMPP, l'utente di default è 'root' senza password\n";
echo "4. Su alcuni sistemi, usa '127.0.0.1' invece di 'localhost'\n";
echo "5. Controlla il file di configurazione MySQL (my.cnf/my.ini)\n";
echo "6. Verifica che la porta MySQL sia quella standard (3306)\n\n";

echo "🔧 COMANDI UTILI\n";
echo str_repeat("-", 15) . "\n";
echo "Verifica stato MySQL:\n";
echo "  • Linux: sudo systemctl status mysql\n";
echo "  • Windows: net start mysql\n";
echo "  • XAMPP: Usa il pannello di controllo\n\n";

echo "Test manuale connessione:\n";
echo "  mysql -h localhost -u root -p\n\n";

// Se è una richiesta web, mostra anche un form per test personalizzato
if (isset($_SERVER['HTTP_HOST'])) {
    echo '<hr>';
    echo '<h3>🧪 Test Personalizzato</h3>';
    echo '<form method="GET">';
    echo '<div style="margin: 10px 0;">';
    echo '<label>Host: <input type="text" name="host" value="localhost" required></label>';
    echo '</div>';
    echo '<div style="margin: 10px 0;">';
    echo '<label>Username: <input type="text" name="user" value="root" required></label>';
    echo '</div>';
    echo '<div style="margin: 10px 0;">';
    echo '<label>Password: <input type="password" name="pass" value=""></label>';
    echo '</div>';
    echo '<div style="margin: 10px 0;">';
    echo '<button type="submit">🔍 Testa Connessione</button>';
    echo '</div>';
    echo '</form>';
}
?>