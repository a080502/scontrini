<?php
require_once 'includes/installation_check.php';
requireBootstrap();

Auth::logout();
Utils::setFlashMessage('success', 'Logout effettuato con successo');
Utils::redirect('login.php');
?>