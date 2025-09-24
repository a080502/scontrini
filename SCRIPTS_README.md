# Script di Automazione - Gestione Scontrini PHP

Gli script di automazione sono ora organizzati in cartelle separate per piattaforma nella directory `scripts/`.

## 📁 Struttura Script

```
scripts/
├── linux/          # Script Bash per Linux/macOS  
│   ├── install.sh   # Installazione automatica del sistema
│   ├── backup.sh    # Backup completo di file e database
│   ├── maintenance.sh # Manutenzione e ottimizzazione
│   └── update.sh    # Aggiornamento del sistema
├── windows/         # Script Batch per Windows
│   ├── install.bat  # Installazione automatica del sistema
│   ├── backup.bat   # Backup completo di file e database
│   └── maintenance.bat # Manutenzione e ottimizzazione
└── README.md        # Guida agli script
```

## 🚀 Utilizzo Rapido

### Linux/macOS
```bash
# Navigare alla cartella del progetto
cd /percorso/al/progetto

# Rendere eseguibili (solo la prima volta)
chmod +x scripts/linux/*.sh

# Eseguire uno script
./scripts/linux/install.sh
./scripts/linux/backup.sh
./scripts/linux/maintenance.sh
./scripts/linux/update.sh
```

### Windows
```cmd
# Navigare alla cartella del progetto
cd C:\percorso\al\progetto

# Eseguire uno script (doppio click o da cmd)
scripts\windows\install.bat
scripts\windows\backup.bat
scripts\windows\maintenance.bat
```

## � Script Disponibili

### 🚀 `install.sh/install.bat`
**Installazione automatica del sistema**

**Funzionalità:**
- Rileva automaticamente il server web (XAMPP, MAMP, Apache, WAMP)
- Verifica prerequisiti PHP e estensioni
- Copia file nella cartella corretta
- Configura database MySQL
- Imposta permessi e .htaccess
- Test funzionamento

**Opzioni:**
1. **Automatica**: Installazione completa senza intervento
2. **Personalizzata**: Scegli cartella e configurazioni
3. **Solo file**: Copia solo i file senza configurazione
4. **Test sistema**: Verifica solo i prerequisiti

---

### 💾 `backup.sh/backup.bat`
**Backup completo di file e database**

**Cosa salva:**
- Tutti i file dell'applicazione (escluso .git)
- Dump completo database MySQL
- Struttura database separata
- Solo dati separati
- File di informazioni sul backup

**Output:** `~/scontrini_backup/scontrini_backup_YYYYMMDD_HHMMSS.tar.gz`

**Funzionalità extra:**
- Pulizia automatica backup vecchi (>30 giorni)
- Compressione ottimizzata
- Log dettagliati

---

### 🔄 `restore.sh`
**Ripristino da backup**

```bash
bash restore.sh
```

**Modalità:**
1. **Completo**: File + database
2. **Solo file**: Ripristina solo l'applicazione
3. **Solo database**: Ripristina solo i dati
4. **Info backup**: Mostra dettagli backup

**Funzionalità:**
- Selezione automatica backup disponibili
- Aggiornamento automatico config.php
- Verifica integrità backup
- Ripristino sicuro con conferme

---

### 🛠️ `maintenance.sh/maintenance.bat`
**Manutenzione e ottimizzazione sistema**

**Operazioni:**
1. **Check sistema**: Verifica PHP, estensioni, spazio disco
2. **Check database**: Connessione, integrità dati, statistiche
3. **Ottimizzazione**: OPTIMIZE TABLE, pulizia query cache
4. **Pulizia**: File temporanei, log rotazione
5. **Backup auto**: Backup automatico prima manutenzione
6. **Report**: Genera report completo sistema
7. **Aggiornamenti**: Controlla nuove versioni disponibili

**Log:** Tutte le operazioni vengono registrate in `maintenance.log`

---

### 🔄 `update.sh` (solo Linux/macOS)
**Aggiornamento sistema via Git**

**Modalità:**
1. **Automatico**: Aggiornamento senza domande
2. **Con review**: Mostra modifiche prima di applicare
3. **Solo check**: Verifica aggiornamenti disponibili
4. **Rollback**: Torna alla versione precedente

**Sicurezza:**
- Backup automatico pre-aggiornamento
- Gestione modifiche locali
- Migrazioni database automatiche
- Verifica post-aggiornamento
- Rollback in caso di problemi

---

## 🎯 Esempi d'Uso

### Prima installazione
```bash
# Download progetto
git clone https://github.com/a080502/PROGETTO_PHP
cd PROGETTO_PHP

# Installazione automatica
bash install.sh
# Scegli opzione 1 per installazione completa

# Vai su http://localhost/scontrini/setup.php
# Completa la configurazione guidata
```

### Backup giornaliero
```bash
# Backup manuale
bash backup.sh

# O aggiungi a crontab per backup automatico
0 2 * * * cd /path/to/scontrini && bash backup.sh
```

### Manutenzione settimanale
```bash
# Manutenzione completa
bash maintenance.sh
# Scegli opzione 8 per manutenzione completa

# Visualizza report
cat system_report.txt
```

### Aggiornamento mensile
```bash
# Controlla aggiornamenti
bash update.sh
# Scegli opzione 3 per solo controllo

# Aggiorna se disponibile
bash update.sh
# Scegli opzione 2 per review modifiche
```

### Ripristino d'emergenza
```bash
# Lista backup disponibili
bash restore.sh
# Scegli opzione 4 per info backup

# Ripristino completo
bash restore.sh
# Scegli opzione 1 e seleziona backup
```

## ⚙️ Configurazione

### Variabili Ambiente (Opzionali)

```bash
# Cartella backup personalizzata
export BACKUP_DIR="/custom/backup/path"

# Modalità debug
export DEBUG=1

# Skip conferme (per automazione)
export AUTO_CONFIRM=1
```

### Crontab per Automazione

```bash
# Modifica crontab
crontab -e

# Aggiungi righe:
# Backup giornaliero alle 2:00
0 2 * * * cd /var/www/html/scontrini && bash backup.sh

# Manutenzione settimanale domenica alle 3:00  
0 3 * * 0 cd /var/www/html/scontrini && bash maintenance.sh <<< "8"

# Check aggiornamenti ogni lunedì alle 9:00
0 9 * * 1 cd /var/www/html/scontrini && bash update.sh <<< "3"
```

## 🔧 Troubleshooting

### Script non eseguibile
```bash
chmod +x *.sh
```

### Errori di permessi
```bash
# Su Linux/Mac
sudo chown -R www-data:www-data /var/www/html/scontrini
sudo chmod -R 755 /var/www/html/scontrini
```

### Database non accessibile
```bash
# Verifica servizio MySQL
sudo service mysql status    # Linux
brew services list | grep mysql  # Mac

# Test connessione
mysql -h localhost -u root -p
```

### Backup troppo grandi
```bash
# Comprimi con maggiore efficienza
export GZIP=-9

# O escludi file specifici modificando backup.sh
```

## 📞 Supporto

Se hai problemi con gli script:

1. **Controlla i log**: `cat maintenance.log`
2. **Esegui test sistema**: `bash maintenance.sh` → opzione 1
3. **Verifica permessi**: `ls -la *.sh`
4. **Controlla configurazione**: `cat config.php`

Per segnalazioni bug o miglioramenti, apri un issue su GitHub.

---

## 🏆 Best Practices

1. **Backup regolari**: Usa `backup.sh` prima di modifiche importanti
2. **Manutenzione periodica**: Esegui `maintenance.sh` settimanalmente  
3. **Aggiornamenti sicuri**: Usa sempre `update.sh` con review
4. **Monitor system**: Controlla regolarmente `system_report.txt`
5. **Test restore**: Prova periodicamente il ripristino backup

Questi script rendono la gestione del sistema **completamente automatizzata** e **sicura**! 🚀