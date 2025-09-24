-- Script semplificato per aggiungere colonna 'attivo' agli utenti esistenti
-- ATTENZIONE: Esegui SOLO se ricevi errore "Unknown column 'u.attivo'"

-- Aggiungi colonna attivo (ignora errore se già esiste)
ALTER TABLE utenti ADD COLUMN attivo TINYINT(1) DEFAULT 1;

-- Aggiorna tutti gli utenti esistenti come attivi  
UPDATE utenti SET attivo = 1;