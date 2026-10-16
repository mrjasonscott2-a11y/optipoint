<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Planning.php';

/**
 * Classe PlanningRepository - Repository pour les plannings
 */
class PlanningRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un planning par son ID avec User, Site et Jour
     */
    public function findById($id) {
        $sql = "SELECT p.*, u.nom as utilisateur_nom, u.matricule as utilisateur_matricule,
                       s.nom as site_nom, j.nom as jour_nom
                FROM plannings p
                LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id
                LEFT JOIN sites s ON p.site_id = s.id
                LEFT JOIN jours j ON p.jour_id = j.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $planning = new Planning($data);
        
        if (isset($data['site_nom'])) {
            $planning->setSiteNom($data['site_nom']);
        }
        if (isset($data['jour_nom'])) {
            $planning->setJourNom($data['jour_nom']);
        }
        if (isset($data['utilisateur_nom'])) {
            $planning->setUtilisateurNom($data['utilisateur_nom']);
        }
        
        return $planning;
    }
    
    /**
     * Récupère tous les plannings avec filtres optionnels (avec JOINs User, Site, Jour)
     */
    public function findAll($filters = []) {
        $sql = "SELECT p.*, u.nom as utilisateur_nom, u.matricule as utilisateur_matricule,
                       s.nom as site_nom, j.nom as jour_nom
                FROM plannings p
                LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id
                LEFT JOIN sites s ON p.site_id = s.id
                LEFT JOIN jours j ON p.jour_id = j.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['user_id'])) {
            $sql .= " AND p.utilisateur_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (isset($filters['site_id'])) {
            $sql .= " AND p.site_id = :site_id";
            $params[':site_id'] = $filters['site_id'];
        }
        
        if (isset($filters['jour_id'])) {
            $sql .= " AND p.jour_id = :jour_id";
            $params[':jour_id'] = $filters['jour_id'];
        }
        
        if (isset($filters['date_debut'])) {
            $sql .= " AND p.date_debut >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }
        
        if (isset($filters['date_fin'])) {
            $sql .= " AND p.date_fin <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }
        
        $sql .= " ORDER BY p.date_debut ASC, p.heure_debut ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $planning = new Planning($data);
            if (isset($data['site_nom'])) {
                $planning->setSiteNom($data['site_nom']);
            }
            if (isset($data['jour_nom'])) {
                $planning->setJourNom($data['jour_nom']);
            }
            if (isset($data['utilisateur_nom'])) {
                $planning->setUtilisateurNom($data['utilisateur_nom']);
            }
            return $planning;
        }, $results);
    }
    
    /**
     * Crée un nouveau planning
     */
    public function create(Planning $planning) {
        $sql = "INSERT INTO plannings 
                (utilisateur_id, site_id, jour_id, date_debut, date_fin, heure_debut, heure_fin, description, lieu)
                VALUES (:utilisateur_id, :site_id, :jour_id, :date_debut, :date_fin, :heure_debut, :heure_fin, :description, :lieu)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':utilisateur_id' => $planning->getUtilisateurId(),
            ':site_id' => $planning->getSiteId(),
            ':jour_id' => $planning->getJourId(),
            ':date_debut' => $planning->getDateDebut(),
            ':date_fin' => $planning->getDateFin(),
            ':heure_debut' => $planning->getHeureDebut(),
            ':heure_fin' => $planning->getHeureFin(),
            ':description' => $planning->getDescription(),
            ':lieu' => $planning->getLieu()
        ]);
        
        $planning->setId($this->db->lastInsertId());
        return $planning;
    }
    
    /**
     * Met à jour un planning
     */
    public function update(Planning $planning) {
        $sql = "UPDATE plannings SET 
                utilisateur_id = :utilisateur_id,
                site_id = :site_id,
                jour_id = :jour_id,
                date_debut = :date_debut,
                date_fin = :date_fin,
                heure_debut = :heure_debut,
                heure_fin = :heure_fin,
                description = :description,
                lieu = :lieu
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $planning->getId(),
            ':utilisateur_id' => $planning->getUtilisateurId(),
            ':site_id' => $planning->getSiteId(),
            ':jour_id' => $planning->getJourId(),
            ':date_debut' => $planning->getDateDebut(),
            ':date_fin' => $planning->getDateFin(),
            ':heure_debut' => $planning->getHeureDebut(),
            ':heure_fin' => $planning->getHeureFin(),
            ':description' => $planning->getDescription(),
            ':lieu' => $planning->getLieu()
        ]);
    }
    
    /**
     * Supprime un planning
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM plannings WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

