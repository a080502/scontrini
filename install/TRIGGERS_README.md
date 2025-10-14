# 🔧 Trigger Avanzati (Opzionali)

## 📋 Panoramica

I trigger avanzati forniscono logging automatico delle modifiche agli scontrini nella tabella `log_attivita`. Questi sono **opzionali** e non necessari per il funzionamento base del sistema.

## ⚡ Installazione Automatica

Durante l'installazione principale, i trigger vengono **saltati automaticamente** per evitare problemi di compatibilità con diversi sistemi MySQL/MariaDB.

## 🛠️ Installazione Manuale dei Trigger

Se desideri abilitare il logging automatico avanzato:

### Opzione 1: Via MySQL CLI
```bash
mysql -h localhost -u username -p database_name < install/triggers_optional.sql
```

### Opzione 2: Via phpMyAdmin
1. Apri phpMyAdmin
2. Seleziona il database del sistema
3. Vai alla tab "SQL"
4. Copia e incolla il contenuto di `install/triggers_optional.sql`
5. Esegui

### Opzione 3: Via Script PHP
```php
<?php
require_once 'config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = file_get_contents('install/triggers_optional.sql');

// Rimuovi DELIMITER per esecuzione via PDO
$sql = str_replace('DELIMITER $$', '', $sql);
$sql = str_replace('DELIMITER ;', '', $sql);
$sql = str_replace('$$', ';', $sql);

$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        $pdo->exec($statement);
    }
}

echo "Trigger installati con successo!";
?>
```

## 📊 Funzionalità dei Trigger

### `tr_scontrini_insert`
- **Quando**: Nuovo scontrino creato
- **Azione**: Registra log "Nuovo scontrino: NUMERO"
- **Tabella**: `log_attivita`

### `tr_scontrini_update`  
- **Quando**: Scontrino modificato
- **Azione**: Registra cambi di stato e importi
- **Esempi log**:
  - "Cambio stato: attivo → incassato"
  - "Modifica importi: 100.00→120.00"

## 🔍 Verifica Installazione

Controlla se i trigger sono attivi:
```sql
SHOW TRIGGERS FROM nome_database;
```

Oppure verifica i log dopo una modifica:
```sql
SELECT * FROM log_attivita ORDER BY created_at DESC LIMIT 10;
```

## ⚠️ Note Importanti

### Compatibilità
- **MySQL 5.7+**: ✅ Supporto completo
- **MariaDB 10.2+**: ✅ Supporto completo  
- **MySQL 5.6**: ⚠️ Potrebbero servire modifiche sintassi

### Performance
- I trigger hanno **impatto minimo** sulle performance
- Ogni inserimento/modifica aggiunge **1 riga** in `log_attivita`
- Tabella log può crescere nel tempo - **considera pulizia periodica**

### Pulizia Log (Opzionale)
```sql
-- Elimina log più vecchi di 1 anno
DELETE FROM log_attivita WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

## 🚫 Rimozione Trigger

Se vuoi disabilitare i trigger:
```sql
DROP TRIGGER IF EXISTS tr_scontrini_insert;
DROP TRIGGER IF EXISTS tr_scontrini_update;
```

## 💡 Alternative

Se i trigger non funzionano, il sistema **continua a funzionare normalmente** senza logging automatico. Puoi implementare logging manuale nel codice PHP se necessario.

---

**ℹ️ I trigger sono una funzionalità avanzata opzionale. Il sistema funziona perfettamente senza di essi.**