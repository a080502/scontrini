# 📋 COSTANTI DI CONFIGURAZIONE

## 🔧 Costanti Disponibili

### Database
- `DB_HOST` - Host del database (default: 'localhost')
- `DB_NAME` - Nome del database 
- `DB_USER` - Username per MySQL
- `DB_PASS` - Password per MySQL

### Applicazione
- `APP_NAME` - Nome dell'applicazione ('Sistema Gestione Scontrini')
- `APP_VERSION` - Versione corrente ('2.0.0')
- `SITE_NAME` - Nome del sito (per compatibilità con codice esistente)

### Sicurezza
- `SESSION_TIMEOUT` - Timeout sessione in secondi (3600 = 1 ora)
- `SESSION_LIFETIME` - Durata sessione (per compatibilità)
- `SESSION_SECRET` - Chiave segreta per sessioni

### Locale
- `LOCALE` - Impostazione locale ('it_IT')

### Debug
- `DEBUG_MODE` - Modalità debug (true/false)

## 🚨 Errori Comuni e Soluzioni

### "Undefined constant APP_NAME"
**Causa**: File config.php mancante o non incluso correttamente
**Soluzione**: 
1. Verifica che esista `config.php` nella root del progetto
2. Controlla che sia incluso tramite `require_once 'includes/bootstrap.php'`

### "Undefined constant SITE_NAME" 
**Causa**: Configurazione mancante
**Soluzione**: Aggiungi nel config.php:
```php
define('SITE_NAME', 'Gestione Scontrini Fiscali');
```

### "Undefined constant DB_HOST"
**Causa**: config.php non caricato o corrotto
**Soluzione**: 
1. Copia `config.example.php` in `config.php`
2. Configura le tue credenziali database

## 🔄 Migrazione da Versione Precedente

Se hai un `config.php` esistente, aggiungi queste righe:

```php
// Aggiungi compatibilità nuove funzionalità
define('APP_NAME', 'Sistema Gestione Scontrini');
define('APP_VERSION', '2.0.0');
define('SESSION_TIMEOUT', 3600);
define('DEBUG_MODE', true);
```

## 🎯 Check List Configurazione

- ✅ File `config.php` esiste e è configurato
- ✅ Database connettibile con le credenziali fornite
- ✅ Tutte le costanti definite senza errori
- ✅ Timezone impostato correttamente
- ✅ Locale italiano attivo

---

**💡 Tip**: Per verificare la configurazione, puoi creare un file test:

```php
<?php
require_once 'config.php';
echo "APP_NAME: " . APP_NAME . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DEBUG_MODE: " . (DEBUG_MODE ? 'ON' : 'OFF') . "\n";
?>
```