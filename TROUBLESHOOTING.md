# Troubleshooting - Gestione Scontrini PHP

## Errori Comuni e Soluzioni

### 🚨 **Errore ".htaccess: Directory not allowed here"**

**Causa:** La direttiva `<Directory>` non è permessa nei file .htaccess su alcune configurazioni XAMPP.

**Soluzione:**
```bash
# Opzione 1: Usa il file .htaccess semplificato
mv .htaccess .htaccess-backup
mv .htaccess-simple .htaccess

# Opzione 2: Disabilita temporaneamente .htaccess
mv .htaccess .htaccess-disabled
```

### 🔐 **Errore "Access denied for user 'root'"**

**Causa:** Credenziali database errate.

**Soluzioni:**
- Verifica che **MySQL sia avviato** in XAMPP
- Usa **password vuota** per installazione XAMPP standard
- Controlla le credenziali in `config.php`

### 💻 **Errore "Class 'PDO' not found"**

**Causa:** Estensione PDO non abilitata in PHP.

**Soluzione:**
1. Apri `C:\xampp\php\php.ini`
2. Trova e decommenta:
   ```ini
   extension=pdo_mysql
   extension=pdo
   ```
3. Riavvia Apache dal pannello XAMPP

### ⚡ **Errore 500 Internal Server Error**

**Cause possibili e soluzioni:**

1. **Problema .htaccess:**
   ```bash
   mv .htaccess .htaccess-disabled
   ```

2. **Errori PHP nascosti:**
   - Abilita `display_errors = On` in php.ini
   - Controlla `C:\xampp\logs\error.log`

3. **Permessi cartelle:**
   - Assicurati che XAMPP abbia accesso in lettura/scrittura

### 📄 **Pagina bianca senza errori**

**Soluzioni:**
1. Abilita visualizzazione errori PHP:
   ```php
   // Aggiungi in cima a config.php per debug
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. Controlla i log:
   - Apache: `C:\xampp\logs\error.log`
   - PHP: `C:\xampp\logs\php_error_log`

### 🌐 **Errori di connessione database**

**Test connessione:**
1. Vai su `http://localhost/phpmyadmin`
2. Verifica che MySQL sia accessibile
3. Crea manualmente il database `scontrini_db`

### 📁 **Struttura cartelle XAMPP corretta:**

```
C:\xampp\htdocs\scontrini\
├── index.php
├── config.php
├── setup.php
├── .htaccess (o .htaccess-simple)
├── includes/
├── assets/
└── api/
```

## 🔧 **Comandi Utili per Debug**

### Test PHP:
```bash
# Da terminale in cartella scontrini
php -v                    # Verifica versione PHP
php -m | grep -i pdo     # Verifica PDO disponibile
php -l config.php        # Controlla sintassi file
```

### Test Apache:
```bash
# Testa configurazione .htaccess
apache2ctl configtest   # Su Linux
# Su Windows usa il pannello XAMPP per riavviare Apache
```

## 🆘 **Se Nulla Funziona**

### Installazione Minima:
1. **Disabilita .htaccess:** `mv .htaccess .htaccess-disabled`
2. **Accedi direttamente:** `http://localhost/scontrini/test.php`
3. **Setup manuale database:** Usa phpMyAdmin per creare tabelle
4. **Debug passo per passo:** Controlla ogni file individualmente

### Reset Completo:
```bash
# Backup dei dati se necessario
cp -r scontrini scontrini-backup

# Reinstallazione pulita
rm -rf scontrini
# Scarica di nuovo i file da GitHub
# Ricopia nella cartella htdocs
```

## 📞 **Ottieni Supporto**

Se i problemi persistono:
1. Controlla i **log di Apache** per errori specifici
2. Verifica la **versione PHP** (minimo 7.4)
3. Testa con **configurazione XAMPP standard**
4. Prova **disabilitando tutti i moduli extra** di Apache

## 🎯 **Configurazioni Testate**

✅ **XAMPP 8.2.x** - Windows 10/11  
✅ **XAMPP 8.1.x** - Windows 10/11  
✅ **XAMPP 7.4.x** - Windows 10  
✅ **LAMP Stack** - Ubuntu 20.04+  
✅ **MAMP** - macOS Monterey+  

### Note Specifiche per Versioni:
- **PHP 8.x**: Completamente compatibile
- **PHP 7.4+**: Compatibile con avvertimenti minori
- **MySQL 5.7+**: Raccomandato
- **MariaDB 10.x**: Alternativa compatibile