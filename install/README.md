# 🚀 Installazione Automatica Sistema Gestione Scontrini

## 📋 Panoramica

Questo sistema fornisce un processo di installazione automatica completamente guidato che configura il Sistema Gestione Scontrini su qualsiasi server compatibile.

## 🎯 Funzionalità Principali

- ✅ **Verifica automatica dei requisiti** di sistema
- 🗄️ **Configurazione automatica del database** MySQL
- 👤 **Creazione guidata dell'utente amministratore**
- 📊 **Installazione opzionale di dati di esempio**
- 🔒 **Protezione contro reinstallazioni accidentali**
- 📝 **Logging completo del processo**

## 🛠️ Come Utilizzare

### Passo 1: Preparazione
1. Estrai tutti i file del progetto nella directory del server web
2. Assicurati che la directory `uploads/` sia scrivibile dal server web
3. Verifica che MySQL sia in esecuzione e accessibile

### Passo 2: Avvio Installazione
1. Apri il browser e vai alla pagina di login del sistema
2. Se l'installazione non è stata ancora effettuata, vedrai il pulsante **"Avvia Installazione Sistema"**
3. Clicca sul pulsante per iniziare il processo guidato

### Passo 3: Processo Guidato
Il sistema ti guiderà attraverso 5 step:

1. **🔍 Verifica Requisiti**: Controllo automatico delle dipendenze
2. **🗄️ Configurazione Database**: Inserimento credenziali MySQL
3. **📊 Dati di Esempio**: Scelta se installare dati demo (opzionale)
4. **👤 Utente Amministratore**: Creazione account admin
5. **🎉 Finalizzazione**: Completamento e attivazione sistema

## 📁 Struttura File di Installazione

```
install/
├── README.md                 # Questa documentazione
├── INSTALLATION_README.md    # Documentazione tecnica dettagliata
├── database_schema.sql       # Schema completo del database
├── config_template.php       # Template configurazione
├── test_installation.php     # Script di test post-installazione
└── backup.php               # Script di backup pre-installazione
```

## ⚙️ File Generati

Durante l'installazione vengono automaticamente generati:

- **`config.php`**: Configurazione principale del sistema
- **`installation.lock`**: File di protezione contro reinstallazioni

## 🧪 Test dell'Installazione

Dopo l'installazione, puoi verificare che tutto sia configurato correttamente:

```bash
php install/test_installation.php
```

Questo script verifica:
- Connessione al database
- Presenza di tutte le tabelle
- Configurazione corretta
- Permessi delle directory
- Estensioni PHP richieste

## 🔧 Risoluzione Problemi

### Errore "Directory uploads/ non scrivibile"
```bash
chmod 755 uploads/
chown www-data:www-data uploads/  # Su sistemi Apache
```

### Errore "Estensione PHP mancante"
```bash
# Ubuntu/Debian
sudo apt-get install php-pdo php-mysql php-gd php-mbstring

# CentOS/RHEL
sudo yum install php-pdo php-mysql php-gd php-mbstring
```

### Errore connessione database
1. Verifica che MySQL sia in esecuzione
2. Controlla le credenziali inserite
3. Assicurati che l'utente abbia i permessi per creare database

## 🔄 Reinstallazione

Per reinstallare il sistema:

1. **Backup** (opzionale): Esegui `php install/backup.php`
2. **Rimuovi il file di lock**: Elimina `installation.lock`
3. **Rimuovi configurazione**: Elimina o rinomina `config.php`
4. **Riavvia processo**: Torna alla pagina di login

## 🛡️ Sicurezza

- L'installatore è accessibile **solo se il sistema non è già installato**
- Dopo l'installazione, l'accesso è **automaticamente bloccato**
- Le password vengono **hashate** con algoritmi sicuri
- Vengono generate **chiavi segrete casuali** per le sessioni

## 📊 Dati di Esempio

Se scegli di installare i dati di esempio, otterrai:

- **3 filiali** con informazioni complete
- **100 scontrini** distribuiti nell'ultimo anno
- **Importi variabili** da 10€ a 500€
- **Date casuali** realistiche
- **Esempi di scontrini** con importi da versare

## 🆘 Supporto

Per assistenza:

1. **Consulta la documentazione**: `install/INSTALLATION_README.md`
2. **Esegui il test**: `php install/test_installation.php`
3. **Controlla i log**: Verifica i log di PHP e del server web
4. **Verifica i requisiti**: Assicurati che tutte le dipendenze siano soddisfatte

## 📈 Post-Installazione

Dopo l'installazione completata:

1. **Accedi** con le credenziali dell'amministratore appena create
2. **Configura le filiali** se necessario
3. **Crea utenti aggiuntivi** per il tuo team
4. **Configura backup automatici** del database
5. **Abilita HTTPS** per la sicurezza in produzione

---

**🎉 Buona gestione dei tuoi scontrini!**