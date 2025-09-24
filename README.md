# Gestione Scontrini Fiscali - Versione PHP

Applicazione web per la gestione degli scontrini fiscali convertita da Python Flask a PHP per compatibilità con server XAMPP.

## Caratteristiche

- **Dashboard completa** con statistiche e riepilogo finanziario
- **Gestione scontrini** con stati: da incassare, incassato, versato, archiviato
- **Raggruppamento per nome** - Gli scontrini nella lista sono raggruppati per nome con totali per ogni gruppo
- **Sistema di autenticazione** con gestione utenti
- **Archivio** per scontrini completati
- **Timeline attività** per monitorare le operazioni
- **Interfaccia responsive** identica al progetto originale
- **Autocomplete** per nomi scontrini frequenti

### Nuove Funzionalità (v2.1)

- **Lista scontrini raggruppata**: Gli scontrini sono ora organizzati per nome con:
  - Ordinamento alfabetico per nome
  - All'interno di ogni gruppo, ordinamento per data (più recenti primi)
  - Totali automatici per ogni gruppo (importo lordo, da versare, stato incassi/versamenti)
  - Design migliorato con header colorato per ogni gruppo
  - Visualizzazione ottimizzata per desktop e mobile

## Requisiti

- **XAMPP** (o Apache + MySQL + PHP)
- **PHP 7.4+** con estensioni PDO e MySQL
- **MySQL 5.7+** o **MariaDB 10.2+**
- Browser moderno per l'interfaccia web

## Installazione su XAMPP

### 1. Preparazione

1. Scarica e installa [XAMPP](https://www.apachefriends.org/download.html)
2. Avvia i servizi **Apache** e **MySQL** dal pannello di controllo XAMPP
3. Copia tutti i file di questo progetto nella cartella `htdocs/scontrini/` di XAMPP

### 2. Risoluzione Problemi .htaccess

Se ricevi errori come `<Directory not allowed here`, segui questi passi:

**Opzione A - File .htaccess Semplificato:**
```bash
# Rinomina il file attuale
mv .htaccess .htaccess-backup
# Usa la versione semplificata
mv .htaccess-simple .htaccess
```

**Opzione B - Disabilita .htaccess temporaneamente:**
```bash
# Rinomina per disabilitare
mv .htaccess .htaccess-disabled
```

**Opzione C - Configurazione Apache:**
Nel file `httpd.conf` di XAMPP, assicurati che `AllowOverride All` sia abilitato per la directory htdocs.

### 3. Setup Database

1. Apri il browser e vai su: `http://localhost/scontrini/test.php` per verificare che tutto funzioni
2. Se il test è OK, vai su: `http://localhost/scontrini/setup.php`
3. **Step 1**: Configura la connessione al database
   - Host: `localhost`
   - Database: `scontrini_db` (verrà creato automaticamente)
   - Username: `root`
   - Password: (lascia vuoto se XAMPP è configurazione standard)

3. **Step 2**: Configura l'utente amministratore
   - Username: scegli un username sicuro
   - Password: scegli una password sicura
   - Nome: il tuo nome completo

4. **Step 3**: Installazione completata!

### 3. Primo Accesso

1. Vai su: `http://localhost/scontrini/`
2. Accedi con le credenziali amministratore configurate
3. Inizia ad aggiungere i tuoi scontrini!

## Struttura del Progetto

```
├── assets/
│   ├── css/
│   │   └── style.css          # Stili identici al progetto originale
│   └── js/
│       └── app.js             # JavaScript per autocomplete e funzionalità
├── includes/
│   ├── auth.php               # Sistema di autenticazione
│   ├── database.php           # Gestione database con PDO
│   ├── layout.php             # Layout base delle pagine
│   ├── utils.php              # Funzioni di utilità
│   └── bootstrap.php          # Caricamento dipendenze
├── api/
│   └── nomi-scontrini.php     # API per autocomplete
├── config.php                 # Configurazione applicazione
├── setup.php                  # Installer (elimina dopo setup)
├── login.php                  # Pagina di accesso
├── index.php                  # Dashboard principale
├── aggiungi.php               # Aggiunta scontrini
├── lista.php                  # Lista scontrini attivi
├── archivio.php               # Scontrini archiviati
├── attivita.php               # Timeline delle attività
├── modifica.php               # Modifica scontrino
├── incassa.php                # Incasso scontrino
├── versa.php                  # Versamento scontrino
├── archivia.php               # Archiviazione scontrino
├── riattiva.php               # Riattivazione da archivio
├── elimina.php                # Eliminazione scontrino
├── annulla_incasso.php        # Annullamento incasso
├── annulla_versamento.php     # Annullamento versamento
└── logout.php                 # Logout utente
```

## Interfaccia Utente

### Lista Scontrini Raggruppata

La pagina `lista.php` presenta una visualizzazione innovativa degli scontrini:

- **Raggruppamento per nome**: Tutti gli scontrini della stessa persona sono raggruppati insieme
- **Ordinamento intelligente**: 
  - Gruppi ordinati alfabeticamente per nome
  - All'interno di ogni gruppo, scontrini ordinati per data (più recenti primi)
- **Totali per gruppo**: Ogni sezione mostra:
  - Totale importo lordo del gruppo
  - Totale da versare del gruppo  
  - Statistiche incassi/versamenti (es. "3/5 incassati - 1/5 versati")
- **Design professionale**: Header colorato per ogni gruppo con gradiente blu
- **Responsive**: Ottimizzata per desktop, tablet e mobile

### Dashboard e Statistiche

La dashboard principale fornisce una panoramica completa con:
- Statistiche finanziarie in tempo reale
- Riepilogo per stato (da incassare, incassati, versati)
- Collegamenti rapidi alle funzioni principali

## Script di Automazione

Il progetto include script di automazione per semplificare installazione, backup e manutenzione:

```
scripts/
├── linux/                      # Script Bash per Linux/macOS
│   ├── install.sh               # Installazione automatica completa
│   ├── backup.sh                # Backup automatico database
│   ├── setup_automatic_backup.sh # Configura backup automatico serale
│   ├── maintenance.sh           # Manutenzione e ottimizzazione
│   ├── restore.sh               # Ripristino da backup
│   └── update.sh                # Aggiornamento progetto
└── windows/                     # Script per Windows
    ├── install.bat              # Installazione automatica completa  
    ├── backup.bat               # Backup automatico database (Batch)
    ├── backup.ps1               # Backup automatico database (PowerShell)
    ├── backup_powershell.bat    # Launcher per versione PowerShell
    ├── setup_automatic_backup.bat # Configura backup automatico serale
    ├── create_scheduled_backup.ps1 # Script PowerShell per task scheduler
    └── maintenance.bat          # Manutenzione e ottimizzazione
```

### Utilizzo degli Script

**Linux/macOS:**
```bash
# Rendere eseguibili (solo la prima volta)
chmod +x scripts/linux/*.sh

# Installazione automatica
./scripts/linux/install.sh

# Backup database
./scripts/linux/backup.sh

# Manutenzione
./scripts/linux/maintenance.sh
```

**Windows:**
```cmd
# Installazione automatica
scripts\windows\install.bat

# Backup database (RACCOMANDATO - PowerShell)
scripts\windows\backup_powershell.bat
# oppure direttamente:
powershell -ExecutionPolicy Bypass -File scripts\windows\backup.ps1

# Backup database (alternativa Batch)
scripts\windows\backup.bat

# Manutenzione
scripts\windows\maintenance.bat
```

**Raccomandazioni Windows:**
- 🟢 **PowerShell** (`backup_powershell.bat` o `backup.ps1`): **RACCOMANDATO** - più robusto e affidabile
- 🟡 **Batch** (`backup.bat`): Alternativa per sistemi senza PowerShell o con restrizioni

### Backup Automatico Serale

Per automatizzare il backup ogni sera:

**Linux/macOS:**
```bash
# Configura backup automatico (crontab)
./scripts/linux/setup_automatic_backup.sh
```

**Windows:**
```cmd
# Configura backup automatico (Task Scheduler)
# Esegui come Amministratore:
scripts\windows\setup_automatic_backup.bat
```

Entrambi gli script ti permetteranno di:
- ⏰ Scegliere l'orario del backup (default: 22:00)
- 🔄 Configurare l'esecuzione automatica giornaliera  
- ✅ Testare immediatamente il funzionamento
- 📋 Ottenere istruzioni per gestire i task automatici

I backup automatici verranno salvati nella stessa cartella dei backup manuali.

Per maggiori dettagli consulta: [scripts/README.md](scripts/README.md)

## Conversione da Flask

Questa versione PHP mantiene:
- **Identica interfaccia utente** con stessi colori e layout
- **Stesse funzionalità** del progetto originale Python
- **Stessa struttura database** (convertita da SQLite a MySQL)
- **Stesso flusso operativo** per gli utenti
- **Stessi controlli e validazioni**
