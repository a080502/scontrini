<?php
/**
 * Script di verifica permessi per l'installazione
 * Esegui questo script per verificare e correggere i permessi prima dell'installazione
 */

echo "<h2>🔍 Verifica Permessi Sistema</h2>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>\n";

$errors = [];
$warnings = [];
$fixes = [];

// Verifica directory corrente
echo "<h3>📁 Permessi Directory</h3>\n";
$dir = '.';
if (is_writable($dir)) {
    echo "<span class='ok'>✅ Directory corrente: SCRIVIBILE</span><br>\n";
} else {
    echo "<span class='error'>❌ Directory corrente: NON SCRIVIBILE</span><br>\n";
    $errors[] = "chmod 755 . # oppure chmod 777 . (meno sicuro)";
}

// Verifica config.php
echo "<h3>⚙️ File di Configurazione</h3>\n";
if (file_exists('config.php')) {
    if (is_writable('config.php')) {
        echo "<span class='ok'>✅ config.php: SCRIVIBILE</span><br>\n";
    } else {
        echo "<span class='error'>❌ config.php: NON SCRIVIBILE</span><br>\n";
        $errors[] = "chmod 666 config.php # oppure chmod 777 config.php";
    }
    echo "Permessi attuali: " . substr(sprintf('%o', fileperms('config.php')), -4) . "<br>\n";
} else {
    echo "<span class='warning'>⚠️ config.php: NON ESISTE (verrà creato durante l'installazione)</span><br>\n";
}

// Verifica directory uploads
echo "<h3>📤 Directory Uploads</h3>\n";
if (is_dir('uploads')) {
    if (is_writable('uploads')) {
        echo "<span class='ok'>✅ uploads/: SCRIVIBILE</span><br>\n";
    } else {
        echo "<span class='error'>❌ uploads/: NON SCRIVIBILE</span><br>\n";
        $errors[] = "chmod 777 uploads/";
    }
    echo "Permessi attuali: " . substr(sprintf('%o', fileperms('uploads')), -4) . "<br>\n";
} else {
    echo "<span class='error'>❌ uploads/: DIRECTORY NON ESISTENTE</span><br>\n";
    $errors[] = "mkdir uploads && chmod 777 uploads";
}

// Verifica proprietà file
echo "<h3>👤 Proprietà File</h3>\n";
$owner = posix_getpwuid(fileowner('.'));
$group = posix_getgrgid(filegroup('.'));
$current_user = posix_getpwuid(posix_geteuid());

echo "Proprietario directory: " . ($owner['name'] ?? 'sconosciuto') . "<br>\n";
echo "Gruppo directory: " . ($group['name'] ?? 'sconosciuto') . "<br>\n";
echo "Utente PHP corrente: " . ($current_user['name'] ?? 'sconosciuto') . "<br>\n";

if ($owner['uid'] !== posix_geteuid()) {
    $warnings[] = "Il proprietario della directory è diverso dall'utente PHP";
    $fixes[] = "chown -R " . ($current_user['name'] ?? 'www-data') . ":" . ($group['name'] ?? 'www-data') . " .";
}

// Mostra comandi di risoluzione
if (!empty($errors) || !empty($warnings)) {
    echo "<h3>🔧 Comandi per Risolvere i Problemi</h3>\n";
    
    if (!empty($errors)) {
        echo "<h4>❌ Problemi Critici (da risolvere):</h4>\n";
        echo "<pre>";
        foreach ($errors as $fix) {
            echo $fix . "\n";
        }
        echo "</pre>";
    }
    
    if (!empty($fixes)) {
        echo "<h4>⚠️ Miglioramenti Consigliati:</h4>\n";
        echo "<pre>";
        foreach ($fixes as $fix) {
            echo $fix . "\n";
        }
        echo "</pre>";
    }
    
    echo "<h4>🚀 Script Completo di Risoluzione:</h4>\n";
    echo "<pre>";
    echo "# Esegui questi comandi nella directory del progetto:\n";
    echo "cd " . realpath('.') . "\n\n";
    
    // Unisci tutti i fix
    $all_fixes = array_merge($errors, $fixes);
    foreach ($all_fixes as $fix) {
        echo $fix . "\n";
    }
    
    echo "\n# Verifica finale:\n";
    echo "ls -la config.php uploads/\n";
    echo "ls -ld .\n";
    echo "</pre>";
    
} else {
    echo "<h3>🎉 Tutti i Permessi sono OK!</h3>\n";
    echo "<span class='ok'>✅ Puoi procedere con l'installazione</span><br>\n";
    echo "<a href='install.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🚀 Avvia Installazione</a>\n";
}

// Informazioni aggiuntive
echo "<h3>ℹ️ Informazioni Sistema</h3>\n";
echo "PHP Version: " . PHP_VERSION . "<br>\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Sconosciuto') . "<br>\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Sconosciuto') . "<br>\n";
echo "Current Directory: " . realpath('.') . "<br>\n";
echo "PHP User: " . get_current_user() . "<br>\n";

echo "<hr><small>🔧 Per problemi persistenti, contatta l'amministratore di sistema</small>\n";
?>