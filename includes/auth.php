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
    
    public static function canManageUser($target_user_id) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $current_user = self::getCurrentUser();
        
        // Admin può gestire tutti
        if (self::isAdmin()) {
            return true;
        }
        
        // Responsabile può gestire utenti della sua filiale
        if (self::isResponsabile()) {
            $db = Database::getInstance();
            $target_user = $db->fetchOne("
                SELECT filiale_id FROM utenti WHERE id = ? AND attivo = 1
            ", [$target_user_id]);
            
            return $target_user && $target_user['filiale_id'] == $current_user['filiale_id'];
        }
        
        // Utente normale può gestire solo se stesso
        return $target_user_id == $current_user['id'];
    }
    
    public static function getAvailableUsersForReceipts() {
        if (!self::isLoggedIn()) {
            return [];
        }
        
        $db = Database::getInstance();
        $current_user = self::getCurrentUser();
        
        if (self::isAdmin()) {
            // Admin vede tutti gli utenti attivi
            return $db->fetchAll("
                SELECT u.id, u.username, u.nome, u.ruolo, f.nome as filiale_nome 
                FROM utenti u 
                JOIN filiali f ON u.filiale_id = f.id 
                WHERE u.attivo = 1 
                ORDER BY f.nome, u.nome
            ");
        } elseif (self::isResponsabile()) {
            // Responsabile vede solo utenti attivi della sua filiale
            return $db->fetchAll("
                SELECT u.id, u.username, u.nome, u.ruolo, f.nome as filiale_nome 
                FROM utenti u 
                JOIN filiali f ON u.filiale_id = f.id 
                WHERE u.filiale_id = ? AND u.attivo = 1 
                ORDER BY u.nome
            ", [$current_user['filiale_id']]);
        }
        
        // Utente normale non può selezionare altri utenti
        return [];
    }
}
?>