<?php
require_once 'includes/database.php';

echo "Inizio migrazione per sistema filiali...\n";

$db = Database::getInstance();

try {
    // 1. Crea tabella filiali
    $db->query("
        CREATE TABLE IF NOT EXISTS filiali (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL UNIQUE,
            indirizzo TEXT,
            telefono VARCHAR(20),
            responsabile_id INT,
            attiva TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (responsabile_id) REFERENCES utenti(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    echo "✓ Tabella filiali creata\n";

    // 2. Modifica tabella utenti - aggiungi filiale_id e nuovo ruolo
    $db->query("
        ALTER TABLE utenti 
        MODIFY COLUMN ruolo ENUM('admin', 'responsabile', 'utente') DEFAULT 'utente'
    ");
    echo "✓ Ruoli utenti aggiornati\n";

    $db->query("
        ALTER TABLE utenti 
        ADD COLUMN filiale_id INT,
        ADD FOREIGN KEY (filiale_id) REFERENCES filiali(id) ON DELETE SET NULL
    ");
    echo "✓ Colonna filiale_id aggiunta a utenti\n";

    // 3. Modifica tabella scontrini - aggiungi utente_id e filiale_id
    $db->query("
        ALTER TABLE scontrini 
        ADD COLUMN utente_id INT,
        ADD COLUMN filiale_id INT,
        ADD FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL,
        ADD FOREIGN KEY (filiale_id) REFERENCES filiali(id) ON DELETE SET NULL
    ");
    echo "✓ Colonne utente_id e filiale_id aggiunte a scontrini\n";

    // 4. Crea filiali di esempio
    $filiali_esempio = [
        ['nome' => 'Sede Centrale', 'indirizzo' => 'Via Roma 1, Milano', 'telefono' => '02-1234567'],
        ['nome' => 'Filiale Nord', 'indirizzo' => 'Via Garibaldi 10, Torino', 'telefono' => '011-7654321'],
        ['nome' => 'Filiale Sud', 'indirizzo' => 'Via Dante 5, Napoli', 'telefono' => '081-9876543']
    ];

    foreach ($filiali_esempio as $filiale) {
        $db->query("
            INSERT IGNORE INTO filiali (nome, indirizzo, telefono) 
            VALUES (?, ?, ?)
        ", [$filiale['nome'], $filiale['indirizzo'], $filiale['telefono']]);
    }
    echo "✓ Filiali di esempio create\n";

    // 5. Crea utenti di esempio per ogni ruolo
    $utenti_esempio = [
        [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'nome' => 'Amministratore Sistema',
            'ruolo' => 'admin',
            'filiale_id' => 1
        ],
        [
            'username' => 'resp_nord',
            'password' => password_hash('resp123', PASSWORD_DEFAULT),
            'nome' => 'Mario Bianchi',
            'ruolo' => 'responsabile',
            'filiale_id' => 2
        ],
        [
            'username' => 'resp_sud',
            'password' => password_hash('resp123', PASSWORD_DEFAULT),
            'nome' => 'Anna Verdi',
            'ruolo' => 'responsabile',
            'filiale_id' => 3
        ],
        [
            'username' => 'user_nord1',
            'password' => password_hash('user123', PASSWORD_DEFAULT),
            'nome' => 'Luca Rossi',
            'ruolo' => 'utente',
            'filiale_id' => 2
        ],
        [
            'username' => 'user_nord2',
            'password' => password_hash('user123', PASSWORD_DEFAULT),
            'nome' => 'Sara Neri',
            'ruolo' => 'utente',
            'filiale_id' => 2
        ],
        [
            'username' => 'user_sud1',
            'password' => password_hash('user123', PASSWORD_DEFAULT),
            'nome' => 'Giuseppe Romano',
            'ruolo' => 'utente',
            'filiale_id' => 3
        ]
    ];

    foreach ($utenti_esempio as $utente) {
        $db->query("
            INSERT IGNORE INTO utenti (username, password, nome, ruolo, filiale_id) 
            VALUES (?, ?, ?, ?, ?)
        ", [$utente['username'], $utente['password'], $utente['nome'], $utente['ruolo'], $utente['filiale_id']]);
    }
    echo "✓ Utenti di esempio creati\n";

    // 6. Aggiorna i responsabili nelle filiali
    $db->query("UPDATE filiali SET responsabile_id = (SELECT id FROM utenti WHERE username = 'admin') WHERE nome = 'Sede Centrale'");
    $db->query("UPDATE filiali SET responsabile_id = (SELECT id FROM utenti WHERE username = 'resp_nord') WHERE nome = 'Filiale Nord'");
    $db->query("UPDATE filiali SET responsabile_id = (SELECT id FROM utenti WHERE username = 'resp_sud') WHERE nome = 'Filiale Sud'");
    echo "✓ Responsabili filiali assegnati\n";

    // 7. Associa gli scontrini esistenti ad utenti e filiali casuali
    $utenti = $db->query("SELECT id, filiale_id FROM utenti WHERE ruolo = 'utente'");
    $scontrini = $db->query("SELECT id FROM scontrini WHERE utente_id IS NULL");
    
    foreach ($scontrini as $scontrino) {
        $utente_casuale = $utenti[array_rand($utenti)];
        $db->query("
            UPDATE scontrini 
            SET utente_id = ?, filiale_id = ? 
            WHERE id = ?
        ", [$utente_casuale['id'], $utente_casuale['filiale_id'], $scontrino['id']]);
    }
    echo "✓ Scontrini esistenti associati a utenti e filiali\n";

    echo "\n✅ Migrazione completata con successo!\n";
    echo "\nCredenziali di accesso:\n";
    echo "Admin: admin / admin123\n";
    echo "Responsabile Nord: resp_nord / resp123\n";
    echo "Responsabile Sud: resp_sud / resp123\n";
    echo "Utente Nord: user_nord1 / user123\n";
    echo "Utente Sud: user_sud1 / user123\n";

} catch (Exception $e) {
    echo "❌ Errore durante la migrazione: " . $e->getMessage() . "\n";
    exit(1);
}
?>