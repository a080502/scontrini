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
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
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
            // Tabella utenti
            $this->query("
                CREATE TABLE IF NOT EXISTS utenti (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    nome VARCHAR(100) NOT NULL,
                    ruolo ENUM('admin', 'user') DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Tabella scontrini
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
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Crea utente admin di default se non esiste
            $admin_exists = $this->fetchOne("SELECT id FROM utenti WHERE username = ?", ['admin']);
            if (!$admin_exists) {
                $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
                $this->query("
                    INSERT INTO utenti (username, password, nome, ruolo) 
                    VALUES (?, ?, ?, ?)
                ", ['admin', $password_hash, 'Amministratore', 'admin']);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Errore inizializzazione database: " . $e->getMessage());
        }
    }
}
?>