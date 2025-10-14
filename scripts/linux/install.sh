#!/bin/bash

# Script di installazione automatica per Gestione Scontrini PHP
# Compatibile con XAMPP, LAMP, MAMP

set -e  # Exit on error

echo "🚀 Installazione Gestione Scontrini PHP"
echo "======================================"

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funzioni helper
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Rileva il sistema operativo
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        OS="linux"
        print_info "Sistema rilevato: Linux"
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
        print_info "Sistema rilevato: macOS"
    elif [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
        OS="windows"
        print_info "Sistema rilevato: Windows"
    else
        OS="unknown"
        print_warning "Sistema operativo non riconosciuto"
    fi
}

# Rileva il server web
detect_webserver() {
    if [ -d "/opt/lampp" ]; then
        WEBSERVER="xampp_linux"
        WEB_ROOT="/opt/lampp/htdocs"
        print_info "XAMPP Linux rilevato"
    elif [ -d "/Applications/XAMPP" ]; then
        WEBSERVER="xampp_macos"
        WEB_ROOT="/Applications/XAMPP/htdocs"
        print_info "XAMPP macOS rilevato"
    elif [ -d "C:/xampp" ] || [ -d "/c/xampp" ]; then
        WEBSERVER="xampp_windows"
        WEB_ROOT="/c/xampp/htdocs"
        print_info "XAMPP Windows rilevato"
    elif [ -d "/Applications/MAMP" ]; then
        WEBSERVER="mamp"
        WEB_ROOT="/Applications/MAMP/htdocs"
        print_info "MAMP rilevato"
    elif command -v apache2 >/dev/null 2>&1; then
        WEBSERVER="apache"
        WEB_ROOT="/var/www/html"
        print_info "Apache rilevato"
    else
        print_warning "Server web non rilevato automaticamente"
        echo "Inserisci manualmente il percorso della document root:"
        read -p "Percorso (es: /var/www/html): " WEB_ROOT
        WEBSERVER="custom"
    fi
}

# Verifica prerequisiti
check_prerequisites() {
    print_info "Verifica prerequisiti..."
    
    # PHP
    if ! command -v php >/dev/null 2>&1; then
        print_error "PHP non trovato. Installa PHP 7.4+ e riprova."
    fi
    
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    print_success "PHP $PHP_VERSION trovato"
    
    # Estensioni PHP
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mysqli" "json" "mbstring")
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "Estensione PHP $ext: OK"
        else
            print_error "Estensione PHP $ext mancante"
        fi
    done
}

# Installa i file
install_files() {
    print_info "Installazione file..."
    
    # Determina la cartella di destinazione
    if [ -z "$1" ]; then
        INSTALL_DIR="$WEB_ROOT/scontrini"
    else
        INSTALL_DIR="$WEB_ROOT/$1"
    fi
    
    # Crea la cartella se non esiste
    if [ ! -d "$INSTALL_DIR" ]; then
        mkdir -p "$INSTALL_DIR"
        print_success "Cartella creata: $INSTALL_DIR"
    fi
    
    # Copia i file (assumendo che lo script sia nella cartella del progetto)
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
    
    if [ "$PROJECT_DIR" != "$INSTALL_DIR" ]; then
        print_info "Copia file da $PROJECT_DIR a $INSTALL_DIR"
        cp -r "$PROJECT_DIR"/* "$INSTALL_DIR/"
        print_success "File copiati"
    else
        print_info "Script eseguito nella cartella di destinazione"
    fi
    
    # Imposta i permessi corretti
    if [ "$OS" != "windows" ]; then
        find "$INSTALL_DIR" -type f -name "*.php" -exec chmod 644 {} \;
        find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
        print_success "Permessi impostati"
    fi
}

# Configura .htaccess
configure_htaccess() {
    print_info "Configurazione .htaccess..."
    
    if [ -f "$INSTALL_DIR/.htaccess" ] && [ -f "$INSTALL_DIR/.htaccess-simple" ]; then
        print_info "Quale configurazione .htaccess vuoi usare?"
        echo "1) Standard (con sicurezza avanzata)"
        echo "2) Semplice (compatibile con tutti i server)"
        echo "3) Disabilita .htaccess"
        
        read -p "Scelta (1-3): " htaccess_choice
        
        case $htaccess_choice in
            1)
                print_info "Usando .htaccess standard"
                ;;
            2)
                mv "$INSTALL_DIR/.htaccess" "$INSTALL_DIR/.htaccess-backup"
                mv "$INSTALL_DIR/.htaccess-simple" "$INSTALL_DIR/.htaccess"
                print_success ".htaccess semplice attivato"
                ;;
            3)
                mv "$INSTALL_DIR/.htaccess" "$INSTALL_DIR/.htaccess-disabled"
                print_warning ".htaccess disabilitato"
                ;;
            *)
                print_info "Mantengo configurazione standard"
                ;;
        esac
    fi
}

# Test della configurazione
test_installation() {
    print_info "Test dell'installazione..."
    
    # URL di test
    if [ "$WEBSERVER" == "mamp" ]; then
        BASE_URL="http://localhost:8888"
    else
        BASE_URL="http://localhost"
    fi
    
    TEST_URL="$BASE_URL/$(basename "$INSTALL_DIR")/test.php"
    
    print_info "URL di test: $TEST_URL"
    
    if command -v curl >/dev/null 2>&1; then
        if curl -s "$TEST_URL" | grep -q "Test OK"; then
            print_success "Test connessione: OK"
        else
            print_warning "Test connessione: Possibili problemi"
        fi
    else
        print_info "Curl non disponibile, salta test automatico"
    fi
}

# Configurazione database
configure_database() {
    print_info "Configurazione database..."
    
    echo "Inserisci i parametri del database MySQL:"
    
    read -p "Host (default: localhost): " db_host
    db_host=${db_host:-localhost}
    
    read -p "Nome database (default: scontrini_db): " db_name
    db_name=${db_name:-scontrini_db}
    
    read -p "Username (default: root): " db_user  
    db_user=${db_user:-root}
    
    read -s -p "Password (lascia vuoto per XAMPP standard): " db_pass
    echo
    
    # Test connessione
    if command -v mysql >/dev/null 2>&1; then
        if mysql -h"$db_host" -u"$db_user" -p"$db_pass" -e "SELECT 1;" >/dev/null 2>&1; then
            print_success "Connessione database: OK"
        else
            print_warning "Impossibile connettersi al database. Verifica i parametri."
        fi
    fi
    
    # Salva configurazione
    cat > "$INSTALL_DIR/config.php" << EOF
<?php
// Configurazione database
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Configurazione sessioni
define('SESSION_LIFETIME', 1800); // 30 minuti
define('SESSION_SECRET', '$(openssl rand -hex 32)');

// Configurazione generale
define('SITE_NAME', 'Gestione Scontrini Fiscali');
define('LOCALE', 'it_IT');

// Timezone
date_default_timezone_set('Europe/Rome');

// Avvia sessione se non già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
EOF
    
    print_success "File config.php creato"
}

# Menu principale
main_menu() {
    echo
    echo "🎯 Scegli il tipo di installazione:"
    echo "1) Installazione completa automatica"
    echo "2) Installazione personalizzata"  
    echo "3) Solo copia file"
    echo "4) Solo test sistema"
    echo "5) Esci"
    echo
    
    read -p "Scelta (1-5): " choice
    
    case $choice in
        1)
            automatic_install
            ;;
        2)
            custom_install
            ;;
        3)
            files_only
            ;;
        4)
            system_test
            ;;
        5)
            print_info "Installazione annullata"
            exit 0
            ;;
        *)
            print_error "Scelta non valida"
            ;;
    esac
}

# Installazione automatica
automatic_install() {
    print_info "🚀 Installazione automatica in corso..."
    
    detect_os
    detect_webserver
    check_prerequisites
    install_files
    configure_htaccess
    configure_database
    test_installation
    
    print_success "✨ Installazione completata!"
    echo
    echo "📋 Prossimi passi:"
    echo "1. Vai su: $BASE_URL/$(basename "$INSTALL_DIR")/setup.php"
    echo "2. Completa la configurazione guidata"
    echo "3. Elimina il file setup.php dopo l'installazione"
    echo
    echo "👤 Credenziali di accesso iniziali:"
    echo "   Username: admin"
    echo "   Password: admin123"
    echo
}

# Installazione personalizzata
custom_install() {
    print_info "🛠️  Installazione personalizzata"
    
    read -p "Nome cartella di installazione (default: scontrini): " folder_name
    folder_name=${folder_name:-scontrini}
    
    detect_os
    detect_webserver
    check_prerequisites
    install_files "$folder_name"
    configure_htaccess
    
    echo "Vuoi configurare il database ora? (y/n)"
    read -p "Scelta: " configure_db
    if [[ $configure_db == "y" || $configure_db == "Y" ]]; then
        configure_database
    fi
    
    test_installation
    
    print_success "✨ Installazione personalizzata completata!"
}

# Solo copia file
files_only() {
    print_info "📁 Copia solo file"
    
    detect_os
    detect_webserver
    install_files
    
    print_success "File copiati in $INSTALL_DIR"
    print_info "Configura manualmente config.php e esegui setup.php"
}

# Solo test sistema
system_test() {
    print_info "🔍 Test del sistema"
    
    detect_os
    detect_webserver  
    check_prerequisites
    
    print_success "Test del sistema completato"
}

# Avvio script
clear
echo "🔧 Installatore Gestione Scontrini PHP"
echo "====================================="

main_menu