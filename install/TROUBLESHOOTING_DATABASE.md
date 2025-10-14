# 🚨 Troubleshooting Installazione - Problemi Database

## ❌ Errore: "Access denied for user"

### Sintomo
```
Errore connessione database: SQLSTATE[HY000] [1045] Access denied for user 'username'@'localhost' (using password: YES/NO)
```

### 🔍 Cause Comuni

1. **Credenziali Errate**: Username o password MySQL non corretti
2. **Utente Non Esistente**: L'utente MySQL non è stato creato
3. **Permessi Insufficienti**: L'utente non ha permessi per creare database
4. **Server Non Avviato**: MySQL non è in esecuzione

### 🛠️ Soluzioni

#### 1. Verifica Stato MySQL

**XAMPP:**
- Apri il pannello di controllo XAMPP
- Verifica che "MySQL" sia avviato (verde)
- Se non è avviato, clicca "Start"

**Linux:**
```bash
sudo systemctl status mysql
sudo systemctl start mysql  # se non è avviato
```

**Windows (non XAMPP):**
```cmd
net start mysql
```

#### 2. Test Credenziali Database

Usa lo script di debug incluso:
```bash
# Via browser
http://localhost/scontrini/install/debug_connection.php

# Via CLI
php install/debug_connection.php
```

#### 3. Credenziali XAMPP di Default

**XAMPP** usa tipicamente:
- **Host:** `localhost` o `127.0.0.1`
- **Username:** `root`
- **Password:** *(vuota)*

#### 4. Credenziali Altre Installazioni

**MAMP:**
- **Username:** `root`
- **Password:** `root`

**WAMP:**
- **Username:** `root`
- **Password:** *(vuota)* o `root`

**Installazione Manuale MySQL:**
- **Username:** Quello impostato durante l'installazione
- **Password:** Quella impostata durante l'installazione

#### 5. Creazione Utente MySQL (Se Necessario)

Accedi a MySQL come root:
```sql
mysql -h localhost -u root -p

-- Crea nuovo utente
CREATE USER 'scontrini_user'@'localhost' IDENTIFIED BY 'password_sicura';

-- Assegna permessi
GRANT ALL PRIVILEGES ON *.* TO 'scontrini_user'@'localhost';

-- Applica modifiche
FLUSH PRIVILEGES;
```

#### 6. Reset Password Root MySQL

**XAMPP:**
1. Ferma MySQL dal pannello XAMPP
2. Avvia MySQL con `--skip-grant-tables`
3. Accedi senza password: `mysql -u root`
4. Reset password:
   ```sql
   UPDATE mysql.user SET authentication_string=PASSWORD('nuova_password') WHERE User='root';
   FLUSH PRIVILEGES;
   ```

#### 7. Problemi di Host

Se `localhost` non funziona, prova:
- `127.0.0.1`
- L'IP del server database se remoto

#### 8. Porte Non Standard

Se MySQL usa una porta diversa da 3306:
- **Host:** `localhost:3307` (esempio)
- Oppure configura nel file di configurazione

### 🧪 Test Manuale Connessione

#### Test con MySQL CLI:
```bash
mysql -h localhost -u root -p
```

#### Test con PHP:
```php
<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    echo "✅ Connessione riuscita!";
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage();
}
?>
```

### 📱 Interfacce Grafiche

#### phpMyAdmin (XAMPP/WAMP)
- URL: `http://localhost/phpmyadmin`
- Testa le credenziali qui

#### MySQL Workbench
- Crea una connessione di test
- Verifica la connessione

### ⚡ Soluzioni Rapide XAMPP

#### Reset Completo MySQL XAMPP:
1. Stop MySQL dal pannello
2. Vai in `xampp/mysql/data/`
3. Backup della cartella (importante!)
4. Elimina `mysql` folder
5. Riavvia XAMPP
6. Riporta i dati dal backup se necessario

#### File di Configurazione XAMPP:
- **Windows:** `xampp/mysql/bin/my.ini`
- **Linux/Mac:** `xampp/etc/my.cnf`

Verifica impostazioni:
```ini
[mysqld]
port=3306
socket=mysql
```

### 🔒 Considerazioni Sicurezza

#### Per Produzione:
1. **Mai** usare `root` senza password
2. Crea utente dedicato con permessi limitati:
   ```sql
   CREATE USER 'scontrini'@'localhost' IDENTIFIED BY 'password_complessa';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON scontrini_db.* TO 'scontrini'@'localhost';
   ```

#### Permessi Minimi Necessari:
- `SELECT`, `INSERT`, `UPDATE`, `DELETE`: Per operazioni dati
- `CREATE`, `ALTER`: Per installazione e aggiornamenti
- `INDEX`: Per ottimizzazioni
- `CREATE TEMPORARY TABLES`: Per query complesse

### 📞 Se Nulla Funziona

1. **Controlla log MySQL:**
   - XAMPP: `xampp/mysql/data/mysql_error.log`
   - Linux: `/var/log/mysql/error.log`

2. **Reinstalla MySQL/XAMPP**

3. **Usa l'installer CLI** per debug maggiore:
   ```bash
   php install/cli_installer.php
   ```

4. **Contatta supporto** con:
   - Sistema operativo
   - Versione MySQL/XAMPP
   - Log di errore completo
   - Output dello script di debug

### 🎯 Checklist Rapida

- [ ] MySQL è in esecuzione?
- [ ] Credenziali corrette?
- [ ] Host corretto (localhost vs 127.0.0.1)?
- [ ] Porta corretta (default 3306)?
- [ ] Utente ha permessi necessari?
- [ ] Password contiene caratteri speciali che potrebbero causare problemi?
- [ ] File di configurazione MySQL corretto?
- [ ] Firewall blocca la connessione?

---

**💡 Suggerimento:** Quando tutto funziona, annota le credenziali usate per installazioni future!