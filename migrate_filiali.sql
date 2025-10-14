-- Migrazione per sistema filiali
-- 1. Crea tabella filiali
CREATE TABLE IF NOT EXISTS filiali (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    indirizzo TEXT,
    telefono VARCHAR(20),
    responsabile_id INT,
    attiva TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Modifica tabella utenti - cambia enum ruoli
ALTER TABLE utenti 
MODIFY COLUMN ruolo ENUM('admin', 'responsabile', 'utente') DEFAULT 'utente';

-- 3. Aggiungi colonna filiale_id a utenti
ALTER TABLE utenti 
ADD COLUMN filiale_id INT;

-- 4. Aggiungi colonne a scontrini
ALTER TABLE scontrini 
ADD COLUMN utente_id INT,
ADD COLUMN filiale_id INT;

-- 5. Crea filiali di esempio
INSERT IGNORE INTO filiali (nome, indirizzo, telefono) VALUES 
('Sede Centrale', 'Via Roma 1, Milano', '02-1234567'),
('Filiale Nord', 'Via Garibaldi 10, Torino', '011-7654321'),
('Filiale Sud', 'Via Dante 5, Napoli', '081-9876543');

-- 6. Crea utenti di esempio
INSERT IGNORE INTO utenti (username, password, nome, ruolo, filiale_id) VALUES 
('admin_sede', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Sede', 'admin', 1),
('resp_nord', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario Bianchi', 'responsabile', 2),
('resp_sud', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Verdi', 'responsabile', 3),
('user_nord1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca Rossi', 'utente', 2),
('user_nord2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sara Neri', 'utente', 2),
('user_sud1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giuseppe Romano', 'utente', 3);

-- 7. Aggiorna responsabili filiali
UPDATE filiali SET responsabile_id = (SELECT id FROM utenti WHERE username = 'admin_sede') WHERE nome = 'Sede Centrale';
UPDATE filiali SET responsabile_id = (SELECT id FROM utenti WHERE username = 'resp_nord') WHERE nome = 'Filiale Nord';
UPDATE filiali SET responsabile_id = (SELECT id FROM utenti WHERE username = 'resp_sud') WHERE nome = 'Filiale Sud';

-- 8. Aggiungi le foreign key dopo aver popolato i dati
ALTER TABLE utenti ADD FOREIGN KEY (filiale_id) REFERENCES filiali(id) ON DELETE SET NULL;
ALTER TABLE filiali ADD FOREIGN KEY (responsabile_id) REFERENCES utenti(id) ON DELETE SET NULL;
ALTER TABLE scontrini ADD FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL;
ALTER TABLE scontrini ADD FOREIGN KEY (filiale_id) REFERENCES filiali(id) ON DELETE SET NULL;