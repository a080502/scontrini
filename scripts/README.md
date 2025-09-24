# Scripts di Automazione - Progetto Scontrini

Questa cartella contiene tutti gli script di automazione per il progetto Scontrini, organizzati per piattaforma.

## Struttura

```
scripts/
├── linux/          # Script Bash per Linux/macOS
│   ├── backup.sh    # Script di backup del database
│   ├── install.sh   # Script di installazione iniziale
│   ├── maintenance.sh # Script di manutenzione
│   ├── restore.sh   # Script di ripristino backup
│   └── update.sh    # Script di aggiornamento
├── windows/         # Script Batch per Windows
│   ├── backup.bat   # Script di backup del database
│   ├── install.bat  # Script di installazione iniziale
│   └── maintenance.bat # Script di manutenzione
└── README.md        # Questo file
```

## Utilizzo Rapido

### Linux/macOS
```bash
# Navigare alla cartella del progetto
cd /percorso/al/progetto

# Eseguire uno script
./scripts/linux/backup.sh
./scripts/linux/install.sh
./scripts/linux/maintenance.sh
./scripts/linux/restore.sh
./scripts/linux/update.sh
```

### Windows
```cmd
# Navigare alla cartella del progetto
cd C:\percorso\al\progetto

# Eseguire uno script
scripts\windows\backup.bat
scripts\windows\install.bat
scripts\windows\maintenance.bat
```

## Funzionalità Principali

### Script di Installazione
- **install.sh/install.bat**: Installazione iniziale completa del progetto
- Creazione del database
- Configurazione iniziale
- Setup utente admin

### Script di Backup
- **backup.sh/backup.bat**: Backup automatico del database
- Backup incrementali e completi
- Compressione automatica
- Rotazione dei backup

### Script di Manutenzione
- **maintenance.sh/maintenance.bat**: Operazioni di manutenzione
- Pulizia log
- Ottimizzazione database
- Controllo integrità

### Script di Ripristino
- **restore.sh**: Ripristino da backup (solo Linux/macOS)
- Ripristino completo o selettivo
- Validazione backup
- Sicurezza nei ripristini

### Script di Aggiornamento
- **update.sh**: Aggiornamento del progetto (solo Linux/macOS)
- Pull dal repository
- Aggiornamento dipendenze
- Migrazione database se necessaria

## Permessi (Linux/macOS)

Per rendere eseguibili gli script su Linux/macOS:
```bash
chmod +x scripts/linux/*.sh
```

## Note di Sicurezza

- Gli script contengono percorsi sicuri
- Backup automatici prima delle operazioni critiche
- Validazione degli input utente
- Log dettagliati delle operazioni

## Supporto

Per problemi o domande sui script, consulta la documentazione principale del progetto o apri un issue nel repository.