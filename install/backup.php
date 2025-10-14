<?php
/**
 * Script di backup pre-installazione
 * Salva le configurazioni esistenti prima di una nuova installazione
 */

$backup_dir = 'install/backup_' . date('Y-m-d_H-i-s');
$files_to_backup = [
    'config.php',
    'installation.lock',
    '.htaccess'
];

echo "🔄 Backup Pre-Installazione\n";
echo "============================\n\n";

// Crea directory di backup
if (!is_dir($backup_dir)) {
    if (mkdir($backup_dir, 0755, true)) {
        echo "✅ Directory di backup creata: $backup_dir\n";
    } else {
        die("❌ Impossibile creare directory di backup\n");
    }
}

// Backup dei file
$backed_up = 0;
foreach ($files_to_backup as $file) {
    if (file_exists($file)) {
        $backup_file = $backup_dir . '/' . basename($file);
        if (copy($file, $backup_file)) {
            echo "✅ Backup di $file → $backup_file\n";
            $backed_up++;
        } else {
            echo "❌ Errore backup di $file\n";
        }
    } else {
        echo "ℹ️  File $file non presente (nessun backup necessario)\n";
    }
}

// Backup del database se configurato
if (file_exists('config.php') && $backed_up > 0) {
    echo "\n🗄️  Tentativo backup database...\n";
    try {
        require_once 'config.php';
        $backup_sql = $backup_dir . '/database_backup.sql';
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s 2>/dev/null',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($backup_sql)
        );
        
        $result = shell_exec($command);
        
        if (file_exists($backup_sql) && filesize($backup_sql) > 0) {
            echo "✅ Backup database salvato: $backup_sql\n";
        } else {
            echo "⚠️  Backup database non riuscito (comando mysqldump non disponibile?)\n";
        }
        
    } catch (Exception $e) {
        echo "⚠️  Backup database non riuscito: " . $e->getMessage() . "\n";
    }
}

// Crea file di informazioni sul backup
$backup_info = [
    'created_at' => date('Y-m-d H:i:s'),
    'files_backed_up' => $backed_up,
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'backup_reason' => 'Pre-installation backup'
];

file_put_contents($backup_dir . '/backup_info.json', json_encode($backup_info, JSON_PRETTY_PRINT));

echo "\n" . str_repeat("=", 40) . "\n";
echo "🎉 Backup completato!\n";
echo "📁 File salvati in: $backup_dir\n";
echo "ℹ️  Puoi ora procedere con la nuova installazione.\n";

// Script di ripristino
$restore_script = "#!/bin/bash
# Script di ripristino automatico
echo \"🔄 Ripristino backup del " . date('Y-m-d H:i:s') . "\"
echo \"======================================\"

BACKUP_DIR=\"$backup_dir\"

if [ -f \"\$BACKUP_DIR/config.php\" ]; then
    cp \"\$BACKUP_DIR/config.php\" config.php
    echo \"✅ config.php ripristinato\"
fi

if [ -f \"\$BACKUP_DIR/installation.lock\" ]; then
    cp \"\$BACKUP_DIR/installation.lock\" installation.lock
    echo \"✅ installation.lock ripristinato\"
fi

if [ -f \"\$BACKUP_DIR/.htaccess\" ]; then
    cp \"\$BACKUP_DIR/.htaccess\" .htaccess
    echo \"✅ .htaccess ripristinato\"
fi

if [ -f \"\$BACKUP_DIR/database_backup.sql\" ]; then
    echo \"ℹ️  Per ripristinare il database:\"
    echo \"   mysql -h[HOST] -u[USER] -p[PASS] [DATABASE] < \$BACKUP_DIR/database_backup.sql\"
fi

echo \"🎉 Ripristino completato!\"
";

file_put_contents($backup_dir . '/restore.sh', $restore_script);
chmod($backup_dir . '/restore.sh', 0755);

echo "📜 Script di ripristino creato: $backup_dir/restore.sh\n";
?>