# 🚀 Guida Installazione - Sistema Gestione Scontrini

## 📋 Requisiti di Sistema

### Server Requirements
- **PHP**: 7.4 o superiore
- **Database**: MySQL 5.7+ o MariaDB 10.2+
- **Web Server**: Apache 2.4+ o Nginx 1.18+
- **Spazio Disco**: Minimo 100MB

### Estensioni PHP Richieste
- `pdo_mysql` - Connessione database
- `gd` - Elaborazione immagini
- `mbstring` - Gestione stringhe multibyte
- `fileinfo` - Analisi file uploaded
- `json` - Gestione dati JSON

### Verifica Requisiti
```bash
php -m | grep -E "(pdo_mysql|gd|mbstring|fileinfo|json)"
```

## 🎯 Modalità di Installazione

### 🌐 Installazione Web (Consigliata)
Interfaccia grafica guidata con 5 step

### 💻 Installazione CLI  
Installer da terminale per ambienti server

---

## 🌐 INSTALLAZIONE WEB

### 1. Preparazione Files
```bash
# Download progetto
git clone [repository-url] sistema-scontrini
cd sistema-scontrini

# Imposta permessi
chmod 755 uploads/
chmod 755 uploads/scontrini/
```

### 2. Configurazione Web Server

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Sicurezza uploads
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location /uploads/ {
    location ~ \.php$ {
        deny all;
    }
}
```

### 3. Accesso Installer
1. Naviga su: `http://tuodominio.it/`
2. Il sistema rileva automaticamente se non è installato
3. Clicca **"Installa Sistema"**

### 4. Processo Guidato

#### Step 1: Verifica Requisiti
- Controllo automatico estensioni PHP
- Verifica permessi cartelle
- Test connettività database

#### Step 2: Configurazione Database
```
Host Database: localhost
Nome Database: scontrini_db
Username: tuo_username
Password: tua_password
```

#### Step 3: Creazione Database
- Creazione automatica tabelle
- Setup indici e vincoli
- Configurazione charset UTF-8

#### Step 4: Dati di Esempio (Opzionale)
- Filiale di esempio
- Utente test
- Scontrini dimostrativi

#### Step 5: Utente Amministratore
```
Nome: Admin
Username: admin
Password: [scegli password sicura]
Email: admin@esempio.it
```

### 5. Completamento
✅ Sistema installato e pronto all'uso!

---

## 💻 INSTALLAZIONE CLI

### 1. Avvio Installer
```bash
cd /percorso/progetto
php install/cli_installer.php
```

### 2. Configurazione Interattiva
L'installer chiederà:
- Host database (default: localhost)
- Nome database (default: scontrini_db)  
- Username database
- Password database
- Dati amministratore

### 3. Esempio Sessione
```
🚀 Installer CLI - Sistema Gestione Scontrini
============================================

Host database [localhost]: localhost
Nome database [scontrini_db]: mio_db
Username database: utente_db
Password database: password_sicura

🔍 Test connessione database...
✅ Connessione riuscita

📊 Creazione tabelle database...
✅ Tabelle create: 5

👤 Configurazione utente amministratore:
Nome: Administrator  
Username: admin
Password: [inserisci password]
Email: admin@miositio.it

✅ Installazione completata!
```

---

## ⚙️ CONFIGURAZIONE POST-INSTALLAZIONE

### 1. File di Configurazione
Il file `config.php` viene creato automaticamente:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'scontrini_db');
define('DB_USER', 'username');
define('DB_PASS', 'password');
// ... altre configurazioni
?>
```

### 2. Trigger Avanzati (Opzionali)
Per attivare il logging automatico:
```bash
mysql -u username -p database_name < install/triggers_optional.sql
```

### 3. Backup Automatico
```bash
# Setup backup giornaliero
sudo cp scripts/linux/setup_automatic_backup.sh /etc/cron.daily/backup-scontrini
sudo chmod +x /etc/cron.daily/backup-scontrini
```

### 4. SSL/HTTPS
**Fortemente consigliato** per proteggere login e dati sensibili:
```apache
# Apache SSL
<VirtualHost *:443>
    ServerName tuodominio.it
    DocumentRoot /var/www/sistema-scontrini
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
</VirtualHost>
```

---

## 🔧 TROUBLESHOOTING

### Errori Comuni

#### "could not find driver"
```bash
# Ubuntu/Debian
sudo apt install php-mysql

# CentOS/RHEL  
sudo yum install php-mysqlnd

# Riavvia web server
sudo systemctl restart apache2
```

#### "Access denied for user"
1. Verifica credenziali database
2. Controlla privilegi utente:
```sql
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

#### "Directory not writable"
```bash
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
```

#### "Installation already completed"
Per reinstallare:
```bash
rm installation.lock
# Poi accedi nuovamente al sistema
```

### Log degli Errori
```bash
# Controlla log PHP
tail -f /var/log/apache2/error.log

# Log database errori
tail -f /var/log/mysql/error.log
```

---

## 📱 CONFIGURAZIONE MOBILE

### Responsive Design
Il sistema è **automaticamente responsive** su tutti i dispositivi.

### PWA (Progressive Web App)
Per installazione come app mobile:
1. Apri sito su mobile
2. Browser mostrerà opzione "Aggiungi alla Home"
3. Sistema utilizzabile offline per consultazione

---

## 🔒 SICUREZZA

### Raccomandazioni Essenziali

1. **Password Sicura Admin**
   - Minimo 12 caratteri
   - Maiuscole, minuscole, numeri, simboli

2. **HTTPS Obbligatorio**
   - Certificato SSL valido
   - Redirect automatico HTTP→HTTPS

3. **Backup Regolari**
   - Automatici giornalieri
   - Test restore periodici

4. **Aggiornamenti**
   - Sistema operativo
   - PHP e estensioni
   - Database MySQL/MariaDB

5. **Firewall**
   - Porta 22 (SSH) solo IP fidati
   - Porta 3306 (MySQL) solo localhost
   - Rate limiting su login

---

## 📞 SUPPORTO

### File di Debug
In caso di problemi, genera informazioni debug:
```bash
php api/test-database.php
php api/test-bootstrap.php
```

### Documentazione Completa
- `README.md` - Panoramica generale
- `TROUBLESHOOTING.md` - Risoluzione problemi
- `install/TRIGGERS_README.md` - Trigger avanzati
- `SISTEMA_AUTORIZZAZIONI.md` - Gestione utenti

### Aggiornamenti Sistema
```bash
git pull origin main
php migrate.php  # Se necessario
```

---

**🎉 Installazione completata! Il sistema è pronto per l'uso professionale.**

Per login iniziale:
- **URL**: `http://tuodominio.it/`
- **Username**: quello inserito durante installazione  
- **Password**: quella scelta durante installazione