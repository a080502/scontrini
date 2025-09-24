# 🔧 Configurazione Sistema Multi-Filiale

## 📁 File di Configurazione

### `config.example.php` (Template)
- File template presente su GitHub
- **NON contiene credenziali reali**
- Usato come riferimento per la configurazione

### `config.php` (File Reale)
- File con credenziali reali del database
- **Escluso da Git** per sicurezza
- Deve essere creato manualmente in ogni installazione

## ⚙️ Setup Configurazione

### 1. Prima Installazione
```bash
# 1. Clona il repository
git clone https://github.com/a080502/PROGETTO_PHP.git

# 2. Copia il file di configurazione
cp config.example.php config.php

# 3. Modifica config.php con le tue credenziali
nano config.php
```

### 2. Configurazione Database
Modifica `config.php` con i tuoi parametri:
```php
define('DB_HOST', 'localhost');        // Il tuo host MySQL
define('DB_NAME', 'gestione_scontrini'); // Nome del tuo database
define('DB_USER', 'root');              // Username MySQL
define('DB_PASS', 'password');          // Password MySQL
```

### 3. Esecuzione Setup
1. Apri il browser su `http://tuodominio/setup.php`
2. Segui i 3 passaggi guidati
3. Il sistema creerà automaticamente tutto il necessario

## 🔒 Sicurezza

### File Protetti da Git
- `config.php` - Credenziali database
- `*.log` - File di log
- `*.bak` - File di backup

### Best Practices
1. **Mai committare** credenziali reali
2. **Usa sempre** `config.example.php` come template
3. **Modifica solo** `config.php` per le credenziali vere
4. **Verifica** che `.gitignore` funzioni correttamente

## 🚨 Risoluzione Problemi

### "File config.php non trovato"
```bash
# Soluzione: Copia il template
cp config.example.php config.php
# Poi modifica con le tue credenziali
```

### "Errore connessione database"
1. Verifica credenziali in `config.php`
2. Controlla che MySQL sia avviato
3. Verifica che il database esista

### "Setup non funziona"
1. Controlla permessi file (755 per directory, 644 per file)
2. Verifica che `config.php` esista e sia configurato
3. Controlla i log PHP per errori dettagliati

## 🔄 Aggiornamenti

### Aggiornamento Codice
```bash
git pull origin main
# Il file config.php rimane intatto
```

### Migrazione Database
- Il sistema migra automaticamente al primo accesso
- Non serve eseguire script SQL manuali
- Tutto è gestito da `setup.php`

---

**💡 Suggerimento**: Mantieni sempre una copia di backup delle tue configurazioni importanti!