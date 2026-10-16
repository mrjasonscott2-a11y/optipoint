<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Jour.php';

/**
 * Classe JourRepository - Repository pour les jours
 */
class JourRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un jour par son ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM jours WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        return $data ? new Jour($data) : null;
    }
    
    /**
     * Trouve un jour par son nom
     */
    public function findByNom($nom) {
        $stmt = $this->db->prepare("SELECT * FROM jours WHERE nom = :nom");
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        return $data ? new Jour($data) : null;
    }
    
    /**
     * Récupère tous les jours
     */
    public function findAll() {
        $stmt = $this->db->prepare("SELECT * FROM jours ORDER BY id ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            return new Jour($data);
        }, $results);
    }
    
    /**
     * Crée un nouveau jour
     */
    public function create(Jour $jour) {
        $sql = "INSERT INTO jours (nom)
                VALUES (:nom)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $jour->getNom()
        ]);
        
        $jour->setId($this->db->lastInsertId());
        return $jour;
    }
    
    /**
     * Met à jour un jour
     */
    public function update(Jour $jour) {
        $sql = "UPDATE jours 
                SET nom = :nom
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $jour->getId(),
            ':nom' => $jour->getNom()
        ]);
    }
    
    /**
     * Supprime un jour
     */
    public function delete($id) {
        // Vérifier si des plannings utilisent ce jour
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM plannings WHERE jour_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer ce jour car il est utilisé par des plannings');
        }
        
        $stmt = $this->db->prepare("DELETE FROM jours WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

