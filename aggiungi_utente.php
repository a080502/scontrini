<?php
require_once 'includes/installation_check.php';
requireBootstrap();
Auth::requireAdmin();

$db = Database::getInstance();
$error = '';

if ($_POST) {
    $username = Utils::sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nome = Utils::sanitizeString($_POST['nome'] ?? '');
    $ruolo = $_POST['ruolo'] ?? 'user';
    
    // Validazione
    if (empty($username)) {
        $error = 'Username è obbligatorio';
    } elseif (empty($password)) {
        $error = 'Password è obbligatoria';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve essere di almeno 6 caratteri';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non corrispondono';
    } elseif (empty($nome)) {
        $error = 'Nome completo è obbligatorio';
    } elseif (!in_array($ruolo, ['admin', 'user'])) {
        $error = 'Ruolo non valido';
    } else {
        // Controlla se username già esiste
        $existing_user = $db->fetchOne("SELECT id FROM utenti WHERE username = ?", [$username]);
        if ($existing_user) {
            $error = 'Username già esistente, scegline un altro';
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $db->query("
                    INSERT INTO utenti (username, password, nome, ruolo) 
                    VALUES (?, ?, ?, ?)
                ", [$username, $password_hash, $nome, $ruolo]);
                
                Utils::setFlashMessage('success', "Utente '$username' creato con successo!");
                Utils::redirect('utenti.php');
            } catch (Exception $e) {
                $error = 'Errore durante la creazione: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Aggiungi Utente - ' . SITE_NAME;
$page_header = 'Aggiungi Nuovo Utente';

ob_start();
?>

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
        <small class="text-muted">Username per il login (caratteri alfanumerici e punti/underscore)</small>
    </div>
    
    <div class="form-group">
        <label for="nome"><i class="fas fa-id-card"></i> Nome Completo *</label>
        <input type="text" id="nome" name="nome" required 
               value="<?php echo htmlspecialchars($nome ?? ''); ?>"
               placeholder="Es: Mario Rossi">
    </div>
    
    <div class="form-group">
        <label for="password"><i class="fas fa-lock"></i> Password *</label>
        <input type="password" id="password" name="password" required 
               minlength="6" placeholder="Minimo 6 caratteri">
        <small class="text-muted">Password sicura di almeno 6 caratteri</small>
    </div>
    
    <div class="form-group">
        <label for="password_confirm"><i class="fas fa-lock"></i> Conferma Password *</label>
        <input type="password" id="password_confirm" name="password_confirm" required 
               minlength="6" placeholder="Ripeti la password">
    </div>
    
    <div class="form-group">
        <label for="ruolo"><i class="fas fa-user-tag"></i> Ruolo *</label>
        <select id="ruolo" name="ruolo" required>
            <option value="user" <?php echo (!isset($ruolo) || $ruolo === 'user') ? 'selected' : ''; ?>>
                👤 Utente Standard
            </option>
            <option value="admin" <?php echo (isset($ruolo) && $ruolo === 'admin') ? 'selected' : ''; ?>>
                👑 Amministratore
            </option>
        </select>
        <small class="text-muted">
            <strong>Utente:</strong> Può gestire solo scontrini<br>
            <strong>Admin:</strong> Può gestire scontrini + utenti del sistema
        </small>
    </div>
    
    <div class="card" style="background-color: #f8f9fa; border-left: 4px solid #007bff;">
        <h5><i class="fas fa-info-circle"></i> Informazioni Ruoli</h5>
        <p><strong>👤 Utente Standard:</strong></p>
        <ul>
            <li>Accesso a dashboard e statistiche</li>
            <li>Gestione completa scontrini (aggiungi, modifica, incassa, versa, archivia)</li>
            <li>Visualizzazione archivio e attività</li>
            <li>Non può gestire altri utenti</li>
        </ul>
        
        <p><strong>👑 Amministratore:</strong></p>
        <ul>
            <li>Tutte le funzionalità dell'utente standard</li>
            <li>Gestione utenti (aggiungi, modifica, elimina)</li>
            <li>Accesso a tutte le sezioni amministrative</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-user-plus"></i> Crea Utente
        </button>
        <a href="utenti.php" class="btn btn-secondary btn-lg">
            <i class="fas fa-times"></i> Annulla
        </a>
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
    } else {
        this.style.borderColor = '#28a745';
        this.style.backgroundColor = '#d4edda';
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>