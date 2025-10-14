-- Schema del database per il Sistema Gestione Scontrini
-- Generato automaticamente durante l'installazione

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Struttura della tabella `filiali`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `filiali` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `indirizzo` text,
  `telefono` varchar(20),
  `responsabile_id` int(11) DEFAULT NULL,
  `attiva` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  KEY `idx_attiva` (`attiva`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struttura della tabella `utenti`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `utenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ruolo` enum('admin','utente') DEFAULT 'utente',
  `filiale_id` int(11) DEFAULT NULL,
  `attivo` tinyint(1) DEFAULT 1,
  `ultimo_accesso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_attivo` (`attivo`),
  KEY `idx_ruolo` (`ruolo`),
  KEY `fk_utenti_filiale` (`filiale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struttura della tabella `scontrini`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `scontrini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) NOT NULL,
  `nome_persona` varchar(100) DEFAULT NULL,
  `data` date NOT NULL,
  `lordo` decimal(10,2) NOT NULL,
  `netto` decimal(10,2) NOT NULL,
  `da_versare` decimal(10,2) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `gps_coords` varchar(100) DEFAULT NULL,
  `filiale_id` int(11) DEFAULT NULL,
  `utente_id` int(11) DEFAULT NULL,
  `stato` enum('attivo','incassato','versato','archiviato') DEFAULT 'attivo',
  `data_incasso` timestamp NULL DEFAULT NULL,
  `data_versamento` timestamp NULL DEFAULT NULL,
  `data_archiviazione` timestamp NULL DEFAULT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  KEY `idx_data` (`data`),
  KEY `idx_stato` (`stato`),
  KEY `idx_filiale` (`filiale_id`),
  KEY `idx_utente` (`utente_id`),
  KEY `idx_nome_persona` (`nome_persona`),
  KEY `idx_data_stato` (`data`, `stato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struttura della tabella `log_attivita`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `log_attivita` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utente_id` int(11) DEFAULT NULL,
  `azione` varchar(100) NOT NULL,
  `descrizione` text,
  `scontrino_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_utente` (`utente_id`),
  KEY `idx_azione` (`azione`),
  KEY `idx_scontrino` (`scontrino_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struttura della tabella `sessioni`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sessioni` (
  `id` varchar(128) NOT NULL,
  `utente_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `data_scadenza` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_utente` (`utente_id`),
  KEY `idx_scadenza` (`data_scadenza`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Aggiunta delle chiavi esterne
-- --------------------------------------------------------

ALTER TABLE `filiali`
  ADD CONSTRAINT `fk_filiali_responsabile` FOREIGN KEY (`responsabile_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `utenti`
  ADD CONSTRAINT `fk_utenti_filiale` FOREIGN KEY (`filiale_id`) REFERENCES `filiali` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `scontrini`
  ADD CONSTRAINT `fk_scontrini_filiale` FOREIGN KEY (`filiale_id`) REFERENCES `filiali` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scontrini_utente` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `log_attivita`
  ADD CONSTRAINT `fk_log_utente` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_log_scontrino` FOREIGN KEY (`scontrino_id`) REFERENCES `scontrini` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `sessioni`
  ADD CONSTRAINT `fk_sessioni_utente` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Indici per ottimizzazione
-- --------------------------------------------------------

CREATE INDEX `idx_scontrini_search` ON `scontrini` (`numero`, `nome_persona`, `data`, `stato`);
CREATE INDEX `idx_log_search` ON `log_attivita` (`utente_id`, `azione`, `created_at`);

COMMIT;