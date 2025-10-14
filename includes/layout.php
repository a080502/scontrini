<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Gestione Scontrini Fiscali'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Script per logout automatico dopo 3 minuti di inattività -->
    <script>
        let inactivityTimer;
        let warningShown = false;

        function resetTimer() {
            clearTimeout(inactivityTimer);
            warningShown = false;
            
            inactivityTimer = setTimeout(function() {
                if (!warningShown) {
                    warningShown = true;
                    if (confirm('Sessione in scadenza per inattività. Clicca OK per rimanere connesso o Annulla per il logout.')) {
                        resetTimer();
                    } else {
                        window.location.href = 'logout.php';
                    }
                }
            }, <?php echo (SESSION_LIFETIME - 60) * 1000; ?>); // 2 minuti prima dello scadere
        }

        // Reset timer su attività utente
        document.addEventListener('click', resetTimer);
        document.addEventListener('keypress', resetTimer);
        document.addEventListener('scroll', resetTimer);
        document.addEventListener('mousemove', resetTimer);

        // Avvia il timer
        resetTimer();
    </script>

    <?php if (Auth::isLoggedIn()): ?>
    <nav class="navbar">
       <div class="brand">
    <a href="index.php">
        <img src="/uploads/loghi/logo.jpg" 
             onerror="this.onerror=null; this.src='/uploads/loghi/logo.png';" 
             alt="Logo" 
             style="height: 50px; margin-right: 10px; vertical-align: middle;">
        <span style="vertical-align: middle;">📋 <?php echo SITE_NAME; ?></span>
    </a>
</div>
         <?php if (Auth::isAdmin() || Auth::isResponsabile() || Auth::isutente()): ?>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="lista.php"><i class="fas fa-list"></i> Lista Scontrini</a>
            <?php echo Utils::smartLink('aggiungi.php', null, '<i class="fas fa-plus"></i> Aggiungi', '', true); ?>
            <a href="archivio.php"><i class="fas fa-archive"></i> Archivio</a>
            <a href="attivita.php"><i class="fas fa-clock"></i> Attività</a>
            <?php endif; ?>
            <?php if (Auth::isAdmin() || Auth::isResponsabile()): ?>
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle"><i class="fas fa-cog"></i> Gestione</a>
                    <div class="nav-dropdown-menu">
                        <a href="import-excel.php"><i class="fas fa-file-excel"></i>  Importa Scontrini da file</a>
                        <a href="utenti.php"><i class="fas fa-users"></i> Utenti</a>
                        <a href="filiali.php"><i class="fas fa-building"></i> Filiali</a>

                    </div>
                </div>
				<?php endif; ?>
				<?php if (Auth::isSuper()): ?>
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle"><i class="fas fa-cog"></i> Gestione</a>
                    <div class="nav-dropdown-menu">
                        <a href="import-excel.php"><i class="fas fa-file-excel"></i>  Importa Scontrini da file</a>
                        <a href="utenti.php"><i class="fas fa-users"></i> Utenti</a>
                        <a href="filiali.php"><i class="fas fa-building"></i> Filiali</a>
						<a href="configurazione_server.php"><i class="fas fa-building"></i> Configurazione Server</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-details">
                <strong><?php echo htmlspecialchars($_SESSION['nome']); ?></strong>
                <small>
                    <?php 
                    $ruoli = ['admin' => 'Amministratore', 'responsabile' => 'Responsabile', 'utente' => 'Utente'];
                    echo $ruoli[$_SESSION['ruolo']] ?? $_SESSION['ruolo'];
                    ?>
                    <?php if (!empty($_SESSION['filiale_nome'])): ?>
                        - <?php echo htmlspecialchars($_SESSION['filiale_nome']); ?>
                    <?php endif; ?>
                </small>
            </div>
            <a href="logout.php" class="btn btn-sm" style="margin-left: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    <?php endif; ?>

    <div class="content-container">
        <?php
        // Mostra messaggi flash
        if (Utils::hasFlashMessage('success')):
        ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo Utils::getFlashMessage('success'); ?>
        </div>
        <?php endif; ?>

        <?php if (Utils::hasFlashMessage('error')): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo Utils::getFlashMessage('error'); ?>
        </div>
        <?php endif; ?>

        <?php if (Utils::hasFlashMessage('warning')): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <?php echo Utils::getFlashMessage('warning'); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($page_header)): ?>
        <h1><?php echo htmlspecialchars($page_header); ?></h1>
        <?php endif; ?>

        <!-- Contenuto principale -->
        <?php if (isset($content)) echo $content; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/mobile-detection.js"></script>
    
    <?php if (isset($additional_scripts)) echo $additional_scripts; ?>
</body>
</html>