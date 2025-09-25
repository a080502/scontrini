<?php
require_once 'includes/bootstrap.php';
require_once 'config.php';
define('APP_NAME', 'NomeApp');
Auth::requireLogin();

// Controllo automatico per dispositivi mobili (solo se non è forzata la versione desktop)
if (!isset($_GET['force_desktop'])) {
    Utils::smartRedirect('aggiungi.php', 'aggiungi-mobile.php');
}

// Redirect automatico alla versione mobile se necessario
if (Utils::isMobileDevice() && !isset($_GET['force_desktop'])) {
    Utils::redirect('aggiungi-mobile.php');
}

$db = Database::getInstance();
$current_user = Auth::getCurrentUser();
$error = '';
$success = '';

// Per responsabili e admin, recupera la lista utenti disponibili
$available_users = Auth::getAvailableUsersForReceipts();

if ($_POST) {
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $data_scontrino = $_POST['data_scontrino'] ?? '';
    $lordo = Utils::safeFloat($_POST['lordo'] ?? '');
    $da_versare = Utils::safeFloat($_POST['da_versare'] ?? '');
    $note = Utils::sanitizeString($_POST['note'] ?? '');
    $selected_user_id = isset($_POST['utente_id']) ? (int)$_POST['utente_id'] : null;
    
    // Se da_versare è vuoto o zero, usa l'importo lordo
    if ($da_versare <= 0) {
        $da_versare = $lordo;
    }
    
    // Determina l'utente e la filiale per lo scontrino
    $target_user_id = $current_user['id'];
    $target_filiale_id = $current_user['filiale_id'];
    
    // Se è responsabile o admin e ha selezionato un utente specifico
    if ((Auth::isResponsabile() || Auth::isAdmin()) && $selected_user_id) {
        // Verifica che l'utente selezionato sia autorizzato
        $selected_user = null;
        foreach ($available_users as $user) {
            if ($user['id'] == $selected_user_id) {
                $selected_user = $user;
                break;
            }
        }
        
        if ($selected_user) {
            // Ottieni i dettagli completi dell'utente selezionato
            $user_details = $db->fetchOne("
                SELECT id, filiale_id FROM utenti WHERE id = ? AND attivo = 1
            ", [$selected_user_id]);
            
            if ($user_details) {
                $target_user_id = $user_details['id'];
                $target_filiale_id = $user_details['filiale_id'];
            }
        }
    }
    
    // Validazione
    if (empty($nome)) {
        $error = 'Il nome dello scontrino è obbligatorio';
    } elseif (empty($data_scontrino)) {
        $error = 'La data dello scontrino è obbligatoria';
    } elseif ($lordo <= 0) {
        $error = 'L\'importo lordo deve essere maggiore di zero';
    } elseif ($da_versare < 0) {
        $error = 'L\'importo da versare non può essere negativo';
    } elseif ($da_versare > $lordo) {
        $error = 'L\'importo da versare non può essere maggiore dell\'importo lordo';
    } else {
        try {
            // Inserisci lo scontrino associandolo all'utente e filiale determinati
            $db->query("
                INSERT INTO scontrini (nome, data_scontrino, lordo, da_versare, note, utente_id, filiale_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$nome, $data_scontrino, $lordo, $da_versare, $note, $target_user_id, $target_filiale_id]);
            
            $success_message = 'Scontrino aggiunto con successo!';
            if ($target_user_id !== $current_user['id']) {
                // Trova il nome dell'utente per il messaggio
                $target_user_name = '';
                foreach ($available_users as $user) {
                    if ($user['id'] == $target_user_id) {
                        $target_user_name = $user['nome'];
                        break;
                    }
                }
                $success_message = "Scontrino aggiunto con successo per l'utente: " . $target_user_name;
            }
            
            Utils::setFlashMessage('success', $success_message);
            Utils::redirect('index.php');
        } catch (Exception $e) {
            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
        }
    }
}

$page_title = 'Aggiungi Scontrino - ' . APP_NAME;
$page_header = 'Aggiungi Nuovo Scontrino';

ob_start();
?>

<form method="POST">
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
    <div class="form-group user-selection-field">
        <label for="utente_id"><i class="fas fa-user"></i> Associa Scontrino a Utente</label>
        <select id="utente_id" name="utente_id" class="form-control">
            <option value="">-- Seleziona utente (o lascia vuoto per te stesso) --</option>
            <?php foreach ($available_users as $user): ?>
                <option value="<?php echo $user['id']; ?>" 
                        <?php echo (isset($selected_user_id) && $selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($user['nome'] . ' (' . $user['username'] . ')'); ?>
                    <?php if (Auth::isAdmin()): ?>
                        - <?php echo htmlspecialchars($user['filiale_nome']); ?>
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">
            <?php if (Auth::isAdmin()): ?>
                Come admin, puoi associare lo scontrino a qualsiasi utente di qualsiasi filiale
            <?php else: ?>
                Come responsabile, puoi associare lo scontrino agli utenti della tua filiale
            <?php endif; ?>
        </small>
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
        <input type="text" id="lordo" name="lordo" required
               pattern="[0-9]+([,\.][0-9]{1,2})?"
               value="<?php echo htmlspecialchars($lordo ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Importo totale dello scontrino (es: 123,45)</small>
    </div>
    
    <div class="form-group">
        <label for="da_versare"><i class="fas fa-hand-holding-usd"></i> Importo da Versare</label>
        <input type="text" id="da_versare" name="da_versare"
               pattern="[0-9]*([,\.][0-9]{1,2})?"
               value="<?php echo htmlspecialchars($da_versare ?? ''); ?>"
               placeholder="0,00">
        <small class="text-muted">Importo che deve essere versato (lascia vuoto se uguale all'importo lordo)</small>
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
    
    <!-- Link per passare alla versione mobile -->
    <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
        <a href="aggiungi-mobile.php?force_mobile=1" style="color: #6c757d; font-size: 14px; text-decoration: none;">
            📱 Passa alla versione mobile
        </a>
    </div>
</form>

<?php if ((Auth::isResponsabile() || Auth::isAdmin()) && !empty($available_users)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectUtente = document.getElementById('utente_id');
    const pageHeader = document.querySelector('h1');
    const originalHeader = pageHeader.textContent;
    
    selectUtente.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const userName = selectedOption.textContent.split(' (')[0];
            pageHeader.textContent = 'Aggiungi Scontrino per: ' + userName;
            pageHeader.className = 'text-primary';
        } else {
            pageHeader.textContent = originalHeader;
            pageHeader.className = '';
        }
    });
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
