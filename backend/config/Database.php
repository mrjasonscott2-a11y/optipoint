<?php
/**
 * Classe Database - Gestion de la connexion à la base de données
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    // Configuration de la base de données
    private const HOST = "localhost";
    private const DBNAME = "optipoint_db3";
    private const USERNAME = "root";
    private const PASSWORD = "mysql";
    private const CHARSET = "utf8";
    
    /**
     * Constructeur privé pour implémenter le pattern Singleton
     */
    private function __construct() {
        try {
            // Configuration MySQL (commentée)
            $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DBNAME . ";charset=" . self::CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Configuration PostgreSQL
            // $dsn = "pgsql:host=" . self::HOST . ";port=5432;dbname=" . self::DBNAME;
            // $options = [
            //     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            //     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            //     PDO::ATTR_EMULATE_PREPARES => false
            // ];
            
            $this->pdo = new PDO($dsn, self::USERNAME, self::PASSWORD, $options);
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère l'instance unique de la classe (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère l'objet PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}
    
    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

