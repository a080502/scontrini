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
    
    /**
     * Rileva se il dispositivo corrente è mobile
     */
    public static function isMobileDevice() {
        // Controlla se è già stato salvato in sessione
        if (isset($_SESSION['is_mobile_device'])) {
            return $_SESSION['is_mobile_device'];
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Lista di pattern per rilevare dispositivi mobili
        $mobile_agents = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
            'Windows Phone', 'Opera Mini', 'IEMobile', 'webOS', 'Kindle',
            'Silk', 'Fennec', 'Maemo', 'Tablet', 'Playbook', 'BB10'
        ];
        
        $isMobile = false;
        foreach ($mobile_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $isMobile = true;
                break;
            }
        }
        
        // Controllo aggiuntivo per larghezza schermo via JavaScript (se disponibile)
        if (isset($_COOKIE['screen_width'])) {
            $screenWidth = (int)$_COOKIE['screen_width'];
            if ($screenWidth > 0 && $screenWidth <= 768) {
                $isMobile = true;
            }
        }
        
        // Controllo per dispositivi touch
        if (isset($_COOKIE['is_touch_device']) && $_COOKIE['is_touch_device'] === '1') {
            if (isset($_COOKIE['screen_width']) && (int)$_COOKIE['screen_width'] <= 1024) {
                $isMobile = true;
            }
        }
        
        // Salva il risultato in sessione
        $_SESSION['is_mobile_device'] = $isMobile;
        
        return $isMobile;
    }
    
    /**
     * Redirect intelligente che sceglie automaticamente la versione mobile o desktop
     */
    public static function smartRedirect($desktop_page, $mobile_page = null) {
        // Auto-detect per pagine con versione mobile disponibile
        if (!$mobile_page) {
            if ($desktop_page === 'aggiungi.php') {
                $mobile_page = 'aggiungi-mobile.php';
            }
        }
        
        // Se non esiste una versione mobile specifica, usa quella desktop
        if (!$mobile_page) {
            self::redirect($desktop_page);
            return;
        }
        
        // Se è un dispositivo mobile, vai alla versione mobile
        if (self::isMobileDevice()) {
            self::redirect($mobile_page);
        } else {
            self::redirect($desktop_page);
        }
    }
    
    /**
     * Genera un link intelligente che punta alla versione corretta (mobile/desktop)
     */
    public static function smartLink($desktop_page, $mobile_page = null, $text = '', $classes = '') {
        $target_page = $desktop_page;
        
        // Auto-detect per pagine con versione mobile disponibile
        if (!$mobile_page) {
            if ($desktop_page === 'aggiungi.php') {
                $mobile_page = 'aggiungi-mobile.php';
            }
        }
        
        if ($mobile_page && self::isMobileDevice()) {
            $target_page = $mobile_page;
        }
        
        return '<a href="' . htmlspecialchars($target_page) . '" class="' . htmlspecialchars($classes) . '">' . htmlspecialchars($text) . '</a>';
    }
    
    /**
     * Gestisce i filtri avanzati per le tabelle in base al ruolo utente
     */
    public static function buildAdvancedFilters($db, $current_user, $filters = [], $table_prefix = 's.') {
        $where_conditions = [];
        $params = [];
        $available_filters = [];
        
        // Filtri base per permessi utente (già esistenti)
        if (Auth::isAdmin()) {
            // Admin vede tutto - può filtrare per filiale, nome e utente
            $available_filters = ['filiale', 'nome', 'utente'];
        } elseif (Auth::isResponsabile()) {
            // Responsabile vede solo la sua filiale - può filtrare per nome e utente
            $where_conditions[] = $table_prefix . "filiale_id = ?";
            $params[] = $current_user['filiale_id'];
            $available_filters = ['nome', 'utente'];
        } else {
            // Utente normale vede solo i suoi - può filtrare per nome
            $where_conditions[] = $table_prefix . "utente_id = ?";
            $params[] = $current_user['id'];
            $available_filters = ['nome'];
        }
        
        // Applica filtri aggiuntivi
        if (!empty($filters['filiale_id']) && in_array('filiale', $available_filters)) {
            $where_conditions[] = $table_prefix . "filiale_id = ?";
            $params[] = $filters['filiale_id'];
        }
        
        if (!empty($filters['utente_id']) && in_array('utente', $available_filters)) {
            $where_conditions[] = $table_prefix . "utente_id = ?";
            $params[] = $filters['utente_id'];
        }
        
        if (!empty($filters['nome_filter']) && in_array('nome', $available_filters)) {
            $where_conditions[] = $table_prefix . "nome LIKE ?";
            $params[] = "%{$filters['nome_filter']}%";
        }
        
        return [
            'where_conditions' => $where_conditions,
            'params' => $params,
            'available_filters' => $available_filters
        ];
    }
    
    /**
     * Genera il HTML per i filtri avanzati
     */
    public static function renderAdvancedFiltersForm($db, $current_user, $current_filters = [], $base_url = '') {
        $filter_data = self::buildAdvancedFilters($db, $current_user, $current_filters);
        $available_filters = $filter_data['available_filters'];
        
        $html = '<div class="advanced-filters" style="background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0;">';
        $html .= '<h5 style="margin-bottom: 10px;">Filtri Avanzati</h5>';
        $html .= '<form method="GET" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: end;">';
        
        // Mantieni filtri esistenti
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['filiale_id', 'utente_id', 'nome_filter']) && !empty($value)) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
            }
        }
        
        // Filtro Filiale (solo per admin)
        if (in_array('filiale', $available_filters)) {
            $filiali = $db->fetchAll("SELECT id, nome FROM filiali WHERE attiva = 1 ORDER BY nome");
            $html .= '<div>';
            $html .= '<label for="filiale_id" style="display: block; font-size: 12px; margin-bottom: 2px;">Filiale:</label>';
            $html .= '<select name="filiale_id" id="filiale_id" style="padding: 5px;">';
            $html .= '<option value="">Tutte</option>';
            foreach ($filiali as $filiale) {
                $selected = ($current_filters['filiale_id'] ?? '') == $filiale['id'] ? 'selected' : '';
                $html .= '<option value="' . $filiale['id'] . '" ' . $selected . '>' . htmlspecialchars($filiale['nome']) . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }
        
        // Filtro Utente (per admin e responsabili)
        if (in_array('utente', $available_filters)) {
            // Per admin: tutti gli utenti, per responsabile: solo della sua filiale
            if (Auth::isAdmin()) {
                $utenti = $db->fetchAll("
                    SELECT u.id, u.nome, f.nome as filiale_nome 
                    FROM utenti u 
                    LEFT JOIN filiali f ON u.filiale_id = f.id 
                    WHERE u.attivo = 1 
                    ORDER BY u.nome
                ");
            } else {
                $utenti = $db->fetchAll("
                    SELECT u.id, u.nome 
                    FROM utenti u 
                    WHERE u.filiale_id = ? AND u.attivo = 1 
                    ORDER BY u.nome
                ", [$current_user['filiale_id']]);
            }
            
            $html .= '<div>';
            $html .= '<label for="utente_id" style="display: block; font-size: 12px; margin-bottom: 2px;">Utente:</label>';
            $html .= '<select name="utente_id" id="utente_id" style="padding: 5px;">';
            $html .= '<option value="">Tutti</option>';
            foreach ($utenti as $utente) {
                $selected = ($current_filters['utente_id'] ?? '') == $utente['id'] ? 'selected' : '';
                $display_name = Auth::isAdmin() && isset($utente['filiale_nome']) 
                    ? $utente['nome'] . ' (' . $utente['filiale_nome'] . ')'
                    : $utente['nome'];
                $html .= '<option value="' . $utente['id'] . '" ' . $selected . '>' . htmlspecialchars($display_name) . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }
        
        // Filtro Nome (per tutti)
        if (in_array('nome', $available_filters)) {
            $html .= '<div>';
            $html .= '<label for="nome_filter" style="display: block; font-size: 12px; margin-bottom: 2px;">Nome Scontrino:</label>';
            $html .= '<input type="text" name="nome_filter" id="nome_filter" value="' . htmlspecialchars($current_filters['nome_filter'] ?? '') . '" placeholder="Cerca per nome..." style="padding: 5px; width: 150px;">';
            $html .= '</div>';
        }
        
        $html .= '<div>';
        $html .= '<button type="submit" class="btn btn-sm" style="padding: 5px 10px;">Applica</button>';
        $html .= '<a href="' . $base_url . '" class="btn btn-sm btn-secondary" style="padding: 5px 10px; margin-left: 5px;">Reset</a>';
        $html .= '</div>';
        
        $html .= '</form>';
        $html .= '</div>';
        
        return $html;
    }
}
?>