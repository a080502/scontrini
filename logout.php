<?php
require_once 'includes/bootstrap.php';

Auth::logout();
Utils::setFlashMessage('success', 'Logout effettuato con successo');
Utils::redirect('login.php');
?>