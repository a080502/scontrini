<?php
require_once 'config.php';

class Auth {
    
    public static function login($username, $password) {
        $db = Database::getInstance();
        
        $user = $db->fetchOne("SELECT * FROM utenti WHERE username = ?", [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['ruolo'] = $user['ruolo'];
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        return false;
    }
    
    public static function logout() {
        session_destroy();
        session_start();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && self::checkSessionTimeout();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        // Aggiorna last activity
        $_SESSION['last_activity'] = time();
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if ($_SESSION['ruolo'] !== 'admin') {
            header('Location: index.php');
            exit;
        }
    }
    
    public static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }
        return true;
    }
    
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nome' => $_SESSION['nome'],
                'ruolo' => $_SESSION['ruolo']
            ];
        }
        return null;
    }
}
?>