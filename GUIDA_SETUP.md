# 🎯 Guida Setup Sistema Multi-Filiale

## Installazione Automatica Completa

Il sistema di setup è stato completamente aggiornato per installare automaticamente tutte le funzionalità del sistema multi-filiale.

## 🚀 Procedura di Installazione

### 1. **Preparazione**
```bash
# Clona il repository
git clone https://github.com/a080502/PROGETTO_PHP.git
cd PROGETTO_PHP

# Assicurati che MySQL sia attivo
# Su XAMPP: Avvia MySQL dal pannello di controllo
# Su Linux: sudo service mysql start
```

### 2. **Esegui Setup**
```
http://localhost/scontrini/setup.php
```
Oppure se usi server integrato PHP:
```bash
php -S localhost:8000
# Vai su http://localhost:8000/setup.php
```

### 3. **Step 1: Configurazione Database**
- **Host**: `localhost` (default)
- **Database**: `scontrini_db` (creato automaticamente)
- **Username**: `root` (tipico per XAMPP)
- **Password**: Lascia vuoto per XAMPP, o inserisci la tua password

### 4. **Step 2: Creazione Sistema Multi-Filiale**
- **Username Admin**: `admin_sede` (personalizzabile)
- **Password**: `admin123` (CAMBIALA per sicurezza!)
- **Nome**: Admin Sede Centrale

Il sistema creerà automaticamente:

## 🏢 **Filiali Create**
1. **Sede Centrale** (Milano) - Responsabile: Admin
2. **Filiale Nord** (Torino) - Responsabile: Mario Bianchi  
3. **Filiale Sud** (Napoli) - Responsabile: Anna Verdi

## 👥 **Utenti di Test**

### Amministratori
- `admin_sede` / `admin123` - Accesso completo a tutto

### Responsabili Filiale
- `resp_nord` / `admin123` - Solo Filiale Nord
- `resp_sud` / `admin123` - Solo Filiale Sud

### Utenti Standard  
- `user_nord1` / `admin123` - Solo i propri scontrini (Filiale Nord)
- `user_sud1` / `admin123` - Solo i propri scontrini (Filiale Sud)

## 🔐 **Livelli di Accesso Automatici**

### 🔴 **Amministratore** (`admin_sede`)
- ✅ Vede **tutti** gli scontrini di **tutte** le filiali
- ✅ Gestisce tutti gli utenti e filiali
- ✅ Crea/modifica/elimina tutto
- ✅ Accesso completo a tutte le funzioni

### 🔵 **Responsabile Filiale** (`resp_nord`, `resp_sud`)  
- ✅ Vede **solo** gli scontrini della **propria** filiale
- ✅ Gestisce solo utenti della propria filiale
- ✅ Modifica dati della propria filiale
- ❌ Non può accedere ad altre filiali

### 🟢 **Utente Standard** (`user_nord1`, `user_sud1`)
- ✅ Vede **solo** i **propri** scontrini
- ✅ Crea nuovi scontrini (associati automaticamente)
- ❌ Non può vedere scontrini di altri utenti
- ❌ Non può gestire utenti o filiali

## 📊 **Database Schema Completo**

### Tabelle Create Automaticamente:
- `filiali` - Gestione filiali con responsabili
- `utenti` - Utenti con ruoli e associazione filiali
- `scontrini` - Scontrini con associazione utente/filiale

### Foreign Key Configurate:
- `utenti.filiale_id` → `filiali.id`
- `filiali.responsabile_id` → `utenti.id`  
- `scontrini.utente_id` → `utenti.id`
- `scontrini.filiale_id` → `filiali.id`

## 🧪 **Test del Sistema**

Dopo l'installazione, testa con diversi utenti:

1. **Login come Admin** (`admin_sede`)
   - Vai su "Gestione → Filiali" - Dovresti vedere tutte le filiali
   - Vai su "Lista Scontrini" - Dovresti vedere tutti gli scontrini
   - Vai su "Gestione → Utenti" - Dovresti vedere tutti gli utenti

2. **Login come Responsabile** (`resp_nord`) 
   - Menu "Gestione" disponibile ma limitato
   - Lista scontrini mostra solo Filiale Nord
   - Utenti mostra solo utenti della Filiale Nord

3. **Login come Utente** (`user_nord1`)
   - Menu "Gestione" non disponibile
   - Lista scontrini mostra solo i propri scontrini
   - Può creare nuovi scontrini (associati automaticamente)

## ⚠️ **Sicurezza Post-Installazione**

1. **Elimina setup.php**
   ```bash
   rm setup.php
   ```

2. **Cambia le password di default**
   - Accedi come admin e vai su "Gestione → Utenti"
   - Modifica le password di tutti gli utenti di test

3. **Personalizza le filiali**
   - Modifica indirizzi e telefoni reali
   - Aggiungi nuove filiali se necessario
   - Assegna utenti reali alle filiali

## 🔄 **Migrazione da Versione Precedente**

Se hai già un'installazione esistente:

1. **Backup del database**
   ```bash
   mysqldump -u root scontrini_db > backup_pre_migrazione.sql
   ```

2. **Esegui script migrazione**
   ```bash
   mysql -u root scontrini_db < migrate_filiali.sql
   ```

3. **Verifica migrazione**
   ```bash
   php test_filiali.php
   ```

## 📞 **Supporto**

- **Repository**: https://github.com/a080502/PROGETTO_PHP
- **Documentazione**: Consulta `README_FILIALI.md`
- **Release Notes**: `RELEASE_NOTES_v2.0.0.md`

---

**🎉 Il tuo sistema multi-filiale è pronto all'uso!**

Ora puoi gestire scontrini fiscali con controllo completo degli accessi per filiali multiple.