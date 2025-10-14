-- Migrazione aggiuntiva: Aggiunge colonna 'attivo' alla tabella utenti
-- Esegui questo DOPO migrate_filiali.sql se vuoi la funzionalità utenti attivi/disattivi

-- Aggiungi colonna attivo alla tabella utenti (default 1 = attivo)
ALTER TABLE utenti 
ADD COLUMN attivo TINYINT(1) DEFAULT 1;

-- Aggiorna tutti gli utenti esistenti come attivi
UPDATE utenti SET attivo = 1;