# Sistema di Installazione Automatica

## Panoramica

Il sistema di installazione automatica consente di configurare facilmente il Sistema Gestione Scontrini su un nuovo server. Il processo è guidato e suddiviso in 5 step principali.

## Processo di Installazione

### Step 1: Verifica Requisiti
Il sistema verifica automaticamente che il server soddisfi tutti i requisiti necessari:

- **PHP Version >= 7.4**: Versione PHP supportata
- **PDO Extension**: Necessaria per la connessione al database
- **PDO MySQL Extension**: Driver MySQL per PDO
- **GD Extension**: Per la gestione delle immagini degli scontrini
- **mbstring Extension**: Per la gestione dei caratteri multi-byte
- **Directory uploads/ scrivibile**: Per salvare le foto degli scontrini
- **File config.php scrivibile**: Per la configurazione automatica

### Step 2: Configurazione Database
Configurazione della connessione al database MySQL:

- **Host Database**: Indirizzo del server MySQL (es. localhost)
- **Nome Database**: Nome del database da creare
- **Username**: Username per la connessione al database
- **Password**: Password per la connessione al database

Il sistema:
- Testa la connessione al database
- Crea automaticamente il database se non esiste
- Genera il file `config.php` con le credenziali
- Crea tutte le tabelle necessarie

### Step 3: Dati di Esempio (Opzionale)
Possibilità di installare dati di esempio per testare il sistema:

- **3 filiali di esempio** con nomi, indirizzi e numeri di telefono
- **100 scontrini** distribuiti nell'ultimo anno
- **Importi casuali** tra 10€ e 500€
- **Date casuali** nell'ultimo anno
- **Alcuni scontrini** con importi da versare

### Step 4: Utente Amministratore
Creazione dell'account amministratore per accedere al sistema:

- **Nome e Cognome**: Dati personali dell'amministratore
- **Username**: Nome utente per l'accesso (deve essere univoco)
- **Email**: Indirizzo email (opzionale)
- **Password**: Password sicura (minimo 8 caratteri)

### Step 5: Finalizzazione
Completamento dell'installazione:

- Creazione del file `installation.lock` per impedire nuove installazioni
- Riepilogo delle operazioni effettuate
- Reindirizzamento alla pagina di login

## File Generati Durante l'Installazione

### `config.php`
File di configurazione principale con:
- Parametri di connessione al database
- Chiave segreta per le sessioni (generata automaticamente)
- Impostazioni dell'applicazione

### `installation.lock`
File di blocco che contiene:
```json
{
    "installed_at": "2025-01-15 10:30:45",
    "version": "2.0.0",
    "installer_ip": "192.168.1.100"
}
```

## Schema Database

Il sistema crea automaticamente le seguenti tabelle:

### `filiali`
- Gestione delle filiali aziendali
- Campi: id, nome, indirizzo, telefono, responsabile_id, attiva

### `utenti`
- Gestione degli utenti del sistema
- Campi: id, nome, cognome, username, password, email, ruolo, filiale_id, attivo

### `scontrini`
- Gestione degli scontrini fiscali
- Campi: id, numero, data, lordo, netto, da_versare, foto, gps_coords, filiale_id, utente_id, stato

### `log_attivita`
- Log delle attività degli utenti
- Campi: id, utente_id, azione, descrizione, scontrino_id, ip_address, created_at

### `sessioni`
- Gestione delle sessioni utente
- Campi: id, utente_id, ip_address, user_agent, data_scadenza

## Sicurezza

### Protezione dell'Installatore
- L'installatore è accessibile solo se il file `installation.lock` non esiste
- Dopo l'installazione, l'accesso è automaticamente bloccato
- Controllo dei permessi sui file e directory

### Generazione Credenziali
- Password hash con `password_hash()` e `PASSWORD_DEFAULT`
- Chiave segreta per sessioni generata con `random_bytes()`
- Validazione input utente con sanitizzazione

### Database
- Utilizzo di prepared statements per prevenire SQL injection
- Chiavi esterne per mantenere l'integrità referenziale
- Trigger automatici per il logging delle attività

## Troubleshooting

### Errori Comuni

#### "Errore connessione database"
- Verificare le credenziali del database
- Controllare che il server MySQL sia in esecuzione
- Verificare che l'utente abbia i permessi per creare database

#### "Directory uploads/ non scrivibile"
```bash
chmod 755 uploads/
chown www-data:www-data uploads/
```

#### "File config.php non scrivibile"
```bash
chmod 644 config.php
chown www-data:www-data config.php
```

#### "Estensione PHP mancante"
```bash
# Ubuntu/Debian
sudo apt-get install php-pdo php-mysql php-gd php-mbstring

# CentOS/RHEL
sudo yum install php-pdo php-mysql php-gd php-mbstring
```

### Reinstallazione
Per reinstallare il sistema:

1. Eliminare il file `installation.lock`
2. Eliminare o rinominare il file `config.php`
3. Accedere nuovamente alla pagina di login
4. Seguire il processo di installazione

## File di Supporto

### `install/database_schema.sql`
Schema completo del database con:
- Definizioni delle tabelle
- Indici per le performance
- Chiavi esterne per l'integrità
- Trigger per il logging automatico

### `install/config_template.php`
Template del file di configurazione usato durante l'installazione.

## Post-Installazione

Dopo l'installazione completata:

1. **Accedere al sistema** con le credenziali dell'amministratore
2. **Configurare le filiali** se non sono stati installati i dati di esempio
3. **Creare utenti aggiuntivi** se necessario
4. **Configurare i permessi** delle directory per la produzione
5. **Abilitare HTTPS** per la sicurezza
6. **Configurare backup automatici** del database

## Supporto

Per problemi durante l'installazione:
- Controllare i log di PHP per errori dettagliati
- Verificare i permessi dei file e directory
- Consultare la documentazione del server web e MySQL
- Contattare l'amministratore di sistema