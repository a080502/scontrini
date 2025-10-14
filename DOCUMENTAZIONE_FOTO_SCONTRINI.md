# Funzionalità Foto Scontrini

## Panoramica
È stata implementata la funzionalità per allegare foto agli scontrini, permettendo di conservare immagini digitali degli scontrini fiscali.

## Caratteristiche Principali

### Upload e Validazione
- **Formati supportati**: JPG, JPEG, PNG, GIF, WebP
- **Dimensione massima**: 5MB per file
- **Ridimensionamento automatico**: Le immagini vengono ridimensionate automaticamente se superano 1920x1920 pixel
- **Validazione sicurezza**: Controllo del tipo MIME e verifica che sia un'immagine valida

### Organizzazione File
- **Struttura directory**: `uploads/scontrini/YYYY/MM/` (organizzazione per anno/mese)
- **Nomenclatura**: `scontrino_[ID]_[timestamp]_[unique].ext`
- **Sicurezza**: Directory protetta da accesso diretto via `.htaccess`

### Visualizzazione
- **Miniature**: Miniature 50x50px nella lista scontrini
- **Anteprima**: Anteprima durante l'upload
- **Visualizzazione completa**: Clic su miniatura per vedere l'immagine a dimensione piena
- **Controllo accessi**: Solo utenti autorizzati possono vedere le foto

## File Modificati

### Nuovi File
1. **`includes/image_manager.php`** - Classe per gestione immagini
2. **`view_photo.php`** - Script per visualizzazione sicura delle foto
3. **`migrate_foto_scontrini.sql`** - Script SQL per aggiornare il database

### File Modificati
1. **`aggiungi.php`** - Aggiunto upload foto durante inserimento
2. **`modifica.php`** - Aggiunto gestione foto in modifica (add/remove/replace)
3. **`lista.php`** - Aggiunta colonna foto nella tabella

### Database
Aggiunte colonne alla tabella `scontrini`:
- `foto_scontrino` VARCHAR(255) - Percorso del file foto
- `foto_mime_type` VARCHAR(50) - Tipo MIME dell'immagine
- `foto_size` INT - Dimensione del file in bytes

## Funzionalità per Pagina

### Pagina Aggiungi Scontrino (`aggiungi.php`)
- **Campo upload foto**: Opzionale, con drag & drop
- **Anteprima**: Preview dell'immagine selezionata
- **Validazione client-side**: Controllo formato e dimensione prima dell'upload
- **Gestione errori**: Messaggio di warning se foto non viene caricata ma scontrino è salvato

### Pagina Modifica Scontrino (`modifica.php`)
- **Visualizzazione foto attuale**: Se presente, con link per ingrandire
- **Rimozione foto**: Checkbox per rimuovere foto esistente
- **Sostituzione foto**: Upload di una nuova foto per sostituire quella esistente
- **Anteprima nuova foto**: Preview dell'immagine selezionata per la sostituzione

### Pagina Lista Scontrini (`lista.php`)
- **Colonna foto**: Miniature 50x50px
- **Indicatore "Nessuna foto"**: Per scontrini senza foto
- **Link visualizzazione**: Clic su miniatura per vedere foto completa

### Visualizzazione Foto (`view_photo.php`)
- **Controllo accessi**: Verifica permessi utente per lo scontrino
- **Sicurezza**: Protezione da path traversal e accesso non autorizzato
- **Cache**: Header di cache per ottimizzare le prestazioni
- **Miniature**: Generazione miniature al volo quando richieste

## Sicurezza

### Controlli di Accesso
- Solo utenti autenticati possono caricare e vedere foto
- Utenti normali vedono solo foto dei propri scontrini
- Responsabili vedono foto della propria filiale
- Admin vedono tutte le foto

### Validazione File
- Controllo tipo MIME e estensione
- Verifica che sia un'immagine valida con `getimagesize()`
- Protezione contro upload di file eseguibili
- Rinominazione automatica per evitare conflitti

### Protezione Directory
- File `.htaccess` impedisce accesso diretto ai file
- Accesso solo tramite script PHP autenticati
- Verifica path per prevenire directory traversal

## Performance

### Ottimizzazioni
- **Ridimensionamento**: Immagini ridimensionate automaticamente
- **Compressione**: JPEG a 85% di qualità, PNG ottimizzati
- **Cache**: Header di cache per ridurre richieste server
- **Miniature**: Generate al volo e cacheable

### Limitazioni
- Dimensione massima file: 5MB
- Dimensioni massime immagine: 1920x1920px
- Formati supportati limitati a immagini web sicure

## Manutenzione

### Pulizia File
I file foto rimangono sul server anche se lo scontrino viene eliminato. È consigliabile:
1. Implementare script di pulizia periodica per file orfani
2. Backup regolare della directory `uploads/`
3. Monitoraggio dello spazio disco

### Backup
Includere la directory `uploads/scontrini/` nei backup:
```bash
tar -czf backup_foto_$(date +%Y%m%d).tar.gz uploads/scontrini/
```

## Utilizzo

### Per Utenti
1. **Aggiungere foto**: Durante inserimento nuovo scontrino, selezionare file nel campo "Foto Scontrino"
2. **Visualizzare foto**: Cliccare sulla miniatura nella lista per vedere l'immagine completa
3. **Modificare foto**: Nella pagina modifica, rimuovere o sostituire la foto esistente

### Per Amministratori
1. **Monitoraggio spazio**: Controllare periodicamente lo spazio usato in `uploads/`
2. **Backup**: Backup regolare delle foto
3. **Pulizia**: Rimuovere foto di scontrini eliminati se necessario

## Compatibilità Browser
- Chrome/Edge: Supporto completo
- Firefox: Supporto completo  
- Safari: Supporto completo
- IE11+: Supporto base (senza drag & drop)

## Miglioramenti Futuri
- Supporto per più foto per scontrino
- Thumbnails precalcolate per performance migliori
- Compressione WEBP automatica
- Preview PDF per scontrini scansionati
- Watermark con logo aziendale