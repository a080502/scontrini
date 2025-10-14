# 🧾 Sistema Gestione Scontrini Fiscali

**Versione 2.1.0** - Applicazione web professionale per la gestione completa degli scontrini fiscali con sistema di installazione automatica, gestione multi-filiale e funzionalità avanzate.

## ✨ Caratteristiche Principali

### 🚀 Sistema di Gestione Completo
- **📊 Dashboard Intelligente**: Statistiche in tempo reale, grafici dinamici e riepilogo finanziario
- **🔄 Workflow Avanzato**: Gestione stati (da incassare → incassato → versato → archiviato)
- **🗂️ Archivio Automatico**: Organizzazione intelligente degli scontrini completati
- **📈 Report e Statistiche**: Analisi dettagliate per periodo, filiale e utente
- **⚡ Performance Ottimizzate**: Query indicizzate e cache per velocità massima

### 👥 Sistema Multi-Utente e Multi-Filiale
- **🏢 Gestione Filiali**: Organizzazione gerarchica per sedi aziendali
- **🔐 Tre Livelli di Autorizzazione**:
  - **Amministratore**: Accesso completo a tutto il sistema
  - **Responsabile Filiale**: Gestione completa della propria filiale
  - **Utente Standard**: Visualizzazione e gestione dei propri scontrini
- **👤 Gestione Utenti Avanzata**: Responsabili possono aggiungere scontrini per utenti della loro filiale
- **🔒 Sicurezza Granulare**: Controlli di accesso rigorosi basati sui ruoli

### 📸 Sistema Foto e Geolocalizzazione
- **📷 Upload Intelligente**: Supporto foto scontrini con ridimensionamento automatico
- **🌍 GPS Integrato**: Coordinate geografiche automatiche da dispositivi mobili
- **📍 Nomenclatura Avanzata**: Nomi file con username, timestamp e coordinate GPS
- **📱 Mobile Optimized**: Interfaccia dedicata per acquisizione foto da smartphone
- **🔐 Accesso Sicuro**: Visualizzazione protetta con controllo autorizzazioni

### 📊 Importazione/Esportazione Dati
- **📥 Import Excel Massivo**: Caricamento multiplo scontrini da file Excel
- **📄 Template Personalizzato**: Formato Excel standardizzato con 8 colonne
- **💰 Calcolo IVA Automatico**: Applicazione automatica IVA 22% sui prezzi netti
- **📅 Gestione Date**: Supporto date da file Excel (formato DD/MM/YYYY o YYYY-MM-DD)
- **💸 Supporto Sconti**: Gestione prezzi negativi per sconti e storni

### 🔍 Filtri e Ricerca Avanzati
- **🔎 Ricerca Multi-Criterio**: Filtri per nome scontrino, utente, filiale
- **📊 Filtri per Ruolo**: Opzioni di filtro adattate al livello di autorizzazione
- **⏱️ Filtri Temporali**: Ricerca per anno, mese e periodo
- **💾 Persistenza Filtri**: Mantenimento filtri durante la navigazione
- **🔄 Reset Intelligente**: Pulizia rapida di tutti i filtri applicati

### 📱 Mobile Ready
- **📲 Design Responsive**: Interfaccia ottimizzata per smartphone e tablet
- **📸 Fotocamera Integrata**: Acquisizione diretta foto da dispositivi mobili
- **🎯 Touch Optimized**: Interfaccia touch-friendly con feedback tattile
- **🌐 PWA Ready**: Installabile come app su dispositivi mobili

### 🆕 Novità v2.1.0 - Sistema di Installazione Automatica

- **🔧 Installer Web**: Processo guidato in 5 step con interfaccia Bootstrap 5
- **⚙️ Installer CLI**: Automatizzazione completa da linea di comando  
- **⚡ Quick Installer**: Installazione rapida con parametri `--auto`, `--skip-sample`, `--default-admin`
- **🔍 Verifica Requisiti**: Controllo automatico dipendenze di sistema (PHP 7.4+, MySQL, estensioni)
- **🗄️ Setup Database**: Configurazione automatica MySQL, creazione schema e indici
- **👤 Primo Utente**: Creazione guidata account amministratore o default automatico
- **📊 Dati di Esempio**: 3 filiali e 100 scontrini realistici per test immediato (opzionale)
- **🔒 Protezione**: File di lock JSON anti-reinstallazione con logging completo
- **🧪 Test Post-Installazione**: Script automatici per verifica dell'installazione

### 🔧 Funzionalità Avanzate Dettagliate

#### 📊 Sistema Scontrini Dettagliati
- **📋 Righe Multiple**: Supporto dettagli scontrino con articoli multipli
- **🔢 Codici Articolo**: Gestione codici prodotto e descrizioni
- **💰 Calcoli Automatici**: Totali lordi e netti con IVA al 22%
- **📊 Riepilogo Intelligente**: Vista aggregata per scontrino

#### 🛠️ Strumenti di Manutenzione
- **🔧 Script Automazione**: Backup, manutenzione, aggiornamento (Linux/Windows)
- **📅 Backup Automatici**: Configurazione backup serali programmati
- **🔄 Sistema Update**: Aggiornamento sicuro via Git con rollback
- **🗄️ Restore Avanzato**: Ripristino completo o selettivo da backup
- **📊 Report Sistema**: Diagnostica completa stato applicazione

#### 🔐 Sicurezza e Validazione
- **🔒 Password Hash**: Algoritmi sicuri con `PASSWORD_DEFAULT`
- **🛡️ SQL Injection Prevention**: Prepared statements su tutte le query
- **✅ Validazione Input**: Sanitizzazione e controllo dati utente
- **🔑 Gestione Sessioni**: Chiavi generate con `random_bytes()`
- **📁 Protezione Upload**: Controllo MIME type e validazione file

## 🚀 Installazione Rapida

### Opzione A: Installazione Web (Raccomandato) 🌐

1. **Estrai** i file nella directory del server web
2. **Configura** permessi directory (755 per cartelle, 644 per file)
3. **Apri** il browser e vai al sistema (`http://localhost/scontrini/`)
4. **Clicca** "Avvia Installazione Sistema"  
5. **Segui** il processo guidato in 5 step:
   - 🔍 Verifica requisiti sistema
   - 🗄️ Configurazione database MySQL
   - 📊 Dati di esempio (opzionale - saltabile)
   - 👤 Creazione utente amministratore (o default)
   - ✅ Finalizzazione e test

### Opzione B: Installazione CLI Interattiva 💻

```bash
# Installazione guidata da terminale
php install/cli_installer.php

# Il sistema chiederà:
# - Credenziali database (host, nome, user, password)
# - Dati amministratore (username, password, nome, email)
# - Opzione dati di esempio
```

### Opzione C: Installazione Rapida Automatica ⚡

```bash
# Installazione completamente automatica con default
php install/quick_installer.php --auto

# Installazione automatica senza dati di esempio
php install/quick_installer.php --auto --skip-sample

# Installazione con admin di default (admin/password123)
php install/quick_installer.php --auto --default-admin

# Combinazione parametri
php install/quick_installer.php --auto --skip-sample --default-admin
```

**Configurazioni Default (--auto):**
- Database: `localhost`, `scontrini_db`, user `root`, password vuota
- Admin (--default-admin): username `admin`, password `password123`

### Opzione D: Installazione Manuale XAMPP 🔧

1. **Scarica** [XAMPP](https://www.apachefriends.org/download.html)
2. **Avvia** Apache e MySQL dal pannello di controllo
3. **Copia** i file in `C:\xampp\htdocs\scontrini\`
4. **Vai** a `http://localhost/scontrini/`
5. **Segui** l'installazione automatica web

## 🔧 Requisiti di Sistema

### Minimi Richiesti
- **PHP**: >= 7.4 
- **MySQL**: >= 5.7 o MariaDB >= 10.2
- **Estensioni PHP Obbligatorie**: 
  - PDO (Database access)
  - PDO_MySQL (MySQL driver)
  - GD (Image processing)
  - mbstring (Multibyte string)
  - JSON (Data handling)
- **Spazio Disco**: 100MB per installazione base
- **Browser**: Chrome, Firefox, Safari, Edge (versioni moderne)
- **Permessi**: Directory `uploads/` scrivibile (755 o 777)

### Raccomandati per Produzione 
- **PHP**: >= 8.0 (migliori performance)
- **MySQL**: >= 8.0 (ottimizzazioni query)
- **RAM**: 512MB minimi per processo PHP
- **SSL/HTTPS**: Certificato per ambiente produzione
- **Backup**: Storage esterno per backup automatici
- **Server**: Apache 2.4+ o Nginx 1.18+ con mod_rewrite abilitato

## 📱 Funzionalità Avanzate

### 🔄 Gestione Completa Workflow
```
Nuovo Scontrino → Da Incassare → Incassato → Versato → Archiviato
                     ↓              ↓         ↓         ↓
                  Dashboard      Controllo  Versamento Archivio
```

**Azioni Supportate:**
- ➕ Aggiungi nuovo scontrino (con dettagli multipli)
- ✏️ Modifica scontrino esistente
- 💰 Incassa scontrino
- 🏦 Versa importo incassato
- 📦 Archivia scontrino completato
- 🔄 Riattiva da archivio
- ❌ Annulla incasso/versamento
- 🗑️ Elimina scontrino

### 📊 Dashboard Intelligente
- **Statistiche Real-time**: 
  - Totali per stato (da incassare, incassati, versati)
  - Importi lordi e netti
  - Totali da versare
  - Contatori per filiale e utente
- **Grafici Dinamici**: Andamenti temporali e distribuzioni
- **Alert Automatici**: Notifiche per scadenze e anomalie
- **Quick Actions**: Accesso rapido alle funzioni più utilizzate
- **Ultimi Scontrini**: Lista aggiornata con azioni dirette
- **Filtri Dinamici**: Visualizzazione personalizzata per ruolo

### 🏢 Gestione Multi-Filiale Completa
- **Organizzazione Gerarchica**: 
  - Filiali con responsabili assegnati
  - Utenti associati a filiali specifiche
  - Scontrini automaticamente collegati
- **Reporting Separato**: 
  - Statistiche per singola filiale
  - Confronti tra filiali (solo admin)
  - Export dati per filiale
- **Permessi Granulari**: 
  - Admin: Tutte le filiali
  - Responsabile: Solo la propria filiale
  - Utente: Solo i propri scontrini
- **Gestione Filiali**:
  - Creazione/modifica filiali
  - Assegnazione responsabili
  - Gestione loghi personalizzati
  - Indirizzi e contatti

### 📸 Sistema Foto e GPS Avanzato
- **Upload Intelligente**: 
  - Formati supportati: JPG, JPEG, PNG, GIF, WebP
  - Ridimensionamento automatico (max 1920x1920px)
  - Compressione ottimizzata (JPEG 85%, PNG ottimizzato)
  - Dimensione massima: 5MB
- **Geolocalizzazione GPS**: 
  - Acquisizione automatica coordinate da mobile
  - Precisione in metri registrata
  - Timeout configurabile (10s desktop, 15s mobile)
  - Cache posizione (60s desktop, 30s mobile)
- **Nomenclatura File Avanzata**: 
  - Pattern: `scontrino_[ID]_user_[USER]_[DATETIME]_gps_[LAT]_[LNG]_acc_[ACC]m_[UNIQUE].ext`
  - Esempio: `scontrino_15_user_mario_2025-10-06_14-30-25_gps_45dot4642_9dot1899_acc_12m_67123abc.jpg`
  - Username sanitizzato (max 20 caratteri)
  - Timestamp completo (YYYY-MM-DD_HH-MM-SS)
  - Coordinate con "dot" invece di "."
- **Visualizzazione Sicura**: 
  - Accesso protetto con autenticazione
  - Miniature 50x50px in lista
  - Anteprima full-size on-click
  - Controllo permessi per visualizzazione
- **Mobile Optimized**:
  - Apertura diretta fotocamera posteriore
  - Anteprima immediata foto scattata
  - Feedback tattile (vibrazione)
  - Interfaccia touch-friendly

### 📊 Importazione/Esportazione Excel
- **Import Massivo**:
  - Caricamento multiplo scontrini da Excel
  - Template standardizzato 8 colonne
  - Validazione automatica dati
  - Report errori dettagliato
- **Formato Import**:
  ```
  Colonna A: Numero Ordine (obbligatorio)
  Colonna B: Nome Scontrino (opzionale - default "SCONTRINO SENZA NOME")
  Colonna C: Data (DD/MM/YYYY o YYYY-MM-DD - default oggi)
  Colonna D: Codice Articolo (opzionale)
  Colonna E: Descrizione (obbligatorio)
  Colonna F: Quantità (obbligatorio, > 0)
  Colonna G: Prezzo Unitario senza IVA (obbligatorio, può essere negativo)
  Colonna H: Prezzo Totale (calcolato automaticamente)
  ```
- **Calcolo Automatico IVA**:
  - Prezzi in Excel = prezzi NETTI (senza IVA)
  - Sistema applica automaticamente IVA 22%
  - Calcolo: `lordo = netto * 1.22`
  - `da_versare = importo_lordo`
- **Supporto Sconti/Storni**:
  - Prezzi negativi consentiti
  - Gestione sconti e rettifiche
  - Calcolo totali corretto
- **Template Excel**:
  - Download template predefinito
  - Esempi pre-compilati
  - API: `/api/excel-template.php`

### 🔍 Sistema Filtri Avanzati
- **Filtri Adattivi per Ruolo**:
  - **Utente**: Filtro per nome scontrino
  - **Responsabile**: Filtro nome + utenti della filiale
  - **Admin**: Filtro nome + utente + filiale
- **Pagine con Filtri**:
  - Lista scontrini attivi
  - Archivio scontrini
  - Dashboard principale
  - Timeline attività
- **Funzionalità Filtri**:
  - Ricerca testuale per nome
  - Selezione utente (dropdown)
  - Selezione filiale (dropdown)
  - Combinazione multipla filtri
  - Persistenza durante navigazione
  - Reset intelligente tutti filtri
- **Integrazione Temporale**:
  - Filtri anno/mese mantenuti
  - Combinazione con filtri avanzati
  - Query ottimizzate con indici

## 🛠️ Strumenti e Utilità Inclusi

### 🧪 Test e Verifica
```bash
# Verifica installazione completa
php install/test_installation.php

# Test connessione database
php api/test-database.php

# Test bootstrap e dipendenze
php api/test-bootstrap.php

# Debug foto scontrini
php debug_foto.php

# Verifica compatibilità PHP
php check_php_compatibility.php

# Controllo permessi file
php check_permissions.php
```

### 🔧 Strumenti di Manutenzione
```bash
# Allineamento schema database
php align_schema.php

# Controllo schema database
php check_schema.php

# Migrazione schema completa
php migrate_schema.php

# Fix trigger database
php fix_triggers.php

# Pulizia database
php clean_database.php

# Correzione permessi
bash fix_permissions.sh  # Linux/macOS
```

### 📁 Strumenti Foto
```bash
# Riparazione foto danneggiate
php repair_photos.php

# Correzione foto esistenti
php fix_photos.php

# Fix nomi file lunghi
php fix_long_filenames.php

# Rinomina file lunghi
php rename_long_files.php

# Debug visualizzazione foto
php debug_view_photo.php
```

### 📚 Documentazione Completa
- **`README.md`**: Panoramica generale e guida rapida
- **`install/README.md`**: Guida installazione dettagliata
- **`install/INSTALLATION_GUIDE.md`**: Guida tecnica completa
- **`SCRIPTS_README.md`**: Documentazione script automazione
- **`scripts/README.md`**: Dettaglio script per piattaforma
- **`SISTEMA_AUTORIZZAZIONI.md`**: Sistema permessi e ruoli
- **`GESTIONE_MULTI_UTENTE.md`**: Gestione utenti e filiali
- **`DOCUMENTAZIONE_FOTO_SCONTRINI.md`**: Sistema foto e upload
- **`GPS_FILENAME_IMPLEMENTATION.md`**: GPS e nomenclatura file
- **`IMPORT_EXCEL_UPDATES_v2.md`**: Importazione Excel
- **`FILTRI_AVANZATI_README.md`**: Sistema filtri avanzati
- **`MOBILE_FOTO_IMPLEMENTATION.md`**: Funzionalità mobile
- **`README_FILIALI.md`**: Gestione filiali
- **`PERMISSIONS_README.md`**: Gestione permessi
- **`TROUBLESHOOTING.md`**: Risoluzione problemi comuni
- **`RELEASE_NOTES_v2.1.0.md`**: Note di rilascio v2.1.0
- **`RELEASE_NOTES_v2.0.0.md`**: Note di rilascio v2.0.0

## 🔒 Sicurezza

### 🛡️ Misure di Sicurezza Implementate
- **Password Hashing**: Algoritmo `PASSWORD_DEFAULT` di PHP (bcrypt)
- **SQL Injection Prevention**: Prepared statements su tutte le query
- **XSS Protection**: Sanitizzazione output con `htmlspecialchars()`
- **CSRF Protection**: Token di sessione per form critici
- **Session Security**: 
  - Chiavi generate con `random_bytes()`
  - Timeout sessione configurabile
  - Rigenerazione ID sessione al login
- **File Upload Security**:
  - Validazione MIME type
  - Verifica immagini con `getimagesize()`
  - Protezione directory upload con `.htaccess`
  - Rinominazione file automatica
  - Limite dimensione 5MB
- **Access Control**:
  - Autenticazione richiesta su tutte le pagine
  - Controllo autorizzazioni basato su ruoli
  - Filtri query per isolamento dati
- **Database Security**:
  - Credenziali in file separato (non versionato)
  - Privilegi minimi necessari
  - Backup automatici cifrati

### 🔧 Configurazione Sicurezza

**Opzione A - File .htaccess (Apache):**
```apache
# Protezione directory uploads
<FilesMatch "\.(php|php3|php4|php5|phtml)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Prevenzione directory listing
Options -Indexes

# Protezione file sensibili
<Files "config.php">
    Order Deny,Allow
    Deny from all
</Files>
```

**Opzione B - Disabilita .htaccess temporaneamente:**
```bash
# Rinomina per disabilitare
mv .htaccess .htaccess-disabled
```

**Opzione C - Configurazione Apache (httpd.conf):**
```apache
<Directory "/path/to/htdocs">
    AllowOverride All
    Require all granted
</Directory>
```

### 🔐 Best Practices Raccomandate
1. **Cambia password default** dopo prima installazione
2. **Abilita HTTPS** in ambiente produzione
3. **Backup regolari** con script automatici
4. **Aggiorna regolarmente** PHP e MySQL
5. **Monitora log** per attività sospette
6. **Limita accessi** con firewall e IP whitelisting
7. **Verifica permessi** file e directory (755/644)
8. **Proteggi config.php** da accesso web diretto

## 📁 Struttura del Progetto

```
scontrini/
├── assets/                         # Risorse frontend
│   ├── css/
│   │   └── style.css              # Stili principali applicazione
│   └── js/
│       ├── app.js                 # JavaScript autocomplete e funzionalità
│       ├── mobile-detection.js    # Rilevamento dispositivi mobili
│       └── scontrino-dettagli.js  # Gestione dettagli scontrino
│
├── includes/                       # File core backend
│   ├── auth.php                   # Sistema autenticazione e autorizzazioni
│   ├── database.php               # Gestione database con PDO
│   ├── layout.php                 # Layout base pagine
│   ├── utils.php                  # Funzioni utilità e filtri avanzati
│   ├── bootstrap.php              # Caricamento dipendenze
│   ├── image_manager.php          # Gestione foto e upload
│   ├── scontrino_dettagli.php     # Gestione dettagli scontrino
│   ├── installation_check.php     # Controllo installazione
│   └── php_compatibility.php      # Verifica compatibilità PHP
│
├── api/                            # API e servizi
│   ├── nomi-scontrini.php         # API autocomplete nomi
│   ├── scontrino-dettagli.php     # API gestione dettagli
│   ├── excel-template.php         # Generazione template Excel
│   ├── import-excel-massivo.php   # Import massivo da Excel
│   ├── test-database.php          # Test connessione DB
│   ├── test-bootstrap.php         # Test bootstrap
│   └── debug-nomi-scontrini.php   # Debug autocomplete
│
├── install/                        # Sistema installazione
│   ├── README.md                  # Guida utente installazione
│   ├── INSTALLATION_GUIDE.md      # Guida tecnica completa
│   ├── cli_installer.php          # Installer CLI interattivo
│   ├── quick_installer.php        # Installer rapido con parametri
│   ├── database_schema.sql        # Schema database completo
│   ├── config_template.php        # Template configurazione
│   ├── test_installation.php      # Test post-installazione
│   ├── backup.php                 # Backup pre-installazione
│   ├── debug_connection.php       # Debug connessioni
│   └── triggers_optional.sql      # Trigger opzionali avanzati
│
├── scripts/                        # Script automazione
│   ├── linux/                     # Script Bash (Linux/macOS)
│   │   ├── install.sh             # Installazione automatica
│   │   ├── backup.sh              # Backup database
│   │   ├── maintenance.sh         # Manutenzione sistema
│   │   ├── restore.sh             # Ripristino da backup
│   │   ├── update.sh              # Aggiornamento sistema
│   │   └── setup_automatic_backup.sh # Setup backup automatici
│   ├── windows/                   # Script Windows
│   │   ├── install.bat            # Installazione automatica
│   │   ├── backup.bat             # Backup database (Batch)
│   │   ├── backup.ps1             # Backup database (PowerShell)
│   │   ├── backup_powershell.bat  # Launcher PowerShell
│   │   ├── maintenance.bat        # Manutenzione sistema
│   │   ├── setup_automatic_backup.bat # Setup backup automatici
│   │   └── create_scheduled_backup.ps1 # Task scheduler PowerShell
│   └── README.md                  # Documentazione script
│
├── uploads/                        # Directory upload file
│   ├── scontrini/                 # Foto scontrini
│   │   └── YYYY/MM/               # Organizzazione anno/mese
│   ├── loghi/                     # Loghi filiali
│   └── .htaccess                  # Protezione accesso diretto
│
├── config.php                      # Configurazione (NON versionato)
├── config.example.php              # Template configurazione
├── install.php                     # Installer web principale
├── installation.lock               # Lock anti-reinstallazione
│
├── login.php                       # Pagina login
├── logout.php                      # Logout utente
├── index.php                       # Dashboard principale
│
├── aggiungi.php                    # Aggiunta scontrino (desktop)
├── aggiungi-mobile.php             # Aggiunta scontrino (mobile)
├── modifica.php                    # Modifica scontrino
├── lista.php                       # Lista scontrini attivi
├── archivio.php                    # Scontrini archiviati
├── attivita.php                    # Timeline attività
│
├── incassa.php                     # Incasso scontrino
├── versa.php                       # Versamento scontrino
├── archivia.php                    # Archiviazione scontrino
├── riattiva.php                    # Riattivazione da archivio
├── elimina.php                     # Eliminazione scontrino
├── annulla_incasso.php             # Annullamento incasso
├── annulla_versamento.php          # Annullamento versamento
│
├── filiali.php                     # Gestione filiali
├── gestione_loghi.php              # Upload loghi filiali
├── utenti.php                      # Gestione utenti
├── aggiungi_utente.php             # Creazione nuovo utente
├── modifica_utente.php             # Modifica utente esistente
│
├── import-excel.php                # Interfaccia import Excel
├── view_photo.php                  # Visualizzazione sicura foto
│
├── migrate*.php                    # Script migrazione database
├── migrate*.sql                    # SQL migrazione database
├── setup.php                       # Setup manuale (legacy)
├── test.php                        # Test base sistema
│
├── .htaccess                       # Configurazione Apache
├── .htaccess-simple                # Configurazione semplificata
├── .gitignore                      # File esclusi da Git
├── vhost-example.conf              # Esempio virtual host
│
└── *.md                            # Documentazione varia
    ├── README.md                   # Questo file
    ├── SCRIPTS_README.md           # Doc script automazione
    ├── SISTEMA_AUTORIZZAZIONI.md   # Doc autorizzazioni
    ├── GESTIONE_MULTI_UTENTE.md    # Doc multi-utente
    ├── DOCUMENTAZIONE_FOTO_SCONTRINI.md # Doc foto
    ├── GPS_FILENAME_IMPLEMENTATION.md # Doc GPS
    ├── IMPORT_EXCEL_UPDATES_v2.md  # Doc import Excel
    ├── FILTRI_AVANZATI_README.md   # Doc filtri avanzati
    ├── README_FILIALI.md           # Doc filiali
    ├── PERMISSIONS_README.md       # Doc permessi
    ├── TROUBLESHOOTING.md          # Risoluzione problemi
    └── RELEASE_NOTES_*.md          # Note rilascio versioni
```

## 🎨 Interfaccia Utente

### 📋 Lista Scontrini Raggruppata

La pagina `lista.php` presenta una visualizzazione innovativa degli scontrini:

- **Raggruppamento per nome**: Tutti gli scontrini della stessa persona raggruppati insieme
- **Ordinamento intelligente**: 
  - Gruppi ordinati alfabeticamente per nome
  - All'interno di ogni gruppo, scontrini ordinati per data (più recenti primi)
- **Totali per gruppo**: Ogni sezione mostra:
  - Totale importo lordo del gruppo
  - Totale da versare del gruppo  
  - Statistiche incassi/versamenti (es. "3/5 incassati - 1/5 versati")
- **Design professionale**: Header colorato per ogni gruppo con gradiente blu
- **Responsive**: Ottimizzata per desktop, tablet e mobile
- **Azioni Rapide**: Bottoni per incasso, versamento, modifica, archiviazione
- **Miniature Foto**: Anteprima 50x50px con link a visualizzazione completa
- **Indicatori Stato**: Badge colorati per stato scontrino (da incassare, incassato, versato)

### 📊 Dashboard e Statistiche

La dashboard principale (`index.php`) fornisce una panoramica completa:

- **Card Statistiche**:
  - Totali scontrini per stato
  - Importi lordi e netti aggregati
  - Totali da versare
  - Contatori filiali e utenti (admin)
- **Grafici Interattivi**:
  - Andamento temporale incassi
  - Distribuzione per filiale
  - Trend mensili e annuali
- **Ultimi Scontrini**:
  - Lista aggiornata in tempo reale
  - Quick actions per operazioni comuni
  - Filtri dinamici per ruolo utente
- **Collegamenti Rapidi**:
  - Accesso diretto a funzioni principali
  - Contatori live aggiornati
  - Navigazione ottimizzata

### 🗂️ Archivio e Timeline

- **Archivio (`archivio.php`)**:
  - Scontrini completati organizzati per anno/mese
  - Statistiche archivio aggregate
  - Possibilità di riattivazione
  - Ricerca e filtri avanzati
  
- **Timeline Attività (`attivita.php`)**:
  - Cronologia completa ultimi 30 giorni
  - Eventi tracciati: aggiunte, modifiche, incassi, versamenti
  - Filtri per utente/filiale
  - Design timeline verticale responsive

### 📱 Versione Mobile

- **Interfaccia Ottimizzata**: Layout specifico per smartphone e tablet
- **Touch Gestures**: Controlli touch-friendly per azioni rapide
- **Fotocamera Integrata**: Acquisizione diretta foto scontrini
- **Geolocalizzazione**: GPS automatico per tracciamento posizione
- **Navigazione Semplificata**: Menu bottom-bar per accesso rapido
- **Form Ottimizzati**: Input specifici mobile (numeri, date, email)

## 🤖 Script di Automazione

Il progetto include un sistema completo di script di automazione per semplificare installazione, backup, manutenzione e aggiornamento del sistema.

### 📂 Struttura Script

```
scripts/
├── linux/          # Script Bash per Linux/macOS  
│   ├── install.sh           # Installazione automatica completa
│   ├── backup.sh            # Backup database e files
│   ├── maintenance.sh       # Manutenzione e ottimizzazione
│   ├── restore.sh           # Ripristino da backup
│   ├── update.sh            # Aggiornamento sistema via Git
│   └── setup_automatic_backup.sh # Configurazione backup automatici (cron)
│
└── windows/         # Script per Windows
    ├── install.bat              # Installazione automatica
    ├── backup.bat               # Backup database (Batch)
    ├── backup.ps1               # Backup database (PowerShell) ⭐ RACCOMANDATO
    ├── backup_powershell.bat    # Launcher PowerShell
    ├── maintenance.bat          # Manutenzione sistema
    ├── setup_automatic_backup.bat # Setup backup automatici (Task Scheduler)
    └── create_scheduled_backup.ps1 # PowerShell per Task Scheduler
```

### 🐧 Utilizzo Script Linux/macOS

```bash
# Rendere eseguibili (solo la prima volta)
chmod +x scripts/linux/*.sh

# 1️⃣ Installazione automatica completa
./scripts/linux/install.sh
# - Verifica requisiti sistema
# - Crea database e utente
# - Importa schema
# - Configura sistema

# 2️⃣ Backup database
./scripts/linux/backup.sh
# - Backup completo database
# - Backup incrementale files
# - Compressione automatica
# - Rotazione backup vecchi

# 3️⃣ Manutenzione periodica
./scripts/linux/maintenance.sh
# - Pulizia log vecchi
# - Ottimizzazione tabelle MySQL
# - Verifica integrità database
# - Report sistema

# 4️⃣ Ripristino da backup
./scripts/linux/restore.sh
# - Lista backup disponibili
# - Ripristino completo/selettivo
# - Validazione backup
# - Sicurezza ripristino

# 5️⃣ Aggiornamento sistema
./scripts/linux/update.sh
# Modalità interattiva con opzioni:
# - Automatico: aggiornamento senza domande
# - Con review: mostra modifiche prima di applicare
# - Solo check: verifica aggiornamenti disponibili
# - Rollback: torna alla versione precedente

# 6️⃣ Setup backup automatici serali
./scripts/linux/setup_automatic_backup.sh
# - Configura crontab per backup automatico
# - Scelta orario (default 22:00)
# - Test immediato funzionamento
```

### 🪟 Utilizzo Script Windows

**PowerShell (Raccomandato)** 🟢
```cmd
# Backup con PowerShell (PIÙ ROBUSTO E AFFIDABILE)
scripts\windows\backup_powershell.bat

# Oppure direttamente:
powershell -ExecutionPolicy Bypass -File scripts\windows\backup.ps1

# Setup backup automatici (esegui come Amministratore)
scripts\windows\setup_automatic_backup.bat
```

**Batch (Alternativa)** 🟡
```cmd
# 1️⃣ Installazione automatica
scripts\windows\install.bat

# 2️⃣ Backup database (alternativa Batch)
scripts\windows\backup.bat

# 3️⃣ Manutenzione sistema
scripts\windows\maintenance.bat
```

### ⏰ Backup Automatici Serali

**Linux/macOS (crontab):**
```bash
./scripts/linux/setup_automatic_backup.sh

# Il sistema configurerà automaticamente:
# - Crontab per esecuzione giornaliera
# - Orario personalizzabile (default 22:00)
# - Log di esecuzione
# - Notifiche errori
```

**Windows (Task Scheduler):**
```cmd
# Esegui come Amministratore
scripts\windows\setup_automatic_backup.bat

# Il sistema configurerà:
# - Task Scheduler Windows
# - Esecuzione giornaliera automatica
# - Orario personalizzabile (default 22:00)
# - Notifiche e log
```

### 🔄 Funzionalità Script Update (update.sh)

Lo script di aggiornamento offre 4 modalità operative:

1. **Aggiornamento Automatico**:
   - Pull automatico da Git
   - Backup pre-aggiornamento
   - Migrazione database automatica
   - Verifica post-aggiornamento

2. **Aggiornamento con Review**:
   - Mostra modifiche prima di applicare
   - Conferma utente richiesta
   - Possibilità di annullare

3. **Solo Controllo**:
   - Verifica aggiornamenti disponibili
   - Non applica modifiche
   - Info su nuove versioni

4. **Rollback**:
   - Torna alla versione precedente
   - Ripristino da backup automatico
   - Sicurezza in caso di problemi

### 🏆 Best Practices

1. **Backup Regolari**: Usa `backup.sh/bat` prima di modifiche importanti
2. **Manutenzione Periodica**: Esegui `maintenance.sh/bat` settimanalmente  
3. **Aggiornamenti Sicuri**: Usa sempre `update.sh` con review
4. **Monitor Sistema**: Controlla regolarmente log e report
5. **Test Restore**: Prova periodicamente il ripristino backup

### 📝 Log e Report

Tutti gli script generano log dettagliati:
- **Backup**: `backups/backup_[timestamp].log`
- **Manutenzione**: `logs/maintenance_[timestamp].log`
- **Aggiornamento**: `logs/update_[timestamp].log`
- **Report Sistema**: `system_report.txt`

Per documentazione completa: [scripts/README.md](scripts/README.md)

## 🚨 Risoluzione Problemi (Troubleshooting)

### ❌ Problemi di Installazione

**Errore: "Permission denied" durante creazione config.php**
```bash
# Soluzione 1: Correggi permessi manualmente
chmod 755 /path/to/project
touch config.php
chmod 666 config.php

# Soluzione 2: Usa lo strumento web di correzione
# Apri install.php e clicca "Correggi Permessi"

# Soluzione 3: Script automatico (Linux/macOS)
bash fix_permissions.sh
```

**Errore: "Directory uploads non scrivibile"**
```bash
# Crea directory e imposta permessi
mkdir -p uploads/scontrini
chmod 777 uploads/scontrini

# O usa il fix automatico dall'installer web
```

**Errore: "Estensioni PHP mancanti"**
```bash
# Ubuntu/Debian
sudo apt-get install php-gd php-mbstring php-mysql

# CentOS/RHEL
sudo yum install php-gd php-mbstring php-mysqlnd

# Verifica installazione
php -m | grep -E "gd|mbstring|pdo_mysql"
```

### 🗄️ Problemi Database

**Errore: "Access denied for user"**
```sql
-- Verifica credenziali in config.php
-- Crea utente MySQL se necessario:
CREATE USER 'scontrini_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON scontrini_db.* TO 'scontrini_user'@'localhost';
FLUSH PRIVILEGES;
```

**Errore: "Table doesn't exist"**
```bash
# Reinstalla schema database
mysql -u root -p scontrini_db < install/database_schema.sql

# O usa lo script di allineamento
php align_schema.php
```

**Errore: "Trigger già esistente"**
```bash
# Fix trigger duplicati
php fix_triggers.php

# O manualmente
mysql -u root -p scontrini_db -e "DROP TRIGGER IF EXISTS nome_trigger"
```

### 📸 Problemi Foto

**Foto non vengono visualizzate**
```bash
# 1. Verifica permessi directory
chmod -R 755 uploads/scontrini/

# 2. Verifica .htaccess in uploads/
cat uploads/.htaccess

# 3. Test visualizzazione
php debug_foto.php
```

**Errore upload foto**
```bash
# Verifica dimensione massima upload PHP
php -i | grep upload_max_filesize

# Aumenta limite in php.ini:
upload_max_filesize = 10M
post_max_size = 10M
```

**Nomi file troppo lunghi**
```bash
# Usa lo script di fix automatico
php fix_long_filenames.php

# O rinomina manualmente
php rename_long_files.php
```

### 🔐 Problemi Autenticazione

**Non riesco ad accedere con admin**
```bash
# Reset password admin via database
mysql -u root -p scontrini_db

# In MySQL:
UPDATE utenti 
SET password_hash = '$2y$10$YPKvFl.G3zMpLLq5E/1AWOxKzN7yCQKIg8qI0FZCjqKqHiAJcZPha' 
WHERE username = 'admin';
# Password diventa: password123

# O usa lo script di reset password
php reset_admin_password.php
```

**Sessione scade subito**
```php
// Verifica in php.ini:
session.gc_maxlifetime = 1440
session.cookie_lifetime = 0

// O in config.php:
ini_set('session.gc_maxlifetime', 3600);
```

### 🌐 Problemi Server Web

**Errore 500 Internal Server Error**
```bash
# 1. Verifica log Apache
tail -f /var/log/apache2/error.log

# 2. Verifica log PHP
tail -f /var/log/php/error.log

# 3. Disabilita .htaccess temporaneamente
mv .htaccess .htaccess-backup
```

**Errore 404 Not Found**
```apache
# Verifica mod_rewrite abilitato (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2

# Verifica AllowOverride in Apache config
<Directory "/var/www/html">
    AllowOverride All
</Directory>
```

**URL non funzionano (pagine bianche)**
```bash
# Verifica bootstrap
php api/test-bootstrap.php

# Verifica database
php api/test-database.php

# Controlla errori PHP
php -l index.php
```

### 📦 Problemi Import Excel

**Import Excel fallisce**
```bash
# Verifica formato file:
# - 8 colonne obbligatorie
# - Prima riga = intestazioni
# - Date formato DD/MM/YYYY o YYYY-MM-DD

# Usa template ufficiale
# Scarica da: /api/excel-template.php
```

**Errori calcolo IVA**
```php
// Verifica che i prezzi nel file Excel siano NETTI
// Il sistema applica automaticamente IVA 22%
// Formula: lordo = netto * 1.22
```

### 🔄 Problemi Aggiornamento

**Git pull fallisce**
```bash
# Salva modifiche locali
git stash

# Aggiorna
git pull origin main

# Ripristina modifiche
git stash pop

# O usa lo script update
./scripts/linux/update.sh
```

**Migrazioni database falliscono**
```bash
# Esegui migrazioni manualmente
php migrate.php

# O usa script specifici
php migrate_schema.php
php migrate_filiali.php
```

### 🧰 Strumenti di Diagnostica

**Diagnostica completa sistema**
```bash
# Test installazione
php install/test_installation.php

# Verifica permessi
php check_permissions.php

# Controllo compatibilità PHP
php check_php_compatibility.php

# Test connessione database
php api/test-database.php

# Debug generale
php configurazione_server.php
```

**Generazione Report**
```bash
# Report sistema completo
./scripts/linux/maintenance.sh
# Genera: system_report.txt

# Log dettagliati in:
# - logs/error.log
# - logs/access.log
# - logs/maintenance.log
```

### 📚 Documentazione Aggiuntiva

Per problemi specifici consulta:
- **`TROUBLESHOOTING.md`**: Guida completa risoluzione problemi
- **`PERMISSIONS_README.md`**: Problemi permessi file
- **`install/INSTALLATION_GUIDE.md`**: Problemi installazione
- **`SISTEMA_AUTORIZZAZIONI.md`**: Problemi autorizzazioni

### 🆘 Supporto

Se il problema persiste:
1. Controlla i log di sistema (`logs/` directory)
2. Verifica i requisiti sistema
3. Consulta la documentazione specifica
4. Apri una issue su GitHub con:
   - Descrizione dettagliata problema
   - Log errori rilevanti
   - Versione PHP e MySQL
   - Sistema operativo

## 💻 Dettagli Tecnici

### 🏗️ Architettura Sistema

**Pattern MVC Semplificato**:
- **Model**: Gestione dati via PDO con prepared statements
- **View**: Template PHP con include per layout riutilizzabili  
- **Controller**: Logica business nei file di pagina

**Database**: MySQL/MariaDB
- Tabelle principali: `utenti`, `filiali`, `scontrini`, `scontrini_dettagli`, `attivita`
- Indici ottimizzati per performance
- Trigger per logging automatico (opzionali)
- Relazioni con chiavi esterne

**Sicurezza**:
- PDO con prepared statements (prevenzione SQL injection)
- Password hashing con `password_hash()` e `PASSWORD_DEFAULT`
- Sanitizzazione input con `htmlspecialchars()` e `filter_var()`
- Validazione lato server e client
- Controllo sessioni con chiavi sicure

### 🔄 Conversione da Flask (Python)

Questa versione PHP è una conversione completa del progetto originale Python/Flask, mantenendo:

**✅ Funzionalità Identiche**:
- Stesso workflow gestione scontrini
- Stessi stati e transizioni
- Stesse validazioni e controlli
- Stesso sistema autorizzazioni

**✅ Interfaccia Identica**:
- Stessi colori e layout
- Stesso design Bootstrap
- Stessi form e interazioni
- Stessa esperienza utente

**✅ Database Equivalente**:
- Schema convertito da SQLite a MySQL
- Stesse tabelle e relazioni
- Stessi indici e vincoli
- Migliorate performance con ottimizzazioni MySQL

**🆕 Miglioramenti Aggiunti**:
- Sistema installazione automatica
- Script automazione backup/manutenzione
- Supporto foto con GPS
- Import/Export Excel
- Filtri avanzati
- Gestione dettagli scontrino
- Versione mobile ottimizzata
- Sistema multi-filiale completo

**🔧 Stack Tecnologico**:

**Prima (Flask/Python)**:
- Python 3.x
- Flask framework
- SQLite database
- Jinja2 templates

**Ora (PHP)**:
- PHP 7.4+ / 8.0+
- MySQL 5.7+ / 8.0+
- Bootstrap 5
- Vanilla JavaScript

### 📊 Performance e Scalabilità

**Ottimizzazioni Database**:
- Indici su colonne frequentemente interrogate
- Query ottimizzate con JOIN selettivi
- Paginazione su liste grandi
- Cache query ripetitive

**Ottimizzazioni Frontend**:
- CSS/JS minimizzati in produzione
- Lazy loading immagini
- Compressione foto automatica
- Cache browser abilitata

**Scalabilità**:
- Supporto migliaia di scontrini
- Gestione centinaia di utenti
- Multi-filiale illimitato
- Backup incrementali per grandi volumi

### 🔌 API e Integrazioni

**API Interne**:
- `/api/nomi-scontrini.php`: Autocomplete nomi
- `/api/scontrino-dettagli.php`: CRUD dettagli
- `/api/excel-template.php`: Template Excel
- `/api/import-excel-massivo.php`: Import dati

**Estensibilità**:
- Struttura modulare per nuove funzionalità
- Hook per integrazioni esterne
- Sistema plugin-ready
- API RESTful ready

## 📈 Roadmap Future

### 🎯 Funzionalità Pianificate

**v2.2.0 (Q1 2026)**:
- 📊 Dashboard avanzata con grafici Chart.js
- 📧 Notifiche email automatiche
- 📱 App mobile nativa (React Native)
- 🔔 Sistema notifiche push

**v2.3.0 (Q2 2026)**:
- 🤖 Integrazione AI per categorizzazione automatica
- 📄 Generazione PDF fatture
- 🔗 API REST completa per integrazioni
- 🌍 Supporto multi-lingua (i18n)

**v3.0.0 (Q3 2026)**:
- ☁️ Versione cloud-native
- 🔄 Sincronizzazione real-time
- 📊 Business Intelligence integrato
- 🔐 OAuth2 e SSO

## 🤝 Contribuire al Progetto

Contributi benvenuti! Per contribuire:

1. **Fork** il repository
2. **Crea** un branch per la feature (`git checkout -b feature/AmazingFeature`)
3. **Commit** le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. **Push** al branch (`git push origin feature/AmazingFeature`)
5. **Apri** una Pull Request

### 📋 Guidelines

- Segui lo stile di codifica esistente
- Aggiungi commenti per codice complesso
- Testa accuratamente le modifiche
- Aggiorna la documentazione se necessario
- Rispetta le best practices di sicurezza

## 📄 Licenza

Questo progetto è distribuito sotto licenza MIT. Vedi file `LICENSE` per dettagli.

## 👨‍💻 Autore

**a080502**
- GitHub: [@a080502](https://github.com/a080502)

## 🙏 Ringraziamenti

- Bootstrap team per il framework UI
- PHP community per le best practices
- Contributors e testers del progetto
- Utenti che forniscono feedback prezioso

---

**🎉 Sistema pronto per uso professionale!**

Per iniziare: `http://localhost/scontrini/` o apri l'installer web.

**Buon lavoro con il Sistema Gestione Scontrini! 🧾✨**
