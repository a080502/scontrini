#!/bin/bash

# Script per configurare backup automatico serale su Linux
# Usage: ./setup_automatic_backup.sh

echo ""
echo "========================================"
echo "  Setup Backup Automatico Serale"
echo "========================================"
echo ""

# Colori
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Percorsi
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_SCRIPT="$SCRIPT_DIR/backup.sh"

# Verifica che esista il backup script
if [ ! -f "$BACKUP_SCRIPT" ]; then
    echo -e "${RED}[ERROR]${NC} Script backup.sh non trovato in: $BACKUP_SCRIPT"
    exit 1
fi

echo -e "${BLUE}[INFO]${NC} Script backup trovato: $BACKUP_SCRIPT"

# Rendi eseguibile il backup script se non lo è già
if [ ! -x "$BACKUP_SCRIPT" ]; then
    chmod +x "$BACKUP_SCRIPT"
    echo -e "${BLUE}[INFO]${NC} Reso eseguibile backup.sh"
fi

# Configura orario
DEFAULT_TIME="22:00"
echo -n "Inserisci orario backup (formato HH:MM, default $DEFAULT_TIME): "
read BACKUP_TIME

if [ -z "$BACKUP_TIME" ]; then
    BACKUP_TIME="$DEFAULT_TIME"
fi

# Converte orario in formato cron (HH:MM -> MM HH)
HOUR=$(echo "$BACKUP_TIME" | cut -d':' -f1)
MINUTE=$(echo "$BACKUP_TIME" | cut -d':' -f2)

echo -e "${BLUE}[INFO]${NC} Orario backup configurato: $BACKUP_TIME (cron: $MINUTE $HOUR * * *)"

# Crea entry crontab
CRON_ENTRY="$MINUTE $HOUR * * * $BACKUP_SCRIPT >/dev/null 2>&1"
CRON_COMMENT="# Backup automatico Gestione Scontrini PHP - ogni giorno alle $BACKUP_TIME"

# Backup crontab attuale
echo -e "${BLUE}[INFO]${NC} Backup crontab attuale..."
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true

# Rimuovi entry esistenti per questo backup (se presenti)
echo -e "${BLUE}[INFO]${NC} Rimozione entry esistenti..."
crontab -l 2>/dev/null | grep -v "$BACKUP_SCRIPT" | crontab - 2>/dev/null || true

# Aggiungi nuova entry
echo -e "${BLUE}[INFO]${NC} Aggiunta nuova entry crontab..."
(crontab -l 2>/dev/null; echo "$CRON_COMMENT"; echo "$CRON_ENTRY") | crontab -

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}[OK]${NC} Backup automatico configurato con successo!"
    echo ""
    echo -e "${BLUE}Dettagli configurazione:${NC}"
    echo "- Orario: $BACKUP_TIME ogni giorno"
    echo "- Script: $BACKUP_SCRIPT"
    echo "- Cron entry: $CRON_ENTRY"
    echo "- Backup salvati in: ~/scontrini_backup/"
    echo ""
    echo -e "${GREEN}Il backup verrà eseguito automaticamente ogni sera alle $BACKUP_TIME${NC}"
    echo ""
    echo -e "${YELLOW}Per gestire i task cron:${NC}"
    echo "- Visualizza: crontab -l"
    echo "- Modifica: crontab -e" 
    echo "- Log: sudo tail -f /var/log/cron"
else
    echo ""
    echo -e "${RED}[ERROR]${NC} Errore durante configurazione crontab"
    echo ""
    echo -e "${YELLOW}Configurazione manuale:${NC}"
    echo "1. Esegui: crontab -e"
    echo "2. Aggiungi riga: $CRON_ENTRY"
    exit 1
fi

# Test opzionale
echo ""
read -p "Vuoi testare il backup ora? (s/N): " TEST_RUN
if [ "$TEST_RUN" = "s" ] || [ "$TEST_RUN" = "S" ]; then
    echo -e "${BLUE}[INFO]${NC} Avvio test backup..."
    "$BACKUP_SCRIPT"
fi

echo ""
echo -e "${GREEN}[SUCCESS]${NC} Configurazione completata!"