#!/bin/bash

# Script di manutenzione per Gestione Scontrini PHP
# Pulizia, ottimizzazione e check di sistema

set -e

echo "🛠️  Manutenzione Gestione Scontrini PHP"
echo "======================================"

# Colori
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Configurazione
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOG_FILE="$PROJECT_DIR/maintenance.log"

# Logging
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Carica configurazione database
load_db_config() {
    if [ -f "$PROJECT_DIR/config.php" ]; then
        DB_HOST=$(grep "define('DB_HOST'" "$PROJECT_DIR/config.php" | sed "s/.*'\(.*\)'.*/\1/")
        DB_NAME=$(grep "define('DB_NAME'" "$PROJECT_DIR/config.php" | sed "s/.*'\(.*\)'.*/\1/")
        DB_USER=$(grep "define('DB_USER'" "$PROJECT_DIR/config.php" | sed "s/.*'\(.*\)'.*/\1/")
        DB_PASS=$(grep "define('DB_PASS'" "$PROJECT_DIR/config.php" | sed "s/.*'\(.*\)'.*/\1/")
        
        print_success "Configurazione database caricata"
        log_message "Database config loaded"
    else
        print_error "File config.php non trovato"
    fi
}

# Check sistema
system_check() {
    print_info "🔍 Controllo sistema..."
    
    # PHP Version
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
    print_info "PHP Version: $PHP_VERSION"
    
    if php -v | grep -q "PHP 7.4\|PHP 8."; then
        print_success "Versione PHP: OK"
    else
        print_warning "Versione PHP non ottimale"
    fi
    
    # Estensioni PHP
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mysqli" "json" "mbstring" "curl")
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "Estensione $ext: OK"
        else
            print_warning "Estensione $ext: MANCANTE"
        fi
    done
    
    # Spazio disco
    DISK_USAGE=$(df "$PROJECT_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
    print_info "Utilizzo disco: ${DISK_USAGE}%"
    
    if [ "$DISK_USAGE" -gt 90 ]; then
        print_warning "Spazio disco quasi esaurito"
    else
        print_success "Spazio disco: OK"
    fi
    
    # Permessi file
    if [ -r "$PROJECT_DIR/config.php" ] && [ -r "$PROJECT_DIR/index.php" ]; then
        print_success "Permessi file: OK"
    else
        print_warning "Problemi permessi file"
    fi
    
    log_message "System check completed - PHP: $PHP_VERSION, Disk: ${DISK_USAGE}%"
}

# Check database
database_check() {
    print_info "🗄️  Controllo database..."
    
    if ! command -v mysql >/dev/null 2>&1; then
        print_warning "MySQL client non disponibile"
        return
    fi
    
    # Test connessione
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" "$DB_NAME" >/dev/null 2>&1; then
        print_success "Connessione database: OK"
    else
        print_error "Impossibile connettersi al database"
        return
    fi
    
    # Statistiche tabelle
    print_info "Statistiche tabelle:"
    
    SCONTRINI_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -se "SELECT COUNT(*) FROM scontrini;" 2>/dev/null || echo "0")
    
    UTENTI_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -se "SELECT COUNT(*) FROM utenti;" 2>/dev/null || echo "0")
    
    print_info "Scontrini: $SCONTRINI_COUNT"
    print_info "Utenti: $UTENTI_COUNT"
    
    # Controlla integrità
    print_info "Controllo integrità dati..."
    
    INCONSISTENT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -se "SELECT COUNT(*) FROM scontrini WHERE da_versare > lordo;" 2>/dev/null || echo "0")
    
    if [ "$INCONSISTENT" -gt 0 ]; then
        print_warning "$INCONSISTENT scontrini con da_versare > lordo"
    else
        print_success "Integrità dati: OK"
    fi
    
    log_message "Database check - Scontrini: $SCONTRINI_COUNT, Utenti: $UTENTI_COUNT, Errors: $INCONSISTENT"
}

# Ottimizzazione database
optimize_database() {
    print_info "⚡ Ottimizzazione database..."
    
    # Repair e optimize tables
    TABLES=("scontrini" "utenti")
    
    for table in "${TABLES[@]}"; do
        print_info "Ottimizzazione tabella $table..."
        
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            -e "OPTIMIZE TABLE $table;" >/dev/null 2>&1
        
        print_success "Tabella $table ottimizzata"
    done
    
    log_message "Database optimization completed"
}

# Pulizia file
cleanup_files() {
    print_info "🧹 Pulizia file..."
    
    # Elimina file temporanei
    find "$PROJECT_DIR" -name "*.tmp" -delete 2>/dev/null || true
    find "$PROJECT_DIR" -name "*.bak" -delete 2>/dev/null || true
    find "$PROJECT_DIR" -name "*~" -delete 2>/dev/null || true
    
    # Ruota log se troppo grande
    if [ -f "$LOG_FILE" ] && [ $(stat -c%s "$LOG_FILE" 2>/dev/null || echo "0") -gt 1048576 ]; then
        mv "$LOG_FILE" "${LOG_FILE}.old"
        touch "$LOG_FILE"
        print_info "Log ruotato (era > 1MB)"
    fi
    
    print_success "Pulizia file completata"
    log_message "File cleanup completed"
}

# Backup automatico
auto_backup() {
    print_info "💾 Backup automatico..."
    
    if [ -f "$SCRIPT_DIR/backup.sh" ]; then
        # Esegui backup silenzioso
        BACKUP_OUTPUT=$(bash "$SCRIPT_DIR/backup.sh" <<< $'y\n' 2>&1)
        
        if [ $? -eq 0 ]; then
            print_success "Backup automatico completato"
        else
            print_warning "Backup automatico fallito"
        fi
    else
        print_warning "Script backup.sh non trovato"
    fi
    
    log_message "Auto backup attempted"
}

# Report sistema
system_report() {
    print_info "📊 Generazione report sistema..."
    
    REPORT_FILE="$SCRIPT_DIR/system_report.txt"
    
    cat > "$REPORT_FILE" << EOF
Report Sistema - Gestione Scontrini PHP
======================================
Generato: $(date)

SISTEMA:
- OS: $(uname -s) $(uname -r)
- PHP: $(php -v | head -n1)
- MySQL: $(mysql --version 2>/dev/null | head -n1 || echo "Non disponibile")

DATABASE:
- Host: $DB_HOST
- Database: $DB_NAME
- Scontrini: $SCONTRINI_COUNT
- Utenti: $UTENTI_COUNT

SPAZIO DISCO:
$(df -h "$SCRIPT_DIR")

PROCESSI PHP:
$(ps aux | grep php | grep -v grep || echo "Nessun processo PHP attivo")

ULTIMI LOG:
$(tail -n 20 "$LOG_FILE" 2>/dev/null || echo "Nessun log disponibile")

CONFIGURAZIONE:
- Sessioni: $(grep SESSION_LIFETIME "$SCRIPT_DIR/config.php" 2>/dev/null || echo "Non configurato")
- Timezone: $(grep date_default_timezone_set "$SCRIPT_DIR/config.php" 2>/dev/null || echo "Non configurato")
EOF
    
    print_success "Report salvato in: $REPORT_FILE"
}

# Check aggiornamenti
check_updates() {
    print_info "🔄 Controllo aggiornamenti..."
    
    if [ -d "$SCRIPT_DIR/.git" ]; then
        cd "$SCRIPT_DIR"
        
        # Fetch latest
        git fetch origin main >/dev/null 2>&1 || true
        
        LOCAL=$(git rev-parse HEAD)
        REMOTE=$(git rev-parse origin/main 2>/dev/null || echo "$LOCAL")
        
        if [ "$LOCAL" != "$REMOTE" ]; then
            print_warning "Aggiornamenti disponibili!"
            print_info "Locale: ${LOCAL:0:7}"
            print_info "Remoto: ${REMOTE:0:7}"
            
            echo
            read -p "Vuoi vedere i cambiamenti? (y/n): " show_diff
            if [[ $show_diff == "y" || $show_diff == "Y" ]]; then
                git log --oneline "$LOCAL".."$REMOTE"
            fi
            
        else
            print_success "Sistema aggiornato"
        fi
    else
        print_info "Repository Git non trovato"
    fi
    
    log_message "Update check completed"
}

# Menu principale
main_menu() {
    echo
    echo "🎯 Operazioni disponibili:"
    echo "1) Check completo sistema"
    echo "2) Solo controllo database"
    echo "3) Ottimizzazione database"
    echo "4) Pulizia file"
    echo "5) Backup automatico"
    echo "6) Report sistema"
    echo "7) Check aggiornamenti"
    echo "8) Manutenzione completa"
    echo "9) Esci"
    echo
    
    read -p "Scelta (1-9): " choice
    
    case $choice in
        1)
            full_check
            ;;
        2)
            database_only
            ;;
        3)
            optimize_only
            ;;
        4)
            cleanup_only
            ;;
        5)
            backup_only
            ;;
        6)
            report_only
            ;;
        7)
            updates_only
            ;;
        8)
            full_maintenance
            ;;
        9)
            print_info "Manutenzione terminata"
            exit 0
            ;;
        *)
            print_error "Scelta non valida"
            main_menu
            ;;
    esac
}

# Operazioni individuali
full_check() {
    system_check
    load_db_config
    database_check
    print_success "✨ Check completo terminato"
}

database_only() {
    load_db_config
    database_check
}

optimize_only() {
    load_db_config
    optimize_database
}

cleanup_only() {
    cleanup_files
}

backup_only() {
    auto_backup
}

report_only() {
    load_db_config
    system_check
    database_check
    system_report
}

updates_only() {
    check_updates
}

# Manutenzione completa
full_maintenance() {
    print_info "🚀 Manutenzione completa in corso..."
    
    system_check
    load_db_config
    database_check
    optimize_database
    cleanup_files
    auto_backup
    system_report
    check_updates
    
    print_success "✨ Manutenzione completa terminata!"
    
    if [ -f "$SCRIPT_DIR/system_report.txt" ]; then
        echo
        read -p "Vuoi vedere il report? (y/n): " show_report
        if [[ $show_report == "y" || $show_report == "Y" ]]; then
            cat "$SCRIPT_DIR/system_report.txt"
        fi
    fi
}

# Avvio
clear
log_message "Maintenance script started"

echo "🔧 Manutenzione Gestione Scontrini PHP"
echo "====================================="

main_menu