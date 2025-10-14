#!/bin/bash

# Script per avviare i servizi web (MySQL, Apache2, PHP)
# Autore: Sistema di gestione servizi web
# Data: $(date)

echo "================================================"
echo "🚀 Avvio servizi web per PROGETTO_PHP"
echo "================================================"
echo

# Colori per output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funzione per verificare se un comando è riuscito
check_status() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1 completato con successo${NC}"
    else
        echo -e "${RED}❌ Errore durante $1${NC}"
        return 1
    fi
}

# Avvio MySQL
echo -e "${YELLOW}📦 Avvio MySQL...${NC}"
sudo service mysql start
check_status "avvio MySQL"
echo

# Avvio Apache2
echo -e "${YELLOW}🌐 Avvio Apache2...${NC}"
sudo service apache2 start
check_status "avvio Apache2"
echo

# Verifica PHP (non richiede avvio come servizio separato)
echo -e "${YELLOW}🐘 Verifica PHP...${NC}"
php --version > /dev/null 2>&1
check_status "verifica PHP"
echo

# Verifica stato servizi
echo "================================================"
echo "📊 Stato dei servizi:"
echo "================================================"

echo -e "${YELLOW}MySQL:${NC}"
sudo service mysql status | head -3
echo

echo -e "${YELLOW}Apache2:${NC}"
sudo service apache2 status
echo

echo -e "${YELLOW}PHP:${NC}"
php --version | head -1
echo

echo "================================================"
echo -e "${GREEN}🎉 Tutti i servizi sono stati avviati!${NC}"
echo -e "${GREEN}🌐 La tua applicazione è disponibile su: http://localhost${NC}"
echo "================================================"