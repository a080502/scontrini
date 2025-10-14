<?php
require_once 'includes/installation_check.php';
requireBootstrap();
Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    Utils::setFlashMessage('error', 'ID scontrino non valido');
    Utils::redirect('archivio.php');
}

// Recupera scontrino
$scontrino = $db->fetchOne("SELECT *, COALESCE(nome_persona, numero) as nome_display FROM scontrini WHERE id = ?", [$id]);

if (!$scontrino) {
    Utils::setFlashMessage('error', 'Scontrino non trovato');
    Utils::redirect('archivio.php');
}

if ($scontrino['stato'] !== 'archiviato') {
    Utils::setFlashMessage('warning', 'Scontrino non è archiviato');
    Utils::redirect('lista.php');
}

try {
    // Riattiva lo scontrino
    $db->query("
        UPDATE scontrini 
        SET stato = 'attivo', data_archiviazione = NULL 
        WHERE id = ?
    ", [$id]);
    
    Utils::setFlashMessage('success', "Scontrino '{$scontrino['nome_display']}' riattivato con successo!");
    
} catch (Exception $e) {
    Utils::setFlashMessage('error', 'Errore durante la riattivazione: ' . $e->getMessage());
}

Utils::redirect('lista.php');
?>