-- Tabella per i dettagli degli articoli dello scontrino
CREATE TABLE IF NOT EXISTS `scontrini_dettagli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scontrino_id` int(11) NOT NULL,
  `numero_ordine` int(11) NOT NULL DEFAULT 1,
  `codice_articolo` varchar(50) DEFAULT NULL,
  `descrizione_materiale` text NOT NULL,
  `qta` decimal(10,3) NOT NULL DEFAULT 1.000,
  `prezzo_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prezzo_totale` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scontrino` (`scontrino_id`),
  KEY `idx_numero_ordine` (`scontrino_id`, `numero_ordine`),
  CONSTRAINT `fk_dettagli_scontrino` FOREIGN KEY (`scontrino_id`) REFERENCES `scontrini` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indice per ricerca articoli
CREATE INDEX `idx_codice_articolo` ON `scontrini_dettagli` (`codice_articolo`);
CREATE INDEX `idx_descrizione` ON `scontrini_dettagli` (`descrizione_materiale`(100));