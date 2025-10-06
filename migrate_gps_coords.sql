-- Migrazione per aggiungere supporto coordinate GPS agli scontrini
-- Eseguire questo script per aggiungere la funzionalità GPS

-- Aggiunge colonne per memorizzare le coordinate GPS
ALTER TABLE scontrini 
ADD COLUMN gps_latitude DECIMAL(10, 7) NULL AFTER foto_size,
ADD COLUMN gps_longitude DECIMAL(10, 7) NULL AFTER gps_latitude,
ADD COLUMN gps_accuracy DECIMAL(8, 2) NULL AFTER gps_longitude,
ADD COLUMN gps_timestamp DATETIME NULL AFTER gps_accuracy;

-- Indice per ottimizzare le query geografiche
CREATE INDEX idx_scontrini_gps ON scontrini(gps_latitude, gps_longitude);