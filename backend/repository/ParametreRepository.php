<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Parametre.php';
require_once __DIR__ . '/../models/TypeParametre.php';

/**
 * Classe ParametreRepository - Repository pour les paramètres
 */
class ParametreRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un paramètre par son ID avec son type
     */
    public function findById($id) {
        $sql = "SELECT p.*, tp.nom as type_parametre_nom, tp.description as type_parametre_description
                FROM parametres p
                LEFT JOIN type_parametres tp ON p.type_parametre_id = tp.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $parametre = new Parametre($data);
        
        // Charger le type de paramètre si disponible
        if (isset($data['type_parametre_nom'])) {
            $typeParametre = new TypeParametre([
                'id' => $data['type_parametre_id'],
                'nom' => $data['type_parametre_nom'],
                'description' => $data['type_parametre_description'] ?? null
            ]);
            $parametre->setTypeParametre($typeParametre);
        }
        
        return $parametre;
    }
    
    /**
     * Trouve un paramètre par son nom
     */
    public function findByNom($nom) {
        $sql = "SELECT p.*, tp.nom as type_parametre_nom, tp.description as type_parametre_description
                FROM parametres p
                LEFT JOIN type_parametres tp ON p.type_parametre_id = tp.id
                WHERE p.nom = :nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $parametre = new Parametre($data);
        
        // Charger le type de paramètre si disponible
        if (isset($data['type_parametre_nom'])) {
            $typeParametre = new TypeParametre([
                'id' => $data['type_parametre_id'],
                'nom' => $data['type_parametre_nom'],
                'description' => $data['type_parametre_description'] ?? null
            ]);
            $parametre->setTypeParametre($typeParametre);
        }
        
        return $parametre;
    }

    
    
    /**
     * Trouve un paramètre par son code
     */
    public function findByCode($code) {
        $sql = "SELECT p.*, tp.nom as type_parametre_nom, tp.code as type_parametre_code
                FROM parametres p
                LEFT JOIN type_parametres tp ON p.type_parametre_id = tp.id
                WHERE p.code = :code";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => $code]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $parametre = new Parametre($data);
        if (isset($data['type_parametre_nom'])) {
            $typeParametre = new TypeParametre([
                'id' => $data['type_parametre_id'],
                'nom' => $data['type_parametre_nom'],
                'code' => $data['type_parametre_code'] ?? null
            ]);
            $parametre->setTypeParametre($typeParametre);
        }
        
        return $parametre;
    }
    
    /**
     * Trouve un paramètre par nom et type (pour conversion nom -> ID)
     */
    public function findByNomAndType($nom, $typeParametreCode) {
        $sql = "SELECT p.*, tp.nom as type_parametre_nom, tp.code as type_parametre_code
                FROM parametres p
                LEFT JOIN type_parametres tp ON p.type_parametre_id = tp.id
                WHERE p.nom = :nom AND tp.code = :type_code";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nom' => $nom, ':type_code' => $typeParametreCode]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $parametre = new Parametre($data);
        if (isset($data['type_parametre_nom'])) {
            $typeParametre = new TypeParametre([
                'id' => $data['type_parametre_id'],
                'nom' => $data['type_parametre_nom'],
                'code' => $data['type_parametre_code'] ?? null
            ]);
            $parametre->setTypeParametre($typeParametre);
        }
        
        return $parametre;
    }
    
    /**
     * Récupère tous les paramètres avec leurs types
     */
    public function findAll($filters = []) {
        $sql = "SELECT p.*, tp.nom as type_parametre_nom, tp.description as type_parametre_description
                FROM parametres p
                LEFT JOIN type_parametres tp ON p.type_parametre_id = tp.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['type_parametre_id'])) {
            $sql .= " AND p.type_parametre_id = :type_parametre_id";
            $params[':type_parametre_id'] = $filters['type_parametre_id'];
        }
        
        $sql .= " ORDER BY tp.nom ASC, p.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $parametre = new Parametre($data);
            
            // Charger le type de paramètre si disponible
            if (isset($data['type_parametre_nom'])) {
                $typeParametre = new TypeParametre([
                    'id' => $data['type_parametre_id'],
                    'nom' => $data['type_parametre_nom'],
                    'description' => $data['type_parametre_description'] ?? null
                ]);
                $parametre->setTypeParametre($typeParametre);
            }
            
            return $parametre;
        }, $results);
    }
    
    /**
     * Récupère les paramètres par type
     */
    public function findByTypeParametreId($typeParametreId) {
        $sql = "SELECT p.*, tp.nom as type_parametre_nom, tp.description as type_parametre_description
                FROM parametres p
                LEFT JOIN type_parametres tp ON p.type_parametre_id = tp.id
                WHERE p.type_parametre_id = :type_parametre_id
                ORDER BY p.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':type_parametre_id' => $typeParametreId]);
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $parametre = new Parametre($data);
            
            // Charger le type de paramètre si disponible
            if (isset($data['type_parametre_nom'])) {
                $typeParametre = new TypeParametre([
                    'id' => $data['type_parametre_id'],
                    'nom' => $data['type_parametre_nom'],
                    'description' => $data['type_parametre_description'] ?? null
                ]);
                $parametre->setTypeParametre($typeParametre);
            }
            
            return $parametre;
        }, $results);
    }
    
    /**
     * Crée un nouveau paramètre
     */
    public function create(Parametre $parametre) {
        $sql = "INSERT INTO parametres (code, nom, description, type_parametre_id)
                VALUES (:code, :nom, :description, :type_parametre_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':code' => $parametre->getCode(),
            ':nom' => $parametre->getNom(),
            ':description' => $parametre->getDescription(),
            ':type_parametre_id' => $parametre->getTypeParametreId()
        ]);
        
        $parametre->setId($this->db->lastInsertId());
        return $parametre;
    }
    
    /**
     * Met à jour un paramètre
     */
    public function update(Parametre $parametre) {
        $sql = "UPDATE parametres 
                SET code = :code, nom = :nom, description = :description, type_parametre_id = :type_parametre_id
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $parametre->getId(),
            ':code' => $parametre->getCode(),
            ':nom' => $parametre->getNom(),
            ':description' => $parametre->getDescription(),
            ':type_parametre_id' => $parametre->getTypeParametreId()
        ]);
    }
    
    /**
     * Supprime un paramètre
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM parametres WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

