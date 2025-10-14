# Implementazione GPS e Nomenclatura Foto Avanzata

## Panoramica
È stata implementata la funzionalità per includere informazioni dettagliate nei nomi dei file delle foto degli scontrini, includendo nome utente, data/ora e coordinate GPS quando disponibili.

## Database - Nuove Colonne GPS

### Tabella `scontrini` - Campi Aggiunti:
```sql
- gps_latitude DECIMAL(10, 7) NULL     -- Latitudine (es: 45.4642035)
- gps_longitude DECIMAL(10, 7) NULL    -- Longitudine (es: 9.1899815)  
- gps_accuracy DECIMAL(8, 2) NULL      -- Precisione in metri (es: 15.50)
- gps_timestamp DATETIME NULL          -- Timestamp acquisizione GPS
```

**Indice per ottimizzazione:**
```sql
CREATE INDEX idx_scontrini_gps ON scontrini(gps_latitude, gps_longitude);
```

## Nomenclatura File Foto

### Nuovo Formato Nome File:
```
scontrino_[ID]_user_[USERNAME]_[YYYY-MM-DD_HH-MM-SS]_gps_[LAT]_[LNG]_acc_[ACCURACY]m_[UNIQUE].ext
```

### Esempi di Nomi File:
```
scontrino_15_user_mario_2025-10-06_14-30-25_gps_45dot4642_9dot1899_acc_12m_67123abc.jpg
scontrino_20_user_admin_2025-10-06_09-15-42_6712def9.png (senza GPS)
scontrino_8_user_luca_2025-10-06_16-45-18_gps_41dot9028_12dot4964_acc_8dot5m_ab34ef56.webp
```

### Componenti del Nome:
1. **ID Scontrino**: `scontrino_15`
2. **Username**: `user_mario` (sanitizzato, max 20 caratteri)
3. **Timestamp**: `2025-10-06_14-30-25` (YYYY-MM-DD_HH-MM-SS)
4. **Coordinate GPS**: `gps_45dot4642_9dot1899` (punto sostituito con "dot", "-" con "neg")
5. **Precisione GPS**: `acc_12m` (accuratezza in metri)
6. **ID Univoco**: `67123abc` (per evitare duplicati)
7. **Estensione**: `.jpg` (formato originale)

## Funzionalità GPS

### Acquisizione Automatica
- **Trigger**: Selezione foto nei form di aggiunta/modifica
- **Permessi**: Richiesta automatica permessi geolocalizzazione
- **Timeout**: 10 secondi (desktop), 15 secondi (mobile)
- **Cache**: 60 secondi (desktop), 30 secondi (mobile)
- **Precisione**: Alta precisione abilitata

### Feedback Utente
- **Stato Acquisizione**: Messaggi in tempo reale
- **Feedback Tattile**: Vibrazione su dispositivi mobili
- **Indicatori Visivi**: Colori diversi per stato (info/success/error/warning)
- **Auto-dismiss**: Messaggi di successo scompaiono automaticamente

### Gestione Errori GPS
```javascript
switch(error.code) {
    case PERMISSION_DENIED: "Permesso negato"
    case POSITION_UNAVAILABLE: "Posizione non disponibile" 
    case TIMEOUT: "Timeout acquisizione"
    default: "Errore sconosciuto"
}
```

## Implementazione Tecnica

### ImageManager - Metodo Aggiornato
```php
public static function saveScontrinoPhoto($file, $scontrino_id, $user_info = null, $gps_data = null)
```

**Parametri Aggiuntivi:**
- `$user_info`: Array con 'username' e 'nome' utente
- `$gps_data`: Array con 'latitude', 'longitude', 'accuracy'

### Sanitizzazione Nome File
```php
private static function sanitizeForFilename($string) {
    // Rimuove caratteri speciali
    $string = preg_replace('/[^a-zA-Z0-9\-_]/', '', $string);
    // Limita lunghezza a 20 caratteri
    $string = substr($string, 0, 20);
    // Fallback se vuoto
    return empty($string) ? 'user' : $string;
}
```

### Gestione Coordinate GPS
```php
// Conversione per nome file sicuro
$lat_clean = str_replace(['.', '-'], ['dot', 'neg'], (string)$gps_data['latitude']);
$lng_clean = str_replace(['.', '-'], ['dot', 'neg'], (string)$gps_data['longitude']);
```

## JavaScript Client-Side

### Acquisizione GPS Desktop
```javascript
function getCurrentLocation() {
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    };
    
    navigator.geolocation.getCurrentPosition(success, error, options);
}
```

### Acquisizione GPS Mobile
```javascript
function getCurrentLocationMobile() {
    const options = {
        enableHighAccuracy: true,
        timeout: 15000,        // Timeout più lungo
        maximumAge: 30000      // Cache più breve
    };
    
    navigator.geolocation.getCurrentPosition(success, error, options);
}
```

### Campi Hidden GPS
```html
<input type="hidden" id="gps_latitude" name="gps_latitude">
<input type="hidden" id="gps_longitude" name="gps_longitude">
<input type="hidden" id="gps_accuracy" name="gps_accuracy">
```

## File Modificati

### Backend PHP:
1. **`includes/image_manager.php`**:
   - Metodo `saveScontrinoPhoto()` aggiornato
   - Aggiunta `sanitizeForFilename()`
   - Costruzione nome file dettagliato

2. **`aggiungi.php`** e **`aggiungi-mobile.php`**:
   - Gestione parametri GPS da POST
   - Passaggio info utente a ImageManager
   - Salvataggio coordinate nel database

3. **`modifica.php`**:
   - Supporto GPS per nuove foto
   - Mantenimento stessa logica di denominazione

### Frontend JavaScript:
1. **Acquisizione GPS automatica** su selezione foto
2. **Feedback visivo** con stati colorati
3. **Gestione errori** con messaggi specifici
4. **Compatibilità mobile** con timeout ottimizzati

### Database:
1. **Migrazione GPS**: Nuove colonne per coordinate
2. **Indice geografico**: Ottimizzazione query spatial

## Vantaggi Implementazione

### Organizzazione File:
- **Identificazione immediata**: Username e timestamp nel nome
- **Tracciabilità geografica**: Coordinate GPS integrate
- **Unicità garantita**: ID univoco previene duplicati
- **Compatibilità sistema**: Caratteri speciali sanitizzati

### Sicurezza:
- **Validazione input**: Coordinate validate come float
- **Sanitizzazione**: Rimozione caratteri pericolosi
- **Permessi GPS**: Richiesta esplicita consenso utente

### User Experience:
- **Feedback immediato**: Status acquisizione GPS
- **Graceful degradation**: Funziona anche senza GPS
- **Mobile-optimized**: Timeout e feedback adattati

## Utilizzo Coordinate GPS

### Query Geografiche:
```sql
-- Scontrini in raggio di 1km da una posizione
SELECT * FROM scontrini 
WHERE gps_latitude BETWEEN (lat - 0.009) AND (lat + 0.009)
  AND gps_longitude BETWEEN (lng - 0.009) AND (lng + 0.009);
```

### Analisi Spaziale:
- Raggruppamento per area geografica
- Identificazione pattern di spesa per zona
- Report di utilizzo territoriale

## Esempi d'Uso

### Scenario 1: Foto con GPS
```
File: scontrino_25_user_marco_2025-10-06_15-30-45_gps_45dot4642_9dot1899_acc_10m_abc123.jpg
Database: lat=45.4642, lng=9.1899, accuracy=10.0, timestamp=2025-10-06 15:30:45
```

### Scenario 2: Foto senza GPS
```
File: scontrino_26_user_sara_2025-10-06_16-15-22_def456.png
Database: lat=NULL, lng=NULL, accuracy=NULL, timestamp=NULL
```

### Scenario 3: Username lungo sanitizzato
```
Input: "francesco.rossi@example.com"
Output: "francescoressi" (max 20 caratteri, caratteri speciali rimossi)
File: scontrino_27_user_francescoressi_2025-10-06_17-00-10_xyz789.webp
```

La funzionalità è ora completamente implementata e fornisce una tracciabilità completa delle foto degli scontrini con informazioni dettagliate su utente, tempo e posizione!