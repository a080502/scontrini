<?php
require_once 'includes/installation_check.php';
requireBootstrap();
Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$error = '';
$is_editing_self = ($id == $_SESSION['user_id']);

// Gli admin possono modificare tutti, gli utenti solo se stessi
if (!$is_editing_self) {
    Auth::requireAdmin();
}

if ($id <= 0) {
    Utils::setFlashMessage('error', 'ID utente non valido');
    Utils::redirect('utenti.php');
}

// Recupera utente
$utente = $db->fetchOne("SELECT * FROM utenti WHERE id = ?", [$id]);

if (!$utente) {
    Utils::setFlashMessage('error', 'Utente non trovato');
    Utils::redirect('utenti.php');
}

if ($_POST) {
    $username = Utils::sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $ruolo = $_POST['ruolo'] ?? $utente['ruolo'];
    
    // Gli utenti non admin non possono cambiare il proprio ruolo
    if ($is_editing_self && $_SESSION['ruolo'] !== 'admin') {
        $ruolo = $utente['ruolo'];
    }
    
    // Validazione
    if (empty($username)) {
        $error = 'Username è obbligatorio';
    } elseif (empty($nome)) {
        $error = 'Nome completo è obbligatorio';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'La password deve essere di almeno 6 caratteri';
    } elseif (!empty($password) && $password !== $password_confirm) {
        $error = 'Le password non corrispondono';
    } elseif (!in_array($ruolo, ['admin', 'user'])) {
        $error = 'Ruolo non valido';
    } else {
        // Controlla se username già esiste (escluso utente corrente)
        $existing_user = $db->fetchOne("SELECT id FROM utenti WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing_user) {
            $error = 'Username già esistente, scegline un altro';
        } else {
            try {
                if (!empty($password)) {
                    // Aggiorna con nuova password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $db->query("
                        UPDATE utenti 
                        SET username = ?, password = ?, nome = ?, ruolo = ? 
                        WHERE id = ?
                    ", [$username, $password_hash, $nome, $ruolo, $id]);
                } else {
                    // Aggiorna senza cambiare password
                    $db->query("
                        UPDATE utenti 
                        SET username = ?, nome = ?, ruolo = ? 
                        WHERE id = ?
                    ", [$username, $nome, $ruolo, $id]);
                }
                
                // Aggiorna sessione se l'utente sta modificando se stesso
                if ($is_editing_self) {
                    $_SESSION['username'] = $username;
                    $_SESSION['nome'] = $nome;
                    $_SESSION['ruolo'] = $ruolo;
                }
                
                Utils::setFlashMessage('success', "Utente '$username' modificato con successo!");
                
                if ($is_editing_self) {
                    Utils::redirect('index.php'); // Torna alla dashboard
                } else {
                    Utils::redirect('utenti.php'); // Torna alla lista utenti
                }
            } catch (Exception $e) {
                $error = 'Errore durante la modifica: ' . $e->getMessage();
            }
        }
    }
} else {
    // Pre-compila i campi
    $username = $utente['username'];
    $nome = $utente['nome'];
    $ruolo = $utente['ruolo'];
}

$page_title = ($is_editing_self ? 'Il Tuo Profilo' : 'Modifica Utente') . ' - ' . SITE_NAME;
$page_header = $is_editing_self ? 'Il Tuo Profilo' : 'Modifica Utente: ' . htmlspecialchars($utente['username']);

ob_start();
?>

<?php if ($is_editing_self): ?>
<div class="alert alert-info">
    <i class="fas fa-user-edit"></i> Stai modificando il tuo profilo personale.
</div>
<?php endif; ?>

<div class="card">
    <h4>Informazioni Account</h4>
    <p><strong>Creato:</strong> <?php echo Utils::formatDateTime($utente['created_at']); ?></p>
    <p><strong>Ruolo attuale:</strong> 
        <?php if ($utente['ruolo'] === 'admin'): ?>
            <span class="badge badge-success">👑 Amministratore</span>
        <?php else: ?>
            <span class="badge badge-warning">👤 Utente</span>
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
        <label for="username"><i class="fas fa-user"></i> Username *</label>
        <input type="text" id="username" name="username" required 
               value="<?php echo htmlspecialchars($username ?? ''); ?>"
               placeholder="Es: mario.rossi">
    </div>
    
    <div class="form-group">
        <label for="nome"><i class="fas fa-id-card"></i> Nome Completo *</label>
        <input type="text" id="nome" name="nome" required 
               value="<?php echo htmlspecialchars($nome ?? ''); ?>"
               placeholder="Es: Mario Rossi">
    </div>
    
    <div class="form-group">
        <label for="password"><i class="fas fa-lock"></i> Nuova Password</label>
        <input type="password" id="password" name="password" 
               minlength="6" placeholder="Lascia vuoto per non modificare">
        <small class="text-muted">Lascia vuoto se non vuoi cambiare la password</small>
    </div>
    
    <div class="form-group">
        <label for="password_confirm"><i class="fas fa-lock"></i> Conferma Nuova Password</label>
        <input type="password" id="password_confirm" name="password_confirm" 
               minlength="6" placeholder="Ripeti la nuova password">
    </div>
    
    <?php if (!$is_editing_self || $_SESSION['ruolo'] === 'admin'): ?>
    <div class="form-group">
        <label for="ruolo"><i class="fas fa-user-tag"></i> Ruolo *</label>
        <select id="ruolo" name="ruolo" required <?php echo $is_editing_self && $_SESSION['ruolo'] !== 'admin' ? 'disabled' : ''; ?>>
            <option value="user" <?php echo $ruolo === 'user' ? 'selected' : ''; ?>>
                👤 Utente Standard
            </option>
            <option value="admin" <?php echo $ruolo === 'admin' ? 'selected' : ''; ?>>
                👑 Amministratore
            </option>
        </select>
        <?php if ($is_editing_self && $_SESSION['ruolo'] !== 'admin'): ?>
        <small class="text-muted">Non puoi modificare il tuo ruolo</small>
        <input type="hidden" name="ruolo" value="<?php echo $ruolo; ?>">
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-warning btn-lg">
            <i class="fas fa-save"></i> Salva Modifiche
        </button>
        
        <?php if ($is_editing_self): ?>
        <a href="index.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
        <?php else: ?>
        <a href="utenti.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
        <?php endif; ?>
    </div>
</form>

<script>
// Validazione password in tempo reale
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    
    if (confirm && password !== confirm) {
        this.style.borderColor = '#dc3545';
        this.style.backgroundColor = '#f8d7da';
    } else if (confirm) {
        this.style.borderColor = '#28a745';
        this.style.backgroundColor = '#d4edda';
    } else {
        this.style.borderColor = '#ddd';
        this.style.backgroundColor = 'white';
    }
});

// Validazione se si inserisce password
document.getElementById('password').addEventListener('input', function() {
    const confirmField = document.getElementById('password_confirm');
    if (this.value) {
        confirmField.required = true;
        confirmField.parentElement.style.display = 'block';
    } else {
        confirmField.required = false;
        confirmField.value = '';
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>