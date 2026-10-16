<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Pointage.php';

/**
 * Classe PointageRepository - Repository pour les pointages
 */
class PointageRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un pointage par son ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT p.*, 
                                    mode_param.nom as mode_travail_nom,
                                    type_param.nom as type_pointage_nom
                                    FROM pointages p
                                    LEFT JOIN parametres mode_param ON p.mode_travail = mode_param.id
                                    LEFT JOIN parametres type_param ON p.type_pointage = type_param.id
                                    WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            $pointage = new Pointage($data);
            if (isset($data['mode_travail_nom'])) {
                $pointage->setModeTravailNom($data['mode_travail_nom']);
            }
            if (isset($data['type_pointage_nom'])) {
                $pointage->setTypePointageNom($data['type_pointage_nom']);
            }
            return $pointage;
        }
        return null;
    }
    
    /**
     * Récupère tous les pointages avec filtres optionnels
     */
    public function findAll($filters = []) {
        $sql = "SELECT p.*, u.nom as employe_nom, u.matricule as employe_matricule, 
                       COALESCE(s.nom, u.service) as employe_service,
                       mode_param.nom as mode_travail_nom,
                       type_param.nom as type_pointage_nom
                FROM pointages p
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                LEFT JOIN services s ON u.service_id = s.id
                LEFT JOIN parametres mode_param ON p.mode_travail = mode_param.id
                LEFT JOIN parametres type_param ON p.type_pointage = type_param.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['user_id'])) {
            $sql .= " AND p.utilisateur_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (isset($filters['date_debut'])) {
            $sql .= " AND p.date_pointage >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }
        
        if (isset($filters['date_fin'])) {
            $sql .= " AND p.date_pointage <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }
        
        if (isset($filters['employe_id'])) {
            $sql .= " AND p.utilisateur_id = :employe_id";
            $params[':employe_id'] = $filters['employe_id'];
        }
        
        $sql .= " ORDER BY p.date_pointage DESC, p.heure DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Crée un nouveau pointage
     */
    public function create(Pointage $pointage) {
        // Vérifier si un pointage du même type existe déjà pour cette date
        $existing = $this->findByUserAndDateAndType(
            $pointage->getUtilisateurId(),
            $pointage->getDatePointage(),
            $pointage->getTypePointage()
        );
        
        if ($existing) {
            throw new Exception('Pointage déjà enregistré pour cette date');
        }
        
        $sql = "INSERT INTO pointages 
                (utilisateur_id, type_pointage, date_pointage, heure, localisation, latitude, longitude, mode_travail, notes)
                VALUES (:utilisateur_id, :type_pointage, :date_pointage, :heure, :localisation, :latitude, :longitude, :mode_travail, :notes)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':utilisateur_id' => $pointage->getUtilisateurId(),
            ':type_pointage' => $pointage->getTypePointage(),
            ':date_pointage' => $pointage->getDatePointage(),
            ':heure' => $pointage->getHeure(),
            ':localisation' => $pointage->getLocalisation(),
            ':latitude' => $pointage->getLatitude(),
            ':longitude' => $pointage->getLongitude(),
            ':mode_travail' => $pointage->getModeTravail(),
            ':notes' => $pointage->getNotes()
        ]);
        
        $pointage->setId($this->db->lastInsertId());
        return $pointage;
    }
    
    /**
     * Trouve un pointage par utilisateur, date et type
     */
    public function findByUserAndDateAndType($userId, $date, $typePointageId) {
        $stmt = $this->db->prepare("
            SELECT * FROM pointages 
            WHERE utilisateur_id = :user_id AND type_pointage = :type_pointage AND date_pointage = :date_pointage
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':type_pointage' => $typePointageId,
            ':date_pointage' => $date
        ]);
        $data = $stmt->fetch();
        return $data ? new Pointage($data) : null;
    }
    
    /**
     * Supprime un pointage
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM pointages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Récupère les statistiques de pointage pour un utilisateur
     */
    public function getStats($userId, $dateDebut, $dateFin) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT date_pointage) as jours_presents,
                SUM(CASE WHEN type_param.nom = 'ARRIVEE' THEN 1 ELSE 0 END) as arrivees
            FROM pointages p
            LEFT JOIN parametres type_param ON p.type_pointage = type_param.id
            WHERE p.utilisateur_id = :user_id 
            AND p.date_pointage BETWEEN :debut AND :fin
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':debut' => $dateDebut,
            ':fin' => $dateFin
        ]);
        return $stmt->fetch();
    }
}

