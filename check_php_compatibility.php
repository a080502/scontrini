<?php
/**
 * Script per verificare la compatibilità PHP del progetto
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Compatibilità PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2>🔍 Verifica Compatibilità PHP</h2>
        
        <?php
        // Verifica versione PHP
        $version = phpversion();
        $isPhp74 = version_compare($version, '7.4', '>=');
        $isPhp8 = version_compare($version, '8.0', '>=');
        ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>📊 Informazioni PHP</h5>
            </div>
            <div class="card-body">
                <p><strong>Versione PHP corrente:</strong> <?php echo $version; ?></p>
                <p><strong>Compatibilità PHP 7.4+:</strong> 
                    <?php echo $isPhp74 ? '<span class="text-success">✓ SÌ</span>' : '<span class="text-danger">✗ NO</span>'; ?>
                </p>
                <p><strong>Supporto nativo PHP 8.0+:</strong> 
                    <?php echo $isPhp8 ? '<span class="text-success">✓ SÌ</span>' : '<span class="text-warning">✗ NO (usa polyfill)</span>'; ?>
                </p>
            </div>
        </div>

        <?php
        // Cerca funzioni PHP 7.4+ e 8.0+ nel codice
        $php74Features = ['fn(', '=>']; // Arrow functions
        $php8Functions = ['str_contains', 'str_starts_with', 'str_ends_with', 'match', 'fdiv'];
        $files = glob('*.php');
        $includeFiles = glob('includes/*.php');
        $files = array_merge($files, $includeFiles);
        
        $issues74 = [];
        $issues8 = [];
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            $content = file_get_contents($file);
            
            // Controlla funzionalità PHP 7.4+
            $file74Issues = [];
            if (preg_match('/\bfn\s*\(/', $content)) {
                $file74Issues[] = 'arrow functions (fn)';
            }
            if (!empty($file74Issues)) {
                $issues74[$file] = $file74Issues;
            }
            
            // Controlla funzionalità PHP 8.0+
            $file8Issues = [];
            foreach ($php8Functions as $func) {
                if (preg_match('/\b' . $func . '\s*\(/', $content)) {
                    $file8Issues[] = $func;
                }
            }
            if (!empty($file8Issues)) {
                $issues8[$file] = $file8Issues;
            }
        }
        ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>📁 Analisi File</h5>
            </div>
            <div class="card-body">
                <?php if (empty($issues74) && empty($issues8)): ?>
                    <div class="alert alert-success">
                        🎉 <strong>Tutti i file sono compatibili con PHP 7.4+!</strong>
                    </div>
                <?php else: ?>
                    <?php if (!empty($issues74)): ?>
                        <div class="alert alert-warning">
                            ⚠️ <strong>Trovate funzionalità PHP 7.4+ nei seguenti file:</strong>
                        </div>
                        <?php foreach ($issues74 as $file => $features): ?>
                            <div class="mb-3">
                                <strong><?php echo htmlspecialchars($file); ?>:</strong>
                                <ul>
                                    <?php foreach ($features as $feature): ?>
                                        <li><code><?php echo $feature; ?></code> (richiede PHP 7.4+)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($issues8)): ?>
                        <div class="alert alert-info">
                            ℹ️ <strong>Trovate funzioni PHP 8.0+ nei seguenti file (ma supportate da polyfill):</strong>
                        </div>
                        <?php foreach ($issues8 as $file => $functions): ?>
                            <div class="mb-3">
                                <strong><?php echo htmlspecialchars($file); ?>:</strong>
                                <ul>
                                    <?php foreach ($functions as $func): ?>
                                        <li><code><?php echo $func; ?>()</code> (supportata da polyfill)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>🧪 Test Funzioni Polyfill</h5>
            </div>
            <div class="card-body">
                <?php
                // Include il file di compatibilità
                if (file_exists('includes/php_compatibility.php')) {
                    require_once 'includes/php_compatibility.php';
                }
                
                $tests = [
                    'str_contains' => function() {
                        return str_contains('Hello World', 'World') === true;
                    },
                    'str_starts_with' => function() {
                        return str_starts_with('Hello World', 'Hello') === true;
                    },
                    'str_ends_with' => function() {
                        return str_ends_with('Hello World', 'World') === true;
                    }
                ];
                
                foreach ($tests as $funcName => $test) {
                    try {
                        $result = $test();
                        $status = $result ? '✓' : '✗';
                        $class = $result ? 'text-success' : 'text-danger';
                        echo "<p><span class='$class'>$status</span> <strong>$funcName():</strong> " . ($result ? "Funziona" : "Errore") . "</p>";
                    } catch (Exception $e) {
                        echo "<p><span class='text-danger'>✗</span> <strong>$funcName():</strong> Errore - " . $e->getMessage() . "</p>";
                    }
                }
                ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>📋 Raccomandazioni</h5>
            </div>
            <div class="card-body">
                <?php if (!$isPhp8): ?>
                    <div class="alert alert-info">
                        <h6>💡 Per prestazioni ottimali:</h6>
                        <p>Considera l'aggiornamento a PHP 8.0+ per avere supporto nativo delle nuove funzioni.</p>
                        <pre>sudo apt update
sudo apt install php8.1 php8.1-mysql php8.1-pdo php8.1-gd php8.1-mbstring
sudo a2enmod php8.1
sudo systemctl restart apache2</pre>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        ✅ La tua versione PHP supporta nativamente tutte le funzioni moderne!
                    </div>
                <?php endif; ?>
                
                <?php if (!$isPhp74): ?>
                    <div class="alert alert-danger">
                        <h6>⚠️ Versione PHP non supportata</h6>
                        <p>Questo progetto richiede PHP 7.4 o superiore. La tua versione (<?php echo $version; ?>) potrebbe causare problemi.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="install.php" class="btn btn-primary">🚀 Torna all'Installazione</a>
            <a href="check_permissions.php" class="btn btn-secondary">🔧 Verifica Permessi</a>
        </div>
    </div>
</body>
</html>