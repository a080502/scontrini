# 🚀 Funzioni Skip - Installazione Automatica

Sono state aggiunte funzionalità di **skip** per velocizzare la procedura di installazione automatica del sistema.

## 📖 Panoramica

Le funzioni skip permettono di:
- Saltare step opzionali durante l'installazione web
- Usare configurazioni di default
- Installazione completamente automatica via CLI
- Ridurre i tempi di setup per ambienti di sviluppo/test

## 🌐 Installazione Web con Skip

### Step Saltabili:

#### 🔸 Step 3: Dati di Esempio
- **Pulsante:** "Salta questo step"
- **Effetto:** Installazione pulita senza dati di esempio
- **Quando usare:** Ambiente di produzione o installazione pulita

#### 🔸 Step 4: Utente Amministratore  
- **Pulsante:** "Crea amministratore di default"
- **Credenziali create:**
  - Username: `admin`
  - Password: `password123`
  - Nome: `Admin Sistema`
  - Email: `admin@sistema.local`
- **Quando usare:** Setup veloce per test o sviluppo

## 💻 Quick Installer CLI

Nuovo script per installazione rapida da linea di comando.

### 📍 Posizione
```bash
/install/quick_installer.php
```

### 🎯 Modalità Automatica
```bash
# Installazione completamente automatica
php install/quick_installer.php --auto

# Con opzioni specifiche
php install/quick_installer.php --auto --skip-sample --default-admin
```

### 🔧 Opzioni Disponibili

| Opzione | Alias | Descrizione |
|---------|-------|-------------|
| `--auto` | `-a` | Installazione automatica con valori di default |
| `--skip-sample` | `-s` | Salta l'installazione dei dati di esempio |
| `--default-admin` | `-d` | Crea amministratore di default (admin/password123) |
| `--help` | `-h` | Mostra aiuto e opzioni |

### 📊 Configurazioni di Default (--auto)

```php
Database:
- Host: localhost
- Nome: scontrini_db  
- User: root
- Password: (vuota)

Amministratore:
- Username: admin
- Password: password123
- Nome: Admin Sistema
- Email: admin@sistema.local
```

## 🎯 Esempi di Utilizzo

### Installazione Completa Automatica
```bash
# Setup completo per sviluppo
php install/quick_installer.php --auto

# Setup produzione (senza dati esempio)
php install/quick_installer.php --auto --skip-sample

# Setup test veloce
php install/quick_installer.php --auto --default-admin
```

### Installazione Interattiva con Skip
```bash
# Modalità guidata con opzioni predefinite
php install/quick_installer.php --default-admin --skip-sample
```

### Solo Help
```bash
php install/quick_installer.php --help
```

## 🔐 Sicurezza

### ⚠️ Password di Default
Quando si usa `--default-admin` o l'opzione skip nel web:
- **Username:** `admin`
- **Password:** `password123`

**🚨 IMPORTANTE:** Cambiare sempre la password dopo il primo accesso in produzione!

### 🧹 Cleanup Automatico
Il quick installer include:
- Rimozione automatica dei file in caso di errore
- Verifica delle connessioni prima di procedere
- Log dettagliato delle operazioni

## 📁 Struttura File

```
install/
├── quick_installer.php      # ⭐ Nuovo installer rapido
├── cli_installer.php        # Installer CLI originale  
├── database_schema.sql      # Schema database
└── ...altri file...

install.php                  # ✨ Aggiornato con funzioni skip
```

## 🚦 Stati di Installazione

Il file `installation.lock` ora include informazioni sui metodi usati:

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

## 🎯 Casi d'Uso

### 👨‍💻 Sviluppatore
```bash
# Setup veloce per sviluppo
php install/quick_installer.php --auto
```

### 🏢 Produzione
```bash
# Setup pulito senza dati di test
php install/quick_installer.php --auto --skip-sample
```

### 🧪 Testing
```bash
# Setup con credenziali note
php install/quick_installer.php --auto --default-admin
```

### 🔧 Demo/Presentazione
```bash
# Setup completo con dati di esempio
php install/quick_installer.php --auto
```

## 🆘 Troubleshooting

### Errore connessione database
```bash
# Verifica credenziali
mysql -u root -p -h localhost

# Test connessione
php install/debug_connection.php
```

### File già esistenti
```bash
# Rimuovi installazione precedente
rm installation.lock config.php

# Reinstalla
php install/quick_installer.php --auto
```

### Permessi
```bash
# Verifica permessi directory
ls -la uploads/
chmod 755 uploads/

# Verifica permessi script
chmod +x install/quick_installer.php
```

---

## 📈 Vantaggi delle Funzioni Skip

✅ **Velocità:** Installazione in secondi invece di minuti  
✅ **Automazione:** Integrabile in script di deployment  
✅ **Flessibilità:** Configurabile per diversi ambienti  
✅ **Sicurezza:** Cleanup automatico in caso di errore  
✅ **User-friendly:** Interfaccia web migliorata con opzioni chiare  

---

*Documentazione aggiornata - Sistema Gestione Scontrini v2.0.0*