<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Service.php';

/**
 * Classe ServiceRepository - Repository pour les services
 */
class ServiceRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un service par son ID avec son responsable
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT s.*, u.nom as responsable_nom, statut_param.nom as statut_nom
                                    FROM services s
                                    LEFT JOIN utilisateurs u ON s.responsable_id = u.id
                                    LEFT JOIN parametres statut_param ON s.statut = statut_param.id
                                    WHERE s.id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            $service = new Service($data);
            if (isset($data['statut_nom'])) {
                $service->setStatutNom($data['statut_nom']);
            }
            if (isset($data['responsable_nom'])) {
                $service->setResponsableNom($data['responsable_nom']);
            }
            return $service;
        }
        return null;
    }
    
    /**
     * Trouve un service par son nom
     */
    public function findByNom($nom) {
        $stmt = $this->db->prepare("SELECT * FROM services WHERE nom = :nom");
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        return $data ? new Service($data) : null;
    }
    
    /**
     * Récupère tous les services
     */
    public function findAll() {
        $stmt = $this->db->prepare("SELECT s.*, u.nom as responsable_nom,
                                    statut_param.nom as statut_nom
                                    FROM services s 
                                    LEFT JOIN utilisateurs u ON s.responsable_id = u.id 
                                    LEFT JOIN parametres statut_param ON s.statut = statut_param.id
                                    ORDER BY s.nom ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $service = new Service($data);
            if (isset($data['statut_nom'])) {
                $service->setStatutNom($data['statut_nom']);
            }
            if (isset($data['responsable_nom'])) {
                $service->setResponsableNom($data['responsable_nom']);
            }
            return $service;
        }, $results);
    }
    
    /**
     * Crée un nouveau service
     */
    public function create(Service $service) {
        $sql = "INSERT INTO services (nom, description, responsable_id, statut)
                VALUES (:nom, :description, :responsable_id, :statut)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $service->getNom(),
            ':description' => $service->getDescription(),
            ':responsable_id' => $service->getResponsableId(),
            ':statut' => $service->getStatut() ?? 'actif'
        ]);
        
        $service->setId($this->db->lastInsertId());
        return $service;
    }
    
    /**
     * Met à jour un service
     */
    public function update(Service $service) {
        $sql = "UPDATE services 
                SET nom = :nom, description = :description, responsable_id = :responsable_id, statut = :statut
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $service->getId(),
            ':nom' => $service->getNom(),
            ':description' => $service->getDescription(),
            ':responsable_id' => $service->getResponsableId(),
            ':statut' => $service->getStatut()
        ]);
    }
    
    /**
     * Supprime un service
     */
    public function delete($id) {
        // Vérifier si des utilisateurs utilisent ce service
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM utilisateurs WHERE service_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer ce service car il est utilisé par des utilisateurs');
        }
        
        $stmt = $this->db->prepare("DELETE FROM services WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

