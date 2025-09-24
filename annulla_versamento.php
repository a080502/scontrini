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

if (!$scontrino['versato']) {
    Utils::setFlashMessage('warning', 'Scontrino non è versato');
    Utils::redirect('lista.php');
}

try {
    // Annulla versamento
    $db->query("
        UPDATE scontrini 
        SET versato = 0, data_versamento = NULL 
        WHERE id = ?
    ", [$id]);
    
    Utils::setFlashMessage('success', "Versamento di '{$scontrino['nome']}' annullato con successo!");
    
} catch (Exception $e) {
    Utils::setFlashMessage('error', 'Errore durante l\'annullamento: ' . $e->getMessage());
}

Utils::redirect('lista.php');
?>