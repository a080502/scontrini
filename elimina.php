<?php
require_once 'includes/installation_check.php';
requireBootstrap();
require_once 'includes/image_manager.php';
Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    Utils::setFlashMessage('error', 'ID scontrino non valido');
    Utils::redirect('lista.php');
}

// Recupera scontrino
$scontrino = $db->fetchOne("SELECT *, COALESCE(nome_persona, numero) as nome_display FROM scontrini WHERE id = ?", [$id]);

if (!$scontrino) {
    Utils::setFlashMessage('error', 'Scontrino non trovato');
    Utils::redirect('lista.php');
}

// Verifica che lo scontrino non sia archiviato (opzionale - potremmo voler permettere l'eliminazione)
// Per ora commentiamo questo controllo per permettere eliminazione anche di scontrini archiviati
/*
if ($scontrino['stato'] === 'archiviato') {
    Utils::setFlashMessage('error', 'Non puoi eliminare uno scontrino archiviato. Prima riattivalo.');
    Utils::redirect('archivio.php');
}
*/

if ($_POST && isset($_POST['conferma'])) {
    try {
        // Elimina il file foto se presente
        if (!empty($scontrino['foto'])) {
            $foto_eliminata = ImageManager::deleteScontrinoPhoto($scontrino['foto']);
            if (!$foto_eliminata) {
                // Log dell'errore ma non bloccare l'eliminazione dello scontrino
                error_log("Impossibile eliminare il file foto: " . $scontrino['foto']);
            }
        }
        
        // Elimina lo scontrino dal database
        $db->query("DELETE FROM scontrini WHERE id = ?", [$id]);
        
        $message = "Scontrino '{$scontrino['nome_display']}' eliminato con successo!";
        if (!empty($scontrino['foto'])) {
            $message .= " La foto associata è stata rimossa.";
        }
        
        Utils::setFlashMessage('success', $message);
        Utils::redirect('lista.php');
        
    } catch (Exception $e) {
        Utils::setFlashMessage('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        Utils::redirect('lista.php');
    }
}

$page_title = 'Elimina Scontrino - ' . SITE_NAME;
$page_header = 'Conferma Eliminazione';

ob_start();
?>

<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Attenzione!</strong> Stai per eliminare definitivamente il seguente scontrino:
</div>

<div class="card">
    <h4><?php echo htmlspecialchars($scontrino['nome_display']); ?></h4>
    <p><strong>Data:</strong> <?php echo Utils::formatDate($scontrino['data']); ?></p>
    <p><strong>Importo:</strong> <?php echo Utils::formatCurrency($scontrino['lordo']); ?></p>
    <p><strong>Stato:</strong>
        <?php if ($scontrino['stato'] === 'archiviato'): ?>
            <span class="badge" style="background-color: #6c757d;">Archiviato</span>
        <?php elseif ($scontrino['stato'] === 'versato'): ?>
            <span class="badge badge-success">Versato</span>
        <?php elseif ($scontrino['stato'] === 'incassato'): ?>
            <span class="badge badge-success">Incassato</span>
        <?php else: ?>
            <span class="badge badge-warning">Da Incassare</span>
        <?php endif; ?>
    </p>
    <?php if ($scontrino['note']): ?>
    <p><strong>Note:</strong> <?php echo htmlspecialchars($scontrino['note']); ?></p>
    <?php endif; ?>
    
    <?php if (!empty($scontrino['foto']) && file_exists($scontrino['foto'])): ?>
    <div style="margin-top: 15px;">
        <p><strong>Foto allegata:</strong></p>
        <div style="text-align: center;">
            <img src="<?php echo ImageManager::getPhotoUrl($scontrino['foto']) . '&thumbnail=1'; ?>" 
                 style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"
                 alt="Foto scontrino da eliminare">
        </div>
        <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> La foto verrà eliminata definitivamente</small></p>
    </div>
    <?php endif; ?>
</div>

<form method="POST" style="text-align: center; margin-top: 30px;">
    <p><strong style="color: #dc3545;">Questa operazione non può essere annullata!</strong></p>
    
    <button type="submit" name="conferma" value="1" class="btn btn-danger btn-lg">
        <i class="fas fa-trash"></i> Sì, Elimina Definitivamente
    </button>
    <a href="lista.php" class="btn btn-secondary btn-lg">
        <i class="fas fa-times"></i> Annulla
    </a>
</form>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>