<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/TypeParametre.php';

/**
 * Classe TypeParametreRepository - Repository pour les types de paramètres
 */
class TypeParametreRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un type de paramètre par son ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM type_parametres WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        return $data ? new TypeParametre($data) : null;
    }
    
    /**
     * Trouve un type de paramètre par son nom
     */
    public function findByNom($nom) {
        $stmt = $this->db->prepare("SELECT * FROM type_parametres WHERE nom = :nom");
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        return $data ? new TypeParametre($data) : null;
    }

    /**
     * Trouve un type de paramètre par son code
     */
    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM type_parametres WHERE code = :code");
        $stmt->execute([':code' => $code]);
        $data = $stmt->fetch();
        return $data ? new TypeParametre($data) : null;
    }
    
    /**
     * Récupère tous les types de paramètres
     */
    public function findAll() {
        $stmt = $this->db->prepare("SELECT * FROM type_parametres ORDER BY nom ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            return new TypeParametre($data);
        }, $results);
    }
    
    /**
     * Crée un nouveau type de paramètre
     */
    public function create(TypeParametre $typeParametre) {
        $sql = "INSERT INTO type_parametres (nom, code, description)
                VALUES (:nom, :code, :description)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $typeParametre->getNom(),
            ':code' => $typeParametre->getCode(),
            ':description' => $typeParametre->getDescription()
        ]);
        
        $typeParametre->setId($this->db->lastInsertId());
        return $typeParametre;
    }
    
    /**
     * Met à jour un type de paramètre
     */
    public function update(TypeParametre $typeParametre) {
        $sql = "UPDATE type_parametres 
                SET nom = :nom, code = :code, description = :description
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $typeParametre->getId(),
            ':nom' => $typeParametre->getNom(),
            ':code' => $typeParametre->getCode(),
            ':description' => $typeParametre->getDescription()
        ]);
    }
    
    /**
     * Supprime un type de paramètre
     */
    public function delete($id) {
        // Vérifier si des paramètres utilisent ce type
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM parametres WHERE type_parametre_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer ce type de paramètre car il est utilisé par des paramètres');
        }
        
        $stmt = $this->db->prepare("DELETE FROM type_parametres WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

