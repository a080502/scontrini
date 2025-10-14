# Modifiche Sistema Importazione Excel

## Data: 13 Ottobre 2025

### Modifiche Implementate

#### 1. **Gestione Nome Scontrino Opzionale**
- **Prima**: Nome scontrino era obbligatorio, l'importazione si fermava in caso di errore
- **Ora**: Se il nome scontrino è vuoto, viene automaticamente impostato "SCONTRINO SENZA NOME  !! AGGIORNARE !!" e l'importazione continua

#### 2. **Validazione Prezzo Unitario**
- **Prima**: Prezzo unitario doveva essere >= 0
- **Ora**: Prezzo unitario può essere anche negativo (per gestire sconti e storni)

#### 3. **Gestione Data Scontrino dal File Excel**
- **Prima**: Tutti gli scontrini avevano la data impostata dall'utente nel form
- **Ora**: La data viene letta dal file Excel (colonna C)
- **Formato supportato**: DD/MM/YYYY (es: 15/01/2024) - formato italiano
- **Formato alternativo**: YYYY-MM-DD (es: 2024-01-15) - formato ISO
- **Fallback**: Se la data non è specificata nel file, viene usata la data odierna

#### 4. **Calcolo IVA Automatico**
- **Prima**: I prezzi venivano importati così come erano nel file
- **Ora**: I prezzi nel file Excel sono considerati SENZA IVA
- **Calcolo automatico**: Il sistema aggiunge automaticamente l'IVA del 22%
  - `netto` = importo dal file Excel (senza IVA)
  - `lordo` = importo * 1.22 (con IVA)
  - `da_versare` = importo lordo

### Nuovo Formato File Excel

Il file Excel ora deve contenere **8 colonne** nell'ordine:

| Colonna | Campo | Descrizione | Obbligatorio |
|---------|--------|-------------|--------------|
| A | Numero D'Ordine | Identifica lo scontrino | ✅ Sì |
| B | Nome Scontrino | Nome del scontrino | ❌ No (default applicato se vuoto) |
| C | Data Scontrino | Data formato DD/MM/YYYY o YYYY-MM-DD | ❌ No (usa data odierna se vuoto) |
| D | Codice Articolo | Codice dell'articolo | ❌ No |
| E | Descrizione | Descrizione dell'articolo | ✅ Sì |
| F | Quantità | Quantità dell'articolo | ✅ Sì (> 0) |
| G | Prezzo Unitario | Prezzo senza IVA | ✅ Sì (anche negativo) |
| H | Prezzo Totale | Totale riga senza IVA | ❌ Calcolato automaticamente |

### Esempio di Dati

```
ORD001 | Materiali Ufficio | 15/01/2024 | PEN001 | Penne biro blu | 10 | 1.50 | 15.00
ORD001 |                   | 15/01/2024 |        | Sconto fedeltà | 1  | -2.00| -2.00
ORD002 |                   | 16/01/2024 | CART01 | Carta A4       | 5  | 3.20 | 16.00
```

### Note Tecniche

#### File Modificati:
1. `/api/excel-template.php` - Template Excel aggiornato
2. `/import-excel.php` - Interfaccia utente e validazione JavaScript
3. `/api/import-excel-massivo.php` - Logica di importazione server-side

#### Calcoli IVA:
```php
$totale_netto = $importo_da_excel; // senza IVA
$totale_lordo = $totale_netto * 1.22; // con IVA 22%
$da_versare = $totale_lordo;
```

#### Gestione Errori:
- **Nome vuoto**: Non blocca più l'importazione, applica valore di default
- **Prezzo negativo**: Ora accettato (per sconti/storni)
- **Data invalida**: Formato DD/MM/YYYY o YYYY-MM-DD richiesto, fallback su data odierna
- **Validazioni rimanenti**: Numero d'ordine, descrizione e quantità rimangono obbligatori

### Benefici delle Modifiche

1. **Maggiore flessibilità**: Importazione meno rigida, più tollerante agli errori
2. **Gestione realistica**: Supporto per sconti/storni con prezzi negativi  
3. **Conformità fiscale**: Calcolo IVA automatico per conformità contabile
4. **Tracciabilità temporale**: Data scontrino dal file Excel per maggiore precisione
5. **Migliore UX**: Processo di importazione più fluido e user-friendly

### Retrocompatibilità

⚠️ **ATTENZIONE**: Queste modifiche cambiano il formato del file Excel richiesto. 

- I file Excel esistenti con 7 colonne non funzioneranno più
- È necessario utilizzare il nuovo template con 8 colonne
- Aggiornare la documentazione utente e i processi esistenti