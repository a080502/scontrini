# Sistema di Gestione Scontrini Fiscali con Filiali

## Panoramica
Sistema PHP per la gestione degli scontrini fiscali con supporto per **filiali multiple** e **livelli di autorizzazione gerarchici**. Il sistema permette di gestire utenti, scontrini e filiali con controlli di accesso basati sui ruoli.

## Caratteristiche Principali

### 🏢 Sistema Filiali
- Gestione di più filiali con informazioni complete
- Assegnazione automatica di utenti alle filiali
- Responsabili di filiale con controlli specifici

### 👥 Livelli Autorizzativi
- **Amministratore (Admin)**: Accesso completo a tutto il sistema
- **Responsabile Filiale**: Gestisce solo la propria filiale e i suoi utenti
- **Utente**: Vede e gestisce solo i propri scontrini

### 📊 Gestione Scontrini
- Associazione automatica degli scontrini all'utente e filiale
- Visualizzazione filtrata in base ai permessi
- Tracciamento completo di incassi e versamenti

## Installazione

### Requisiti
- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx con mod_rewrite

### Setup Database
1. Creare il database MySQL:
```sql
CREATE DATABASE scontrini_db;
```

2. Eseguire la migrazione per le filiali:
```bash
mysql -u root scontrini_db < migrate_filiali.sql
```

## Credenziali di Accesso Predefinite

### Amministratore
- **Username**: `admin_sede`
- **Password**: `secret` (hash predefinito)
- **Ruolo**: Amministratore
- **Accesso**: Tutte le filiali

### Responsabili Filiale
- **Username**: `resp_nord` | **Password**: `secret`
- **Username**: `resp_sud` | **Password**: `secret`
- **Ruolo**: Responsabile
- **Accesso**: Solo la propria filiale

### Utenti Standard
- **Username**: `user_nord1`, `user_nord2` | **Password**: `secret`
- **Username**: `user_sud1` | **Password**: `secret`
- **Ruolo**: Utente
- **Accesso**: Solo i propri scontrini

## Struttura del Sistema

### Controlli di Accesso

#### Amministratore
- ✅ Vede tutti gli scontrini di tutte le filiali
- ✅ Gestisce tutti gli utenti
- ✅ Crea e modifica filiali
- ✅ Assegna responsabili alle filiali

#### Responsabile Filiale
- ✅ Vede tutti gli scontrini della propria filiale
- ✅ Gestisce utenti della propria filiale
- ✅ Modifica informazioni della propria filiale
- ❌ Non può accedere ad altre filiali

#### Utente Standard
- ✅ Vede solo i propri scontrini
- ✅ Crea nuovi scontrini (associati automaticamente)
- ❌ Non può vedere scontrini di altri utenti
- ❌ Non può gestire utenti o filiali

### Pagine Principali

#### `/filiali.php`
- Gestione completa delle filiali
- Assegnazione responsabili
- Controllo stato attivo/inattivo

#### `/utenti.php`
- Gestione utenti con controlli di accesso
- Assegnazione a filiali
- Modifica ruoli (solo admin)

#### `/lista.php`
- Lista scontrini filtrata per permessi
- Visualizzazione informazioni utente/filiale
- Statistiche personalizzate per ruolo

## Struttura Database

### Tabelle Principali

#### `filiali`
```sql
CREATE TABLE filiali (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    indirizzo TEXT,
    telefono VARCHAR(20),
    responsabile_id INT,
    attiva TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `utenti` (modificata)
```sql
ALTER TABLE utenti 
MODIFY COLUMN ruolo ENUM('admin', 'responsabile', 'utente') DEFAULT 'utente',
ADD COLUMN filiale_id INT,
ADD FOREIGN KEY (filiale_id) REFERENCES filiali(id);
```

#### `scontrini` (modificata)
```sql
ALTER TABLE scontrini 
ADD COLUMN utente_id INT,
ADD COLUMN filiale_id INT,
ADD FOREIGN KEY (utente_id) REFERENCES utenti(id),
ADD FOREIGN KEY (filiale_id) REFERENCES filiali(id);
```

## Funzionalità Avanzate

### Filtri Automatici
- Gli scontrini vengono automaticamente filtrati in base al ruolo dell'utente
- Le statistiche si adattano ai permessi di visualizzazione
- Menu di navigazione dinamico per ruolo

### Associazioni Automatiche
- Nuovi scontrini vengono associati automaticamente all'utente che li crea
- Filiale di appartenenza derivata dall'utente
- Controlli di integrità referenziale

### Sicurezza
- Controlli di autorizzazione su ogni operazione
- Validazione dei permessi sia a livello di interfaccia che di database
- Sessioni con timeout automatico

---
*Sistema sviluppato con PHP, MySQL e Bootstrap per una gestione efficiente e sicura degli scontrini fiscali multi-filiale.*