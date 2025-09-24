<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Imposta charset UTF-8
            $this->connection->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            die("Errore connessione database: " . $e->getMessage());
        }
        
        // Esegui migrazioni automatiche
        $this->runMigrations();
    }
    
    private function runMigrations() {
        try {
            // Migrazione: Aggiungi colonna da_versare se non esiste
            $columns = $this->query("SHOW COLUMNS FROM scontrini LIKE 'da_versare'");
            if (empty($columns)) {
                $this->query("
                    ALTER TABLE scontrini 
                    ADD COLUMN da_versare DECIMAL(10,2) DEFAULT NULL 
                    AFTER lordo
                ");
            }
        } catch (Exception $e) {
            // Ignora errori di migrazione se la tabella non esiste ancora
            // (es. durante il primo setup)
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Errore query database: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function initializeDatabase() {
        try {
            // 1. Tabella filiali (SENZA foreign key inizialmente)
            $this->query("
                CREATE TABLE IF NOT EXISTS filiali (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL UNIQUE,
                    indirizzo TEXT,
                    telefono VARCHAR(20),
                    responsabile_id INT,
                    attiva TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            // 2. Tabella utenti (con riferimento a filiali)
            $this->query("
                CREATE TABLE IF NOT EXISTS utenti (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    nome VARCHAR(100) NOT NULL,
                    ruolo ENUM('admin', 'responsabile', 'utente') DEFAULT 'utente',
                    filiale_id INT,
                    attivo TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            // 3. Tabella scontrini (con riferimenti a utenti e filiali)
            $this->query("
                CREATE TABLE IF NOT EXISTS scontrini (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(200) NOT NULL,
                    data_scontrino DATE NOT NULL,
                    lordo DECIMAL(10,2) NOT NULL,
                    da_versare DECIMAL(10,2) DEFAULT NULL,
                    incassato BOOLEAN DEFAULT FALSE,
                    versato BOOLEAN DEFAULT FALSE,
                    data_incasso DATETIME NULL,
                    data_versamento DATETIME NULL,
                    archiviato BOOLEAN DEFAULT FALSE,
                    data_archiviazione DATETIME NULL,
                    note TEXT NULL,
                    utente_id INT,
                    filiale_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
            ");
            
            // 4. Crea filiali di default se non esistono
            $sede_centrale = $this->fetchOne("SELECT id FROM filiali WHERE nome = ?", ['Sede Centrale']);
            if (!$sede_centrale) {
                // Filiali predefinite
                $filiali_default = [
                    ['nome' => 'Sede Centrale', 'indirizzo' => 'Via Roma 1, Milano', 'telefono' => '02-1234567'],
                    ['nome' => 'Filiale Nord', 'indirizzo' => 'Via Garibaldi 10, Torino', 'telefono' => '011-7654321'],
                    ['nome' => 'Filiale Sud', 'indirizzo' => 'Via Dante 5, Napoli', 'telefono' => '081-9876543']
                ];
                
                foreach ($filiali_default as $filiale) {
                    $this->query("
                        INSERT INTO filiali (nome, indirizzo, telefono) 
                        VALUES (?, ?, ?)
                    ", [$filiale['nome'], $filiale['indirizzo'], $filiale['telefono']]);
                }
            }
            
            // 5. Crea utenti di esempio se non esistono
            $admin_exists = $this->fetchOne("SELECT id FROM utenti WHERE username = ?", ['admin_sede']);
            if (!$admin_exists) {
                $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
                
                // Ottieni ID filiali
                $sede_id = $this->fetchOne("SELECT id FROM filiali WHERE nome = ?", ['Sede Centrale'])['id'];
                $nord_id = $this->fetchOne("SELECT id FROM filiali WHERE nome = ?", ['Filiale Nord'])['id'];
                $sud_id = $this->fetchOne("SELECT id FROM filiali WHERE nome = ?", ['Filiale Sud'])['id'];
                
                // Utenti predefiniti
                $utenti_default = [
                    [
                        'username' => 'admin_sede',
                        'password' => $password_hash,
                        'nome' => 'Admin Sede Centrale',
                        'ruolo' => 'admin',
                        'filiale_id' => $sede_id
                    ],
                    [
                        'username' => 'resp_nord',
                        'password' => $password_hash,
                        'nome' => 'Mario Bianchi',
                        'ruolo' => 'responsabile',
                        'filiale_id' => $nord_id
                    ],
                    [
                        'username' => 'resp_sud',
                        'password' => $password_hash,
                        'nome' => 'Anna Verdi',
                        'ruolo' => 'responsabile',
                        'filiale_id' => $sud_id
                    ],
                    [
                        'username' => 'user_nord1',
                        'password' => $password_hash,
                        'nome' => 'Luca Rossi',
                        'ruolo' => 'utente',
                        'filiale_id' => $nord_id
                    ],
                    [
                        'username' => 'user_sud1',
                        'password' => $password_hash,
                        'nome' => 'Giuseppe Romano',
                        'ruolo' => 'utente',
                        'filiale_id' => $sud_id
                    ]
                ];
                
                foreach ($utenti_default as $utente) {
                    $this->query("
                        INSERT INTO utenti (username, password, nome, ruolo, filiale_id) 
                        VALUES (?, ?, ?, ?, ?)
                    ", [$utente['username'], $utente['password'], $utente['nome'], $utente['ruolo'], $utente['filiale_id']]);
                }
                
                // 6. Aggiorna responsabili delle filiali (dopo aver creato gli utenti)
                $admin_id = $this->fetchOne("SELECT id FROM utenti WHERE username = ?", ['admin_sede'])['id'];
                $resp_nord_id = $this->fetchOne("SELECT id FROM utenti WHERE username = ?", ['resp_nord'])['id'];
                $resp_sud_id = $this->fetchOne("SELECT id FROM utenti WHERE username = ?", ['resp_sud'])['id'];
                
                $this->query("UPDATE filiali SET responsabile_id = ? WHERE nome = ?", [$admin_id, 'Sede Centrale']);
                $this->query("UPDATE filiali SET responsabile_id = ? WHERE nome = ?", [$resp_nord_id, 'Filiale Nord']);
                $this->query("UPDATE filiali SET responsabile_id = ? WHERE nome = ?", [$resp_sud_id, 'Filiale Sud']);
            }
            
            // 7. Aggiungi le foreign key DOPO aver popolato i dati (se non esistono già)
            try {
                $this->query("ALTER TABLE utenti ADD CONSTRAINT fk_utenti_filiale FOREIGN KEY (filiale_id) REFERENCES filiali(id) ON DELETE SET NULL");
            } catch (Exception $e) {
                // Ignora se la constraint esiste già
            }
            
            try {
                $this->query("ALTER TABLE filiali ADD CONSTRAINT fk_filiali_responsabile FOREIGN KEY (responsabile_id) REFERENCES utenti(id) ON DELETE SET NULL");
            } catch (Exception $e) {
                // Ignora se la constraint esiste già
            }
            
            try {
                $this->query("ALTER TABLE scontrini ADD CONSTRAINT fk_scontrini_utente FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL");
            } catch (Exception $e) {
                // Ignora se la constraint esiste già
            }
            
            try {
                $this->query("ALTER TABLE scontrini ADD CONSTRAINT fk_scontrini_filiale FOREIGN KEY (filiale_id) REFERENCES filiali(id) ON DELETE SET NULL");
            } catch (Exception $e) {
                // Ignora se la constraint esiste già
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Errore inizializzazione database: " . $e->getMessage());
        }
    }
}
?>