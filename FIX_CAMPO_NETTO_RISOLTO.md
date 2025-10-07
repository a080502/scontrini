# 🐛 Fix: Errore Campo 'netto' - RISOLTO

## Problema Riscontrato
```
Errore durante il salvataggio: Errore query database: SQLSTATE[HY000]: General error: 1364 Field 'netto' doesn't have a default value
```

## Analisi del Problema

### Causa Root
- La tabella `scontrini` nel database ha il campo `netto` definito come `NOT NULL` senza valore di default
- Le query INSERT nei file `aggiungi.php`, `aggiungi-mobile.php` e `test_schema.php` non includevano il campo `netto`
- MySQL richiede un valore per tutti i campi NOT NULL durante l'inserimento

### Schema Database
```sql
mysql> DESCRIBE scontrini;
| Field    | Type          | Null | Key | Default |
|----------|---------------|------|-----|---------|
| netto    | decimal(10,2) | NO   |     | NULL    |
```

## Soluzione Implementata

### 📝 File Modificati

#### 1. `/aggiungi.php`
**Prima:**
```php
INSERT INTO scontrini (numero, data, lordo, da_versare, note, utente_id, filiale_id, foto, gps_coords) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
```

**Dopo:**
```php
// Calcola il netto (se non specificato, uguale al lordo)
$netto = $lordo;

INSERT INTO scontrini (numero, data, lordo, netto, da_versare, note, utente_id, filiale_id, foto, gps_coords) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
```

#### 2. `/aggiungi-mobile.php`
- Stessa correzione applicata alla versione mobile

#### 3. `/test_schema.php`
- Aggiunto campo `netto` al test di inserimento

### 💡 Logica Implementata
- **Campo `netto`**: Attualmente impostato uguale al `lordo`
- **Estensibilità**: Il calcolo può essere personalizzato in futuro per includere:
  - Detrazioni fiscali
  - Commissioni
  - Altri calcoli aziendali specifici

## Test e Verifica

### ✅ Risultati
- ✅ Query INSERT ora funzionano correttamente
- ✅ Nessun errore durante l'aggiunta di nuovi scontrini
- ✅ Compatibilità mantenuta con il resto del sistema
- ✅ Schema database non modificato (approccio conservativo)

### 🧪 Come Testare
1. Accedi all'applicazione web
2. Vai su "Aggiungi Nuovo Scontrino"
3. Compila i campi richiesti
4. Salva - dovrebbe funzionare senza errori

## Commit
- **Hash**: `6597881`
- **Messaggio**: "🐛 Fix: Risolto errore 'Field netto doesn't have a default value'"
- **Data**: $(date)

## Note Tecniche

### Approcci Considerati
1. **✅ Modificare il codice** (approccio scelto)
   - Pro: Mantiene lo schema database esistente
   - Pro: Controllo completo sulla logica di calcolo
   - Con: Richiede aggiornamento di più file

2. **❌ Modificare lo schema database**
   - Pro: Fix rapido
   - Con: Potenziali problemi con dati esistenti
   - Con: Potrebbe rompere altre parti del sistema

### Considerazioni Future
- Il campo `netto` può essere utilizzato per implementare:
  - Calcoli automatici di tasse
  - Gestione di sconti o maggiorazioni
  - Separazione tra importo lordo e netto effettivo