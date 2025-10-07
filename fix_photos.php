<?php
/**
 * SCRIPT DI EMERGENZA PER RIPARARE LE FOTO
 * Eseguire questo script dal browser per riparare i riferimenti alle foto
 */
require_once 'includes/bootstrap.php';

// Verifica autenticazione admin
Auth::requireLogin();
$current_user = Auth::getCurrentUser();
if (!Auth::isAdmin()) {
    die('❌ Solo gli amministratori possono eseguire questo script');
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riparazione Foto - Scontrini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4><i class="fas fa-tools"></i> Riparazione Foto Database</h4>
                </div>
                <div class="card-body">

<?php
if ($_POST && isset($_POST['repair'])) {
    echo '<div class="alert alert-info"><i class="fas fa-cog fa-spin"></i> Avvio riparazione...</div>';
    
    try {
        $db = Database::getInstance();
        $repaired = 0;
        $errors = 0;
        
        // 1. Migra da foto_scontrino a foto se necessario
        $columns = $db->fetchAll("SHOW COLUMNS FROM scontrini");
        $has_foto_scontrino = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'foto_scontrino') {
                $has_foto_scontrino = true;
                break;
            }
        }
        
        if ($has_foto_scontrino) {
            echo '<div class="alert alert-info">🔄 Migrazione da foto_scontrino a foto...</div>';
            
            $to_migrate = $db->fetchAll("
                SELECT id, foto_scontrino 
                FROM scontrini 
                WHERE foto_scontrino IS NOT NULL 
                AND foto_scontrino != '' 
                AND (foto IS NULL OR foto = '')
            ");
            
            foreach ($to_migrate as $record) {
                $updated = $db->query("UPDATE scontrini SET foto = ? WHERE id = ?", [
                    $record['foto_scontrino'], 
                    $record['id']
                ]);
                
                if ($updated) {
                    $repaired++;
                    echo '<div class="text-success">✅ Migrato record ID: ' . $record['id'] . '</div>';
                } else {
                    $errors++;
                    echo '<div class="text-danger">❌ Errore migrazione record ID: ' . $record['id'] . '</div>';
                }
            }
        }
        
        // 2. Cerca e ripara foto orfane
        echo '<div class="alert alert-info">🔍 Ricerca foto orfane...</div>';
        
        $upload_dir = 'uploads/scontrini';
        if (is_dir($upload_dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($upload_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $relative_path = str_replace('\\', '/', $file->getPath() . '/' . $file->getFilename());
                        $filename = basename($relative_path);
                        
                        // Estrae ID scontrino dal nome file
                        if (preg_match('/scontrino_(\d+)_/', $filename, $matches)) {
                            $scontrino_id = (int)$matches[1];
                            
                            // Verifica se esiste record con foto vuota
                            $scontrino = $db->fetchOne("
                                SELECT id, foto 
                                FROM scontrini 
                                WHERE id = ? AND (foto IS NULL OR foto = '')
                            ", [$scontrino_id]);
                            
                            if ($scontrino) {
                                // Aggiorna con il percorso corretto
                                $updated = $db->query("UPDATE scontrini SET foto = ? WHERE id = ?", [
                                    $relative_path, 
                                    $scontrino_id
                                ]);
                                
                                if ($updated) {
                                    $repaired++;
                                    echo '<div class="text-success">🔧 Riparato: ID ' . $scontrino_id . ' -> ' . $relative_path . '</div>';
                                } else {
                                    $errors++;
                                    echo '<div class="text-danger">❌ Errore riparazione: ID ' . $scontrino_id . '</div>';
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Statistiche finali
        $with_photos = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE foto IS NOT NULL AND foto != ''")['count'];
        $without_photos = $db->fetchOne("SELECT COUNT(*) as count FROM scontrini WHERE foto IS NULL OR foto = ''")['count'];
        
        echo '<div class="alert alert-success">
                <h5>✅ Riparazione completata!</h5>
                <ul>
                    <li><strong>Foto riparate:</strong> ' . $repaired . '</li>
                    <li><strong>Errori:</strong> ' . $errors . '</li>
                    <li><strong>Scontrini con foto:</strong> ' . $with_photos . '</li>
                    <li><strong>Scontrini senza foto:</strong> ' . $without_photos . '</li>
                </ul>
              </div>';
        
        echo '<div class="mt-3">
                <a href="lista.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> Torna alla Lista
                </a>
              </div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">
                <h5>❌ Errore durante la riparazione:</h5>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
              </div>';
    }
    
} else {
    // Mostra form di conferma
    ?>
    
    <div class="alert alert-warning">
        <h5><i class="fas fa-exclamation-triangle"></i> Problema Foto Scontrini</h5>
        <p>Questo script riparerà i riferimenti alle foto nel database. Specificatamente:</p>
        <ul>
            <li>Migrerà i dati da <code>foto_scontrino</code> a <code>foto</code> se necessario</li>
            <li>Collegherà le foto esistenti ai rispettivi scontrini</li>
            <li>Riparerà i riferimenti rotti nel database</li>
        </ul>
        <p><strong>Nota:</strong> Questa operazione è sicura e non cancella nessun file.</p>
    </div>
    
    <form method="POST">
        <div class="d-grid gap-2">
            <button type="submit" name="repair" value="1" class="btn btn-warning btn-lg">
                <i class="fas fa-tools"></i> Avvia Riparazione
            </button>
        </div>
    </form>
    
    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Annulla
        </a>
    </div>
    
<?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>