-- Migrazione per aggiungere supporto foto agli scontrini
-- Eseguire questo script per aggiungere la funzionalità di allegare foto

-- Aggiunge colonna per memorizzare il percorso della foto dello scontrino
ALTER TABLE scontrini 
ADD COLUMN foto_scontrino VARCHAR(255) NULL AFTER note,
ADD COLUMN foto_mime_type VARCHAR(50) NULL AFTER foto_scontrino,
ADD COLUMN foto_size INT NULL AFTER foto_mime_type;

-- Indice per ottimizzare le query che cercano scontrini con foto
CREATE INDEX idx_scontrini_foto ON scontrini(foto_scontrino);