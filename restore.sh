#!/bin/bash

# Script di ripristino per Gestione Scontrini PHP
# Ripristina backup creati con backup.sh

set -e

echo "🔄 Ripristino Gestione Scontrini PHP"
echo "===================================="

# Colori
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; exit 1; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Funzione per selezionare il backup
select_backup() {
    BACKUP_DIR="$HOME/scontrini_backup"
    
    if [ ! -d "$BACKUP_DIR" ]; then
        print_error "Cartella backup non trovata: $BACKUP_DIR"
    fi
    
    print_info "Backup disponibili:"
    echo
    
    backups=($(find "$BACKUP_DIR" -name "scontrini_backup_*.tar.gz" -type f | sort -r))
    
    if [ ${#backups[@]} -eq 0 ]; then
        print_error "Nessun backup trovato in $BACKUP_DIR"
    fi
    
    for i in "${!backups[@]}"; do
        backup_name=$(basename "${backups[$i]}")
        backup_date=$(echo "$backup_name" | sed 's/scontrini_backup_\([0-9]*_[0-9]*\).*/\1/' | sed 's/_/ /')
        backup_size=$(du -h "${backups[$i]}" | cut -f1)
        echo "$((i+1))) $backup_date ($backup_size)"
    done
    
    echo
    read -p "Seleziona backup (1-${#backups[@]}): " selection
    
    if [[ $selection -ge 1 && $selection -le ${#backups[@]} ]]; then
        SELECTED_BACKUP="${backups[$((selection-1))]}"
        print_success "Backup selezionato: $(basename "$SELECTED_BACKUP")"
    else
        print_error "Selezione non valida"
    fi
}

# Funzione per estrarre il backup
extract_backup() {
    TEMP_DIR=$(mktemp -d)
    
    print_info "Estrazione backup in corso..."
    
    cd "$TEMP_DIR"
    tar -xzf "$SELECTED_BACKUP"
    
    BACKUP_FOLDER=$(find . -maxdepth 1 -type d -name "scontrini_backup_*" | head -1)
    
    if [ -z "$BACKUP_FOLDER" ]; then
        print_error "Struttura backup non valida"
    fi
    
    cd "$BACKUP_FOLDER"
    
    print_success "Backup estratto in $TEMP_DIR/$BACKUP_FOLDER"
}

# Funzione per ripristinare i file
restore_files() {
    print_info "Dove vuoi ripristinare i file?"
    echo "1) Cartella corrente"
    echo "2) Cartella personalizzata"
    echo "3) Auto-detect webserver"
    
    read -p "Scelta (1-3): " restore_choice
    
    case $restore_choice in
        1)
            RESTORE_DIR="$(pwd)"
            ;;
        2)
            read -p "Inserisci percorso: " RESTORE_DIR
            ;;
        3)
            # Auto-detect come in install.sh
            if [ -d "/opt/lampp/htdocs" ]; then
                RESTORE_DIR="/opt/lampp/htdocs/scontrini"
            elif [ -d "/Applications/XAMPP/htdocs" ]; then
                RESTORE_DIR="/Applications/XAMPP/htdocs/scontrini"
            elif [ -d "/c/xampp/htdocs" ]; then
                RESTORE_DIR="/c/xampp/htdocs/scontrini"
            elif [ -d "/Applications/MAMP/htdocs" ]; then
                RESTORE_DIR="/Applications/MAMP/htdocs/scontrini"
            elif [ -d "/var/www/html" ]; then
                RESTORE_DIR="/var/www/html/scontrini"
            else
                print_error "Server web non rilevato automaticamente"
            fi
            ;;
        *)
            print_error "Scelta non valida"
            ;;
    esac
    
    print_info "Ripristino file in: $RESTORE_DIR"
    
    # Crea cartella se non esiste
    mkdir -p "$RESTORE_DIR"
    
    # Estrai file
    if [ -f "files.tar.gz" ]; then
        tar -xzf files.tar.gz -C "$RESTORE_DIR"
        print_success "File ripristinati"
        
        # Imposta permessi
        if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "cygwin" ]]; then
            find "$RESTORE_DIR" -type f -name "*.php" -exec chmod 644 {} \;
            find "$RESTORE_DIR" -type d -exec chmod 755 {} \;
            print_success "Permessi impostati"
        fi
    else
        print_error "File files.tar.gz non trovato nel backup"
    fi
}

# Funzione per ripristinare il database
restore_database() {
    print_info "Ripristino database"
    
    if [ ! -f "database.sql" ]; then
        print_warning "File database.sql non trovato, salto ripristino database"
        return
    fi
    
    # Leggi configurazione dal backup se disponibile
    if [ -f "backup_info.txt" ]; then
        DB_HOST_BACKUP=$(grep "Host:" backup_info.txt | cut -d' ' -f2)
        DB_NAME_BACKUP=$(grep "Nome:" backup_info.txt | cut -d' ' -f2)
        DB_USER_BACKUP=$(grep "User:" backup_info.txt | cut -d' ' -f2)
        
        print_info "Configurazione dal backup:"
        print_info "Host: $DB_HOST_BACKUP"
        print_info "Database: $DB_NAME_BACKUP"
        print_info "User: $DB_USER_BACKUP"
    fi
    
    echo "Inserisci parametri database di destinazione:"
    
    read -p "Host (default: localhost): " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    
    read -p "Nome database (default: scontrini_db): " DB_NAME
    DB_NAME=${DB_NAME:-scontrini_db}
    
    read -p "Username (default: root): " DB_USER
    DB_USER=${DB_USER:-root}
    
    read -s -p "Password: " DB_PASS
    echo
    
    # Test connessione
    if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" >/dev/null 2>&1; then
        print_error "Impossibile connettersi al database"
    fi
    
    print_success "Connessione database: OK"
    
    # Chiedi se creare il database
    print_warning "ATTENZIONE: Il ripristino sovrascriverà tutti i dati esistenti!"
    read -p "Continuare? (y/N): " confirm
    
    if [[ ! $confirm == "y" && ! $confirm == "Y" ]]; then
        print_info "Ripristino database annullato"
        return
    fi
    
    # Crea database se non esiste
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" \
        -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # Ripristina database
    print_info "Ripristino database in corso..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database.sql
    
    print_success "Database ripristinato"
    
    # Aggiorna config.php se esiste
    if [ -f "$RESTORE_DIR/config.php" ]; then
        print_info "Aggiornamento config.php..."
        
        sed -i.bak "s/define('DB_HOST', '.*')/define('DB_HOST', '$DB_HOST')/g" "$RESTORE_DIR/config.php"
        sed -i.bak "s/define('DB_NAME', '.*')/define('DB_NAME', '$DB_NAME')/g" "$RESTORE_DIR/config.php"
        sed -i.bak "s/define('DB_USER', '.*')/define('DB_USER', '$DB_USER')/g" "$RESTORE_DIR/config.php"
        sed -i.bak "s/define('DB_PASS', '.*')/define('DB_PASS', '$DB_PASS')/g" "$RESTORE_DIR/config.php"
        
        print_success "config.php aggiornato"
    fi
}

# Funzione di pulizia
cleanup() {
    if [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
        print_info "File temporanei eliminati"
    fi
}

# Menu principale
main_menu() {
    echo "🎯 Tipo di ripristino:"
    echo "1) Ripristino completo (file + database)"
    echo "2) Solo file"
    echo "3) Solo database"
    echo "4) Mostra info backup"
    echo "5) Esci"
    echo
    
    read -p "Scelta (1-5): " choice
    
    case $choice in
        1)
            full_restore
            ;;
        2)
            files_only_restore
            ;;
        3)
            database_only_restore
            ;;
        4)
            show_backup_info
            ;;
        5)
            print_info "Ripristino annullato"
            exit 0
            ;;
        *)
            print_error "Scelta non valida"
            ;;
    esac
}

# Ripristino completo
full_restore() {
    print_info "🔄 Ripristino completo in corso..."
    
    select_backup
    extract_backup
    restore_files
    restore_database
    
    print_success "✨ Ripristino completo completato!"
    
    show_next_steps
}

# Solo file
files_only_restore() {
    print_info "📁 Ripristino solo file"
    
    select_backup
    extract_backup
    restore_files
    
    print_success "✨ File ripristinati!"
}

# Solo database
database_only_restore() {
    print_info "🗄️  Ripristino solo database"
    
    select_backup
    extract_backup
    restore_database
    
    print_success "✨ Database ripristinato!"
}

# Mostra info backup
show_backup_info() {
    select_backup
    extract_backup
    
    if [ -f "backup_info.txt" ]; then
        print_info "Informazioni backup:"
        echo
        cat backup_info.txt
    else
        print_warning "File backup_info.txt non trovato"
    fi
}

# Prossimi passi
show_next_steps() {
    echo
    print_info "📋 Prossimi passi:"
    echo "1. Verifica che l'applicazione sia accessibile"
    echo "2. Testa il login con le credenziali del backup"
    echo "3. Controlla i log per eventuali errori"
    echo
    
    if [ -n "$RESTORE_DIR" ]; then
        echo "📁 File ripristinati in: $RESTORE_DIR"
        
        # Prova a determinare l'URL
        if [[ "$RESTORE_DIR" == *"htdocs"* ]]; then
            FOLDER_NAME=$(basename "$RESTORE_DIR")
            echo "🌐 URL probabile: http://localhost/$FOLDER_NAME"
        fi
    fi
}

# Trap per pulizia
trap cleanup EXIT

# Avvio
clear
echo "🔧 Ripristino Gestione Scontrini PHP"
echo "===================================="

main_menu