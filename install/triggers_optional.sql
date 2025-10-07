-- Trigger avanzati per il log delle attività
-- Questo file contiene trigger opzionali che migliorano il logging
-- Può essere eseguito manualmente dopo l'installazione

DELIMITER $$

CREATE TRIGGER `tr_scontrini_insert` AFTER INSERT ON `scontrini`
FOR EACH ROW
BEGIN
    INSERT INTO log_attivita (utente_id, azione, descrizione, scontrino_id, ip_address)
    VALUES (NEW.utente_id, 'INSERT', CONCAT('Nuovo scontrino: ', NEW.numero), NEW.id, 
            COALESCE(@user_ip, 'system'));
END$$

CREATE TRIGGER `tr_scontrini_update` AFTER UPDATE ON `scontrini`
FOR EACH ROW
BEGIN
    DECLARE desc_text TEXT DEFAULT '';
    
    IF OLD.stato != NEW.stato THEN
        SET desc_text = CONCAT('Cambio stato: ', OLD.stato, ' → ', NEW.stato);
    END IF;
    
    IF OLD.lordo != NEW.lordo OR OLD.netto != NEW.netto THEN
        SET desc_text = CONCAT(desc_text, IF(desc_text != '', '; ', ''), 
                               'Modifica importi: ', OLD.lordo, '→', NEW.lordo);
    END IF;
    
    IF desc_text != '' THEN
        INSERT INTO log_attivita (utente_id, azione, descrizione, scontrino_id, ip_address)
        VALUES (NEW.utente_id, 'UPDATE', desc_text, NEW.id, 
                COALESCE(@user_ip, 'system'));
    END IF;
END$$

DELIMITER ;

-- Per installare questi trigger manualmente:
-- mysql -h hostname -u username -p database_name < install/triggers_optional.sql