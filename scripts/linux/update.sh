#!/bin/bash

# Script di update per Gestione Scontrini PHP
# Aggiorna il sistema alla versione più recente

set -e

echo "🔄 Aggiornamento Gestione Scontrini PHP"
echo "======================================"

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

# Configurazione
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$HOME/scontrini_backup"
TEMP_DIR=$(mktemp -d)

# Cleanup su exit
cleanup() {
    if [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
}
trap cleanup EXIT

# Check prerequisiti
check_prerequisites() {
    print_info "Controllo prerequisiti..."
    
    # Git
    if ! command -v git >/dev/null 2>&1; then
        print_error "Git non trovato. Installare git per continuare."
    fi
    
    # Controlla se siamo in un repo git
    if [ ! -d "$SCRIPT_DIR/.git" ]; then
        print_error "Directory non è un repository Git"
    fi
    
    # Backup script
    if [ ! -f "$SCRIPT_DIR/backup.sh" ]; then
        print_warning "Script backup.sh non trovato"
    fi
    
    print_success "Prerequisiti: OK"
}

# Backup pre-aggiornamento
pre_update_backup() {
    print_info "Backup pre-aggiornamento..."
    
    if [ -f "$SCRIPT_DIR/backup.sh" ]; then
        # Esegui backup automatico
        bash "$SCRIPT_DIR/backup.sh" <<< $'y\n' >/dev/null 2>&1
        
        if [ $? -eq 0 ]; then
            print_success "Backup completato"
        else
            print_warning "Backup fallito, continuo comunque"
        fi
    else
        # Backup manuale basic
        mkdir -p "$BACKUP_DIR"
        tar -czf "$BACKUP_DIR/pre_update_$(date +%Y%m%d_%H%M%S).tar.gz" \
            --exclude=".git" -C "$SCRIPT_DIR" .
        print_success "Backup manuale completato"
    fi
}

# Check aggiornamenti disponibili
check_updates() {
    print_info "Controllo aggiornamenti..."
    
    cd "$SCRIPT_DIR"
    
    # Salva branch corrente
    CURRENT_BRANCH=$(git branch --show-current)
    
    # Fetch ultimo
    git fetch origin >/dev/null 2>&1
    
    LOCAL=$(git rev-parse HEAD)
    REMOTE=$(git rev-parse origin/$CURRENT_BRANCH)
    
    if [ "$LOCAL" = "$REMOTE" ]; then
        print_success "Sistema già aggiornato"
        exit 0
    fi
    
    print_info "Aggiornamenti disponibili:"
    print_info "Versione locale: ${LOCAL:0:7}"
    print_info "Versione remota: ${REMOTE:0:7}"
    
    echo
    echo "Modifiche in arrivo:"
    git log --oneline --graph "$LOCAL".."$REMOTE"
    echo
}

# Mostra changelog
show_changelog() {
    print_info "📋 Changelog:"
    echo
    
    cd "$SCRIPT_DIR"
    
    # Ultimi 10 commit
    git log --oneline --decorate -10
    
    echo
    read -p "Vuoi vedere i dettagli delle modifiche? (y/n): " show_details
    
    if [[ $show_details == "y" || $show_details == "Y" ]]; then
        LOCAL=$(git rev-parse HEAD)
        REMOTE=$(git rev-parse origin/$(git branch --show-current))
        
        git log --stat "$LOCAL".."$REMOTE"
    fi
}

# Verifica modifiche locali
check_local_changes() {
    cd "$SCRIPT_DIR"
    
    if ! git diff --quiet || ! git diff --cached --quiet; then
        print_warning "Ci sono modifiche locali non salvate!"
        
        echo
        echo "File modificati:"
        git status --porcelain
        echo
        
        echo "Cosa vuoi fare?"
        echo "1) Salva le modifiche in un commit"
        echo "2) Scarta le modifiche locali"
        echo "3) Interrompi aggiornamento"
        
        read -p "Scelta (1-3): " local_choice
        
        case $local_choice in
            1)
                read -p "Messaggio commit: " commit_msg
                git add .
                git commit -m "$commit_msg"
                print_success "Modifiche locali salvate"
                ;;
            2)
                git reset --hard HEAD
                git clean -fd
                print_warning "Modifiche locali scartate"
                ;;
            3)
                print_info "Aggiornamento interrotto"
                exit 0
                ;;
            *)
                print_error "Scelta non valida"
                ;;
        esac
    fi
}

# Esegui aggiornamento
perform_update() {
    print_info "🚀 Aggiornamento in corso..."
    
    cd "$SCRIPT_DIR"
    
    # Pull ultima versione
    if git pull origin $(git branch --show-current); then
        print_success "Codice aggiornato"
    else
        print_error "Errore durante l'aggiornamento del codice"
    fi
    
    # Check se ci sono migrazioni database
    if [ -f "migrate.php" ]; then
        print_info "Esecuzione migrazioni database..."
        
        if php migrate.php >/dev/null 2>&1; then
            print_success "Migrazioni database completate"
        else
            print_warning "Possibili problemi con migrazioni database"
        fi
    fi
    
    # Aggiorna permessi se necessario
    if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "cygwin" ]]; then
        find . -type f -name "*.php" -exec chmod 644 {} \;
        find . -type d -exec chmod 755 {} \;
        chmod +x *.sh 2>/dev/null || true
        print_success "Permessi aggiornati"
    fi
}

# Verifica post-aggiornamento
post_update_check() {
    print_info "🔍 Verifica post-aggiornamento..."
    
    # Test sintassi PHP
    php_files=$(find "$SCRIPT_DIR" -name "*.php" -type f)
    
    for file in $php_files; do
        if ! php -l "$file" >/dev/null 2>&1; then
            print_warning "Errore sintassi in: $file"
        fi
    done
    
    # Test configurazione
    if [ -f "$SCRIPT_DIR/config.php" ]; then
        if php -f "$SCRIPT_DIR/config.php" >/dev/null 2>&1; then
            print_success "Configurazione: OK"
        else
            print_warning "Possibili problemi con config.php"
        fi
    fi
    
    # Test accesso database
    if [ -f "$SCRIPT_DIR/test.php" ]; then
        if php -f "$SCRIPT_DIR/test.php" 2>&1 | grep -q "Test OK"; then
            print_success "Database: OK"
        else
            print_warning "Possibili problemi database"
        fi
    fi
    
    print_success "Verifica completata"
}

# Rollback
rollback_update() {
    print_warning "🔙 Rollback aggiornamento..."
    
    cd "$SCRIPT_DIR"
    
    # Torna al commit precedente
    git reset --hard HEAD~1
    
    print_warning "Sistema riportato alla versione precedente"
    
    # Ripristina backup se disponibile
    latest_backup=$(find "$BACKUP_DIR" -name "pre_update_*.tar.gz" -type f | sort -r | head -1)
    
    if [ -n "$latest_backup" ]; then
        read -p "Vuoi ripristinare anche il backup? (y/n): " restore_backup
        
        if [[ $restore_backup == "y" || $restore_backup == "Y" ]]; then
            print_info "Ripristino backup..."
            
            # Estrai backup
            tar -xzf "$latest_backup" -C "$SCRIPT_DIR"
            
            print_success "Backup ripristinato"
        fi
    fi
}

# Menu aggiornamento
update_menu() {
    echo "🎯 Tipo di aggiornamento:"
    echo "1) Aggiornamento automatico"
    echo "2) Aggiornamento con review"
    echo "3) Solo controllo aggiornamenti"
    echo "4) Rollback ultima versione"
    echo "5) Esci"
    echo
    
    read -p "Scelta (1-5): " choice
    
    case $choice in
        1)
            automatic_update
            ;;
        2)
            manual_update
            ;;
        3)
            check_only
            ;;
        4)
            rollback_only
            ;;
        5)
            print_info "Aggiornamento annullato"
            exit 0
            ;;
        *)
            print_error "Scelta non valida"
            ;;
    esac
}

# Aggiornamento automatico
automatic_update() {
    print_info "🚀 Aggiornamento automatico"
    
    check_prerequisites
    check_updates
    pre_update_backup
    check_local_changes
    perform_update
    post_update_check
    
    print_success "✨ Aggiornamento completato!"
    show_final_info
}

# Aggiornamento manuale
manual_update() {
    print_info "🛠️  Aggiornamento con review"
    
    check_prerequisites
    check_updates
    show_changelog
    
    read -p "Continuare con l'aggiornamento? (y/n): " confirm
    
    if [[ ! $confirm == "y" && ! $confirm == "Y" ]]; then
        print_info "Aggiornamento annullato"
        exit 0
    fi
    
    pre_update_backup
    check_local_changes
    perform_update
    post_update_check
    
    print_success "✨ Aggiornamento completato!"
    show_final_info
}

# Solo controllo
check_only() {
    check_prerequisites
    check_updates
    show_changelog
}

# Solo rollback
rollback_only() {
    print_warning "⚠️  ATTENZIONE: Il rollback rimuoverà le modifiche più recenti!"
    read -p "Sei sicuro di voler continuare? (y/N): " confirm_rollback
    
    if [[ $confirm_rollback == "y" || $confirm_rollback == "Y" ]]; then
        rollback_update
    else
        print_info "Rollback annullato"
    fi
}

# Info finale
show_final_info() {
    echo
    print_info "📋 Informazioni post-aggiornamento:"
    
    cd "$SCRIPT_DIR"
    
    CURRENT_VERSION=$(git describe --tags 2>/dev/null || git rev-parse --short HEAD)
    print_info "Versione corrente: $CURRENT_VERSION"
    
    if [ -f "$SCRIPT_DIR/index.php" ]; then
        # Prova a determinare URL
        if [[ "$SCRIPT_DIR" == *"htdocs"* ]]; then
            FOLDER_NAME=$(basename "$SCRIPT_DIR")
            print_info "URL: http://localhost/$FOLDER_NAME"
        fi
    fi
    
    echo
    print_info "🔧 Per problemi, usa: bash maintenance.sh"
    print_info "💾 Backup salvati in: $BACKUP_DIR"
}

# Avvio
clear
echo "🔧 Update Gestione Scontrini PHP"
echo "================================"

update_menu