<?php
require_once 'includes/bootstrap.php';
Auth::requireLogin();

$db = Database::getInstance();
$error = '';
$success = '';

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
                INSERT INTO scontrini (nome, data_scontrino, lordo, note) 
                VALUES (?, ?, ?, ?)
            ", [$nome, $data_scontrino, $lordo, $note]);
            
            Utils::setFlashMessage('success', 'Scontrino aggiunto con successo!');
            Utils::redirect('index.php');
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
}

$page_title = 'Aggiungi Scontrino - ' . SITE_NAME;
$page_header = 'Aggiungi Nuovo Scontrino';

ob_start();
?>

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
        <small class="text-muted">Inizia a digitare per vedere i suggerimenti dai nomi precedenti</small>
    </div>
    
    <div class="form-group">
        <label for="data_scontrino"><i class="fas fa-calendar"></i> Data Scontrino *</label>
        <input type="date" id="data_scontrino" name="data_scontrino" required
               value="<?php echo htmlspecialchars($data_scontrino ?? date('Y-m-d')); ?>">
    </div>
    
    <div class="form-group">
        <label for="lordo"><i class="fas fa-euro-sign"></i> Importo Lordo *</label>
        <input type="number" id="lordo" name="lordo" step="0.01" min="0.01" required
               value="<?php echo htmlspecialchars($lordo ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Usa la virgola o il punto per i decimali</small>
    </div>
    
    <div class="form-group">
        <label for="note"><i class="fas fa-sticky-note"></i> Note (opzionale)</label>
        <textarea id="note" name="note" rows="3" 
                  placeholder="Note aggiuntive..."><?php echo htmlspecialchars($note ?? ''); ?></textarea>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-save"></i> Salva Scontrino
        </button>
        <a href="index.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>