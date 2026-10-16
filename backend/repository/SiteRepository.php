<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Site.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Classe SiteRepository - Repository pour les sites
 */
class SiteRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un site par son ID avec son manager
     */
    public function findById($id) {
        $sql = "SELECT s.*, u.nom as manager_nom, statut_param.nom as statut_nom
                FROM sites s
                LEFT JOIN utilisateurs u ON s.manager_id = u.id
                LEFT JOIN parametres statut_param ON s.statut = statut_param.id
                WHERE s.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $site = new Site($data);
        
        // Charger le manager si disponible
        if (isset($data['manager_nom'])) {
            $manager = new User([
                'id' => $data['manager_id'],
                'nom' => $data['manager_nom']
            ]);
            $site->setManager($manager);
            $site->setManagerNom($data['manager_nom']); // Stocker aussi le nom directement
        }
        
        if (isset($data['statut_nom'])) {
            $site->setStatutNom($data['statut_nom']);
        }
        
        return $site;
    }
    
    /**
     * Trouve un site par son nom
     */
    public function findByNom($nom) {
        $sql = "SELECT s.*, u.nom as manager_nom, statut_param.nom as statut_nom
                FROM sites s
                LEFT JOIN utilisateurs u ON s.manager_id = u.id
                LEFT JOIN parametres statut_param ON s.statut = statut_param.id
                WHERE s.nom = :nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $site = new Site($data);
        
        if (isset($data['manager_nom'])) {
            $manager = new User([
                'id' => $data['manager_id'],
                'nom' => $data['manager_nom']
            ]);
            $site->setManager($manager);
            $site->setManagerNom($data['manager_nom']); // Stocker aussi le nom directement
        }
        
        if (isset($data['statut_nom'])) {
            $site->setStatutNom($data['statut_nom']);
        }
        
        return $site;
    }
    
    /**
     * Récupère tous les sites avec leurs managers
     */
    public function findAll() {
        $sql = "SELECT s.*, u.nom as manager_nom, statut_param.nom as statut_nom
                FROM sites s
                LEFT JOIN utilisateurs u ON s.manager_id = u.id
                LEFT JOIN parametres statut_param ON s.statut = statut_param.id
                ORDER BY s.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $site = new Site($data);
            
            if (isset($data['manager_nom'])) {
                $manager = new User([
                    'id' => $data['manager_id'],
                    'nom' => $data['manager_nom']
                ]);
                $site->setManager($manager);
                $site->setManagerNom($data['manager_nom']); // Stocker aussi le nom directement
            }
            
            if (isset($data['statut_nom'])) {
                $site->setStatutNom($data['statut_nom']);
            }
            
            return $site;
        }, $results);
    }
    
    /**
     * Crée un nouveau site
     */
    public function create(Site $site) {
        $sql = "INSERT INTO sites (nom, description, manager_id, statut)
                VALUES (:nom, :description, :manager_id, :statut)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $site->getNom(),
            ':description' => $site->getDescription(),
            ':manager_id' => $site->getManagerId(),
            ':statut' => $site->getStatut()
        ]);
        
        $site->setId($this->db->lastInsertId());
        return $site;
    }
    
    /**
     * Met à jour un site
     */
    public function update(Site $site) {
        $sql = "UPDATE sites 
                SET nom = :nom, description = :description, manager_id = :manager_id, statut = :statut
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $site->getId(),
            ':nom' => $site->getNom(),
            ':description' => $site->getDescription(),
            ':manager_id' => $site->getManagerId(),
            ':statut' => $site->getStatut()
        ]);
    }
    
    /**
     * Supprime un site
     */
    public function delete($id) {
        // Vérifier si des plannings utilisent ce site
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM plannings WHERE site_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer ce site car il est utilisé par des plannings');
        }
        
        $stmt = $this->db->prepare("DELETE FROM sites WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

