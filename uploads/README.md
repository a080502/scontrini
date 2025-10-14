# Directory degli Upload

Questa directory contiene i file caricati dal sistema.

## Struttura
- `scontrini/` - Foto degli scontrini organizzate per anno/mese
  - `2025/01/` - Gennaio 2025
  - `2025/02/` - Febbraio 2025
  - etc.

## Sicurezza
- I file sono accessibili solo tramite script PHP autorizzati
- Validazione dei tipi di file consentiti
- Limite di dimensione file
- I nomi dei file sono rinominati per evitare conflitti

## Manutenzione
- Backup periodico consigliato
- Pulizia file orfani (scontrini eliminati)