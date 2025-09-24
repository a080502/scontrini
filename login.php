<?php
require_once 'includes/bootstrap.php';

// Se già loggato, reindirizza alla dashboard
if (Auth::isLoggedIn()) {
    Utils::redirect('index.php');
}

$error = '';

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

$page_title = 'Login - ' . SITE_NAME;
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
        
        <div style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px;">
            <p>Credenziali di default:<br>
            <strong>Username:</strong> admin<br>
            <strong>Password:</strong> admin123</p>
        </div>
    </div>
</body>
</html>