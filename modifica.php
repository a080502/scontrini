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

$error = '';

if ($_POST) {
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $data_scontrino = $_POST['data_scontrino'] ?? '';
    $lordo = Utils::safeFloat($_POST['lordo'] ?? '');
    $note = Utils::sanitizeString($_POST['note'] ?? '');
    
    // Validazione
    if (empty($nome)) {
        $error = 'Il nome dello scontrino è obbligatorio';
    } elseif (empty($data_scontrino)) {
        $error = 'La data dello scontrino è obbligatoria';
    } elseif ($lordo <= 0) {
        $error = 'L\'importo lordo deve essere maggiore di zero';
    } else {
        try {
            $db->query("
                UPDATE scontrini 
                SET nome = ?, data_scontrino = ?, lordo = ?, note = ?, updated_at = NOW()
                WHERE id = ?
            ", [$nome, $data_scontrino, $lordo, $note, $id]);
            
            Utils::setFlashMessage('success', 'Scontrino modificato con successo!');
            Utils::redirect('lista.php');
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
} else {
    // Pre-compila i campi con i dati esistenti
    $nome = $scontrino['nome'];
    $data_scontrino = $scontrino['data_scontrino'];
    $lordo = $scontrino['lordo'];
    $note = $scontrino['note'];
}

$page_title = 'Modifica Scontrino - ' . SITE_NAME;
$page_header = 'Modifica Scontrino';

ob_start();
?>

<div class="card">
    <h4>Stato Attuale</h4>
    <p><strong>Creato:</strong> <?php echo Utils::formatDateTime($scontrino['created_at']); ?></p>
    <?php if ($scontrino['updated_at'] !== $scontrino['created_at']): ?>
    <p><strong>Ultima modifica:</strong> <?php echo Utils::formatDateTime($scontrino['updated_at']); ?></p>
    <?php endif; ?>
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
</div>

<form method="POST">
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label for="nome"><i class="fas fa-tag"></i> Nome Scontrino *</label>
        <div class="autocomplete">
            <input type="text" id="nome" name="nome" required 
                   value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                   placeholder="Es: Acquisto materiali ufficio">
        </div>
    </div>
    
    <div class="form-group">
        <label for="data_scontrino"><i class="fas fa-calendar"></i> Data Scontrino *</label>
        <input type="date" id="data_scontrino" name="data_scontrino" required
               value="<?php echo htmlspecialchars($data_scontrino ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="lordo"><i class="fas fa-euro-sign"></i> Importo Lordo *</label>
        <input type="number" id="lordo" name="lordo" step="0.01" min="0.01" required
               value="<?php echo htmlspecialchars($lordo ?? ''); ?>"
               placeholder="0,00">
    </div>
    
    <div class="form-group">
        <label for="note"><i class="fas fa-sticky-note"></i> Note (opzionale)</label>
        <textarea id="note" name="note" rows="3" 
                  placeholder="Note aggiuntive..."><?php echo htmlspecialchars($note ?? ''); ?></textarea>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-warning btn-lg">
            <i class="fas fa-save"></i> Salva Modifiche
        </button>
        <a href="lista.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>