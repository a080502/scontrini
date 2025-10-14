<?php
// Test di funzionalità base senza database
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Gestione Scontrini Fiscali</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="content-container">
        <h1><i class="fas fa-receipt"></i> Test Installazione</h1>
        
        <div class="card">
            <h3>Controlli di Sistema</h3>
            
            <?php
            $tests = [
                'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'PDO Extension' => extension_loaded('pdo'),
                'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
                'Session Support' => function_exists('session_start'),
                'File CSS presente' => file_exists('assets/css/style.css'),
                'File JS presente' => file_exists('assets/js/app.js'),
                'Cartella includes presente' => is_dir('includes'),
                'File config presente' => file_exists('config.php'),
                'Cartella api presente' => is_dir('api')
            ];
            
            $all_ok = true;
            ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Risultato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test => $result): ?>
                    <?php $all_ok = $all_ok && $result; ?>
                    <tr>
                        <td><?php echo $test; ?></td>
                        <td>
                            <?php if ($result): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> OK</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #dc3545; color: white;"><i class="fas fa-times"></i> ERRORE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($all_ok): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>Tutti i test sono passati!</strong> L'applicazione è pronta per l'installazione.
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="setup.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-cog"></i> Procedi con il Setup
                </a>
            </div>
            
            <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                <strong>Alcuni test sono falliti!</strong> Verifica la configurazione di PHP e XAMPP.
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>Informazioni Sistema</h3>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></p>
            <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
        </div>
        
        <div class="card">
            <h3>Test Stili</h3>
            <p>Verifica che i CSS siano caricati correttamente:</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">42</div>
                    <div>Test Card</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number">100%</div>
                    <div>Success Card</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-number">3</div>
                    <div>Warning Card</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-number">0</div>
                    <div>Danger Card</div>
                </div>
            </div>
            
            <div class="totali">
                <h4>Test Stili Totali</h4>
                <p><strong>Importo Test:</strong> <span class="euro">€ 1.234,56</span></p>
            </div>
            
            <button class="btn btn-primary">Pulsante Primario</button>
            <button class="btn btn-success">Pulsante Successo</button>
            <button class="btn btn-warning">Pulsante Warning</button>
            <button class="btn btn-danger">Pulsante Danger</button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>