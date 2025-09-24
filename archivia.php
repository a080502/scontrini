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

if ($scontrino['archiviato']) {
    Utils::setFlashMessage('warning', 'Scontrino già archiviato');
    Utils::redirect('archivio.php');
}

try {
    // Archivia lo scontrino
    $db->query("
        UPDATE scontrini 
        SET archiviato = 1, data_archiviazione = NOW() 
        WHERE id = ?
    ", [$id]);
    
    Utils::setFlashMessage('success', "Scontrino '{$scontrino['nome']}' archiviato con successo!");
    
} catch (Exception $e) {
    Utils::setFlashMessage('error', 'Errore durante l\'archiviazione: ' . $e->getMessage());
}

Utils::redirect('lista.php');
?>