<?php
require_once 'includes/bootstrap.php';
Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    Utils::setFlashMessage('error', 'ID scontrino non valido');
    Utils::redirect('lista.php');
}

// Recupera scontrino
$scontrino = $db->fetchOne("SELECT * FROM scontrini WHERE id = ?", [$id]);

if (!$scontrino) {
    Utils::setFlashMessage('error', 'Scontrino non trovato');
    Utils::redirect('lista.php');
}

if ($_POST && isset($_POST['conferma'])) {
    try {
        // Elimina lo scontrino
        $db->query("DELETE FROM scontrini WHERE id = ?", [$id]);
        
        Utils::setFlashMessage('success', "Scontrino '{$scontrino['nome']}' eliminato con successo!");
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
    <h4><?php echo htmlspecialchars($scontrino['nome']); ?></h4>
    <p><strong>Data:</strong> <?php echo Utils::formatDate($scontrino['data_scontrino']); ?></p>
    <p><strong>Importo:</strong> <?php echo Utils::formatCurrency($scontrino['lordo']); ?></p>
    <p><strong>Stato:</strong>
        <?php if ($scontrino['archiviato']): ?>
            <span class="badge" style="background-color: #6c757d;">Archiviato</span>
        <?php elseif ($scontrino['versato']): ?>
            <span class="badge badge-success">Versato</span>
        <?php elseif ($scontrino['incassato']): ?>
            <span class="badge badge-success">Incassato</span>
        <?php else: ?>
            <span class="badge badge-warning">Da Incassare</span>
        <?php endif; ?>
    </p>
    <?php if ($scontrino['note']): ?>
    <p><strong>Note:</strong> <?php echo htmlspecialchars($scontrino['note']); ?></p>
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