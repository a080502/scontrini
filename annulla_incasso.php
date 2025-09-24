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

if (!$scontrino['incassato']) {
    Utils::setFlashMessage('warning', 'Scontrino non è incassato');
    Utils::redirect('lista.php');
}

if ($scontrino['versato']) {
    Utils::setFlashMessage('error', 'Non puoi annullare l\'incasso di uno scontrino già versato');
    Utils::redirect('lista.php');
}

try {
    // Annulla incasso
    $db->query("
        UPDATE scontrini 
        SET incassato = 0, data_incasso = NULL 
        WHERE id = ?
    ", [$id]);
    
    Utils::setFlashMessage('success', "Incasso di '{$scontrino['nome']}' annullato con successo!");
    
} catch (Exception $e) {
    Utils::setFlashMessage('error', 'Errore durante l\'annullamento: ' . $e->getMessage());
}

Utils::redirect('lista.php');
?>