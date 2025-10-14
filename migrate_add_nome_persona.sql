-- Migrazione per aggiungere campo nome_persona alla tabella scontrini
-- Risolve il problema del constraint UNIQUE sul campo numero

-- Step 1: Aggiungi il campo nome_persona
ALTER TABLE `scontrini` ADD COLUMN `nome_persona` VARCHAR(100) DEFAULT NULL AFTER `numero`;

-- Step 2: Copia i dati dal campo numero al campo nome_persona per i record esistenti
-- (solo se il numero non è un vero numero progressivo)
UPDATE `scontrini` 
SET `nome_persona` = `numero` 
WHERE `numero` NOT REGEXP '^[0-9]+$';

-- Step 3: Genera numeri progressivi univoci per tutti gli scontrini
-- Crea una numerazione basata su ID per garantire unicità
UPDATE `scontrini` 
SET `numero` = CONCAT('SC', LPAD(id, 6, '0'))
WHERE 1=1;

-- Step 4: Aggiungi indice per il campo nome_persona per performance
CREATE INDEX `idx_nome_persona` ON `scontrini` (`nome_persona`);

-- Step 5: Aggiorna l'indice di ricerca per includere nome_persona
DROP INDEX IF EXISTS `idx_scontrini_search`;
CREATE INDEX `idx_scontrini_search` ON `scontrini` (`numero`, `nome_persona`, `data`, `stato`);

-- Nota: Il constraint UNIQUE su numero rimane per garantire che ogni scontrino 
-- abbia un numero univoco, ma ora nome_persona può avere duplicati