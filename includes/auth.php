<?php
require_once 'config.php';

class Auth {
    
    public static function login($username, $password) {
        $db = Database::getInstance();
        
        $user = $db->fetchOne("
            SELECT u.*, f.nome as filiale_nome 
            FROM utenti u 
            LEFT JOIN filiali f ON u.filiale_id = f.id 
            WHERE u.username = ?
        ", [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['ruolo'] = $user['ruolo'];
            $_SESSION['filiale_id'] = $user['filiale_id'];
            $_SESSION['filiale_nome'] = $user['filiale_nome'];
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
    
    public static function requireAdminOrResponsabile() {
        self::requireLogin();
        if (!in_array($_SESSION['ruolo'], ['admin', 'responsabile'])) {
            header('Location: index.php');
            exit;
        }
    }
    
    public static function isAdmin() {
        return isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin';
    }
    
    public static function isResponsabile() {
        return isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'responsabile';
    }
    
    public static function isUtente() {
        return isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'utente';
    }
    
    public static function canViewAllFiliali() {
        return self::isAdmin();
    }
    
    public static function getVisibleFiliali() {
        $db = Database::getInstance();
        
        if (self::isAdmin()) {
            // Admin vede tutte le filiali
            return $db->fetchAll("SELECT * FROM filiali WHERE attiva = 1 ORDER BY nome");
        } elseif (self::isResponsabile()) {
            // Responsabile vede solo la sua filiale
            return $db->fetchAll("SELECT * FROM filiali WHERE id = ? AND attiva = 1", [$_SESSION['filiale_id']]);
        } else {
            // Utente normale vede solo la sua filiale
            return $db->fetchAll("SELECT * FROM filiali WHERE id = ? AND attiva = 1", [$_SESSION['filiale_id']]);
        }
    }
    
    public static function canAccessScontrino($scontrino) {
        if (self::isAdmin()) {
            return true; // Admin può vedere tutto
        } elseif (self::isResponsabile()) {
            return $scontrino['filiale_id'] == $_SESSION['filiale_id']; // Responsabile vede solo la sua filiale
        } else {
            return $scontrino['utente_id'] == $_SESSION['user_id']; // Utente vede solo i suoi
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
                'ruolo' => $_SESSION['ruolo'],
                'filiale_id' => $_SESSION['filiale_id'] ?? null,
                'filiale_nome' => $_SESSION['filiale_nome'] ?? null
            ];
        }
        return null;
    }
}
?>