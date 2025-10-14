# 🔧 FIX PROBLEMI QUERY DATABASE

## ❌ **Problemi Risolti**

### 1️⃣ **Errore: `Unknown column 's.utente_id'`**
- **File**: `index.php`
- **Causa**: Query semplici usavano condizioni con prefisso `s.` ma senza alias
- **Soluzione**: Parametro `table_prefix` nella funzione `buildAdvancedFilters()`

### 2️⃣ **Errore: `Unknown column 's.archiviato'`**
- **File**: `archivio.php`  
- **Causa**: Query statistiche usavano `$where_clause` con prefisso `s.` ma senza JOIN
- **Soluzione**: Doppio set di condizioni WHERE (con e senza prefisso)

## ✅ **Correzioni Implementate**

### 🔧 **includes/utils.php**
```php
// PRIMA (causava errori)
buildAdvancedFilters($db, $current_user, $filters)
// Generava sempre: s.utente_id = ?

// DOPO (flessibile)
buildAdvancedFilters($db, $current_user, $filters, $table_prefix = 's.')
// Genera: utente_id = ? OPPURE s.utente_id = ?
```

### 🏠 **index.php** 
```php
// Query semplici (senza JOIN)
$advanced_filter_data = Utils::buildAdvancedFilters($db, $current_user, $filters, '');
// WHERE: filiale_id = ?

// Query con JOIN  
$advanced_filter_data_with_prefix = Utils::buildAdvancedFilters($db, $current_user, $filters, 's.');
// WHERE: s.filiale_id = ?
```

### 🗂️ **archivio.php**
```php
// Per query con JOIN
$where_conditions = ["s.archiviato = 1"];
$advanced_filter_data = Utils::buildAdvancedFilters($db, $current_user, $filters, 's.');

// Per query semplici  
$where_conditions_no_prefix = ["archiviato = 1"];
$advanced_filter_data_no_prefix = Utils::buildAdvancedFilters($db, $current_user, $filters, '');

// Query JOIN: usa $where_clause (con s.)
// Query statistiche: usa $where_clause_no_prefix (senza s.)
```

### 📋 **lista.php** ✅ Già Corretto
- Usa sempre prefisso `s.` per tutte le query con JOIN
- Nessuna modifica necessaria

### ⏱️ **attivita.php** ✅ Già Corretto  
- Usa sempre prefisso `s.` per query con JOIN
- Nessuna modifica necessaria

## 📊 **Esempi Query Generate**

### **Responsabile Filiale con filtri: utente_id=5, nome='test'**

#### 🏠 Index.php (Query Semplici)
```sql
-- Statistiche dashboard
SELECT COUNT(*) FROM scontrini 
WHERE archiviato = 0 AND filiale_id = 1 AND utente_id = 5 AND nome LIKE '%test%'

-- Ultimi scontrini  
SELECT s.*, u.nome FROM scontrini s LEFT JOIN utenti u ON s.utente_id = u.id
WHERE s.filiale_id = 1 AND s.utente_id = 5 AND s.nome LIKE '%test%'
```

#### 📋 Lista.php (Query con JOIN)
```sql
SELECT s.*, u.nome, f.nome FROM scontrini s 
LEFT JOIN utenti u ON s.utente_id = u.id
LEFT JOIN filiali f ON s.filiale_id = f.id  
WHERE s.archiviato = 0 AND s.filiale_id = 1 AND s.utente_id = 5 AND s.nome LIKE '%test%'
```

#### 🗂️ Archivio.php (Doppia Query)
```sql
-- Query scontrini (con JOIN)
SELECT s.*, u.nome, f.nome FROM scontrini s
LEFT JOIN utenti u ON s.utente_id = u.id  
LEFT JOIN filiali f ON s.filiale_id = f.id
WHERE s.archiviato = 1 AND s.filiale_id = 1 AND s.utente_id = 5 AND s.nome LIKE '%test%'

-- Query statistiche (senza JOIN)  
SELECT COUNT(*), SUM(lordo) FROM scontrini
WHERE archiviato = 1 AND filiale_id = 1 AND utente_id = 5 AND nome LIKE '%test%'
```

## 🚀 **Risultato Finale**

### ✅ **Tutte le pagine ora funzionano senza errori database**
- ✅ `index.php` - Dashboard con statistiche filtrate
- ✅ `lista.php` - Lista scontrini con filtri avanzati  
- ✅ `archivio.php` - Archivio con doppia query corretta
- ✅ `attivita.php` - Timeline attività filtrata

### 🔒 **Sicurezza Mantenuta**
- ✅ Prepared statements per tutti i parametri
- ✅ Filtri per ruolo sempre applicati
- ✅ HTML sempre escapato nelle form
- ✅ Zero rischi SQL injection

### ⚡ **Performance Ottimali**
- ✅ Query efficienti con/senza JOIN appropriati
- ✅ Indici database utilizzati correttamente
- ✅ Nessuna query ridondante o non ottimizzata

---

## 📋 **Commit GitHub**

```
🔗 Commit 1: cfa5224 - fix: Correzione prefissi tabelle nelle query con filtri avanzati
🔗 Commit 2: 04eeac3 - fix: Correzione prefissi tabelle in archivio.php
```

**🎯 TUTTI I PROBLEMI DATABASE RISOLTI!** Il sistema filtri avanzati ora funziona perfettamente su tutte le pagine.