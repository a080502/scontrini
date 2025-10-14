# 🔐 SISTEMA AUTORIZZAZIONI PER RUOLI

## 🚨 Problema Risolto: "Utente vede scontrini di altri"

### ✅ **Controlli Implementati**

#### 👤 **Utente Normale**
- ✅ Vede **SOLO i propri scontrini**
- ✅ Statistiche calcolate **solo sui suoi dati**
- ✅ Non può accedere a scontrini di altri utenti
- 🔍 **Filtro**: `WHERE utente_id = [ID_UTENTE_CORRENTE]`

#### 👨‍💼 **Responsabile Filiale** 
- ✅ Vede **tutti gli scontrini della sua filiale**
- ✅ Include tutti gli utenti della sua filiale
- ✅ Non vede scontrini di altre filiali
- 🔍 **Filtro**: `WHERE filiale_id = [ID_FILIALE_RESPONSABILE]`

#### 👑 **Amministratore**
- ✅ Vede **tutti gli scontrini di tutto il sistema**
- ✅ Nessun filtro applicato
- ✅ Controllo completo su tutte le filiali
- 🔍 **Filtro**: Nessuno (accesso completo)

## 📋 **Pagine Aggiornate**

### ✅ `index.php` (Dashboard)
- **Statistiche filtrate** per ruolo utente
- **Ultimi scontrini** solo quelli autorizzati
- **Colonne dinamiche** (Utente/Filiale per admin e responsabili)

### ✅ `lista.php` (Lista Scontrini)
- **Filtri per permessi** già implementati
- **Ricerca limitata** ai dati autorizzati
- **Export** solo dati consentiti

### ✅ `archivio.php` (Archivio)
- **Archivio filtrato** per ruolo
- **Statistiche archivio** solo dati autorizzati
- **Anni disponibili** basati sui permessi

### ✅ `aggiungi.php` (Aggiungi Scontrino)
- **Selezione utente** per responsabili/admin
- **Validazione autorizzazioni** lato server
- **Associazione corretta** utente/filiale

## 🔧 **Implementazione Tecnica**

### Query Pattern
```php
// Determina filtri basati sul ruolo
$where_clause = "";
$query_params = [];

if (Auth::isAdmin()) {
    // Admin vede tutto
    $where_clause = "";
} elseif (Auth::isResponsabile()) {
    // Responsabile vede solo la sua filiale
    $where_clause = " AND filiale_id = ?";
    $query_params[] = $current_user['filiale_id'];
} else {
    // Utente normale vede solo i propri scontrini
    $where_clause = " AND utente_id = ?";
    $query_params[] = $current_user['id'];
}
```

### Funzioni di Controllo
- `Auth::isAdmin()` - Verifica ruolo admin
- `Auth::isResponsabile()` - Verifica ruolo responsabile  
- `Auth::getCurrentUser()` - Dati utente loggato
- `Auth::canAccessScontrino($id)` - Verifica accesso specifico

## 🛡️ **Sicurezza Garantita**

### ✅ **Prevenzioni**
- **Iniezione SQL**: Query parametrizzate
- **Accesso non autorizzato**: Filtri su ogni query
- **Escalation privilegi**: Controlli rigorosi per ruolo
- **Data leak**: Statistiche filtrate per utente

### 🔒 **Controlli Lato Server**
- Ogni query include filtri di autorizzazione
- Validazione permessi prima di ogni operazione
- Controllo sessione attiva
- Verifica ruolo per ogni azione

## 🎯 **Esempi Pratici**

### Scenario Utente "Mario"
```
- Mario vede: Solo i suoi 15 scontrini
- Statistiche: Basate solo sui suoi dati  
- Export: Solo i suoi scontrini
- Archivio: Solo i suoi scontrini archiviati
```

### Scenario Responsabile "Anna" (Filiale Nord)
```
- Anna vede: Tutti gli scontrini della Filiale Nord
- Include: Mario, Lucia, Giuseppe (utenti Filiale Nord)
- Non vede: Scontrini di Filiale Sud o Sede
- Può aggiungere: Scontrini per utenti della sua filiale
```

### Scenario Admin "SuperUser"
```
- Vede: Tutto il sistema (tutte le filiali)
- Gestisce: Tutti gli utenti e scontrini
- Può aggiungere: Scontrini per qualsiasi utente
- Accesso: Completo a tutte le funzionalità
```

---

**✅ Ora ogni utente vede solo quello che gli spetta secondo la gerarchia aziendale!**