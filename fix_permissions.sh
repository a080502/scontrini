#!/bin/bash

# Script automatico per correggere i permessi del Sistema Gestione Scontrini
# Esegui con: bash fix_permissions.sh

echo "🔧 Sistema Gestione Scontrini - Correzione Permessi"
echo "=================================================="

# Vai alla directory dello script
cd "$(dirname "$0")"
CURRENT_DIR=$(pwd)

echo "📁 Directory corrente: $CURRENT_DIR"
echo ""

# Verifica se siamo nella directory giusta
if [ ! -f "install.php" ]; then
    echo "❌ ERRORE: install.php non trovato"
    echo "   Assicurati di eseguire questo script dalla directory del progetto"
    exit 1
fi

echo "✅ File install.php trovato - directory corretta"
echo ""

# Backup dei permessi attuali
echo "💾 Backup permessi attuali..."
ls -la > permissions_backup.txt
echo "   Salvato in: permissions_backup.txt"
echo ""

# Correggi permessi directory principale
echo "🔧 Correzione permessi directory principale..."
chmod 755 .
echo "   chmod 755 . -> OK"

# Correggi config.php se esiste
if [ -f "config.php" ]; then
    echo "🔧 Correzione permessi config.php..."
    chmod 666 config.php
    echo "   chmod 666 config.php -> OK"
else
    echo "⚠️  config.php non esiste (verrà creato durante l'installazione)"
fi

# Crea e correggi directory uploads
echo "🔧 Correzione directory uploads..."
if [ ! -d "uploads" ]; then
    mkdir uploads
    echo "   mkdir uploads -> OK"
fi
chmod 777 uploads
echo "   chmod 777 uploads -> OK"

# Crea sottodirectory se non esistono
if [ ! -d "uploads/foto_scontrini" ]; then
    mkdir -p uploads/foto_scontrini
    chmod 777 uploads/foto_scontrini
    echo "   mkdir uploads/foto_scontrini -> OK"
fi

# Verifica se abbiamo i diritti di proprietà
CURRENT_USER=$(whoami)
FILE_OWNER=$(ls -ld . | awk '{print $3}')

echo ""
echo "👤 Informazioni proprietà:"
echo "   Utente corrente: $CURRENT_USER"
echo "   Proprietario file: $FILE_OWNER"

if [ "$CURRENT_USER" != "$FILE_OWNER" ] && [ "$CURRENT_USER" != "root" ]; then
    echo ""
    echo "⚠️  AVVISO: Non sei il proprietario dei file"
    echo "   Se hai problemi, prova a eseguire:"
    echo "   sudo chown -R $CURRENT_USER:$CURRENT_USER ."
    echo "   oppure:"
    echo "   sudo chown -R www-data:www-data ."
fi

echo ""
echo "🔍 Verifica finale permessi:"
echo "Directory principale:"
ls -ld .

if [ -f "config.php" ]; then
    echo "config.php:"
    ls -la config.php
fi

echo "uploads/:"
ls -ld uploads

echo ""
echo "✅ COMPLETATO! Correzione permessi terminata"
echo ""
echo "🚀 Prossimi passi:"
echo "1. Verifica i permessi con: php check_permissions.php"
echo "2. Avvia l'installazione: apri install.php nel browser"
echo ""
echo "📋 File di log creati:"
echo "   - permissions_backup.txt (backup permessi originali)"
echo ""

# Test rapido scrittura
echo "🧪 Test rapido scrittura..."
if touch test_write.tmp 2>/dev/null; then
    rm test_write.tmp
    echo "   ✅ Test scrittura: OK"
else
    echo "   ❌ Test scrittura: FALLITO"
    echo "   Potresti aver bisogno di permessi aggiuntivi"
fi

echo ""
echo "🔧 Se hai ancora problemi, esegui:"
echo "   php check_permissions.php"
echo "   per una diagnosi dettagliata"