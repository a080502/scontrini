# 🧾 Sistema Gestione Scontrini Fiscali

**Versione 2.1.0** - Applicazione web professionale per la gestione completa degli scontrini fiscali con sistema di installazione automatica.

## ✨ Caratteristiche Principali

- **🚀 Installazione Automatica**: Processo guidato in 5 step per deployment immediato
- **📊 Dashboard Completa**: Statistiche in tempo reale e riepilogo finanziario
- **🔄 Gestione Stati**: da incassare → incassato → versato → archiviato
- **👥 Multi-Utente**: Sistema di autenticazione con ruoli amministratore/utente
- **🏢 Gestione Filiali**: Organizzazione per sedi aziendali
- **📸 Foto Scontrini**: Upload e gestione immagini con geolocalizzazione GPS
- **📱 Mobile Ready**: Interfaccia responsive ottimizzata per tutti i dispositivi
- **🗂️ Archivio Intelligente**: Organizzazione automatica scontrini completati
- **⚡ Performance**: Query ottimizzate e cache intelligente

### 🆕 Novità v2.1.0 - Sistema di Installazione Automatica

- **🔧 Installer Web**: Processo guidato con interfaccia Bootstrap 5
- **⚙️ Installer CLI**: Automatizzazione completa da linea di comando  
- **🔍 Verifica Requisiti**: Controllo automatico dipendenze di sistema
- **🗄️ Setup Database**: Configurazione automatica MySQL e schema
- **👤 Primo Utente**: Creazione guidata account amministratore
- **📊 Dati di Esempio**: 3 filiali e 100 scontrini per test immediato
- **🔒 Protezione**: File di lock anti-reinstallazione

## 🚀 Installazione Rapida

### Opzione A: Installazione Web (Raccomandato)

1. **Estrai** i file nella directory del server web
2. **Apri** il browser e vai al sistema
3. **Clicca** "Avvia Installazione Sistema"  
4. **Segui** il processo guidato in 5 step

### Opzione B: Installazione CLI

```bash
php install/cli_installer.php
```

### Opzione C: Installazione Manuale XAMPP

1. **Scarica** [XAMPP](https://www.apachefriends.org/download.html)
2. **Avvia** Apache e MySQL
3. **Copia** i file in `htdocs/scontrini/`
4. **Vai** a `http://localhost/scontrini/`
5. **Segui** l'installazione automatica

## 🔧 Requisiti di Sistema

### Minimi
- **PHP**: >= 7.4 
- **MySQL**: >= 5.7 o MariaDB >= 10.2
- **Estensioni PHP**: PDO, PDO_MySQL, GD, mbstring
- **Spazio**: 100MB per installazione base
- **Browser**: Chrome, Firefox, Safari, Edge moderni

### Raccomandati  
- **PHP**: >= 8.0
- **MySQL**: >= 8.0
- **RAM**: 512MB per il processo PHP
- **SSL/HTTPS**: Per ambiente di produzione

## 📱 Funzionalità Avanzate

### 🔄 Gestione Completa Workflow
```
Nuovo Scontrino → Da Incassare → Incassato → Versato → Archiviato
                     ↓              ↓         ↓         ↓
                  Dashboard      Controllo  Versamento Archivio
```

### 📊 Dashboard Intelligente
- **Statistiche Real-time**: Totali per stato, periodo, filiale
- **Grafici Dinamici**: Andamenti temporali e distribuzioni
- **Alert Automatici**: Notifiche per scadenze e anomalie
- **Quick Actions**: Azioni rapide sui dati più recenti

### 🏢 Gestione Multi-Filiale
- **Organizzazione Gerarchica**: Filiali, responsabili, utenti
- **Reporting Separato**: Statistiche per singola filiale
- **Permessi Granulari**: Accesso limitato per filiale
- **Consolidamento**: Vista unificata per amministratori

### 📸 Sistema Foto e GPS
- **Upload Intelligente**: Ridimensionamento automatico immagini
- **Geolocalizzazione**: Coordinate GPS automatiche su mobile
- **Nomenclatura Avanzata**: File con timestamp, utente e posizione
- **Sicurezza**: Controllo accessi e validazione formati

## 🛠️ Strumenti Inclusi

### 🧪 Test e Verifica
```bash
php install/test_installation.php    # Verifica installazione
php install/backup.php              # Backup pre-installazione
```

### 📚 Documentazione
- **`install/README.md`**: Guida utente installazione
- **`install/INSTALLATION_README.md`**: Documentazione tecnica
- **`RELEASE_NOTES_v2.1.0.md`**: Changelog dettagliato

## 🔒 Sicurezza

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
