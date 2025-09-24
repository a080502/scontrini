<?php
class Utils {
    
    public static function formatCurrency($amount) {
        return '€ ' . number_format($amount, 2, ',', '.');
    }
    
    public static function formatDate($date, $format = 'd/m/Y') {
        if (empty($date)) return '';
        
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date->format($format);
    }
    
    public static function formatDateTime($datetime, $format = 'd/m/Y H:i') {
        return self::formatDate($datetime, $format);
    }
    
    public static function safeFloat($value) {
        if (empty($value)) return 0.0;
        
        // Gestisce sia virgola che punto come separatore decimale
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
    
    public static function sanitizeString($string) {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
    
    public static function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    public static function setFlashMessage($type, $message) {
        $_SESSION['flash_' . $type] = $message;
    }
    
    public static function getFlashMessage($type) {
        if (isset($_SESSION['flash_' . $type])) {
            $message = $_SESSION['flash_' . $type];
            unset($_SESSION['flash_' . $type]);
            return $message;
        }
        return null;
    }
    
    public static function hasFlashMessage($type) {
        return isset($_SESSION['flash_' . $type]);
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>