# 🔧 RISOLUZIONE ERRORE: "Unknown column 'u.attivo'"

## 🚨 Problema
```
Fatal error: Column not found: 1054 Unknown column 'u.attivo' in 'where clause'
```

## ✅ Soluzioni Rapide

### Opzione 1: Nuova Installazione (CONSIGLIATO)
Se puoi permetterti di ricreare il database:

1. **Elimina il database esistente**
2. **Vai su `/setup.php`** 
3. **Segui il setup guidato** - Il nuovo database includerà automaticamente la colonna `attivo`

### Opzione 2: Aggiorna Database Esistente
Se devi mantenere i dati esistenti:

1. **Apri phpMyAdmin o console MySQL**
2. **Esegui questo comando SQL**:
```sql
ALTER TABLE utenti ADD COLUMN attivo TINYINT(1) DEFAULT 1;
UPDATE utenti SET attivo = 1;
```

3. **Oppure importa il file** `update_utenti_attivo.sql`

### Opzione 3: Script Automatico
Se hai accesso via terminale:

```bash
# Vai nella directory del progetto
cd /percorso/al/progetto

# Esegui lo script di aggiornamento
mysql -u username -p nome_database < update_utenti_attivo.sql
```

## 🔍 Verifica Risoluzione

Dopo aver applicato la soluzione, verifica che funzioni:

1. **Vai su `/aggiungi.php`**
2. **Login come responsabile o admin**
3. **Dovrebbe apparire il campo "Associa Scontrino a Utente"**
4. **Nessun errore dovrebbe apparire**

## 📋 Cosa Abbiamo Risolto

- ✅ **Aggiunta colonna `attivo`** alla tabella `utenti`
- ✅ **Tutti gli utenti esistenti** marcati come attivi
- ✅ **Compatibilità** con le nuove funzionalità multi-utente
- ✅ **Setup automatico** per future installazioni

## 🔄 Per Sviluppi Futuri

La colonna `attivo` permette di:
- **Disabilitare utenti** senza eliminarli
- **Nascondere utenti inattivi** dalle selezioni
- **Mantenere lo storico** degli utenti

---

**⚡ Nota**: Se stai usando XAMPP, il modo più veloce è ricreare tutto con `/setup.php`!