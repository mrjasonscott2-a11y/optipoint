<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Habilitation.php';

/**
 * Classe HabilitationRepository - Repository pour les habilitations
 */
class HabilitationRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve une habilitation par son ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM habilitations WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        return $data ? new Habilitation($data) : null;
    }
    
    /**
     * Trouve une habilitation par son nom
     */
    public function findByNom($nom) {
        $stmt = $this->db->prepare("SELECT * FROM habilitations WHERE nom = :nom");
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        return $data ? new Habilitation($data) : null;
    }
    
    /**
     * Récupère toutes les habilitations
     */
    public function findAll() {
        $stmt = $this->db->prepare("SELECT * FROM habilitations ORDER BY nom ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            return new Habilitation($data);
        }, $results);
    }
    
    /**
     * Récupère les habilitations d'un profil
     */
    public function findByProfilId($profilId) {
        $sql = "SELECT h.* 
                FROM habilitations h
                INNER JOIN profil_habilitations ph ON h.id = ph.habilitation_id
                WHERE ph.profil_id = :profil_id
                ORDER BY h.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':profil_id' => $profilId]);
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            return new Habilitation($data);
        }, $results);
    }
    
    /**
     * Crée une nouvelle habilitation
     */
    public function create(Habilitation $habilitation) {
        $sql = "INSERT INTO habilitations (nom, description)
                VALUES (:nom, :description)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $habilitation->getNom(),
            ':description' => $habilitation->getDescription()
        ]);
        
        $habilitation->setId($this->db->lastInsertId());
        return $habilitation;
    }
    
    /**
     * Met à jour une habilitation
     */
    public function update(Habilitation $habilitation) {
        $sql = "UPDATE habilitations 
                SET nom = :nom, description = :description
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $habilitation->getId(),
            ':nom' => $habilitation->getNom(),
            ':description' => $habilitation->getDescription()
        ]);
    }
    
    /**
     * Supprime une habilitation
     */
    public function delete($id) {
        // Vérifier si des profils utilisent cette habilitation
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM profil_habilitations WHERE habilitation_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer cette habilitation car elle est utilisée par des profils');
        }
        
        $stmt = $this->db->prepare("DELETE FROM habilitations WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Associe des habilitations à un profil (many-to-many)
     */
    public function attachToProfil($profilId, $habilitationIds) {
        // Supprimer les associations existantes
        $this->detachFromProfil($profilId);
        
        // Ajouter les nouvelles associations
        if (!empty($habilitationIds)) {
            $sql = "INSERT INTO profil_habilitations (profil_id, habilitation_id) VALUES ";
            $values = [];
            $params = [];
            
            foreach ($habilitationIds as $index => $habilitationId) {
                $values[] = "(:profil_id, :habilitation_id_$index)";
                $params[":habilitation_id_$index"] = $habilitationId;
            }
            
            $sql .= implode(', ', $values);
            $params[':profil_id'] = $profilId;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }
        
        return true;
    }
    
    /**
     * Retire toutes les habilitations d'un profil
     */
    public function detachFromProfil($profilId) {
        $stmt = $this->db->prepare("DELETE FROM profil_habilitations WHERE profil_id = :profil_id");
        return $stmt->execute([':profil_id' => $profilId]);
    }
}

