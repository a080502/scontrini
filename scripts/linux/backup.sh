#!/bin/bash

# Script di backup per Gestione Scontrini PHP
# Crea backup completi di file e database

set -e

echo "💾 Backup Gestione Scontrini PHP"
echo "==============================="

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
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="scontrini_backup_$DATE"

# Carica configurazione se esiste
if [ -f "$SCRIPT_DIR/../../config.php" ]; then
    DB_HOST=$(grep "define('DB_HOST'" "$SCRIPT_DIR/../../config.php" | cut -d"'" -f4)
    DB_NAME=$(grep "define('DB_NAME'" "$SCRIPT_DIR/../../config.php" | cut -d"'" -f4)
    DB_USER=$(grep "define('DB_USER'" "$SCRIPT_DIR/../../config.php" | cut -d"'" -f4)
    DB_PASS=$(grep "define('DB_PASS'" "$SCRIPT_DIR/../../config.php" | cut -d"'" -f4)
    
    # Verifica che i valori siano stati letti
    if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
        print_error "Errore lettura config.php - valori database mancanti"
    fi
    
    print_info "Configurazione database caricata da config.php"
else
    print_warning "config.php non trovato, inserisci manualmente i dati"
    
    read -p "Host database: " DB_HOST
    read -p "Nome database: " DB_NAME
    read -p "Username: " DB_USER
    read -s -p "Password: " DB_PASS
    echo
fi

# Crea cartella backup
mkdir -p "$BACKUP_DIR/$BACKUP_NAME"
print_success "Cartella backup creata: $BACKUP_DIR/$BACKUP_NAME"

# Backup file applicazione
print_info "Backup file applicazione..."

tar -czf "$BACKUP_DIR/$BACKUP_NAME/files.tar.gz" \
    --exclude=".git" \
    --exclude="*.log" \
    --exclude="setup_completed.lock" \
    -C "$SCRIPT_DIR/../.." .

print_success "File applicazione salvati in files.tar.gz"

# Backup database
print_info "Backup database..."

if command -v mysqldump >/dev/null 2>&1; then
    # Costruisci comando mysqldump con password opzionale
    if [ -n "$DB_PASS" ]; then
        MYSQL_PASS_ARG="-p$DB_PASS"
    else
        MYSQL_PASS_ARG=""
    fi
    
    # Backup con structure e data
    mysqldump -h"$DB_HOST" -u"$DB_USER" $MYSQL_PASS_ARG \
        --routines --triggers --single-transaction \
        "$DB_NAME" > "$BACKUP_DIR/$BACKUP_NAME/database.sql"
    
    if [ $? -eq 0 ]; then
        print_success "Database salvato in database.sql"
        
        # Backup solo structure (per ripristini puliti)
        mysqldump -h"$DB_HOST" -u"$DB_USER" $MYSQL_PASS_ARG \
            --no-data --routines --triggers \
            "$DB_NAME" > "$BACKUP_DIR/$BACKUP_NAME/database_structure.sql"
        
        print_success "Struttura database salvata in database_structure.sql"
        
        # Backup solo dati
        mysqldump -h"$DB_HOST" -u"$DB_USER" $MYSQL_PASS_ARG \
            --no-create-info --skip-triggers \
            "$DB_NAME" > "$BACKUP_DIR/$BACKUP_NAME/database_data.sql"
        
        print_success "Dati database salvati in database_data.sql"
    else
        print_warning "Errore backup database - verifica connessione MySQL"
    fi
    
else
    print_warning "mysqldump non disponibile, backup database saltato"
fi

# Crea info file
cat > "$BACKUP_DIR/$BACKUP_NAME/backup_info.txt" << EOF
Backup Gestione Scontrini PHP
=============================

Data backup: $(date)
Versione: $(git -C "$SCRIPT_DIR/../.." describe --tags 2>/dev/null || echo "Non disponibile")
Commit: $(git -C "$SCRIPT_DIR/../.." rev-parse HEAD 2>/dev/null || echo "Non disponibile")

Database:
- Host: $DB_HOST
- Nome: $DB_NAME  
- User: $DB_USER

File inclusi:
- files.tar.gz: Tutti i file dell'applicazione
- database.sql: Backup completo database
- database_structure.sql: Solo struttura database
- database_data.sql: Solo dati database

Restore:
1. Estrai files.tar.gz nella cartella web server
2. Importa database.sql in MySQL
3. Aggiorna config.php se necessario
EOF

print_success "File info creato"

# Comprimi tutto
print_info "Compressione finale..."

cd "$BACKUP_DIR"
tar -czf "$BACKUP_NAME.tar.gz" "$BACKUP_NAME"
rm -rf "$BACKUP_NAME"

print_success "Backup completato: $BACKUP_DIR/$BACKUP_NAME.tar.gz"

# Statistiche
BACKUP_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_NAME.tar.gz" | cut -f1)
print_info "Dimensione backup: $BACKUP_SIZE"

# Pulizia backup vecchi (opzionale)
print_info "Vuoi eliminare i backup più vecchi di 30 giorni? (y/n)"
read -p "Scelta: " cleanup

if [[ $cleanup == "y" || $cleanup == "Y" ]]; then
    find "$BACKUP_DIR" -name "scontrini_backup_*.tar.gz" -mtime +30 -delete
    print_success "Backup vecchi eliminati"
fi

echo
print_success "✨ Backup completato con successo!"
echo
echo "📁 Percorso: $BACKUP_DIR/$BACKUP_NAME.tar.gz"
echo "📋 Per ripristinare: usa restore.sh o estrai manualmente"
echo