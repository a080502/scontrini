<?php
// Verifica se l'installazione è stata effettuata
$installation_completed = file_exists('installation.lock');

// Se l'installazione non è stata effettuata, mostra solo il pulsante di installazione
if (!$installation_completed) {
    // Non includere bootstrap.php se non è ancora installato
    $page_title = 'Sistema non installato';
    $show_install_button = true;
} else {
    // Sistema installato, procedi normalmente
    require_once 'includes/bootstrap.php';
    
    // Se già loggato, reindirizza alla dashboard
    if (Auth::isLoggedIn()) {
        Utils::redirect('index.php');
    }
    
    $show_install_button = false;
    $error = '';
    
    // Mostra messaggio di installazione completata se richiesto
    if (isset($_GET['installed']) && $_GET['installed'] == '1') {
        $success_message = 'Installazione completata con successo! Ora puoi accedere al sistema.';
    }
    
    if ($_POST) {
        $username = Utils::sanitizeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username e password sono obbligatori';
        } else {
            if (Auth::login($username, $password)) {
                Utils::setFlashMessage('success', 'Benvenuto, ' . $_SESSION['nome'] . '!');
                Utils::redirect('index.php');
            } else {
                $error = 'Credenziali non valide';
            }
        }
    }
}

$page_title = $installation_completed ? 'Login - ' . (defined('SITE_NAME') ? SITE_NAME : 'Sistema Gestione Scontrini') : 'Sistema non installato';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        .login-header h1 {
            border: none;
            font-size: 2em;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-receipt fa-3x" style="margin-bottom: 15px;"></i>
            <h1>Gestione Scontrini</h1>
            <p class="text-muted">Accedi al tuo account</p>
        </div>
        
        <?php if (!$installation_completed): ?>
            <!-- Messaggio di sistema non installato -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Sistema non installato</strong><br>
                È necessario completare l'installazione prima di poter accedere al sistema.
            </div>
            
            <a href="install.php" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 18px;">
                <i class="fas fa-cogs"></i> Avvia Installazione Sistema
            </a>
            
            <div style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px;">
                <p>L'installazione ti guiderà attraverso la configurazione del database<br>
                e la creazione dell'utente amministratore.</p>
            </div>
            
        <?php else: ?>
            <!-- Form di login normale -->
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>