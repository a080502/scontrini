<?php
require_once 'includes/bootstrap.php';
Auth::requireAdminOrResponsabile();

$msg = '';
$error = '';

// Gestione upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $file = $_FILES['logo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file['error'] === UPLOAD_ERR_OK && in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
        $dest = __DIR__."/uploads/loghi/logo.$ext";
        // Cancella eventuali vecchi loghi
        foreach ($allowed as $e) {
            $old = __DIR__."/uploads/loghi/logo.$e";
            if (file_exists($old)) unlink($old);
        }
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $msg = "Logo caricato correttamente!";
        } else {
            $error = "Errore nel salvataggio del logo.";
        }
    } else {
        $error = "Formato o dimensione non valida.";
    }
}

// Gestione cancellazione
if (isset($_GET['delete']) && Auth::isAdmin()) {
    $allowed = ['jpg','jpeg','png','webp','gif'];
    foreach ($allowed as $e) {
        $old = __DIR__."/uploads/loghi/logo.$e";
        if (file_exists($old)) unlink($old);
    }
    $msg = "Logo eliminato.";
}

$logoPath = '';
foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
    if (file_exists(__DIR__ . "/uploads/loghi/logo.$ext")) {
        $logoPath = "/uploads/loghi/logo.$ext";
        break;
    }
}

$page_title = 'Gestione Logo Azienda';
ob_start();
?>
<div class="container mt-4">
    <h2>Gestione Logo Azienda</h2>
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <?php if ($logoPath): ?>
        <div>
            <img src="<?= $logoPath ?>" alt="Logo Azienda" style="max-height:120px;">
            <br>
            <a href="?delete=1" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Eliminare il logo?')">Elimina Logo</a>
        </div>
    <?php else: ?>
        <p>Nessun logo caricato.</p>
    <?php endif; ?>
    <hr>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="logo" class="form-label">Carica nuovo logo (formati: jpg, png, webp, gif, max 5MB):</label>
            <input type="file" name="logo" id="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif" required>
        </div>
        <button type="submit" class="btn btn-primary">Carica</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>