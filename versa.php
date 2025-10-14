<?php
require_once 'includes/installation_check.php';
requireBootstrap();
Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    Utils::setFlashMessage('error', 'ID scontrino non valido');
    Utils::redirect('lista.php');
}

// Recupera scontrino
$scontrino = $db->fetchOne("SELECT *, COALESCE(nome_persona, numero) as nome_display FROM scontrini WHERE id = ?", [$id]);

if (!$scontrino) {
    Utils::setFlashMessage('error', 'Scontrino non trovato');
    Utils::redirect('lista.php');
}

if ($scontrino['stato'] === 'archiviato') {
    Utils::setFlashMessage('error', 'Non puoi versare uno scontrino archiviato');
    Utils::redirect('lista.php');
}

if ($scontrino['stato'] !== 'incassato') {
    Utils::setFlashMessage('error', 'Devi prima incassare lo scontrino');
    Utils::redirect('lista.php');
}

if ($scontrino['stato'] === 'versato') {
    Utils::setFlashMessage('warning', 'Scontrino già versato');
    Utils::redirect('lista.php');
}

try {
    // Versa lo scontrino
    $db->query("
        UPDATE scontrini 
        SET stato = 'versato', data_versamento = NOW() 
        WHERE id = ?
    ", [$id]);
    
    Utils::setFlashMessage('success', "Scontrino '{$scontrino['nome_display']}' versato con successo!");
    
} catch (Exception $e) {
    Utils::setFlashMessage('error', 'Errore durante il versamento: ' . $e->getMessage());
}

Utils::redirect('lista.php');
?>