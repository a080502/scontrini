# Sistema di Armonizzazione Nomi Scontrini

## Panoramica

Durante l'importazione Excel, il sistema analizza automaticamente i nomi degli scontrini e rileva possibili varianti dello stesso nome che potrebbero essere armonizzate.

## Funzionalità

### 🔍 **Rilevamento Automatico**

Il sistema utilizza algoritmi di similarità per identificare nomi che potrebbero essere varianti:

- **Levenshtein Distance**: Calcola la "distanza" tra due stringhe
- **Normalizzazione**: Ignora accenti, maiuscole, punteggiatura
- **Soglia di Similarità**: 75% di somiglianza (configurabile)

### 📋 **Esempi di Rilevamento**

| Nomi Originali | Similarità | Suggerito |
|----------------|------------|-----------|
| "Materiali Ufficio", "Materiale Ufficio", "Mat. Ufficio" | 85%+ | "Mat. Ufficio" |
| "Acquisto Computer", "Acquisti Computer", "Acq. Computer" | 80%+ | "Acq. Computer" |
| "Spese Viaggio Milano", "Spese Viaggi Milano" | 90%+ | "Spese Viaggio Milano" |

### 🎯 **Processo di Armonizzazione**

1. **Caricamento File**: Il sistema analizza tutti i nomi nel file Excel
2. **Identificazione Gruppi**: Raggruppa nomi simili (soglia 75%+)
3. **Suggerimenti Automatici**: Propone il nome più breve o comune come standard
4. **Scelta Utente**: L'operatore può:
   - ✅ Accettare il suggerimento
   - 🔄 Scegliere un altro nome dal gruppo
   - ✏️ Scrivere un nome completamente personalizzato
   - ❌ Ignorare il suggerimento

### 🛠️ **Interfaccia Utente**

#### **Sezione Suggerimenti**
```
┌─ 🪄 Suggerimenti Armonizzazione Nomi ──────────────────┐
│ Sono stati rilevati nomi similari che potrebbero      │
│ essere armonizzati. Seleziona quali accorpare:        │
│                                                        │
│ ☑️ Accorpa questi nomi simili:                        │
│                                                        │
│ Nomi trovati:                     Nome personalizzato: │
│ ○ Materiali Ufficio              [________________]    │
│ ● Materiale Ufficio  (suggerito)                      │
│ ○ Mat. Ufficio                                         │
│                                                        │
│ [✅ Applica Armonizzazione] [❌ Ignora Suggerimenti]    │
└────────────────────────────────────────────────────────┘
```

### ⚙️ **Algoritmo di Similarità**

#### **Normalizzazione Testo**
```javascript
// Rimuove accenti, punteggiatura, converte in minuscolo
"Materiali & Ufficio!" → "materiali ufficio"
```

#### **Calcolo Distanza**
```javascript
// Levenshtein Distance tra stringhe normalizzate
distance("materiali ufficio", "materiale ufficio") = 1
similarity = 1 - (1 / 17) = 94%
```

#### **Raggruppamento**
```javascript
// Soglia minima 75%
if (similarity >= 0.75) {
    // Aggiungi al gruppo
}
```

### 🎛️ **Opzioni di Configurazione**

#### **Soglia Similarità** (modificabile nel codice)
```javascript
const sogliaMinima = 0.75; // 75% default
```

#### **Criteri di Suggerimento**
1. **Nome più breve** (priorità alta)
2. **Ordine alfabetico** (se stessa lunghezza)
3. **Primo trovato** (fallback)

### 📊 **Statistiche e Reporting**

Il sistema fornisce informazioni sui suggerimenti:

- **Gruppi identificati**: Numero di gruppi di nomi simili
- **Nomi coinvolti**: Totale nomi che potrebbero essere armonizzati
- **Potenziale risparmio**: Numero di nomi duplicati eliminabili

### 🔧 **Implementazione Tecnica**

#### **File Modificati**
- `import-excel.php`: Logica principale e UI
- Algoritmi integrati in JavaScript lato client

#### **Funzioni Principali**
- `levenshteinDistance()`: Calcolo distanza tra stringhe
- `calculateSimilarity()`: Normalizzazione e calcolo similarità
- `trovaNoomiSimili()`: Identificazione gruppi simili
- `applicaArmonizzazione()`: Applicazione modifiche selezionate

### 💡 **Benefici**

1. **Consistenza Dati**: Elimina variazioni accidentali dei nomi
2. **Reportistica Migliore**: Raggruppamenti più accurati
3. **Efficienza**: Riduce duplicazioni nei database
4. **User Experience**: Processo guidato e intuitivo
5. **Flessibilità**: Operatore mantiene controllo finale

### 🚨 **Note Importanti**

- **Processo Opzionale**: L'armonizzazione può essere completamente ignorata
- **Controllo Utente**: Tutte le decisioni spettano all'operatore
- **Reversibilità**: Le modifiche si applicano solo durante l'importazione
- **Performance**: Analisi eseguita lato client (nessun overhead server)

### 🔄 **Flusso di Lavoro**

1. **Upload File Excel** → Sistema analizza nomi
2. **Anteprima Dati** → Mostra statistiche importazione
3. **Suggerimenti Armonizzazione** → Propone raggruppamenti (se rilevati)
4. **Decisione Operatore** → Accetta/Modifica/Ignora suggerimenti
5. **Importazione Finale** → Applica nomi armonizzati selezionati

Questo sistema garantisce dati più puliti e consistenti mantenendo sempre il controllo nelle mani dell'operatore! 🎯