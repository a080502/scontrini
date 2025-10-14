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
    Utils::setFlashMessage('error', 'Non puoi incassare uno scontrino archiviato');
    Utils::redirect('lista.php');
}

if ($scontrino['stato'] === 'incassato' || $scontrino['stato'] === 'versato') {
    Utils::setFlashMessage('warning', 'Scontrino già incassato');
    Utils::redirect('lista.php');
}

try {
    // Incassa lo scontrino
    $db->query("
        UPDATE scontrini 
        SET stato = 'incassato', data_incasso = NOW() 
        WHERE id = ?
    ", [$id]);
    
    Utils::setFlashMessage('success', "Scontrino '{$scontrino['nome_display']}' incassato con successo!");
    
} catch (Exception $e) {
    Utils::setFlashMessage('error', 'Errore durante l\'incasso: ' . $e->getMessage());
}

Utils::redirect('lista.php');
?>