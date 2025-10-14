# 🔧 Strumenti per la Gestione Permessi

Questo progetto include diversi strumenti per gestire i permessi dei file durante l'installazione.

## 📋 Strumenti Disponibili

### 1. 🌐 **Correzione Permessi Web** (Raccomandato)
**Direttamente dall'installer:**
- Vai su `install.php` 
- Se i requisiti falliscono, clicca **"Correggi Permessi"**
- Il sistema tenterà di correggere automaticamente i permessi via web

### 2. 🔍 **Diagnosi Dettagliata Web**
**File:** `check_permissions.php`
```
http://tuodominio.com/tuoprogetto/check_permissions.php
```
- Analisi completa permessi e proprietà
- Comandi specifici per risolvere problemi
- Test di scrittura automatico
- Script di risoluzione completo

### 3. 🚀 **Correzione Automatica da Terminale**
**File:** `fix_permissions.sh`
```bash
# SSH nel tuo server e esegui:
cd /path/to/your/project
bash fix_permissions.sh
```

## 🎯 Metodi di Risoluzione (in ordine di preferenza)

### Metodo 1: Web Installer 🌐
1. Apri `install.php`
2. Se vedi errori requisiti, clicca **"Correggi Permessi"**
3. Clicca **"Ricontrolla"** per verificare
4. Procedi con l'installazione

### Metodo 2: Diagnosi Web 🔍
1. Apri `check_permissions.php` nel browser
2. Copia i comandi mostrati
3. Eseguili via SSH/terminale
4. Torna all'installazione

### Metodo 3: Script Automatico 🚀
```bash
# SSH nel server
cd /var/www/html/tuoprogetto
bash fix_permissions.sh
```

### Metodo 4: Comandi Manuali ⌨️
```bash
# Permessi minimi necessari
chmod 755 .
chmod 666 config.php  # se esiste
mkdir -p uploads
chmod 777 uploads
mkdir -p uploads/foto_scontrini
chmod 777 uploads/foto_scontrini
```

## 🔧 Problemi Comuni e Soluzioni

### ❌ "Permission denied" durante creazione config.php
**Soluzioni:**
1. Clicca "Correggi Permessi" nell'installer
2. Oppure: `chmod 755 . && touch config.php && chmod 666 config.php`

### ❌ Directory uploads non scrivibile
**Soluzioni:**
1. Clicca "Correggi Permessi" nell'installer  
2. Oppure: `mkdir -p uploads && chmod 777 uploads`

### ❌ Tutti i requisiti falliscono
**Soluzioni:**
1. Usa `check_permissions.php` per diagnosi completa
2. Esegui `fix_permissions.sh` da terminale
3. Contatta l'amministratore di sistema

## 🛡️ Sicurezza

### Permessi Consigliati per Produzione:
```bash
# Directory principale
chmod 755 .

# File configurazione
chmod 644 config.php

# Directory uploads (deve rimanere 777 per funzionare)
chmod 777 uploads
chmod 777 uploads/foto_scontrini

# File PHP
find . -name "*.php" -exec chmod 644 {} \;

# Script eseguibili
chmod +x fix_permissions.sh
```

### ⚠️ Note Sicurezza:
- La directory `uploads/` richiede permessi 777 per funzionare
- In produzione, considera l'uso di un utente dedicato
- Evita permessi 777 su file PHP
- Rimuovi `check_permissions.php` dopo l'installazione

## 🆘 Supporto

Se hai ancora problemi:

1. **Esegui la diagnosi completa:**
   ```
   php check_permissions.php
   ```

2. **Verifica proprietario file:**
   ```bash
   ls -la config.php uploads/
   whoami
   ```

3. **Cambia proprietario se necessario:**
   ```bash
   # Per server web standard
   sudo chown -R www-data:www-data .
   
   # Per utente specifico
   sudo chown -R $(whoami):$(whoami) .
   ```

4. **Test finale:**
   ```bash
   touch test.txt && rm test.txt
   ```

## 📞 Contatti

Per problemi persistenti, fornisci:
- Output di `check_permissions.php`
- Sistema operativo e web server
- Messaggio di errore completo
- Risultato di `ls -la` nella directory del progetto