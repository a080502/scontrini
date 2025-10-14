# 🚀 Sistema di Installazione con Funzioni Skip

## 📋 Panoramica

Sistema di installazione automatica con **funzioni skip** per installazione rapida e flessibile del Sistema Gestione Scontrini.

## 🎯 Metodi di Installazione

### 🌐 1. Installazione Web (Consigliata)
**File:** `../install.php`
- Interfaccia guidata step-by-step
- ✨ **NUOVO:** Funzioni skip per saltare step opzionali
- Verifica automatica dei requisiti
- Gestione errori user-friendly

**Funzioni Skip:**
- Skip dati di esempio → Installazione pulita
- Skip utente admin → Crea admin di default (admin/password123)

### ⚡ 2. Quick Installer CLI (Nuovo!)
**File:** `quick_installer.php`
- **Installazione automatica in 10-30 secondi**
- Configurazioni predefinite per sviluppo/test
- Opzioni skip integrate

```bash
# Installazione completa automatica
php install/quick_installer.php --auto

# Con opzioni specifiche  
php install/quick_installer.php --auto --skip-sample --default-admin
```

### � 3. Installer CLI Completo
**File:** `cli_installer.php`
- Modalità interattiva completa
- Controllo granulare di ogni opzione

## 🚀 Quick Start (30 secondi)

```bash
# 1. Vai nella directory del progetto
cd /path/to/progetto

# 2. Installazione automatica completa
php install/quick_installer.php --auto

# 3. Accedi al sistema
# URL: http://localhost/progetto
# Username: admin
# Password: password123
```

## 📊 Opzioni Quick Installer

| Comando | Alias | Descrizione | Tempo |
|---------|-------|-------------|--------|
| `--auto` | `-a` | Installazione automatica completa | ~15 sec |
| `--skip-sample` | `-s` | Salta dati di esempio | ~10 sec |
| `--default-admin` | `-d` | Crea admin di default | ~15 sec |
| `--help` | `-h` | Mostra aiuto | - |

### Esempi Combinati
```bash
# Setup produzione (pulito, senza dati test)
php install/quick_installer.php --auto --skip-sample

# Setup sviluppo veloce
php install/quick_installer.php --auto --default-admin

# Setup demo completo
php install/quick_installer.php --auto
```

## � Configurazioni di Default

### Database (--auto)
```
Host: localhost
Nome: scontrini_db
User: root
Password: (vuota)
```

### Amministratore (--default-admin)
```
Username: admin
Password: password123
Nome: Admin Sistema
Email: admin@sistema.local
```

## 📁 File di Installazione

```
install/
├── quick_installer.php      # ⭐ Installer rapido (NUOVO)
├── cli_installer.php        # CLI completo originale
├── database_schema.sql      # Schema database
├── config_template.php      # Template configurazione
├── test_installation.php    # Test post-installazione
└── debug_connection.php     # Debug connessioni

../install.php              # ✨ Web installer con skip
```

## 🎯 Processo Guidato Web

Il sistema web ti guida attraverso 5 step con opzioni skip:

1. **🔍 Verifica Requisiti**: Controllo automatico dipendenze
2. **🗄️ Configurazione Database**: Credenziali MySQL
3. **📊 Dati di Esempio**: Opzionale + **Skip disponibile**
4. **👤 Utente Amministratore**: Manuale + **Skip con default**
5. **🎉 Finalizzazione**: Completamento sistema

## 🧪 Test Post-Installazione

```bash
# Test completo
php install/test_installation.php

# Debug connessione
php install/debug_connection.php

# Verifica file di lock
cat installation.lock
```

## 🆘 Risoluzione Problemi

### Reinstallazione
```bash
# Rimuovi installazione precedente
rm installation.lock config.php

# Reinstalla velocemente
php install/quick_installer.php --auto
```

### Errori Database
```bash
# Verifica MySQL
sudo service mysql status

# Test connessione manuale
mysql -u root -p -h localhost

# Debug automatico
php install/debug_connection.php
```

### Permessi
```bash
# Directory uploads
chmod 755 uploads/

# Script installer
chmod +x install/quick_installer.php
```

## ⚠️ Sicurezza

### 🚨 Password di Default
Se usi `--default-admin` o skip admin web:
- Username: `admin`
- Password: `password123`
- **CAMBIA la password dopo il primo accesso!**

### � File Protetti
Proteggi dopo l'installazione:
- `config.php` (credenziali database)
- `installation.lock` (info installazione)

## 📈 Performance e Confronti

| Metodo | Tempo | Controllo | Adatto per |
|--------|-------|-----------|------------|
| **Quick Auto** | 10-30 sec | Basso | Sviluppo/Test |
| **Web Skip** | 2-5 min | Medio | Produzione guidata |
| **CLI Completo** | 3-10 min | Alto | Setup personalizzato |

## 📊 Dati di Esempio (se installati)

- **3 filiali** complete
- **100 scontrini** ultimo anno
- **Importi**: 10€ - 500€
- **Date realistiche**
- **Esempi versamenti**

## 📝 Logging Avanzato

Il file `installation.lock` ora include:
```json
{
  "installed_at": "2025-10-12 15:30:00",
  "version": "2.0.0",
  "installer_type": "quick_command_line",
  "auto_install": true,
  "skip_sample": true,
  "default_admin": true
}
```

## � Documentazione Aggiuntiva

- 📖 [Funzioni Skip Dettagliate](../SKIP_INSTALLATION_FEATURES.md)
- 🛠️ [Guida Setup Completa](../GUIDA_SETUP.md)
- 🚨 [Troubleshooting](../TROUBLESHOOTING.md)
- 🔧 [Configurazione](../CONFIGURAZIONE.md)

---

**🎉 Installazione veloce e flessibile per ogni esigenza!**

*Sistema di Installazione v2.0.0 con Skip Features*