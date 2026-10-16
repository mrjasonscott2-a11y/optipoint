<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Tache.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Classe TacheRepository - Repository pour les tâches
 */
class TacheRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve une tâche par son ID avec le planning
     */
    public function findById($id) {
        $sql = "SELECT t.*, p.description as planning_description, p.date_debut as planning_date,
                       statut_param.nom as statut_nom
                FROM taches t
                LEFT JOIN plannings p ON t.planning_id = p.id
                LEFT JOIN parametres statut_param ON t.statut = statut_param.id
                WHERE t.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $tache = new Tache($data);
        
        if (isset($data['statut_nom'])) {
            $tache->setStatutNom($data['statut_nom']);
        }
        if (isset($data['planning_description'])) {
            $tache->setPlanningDescription($data['planning_description']);
        }
        if (isset($data['planning_date'])) {
            $tache->setPlanningDate($data['planning_date']);
        }
        
        return $tache;
    }
    
    /**
     * Récupère toutes les tâches avec leurs plannings
     */
    public function findAll($filters = []) {
        $sql = "SELECT t.*, p.description as planning_description, p.date_debut as planning_date,
                       statut_param.nom as statut_nom
                FROM taches t
                LEFT JOIN plannings p ON t.planning_id = p.id
                LEFT JOIN parametres statut_param ON t.statut = statut_param.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['planning_id'])) {
            $sql .= " AND t.planning_id = :planning_id";
            $params[':planning_id'] = $filters['planning_id'];
        }
        
        if (isset($filters['utilisateur_id'])) {
            // Filtrer par utilisateur via le planning
            $sql .= " AND p.utilisateur_id = :utilisateur_id";
            $params[':utilisateur_id'] = $filters['utilisateur_id'];
        }
        
        if (isset($filters['statut'])) {
            $sql .= " AND t.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // Retourner les données brutes pour que le mapper puisse utiliser les JOINs
        return $results;
    }
    
    /**
     * Récupère les tâches d'un planning
     */
    public function findByPlanningId($planningId) {
        return $this->findAll(['planning_id' => $planningId]);
    }
    
    /**
     * Récupère les tâches d'un utilisateur (via le planning)
     */
    public function findByUtilisateurId($utilisateurId) {
        return $this->findAll(['utilisateur_id' => $utilisateurId]);
    }
    
    /**
     * Crée une nouvelle tâche
     */
    public function create(Tache $tache) {
        $sql = "INSERT INTO taches (nom, description, planning_id, statut)
                VALUES (:nom, :description, :planning_id, :statut)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $tache->getNom(),
            ':description' => $tache->getDescription(),
            ':planning_id' => $tache->getPlanningId(),
            ':statut' => $tache->getStatut()
        ]);
        
        $tache->setId($this->db->lastInsertId());
        return $tache;
    }
    
    /**
     * Met à jour une tâche
     */
    public function update(Tache $tache) {
        $sql = "UPDATE taches 
                SET nom = :nom, description = :description, planning_id = :planning_id, statut = :statut
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $tache->getId(),
            ':nom' => $tache->getNom(),
            ':description' => $tache->getDescription(),
            ':planning_id' => $tache->getPlanningId(),
            ':statut' => $tache->getStatut()
        ]);
    }
    
    /**
     * Supprime une tâche
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM taches WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

