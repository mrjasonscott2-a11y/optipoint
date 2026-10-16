<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Equipe.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Classe EquipeRepository - Repository pour les équipes
 */
class EquipeRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve une équipe par son ID avec son manager
     */
    public function findById($id) {
        $sql = "SELECT e.*, u.nom as manager_nom, statut_param.nom as statut_nom
                FROM equipes e
                LEFT JOIN utilisateurs u ON e.manager_id = u.id
                LEFT JOIN parametres statut_param ON e.statut = statut_param.id
                WHERE e.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $equipe = new Equipe($data);
        
        // Charger le manager si disponible
        if (isset($data['manager_nom'])) {
            $manager = new User([
                'id' => $data['manager_id'],
                'nom' => $data['manager_nom']
            ]);
            $equipe->setManager($manager);
            $equipe->setManagerNom($data['manager_nom']); // Stocker aussi le nom directement
        }
        
        if (isset($data['statut_nom'])) {
            $equipe->setStatutNom($data['statut_nom']);
        }
        
        return $equipe;
    }
    
    /**
     * Trouve une équipe par son nom
     */
    public function findByNom($nom) {
        $sql = "SELECT e.*, u.nom as manager_nom, statut_param.nom as statut_nom
                FROM equipes e
                LEFT JOIN utilisateurs u ON e.manager_id = u.id
                LEFT JOIN parametres statut_param ON e.statut = statut_param.id
                WHERE e.nom = :nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $equipe = new Equipe($data);
        
        if (isset($data['manager_nom'])) {
            $manager = new User([
                'id' => $data['manager_id'],
                'nom' => $data['manager_nom']
            ]);
            $equipe->setManager($manager);
            $equipe->setManagerNom($data['manager_nom']); // Stocker aussi le nom directement
        }
        
        if (isset($data['statut_nom'])) {
            $equipe->setStatutNom($data['statut_nom']);
        }
        
        return $equipe;
    }
    
    /**
     * Récupère toutes les équipes avec leurs managers
     */
    public function findAll() {
        $sql = "SELECT e.*, u.nom as manager_nom, statut_param.nom as statut_nom
                FROM equipes e
                LEFT JOIN utilisateurs u ON e.manager_id = u.id
                LEFT JOIN parametres statut_param ON e.statut = statut_param.id
                ORDER BY e.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $equipe = new Equipe($data);
            
            if (isset($data['manager_nom'])) {
                $manager = new User([
                    'id' => $data['manager_id'],
                    'nom' => $data['manager_nom']
                ]);
                $equipe->setManager($manager);
                $equipe->setManagerNom($data['manager_nom']); // Stocker aussi le nom directement
            }
            
            if (isset($data['statut_nom'])) {
                $equipe->setStatutNom($data['statut_nom']);
            }
            
            return $equipe;
        }, $results);
    }
    
    /**
     * Récupère les utilisateurs d'une équipe
     */
    public function findUsersByEquipeId($equipeId) {
        $sql = "SELECT u.*, s.nom as service_nom, p.nom as profil_nom, statut_param.nom as statut_nom
                FROM utilisateurs u
                LEFT JOIN services s ON u.service_id = s.id
                LEFT JOIN profils p ON u.profil_id = p.id
                LEFT JOIN parametres statut_param ON u.statut = statut_param.id
                WHERE u.equipe_id = :equipe_id
                ORDER BY u.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':equipe_id' => $equipeId]);
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $user = new User($data);
            if (isset($data['statut_nom'])) {
                $user->setStatutNom($data['statut_nom']);
            }
            return $user;
        }, $results);
    }
    
    /**
     * Crée une nouvelle équipe
     */
    public function create(Equipe $equipe) {
        $sql = "INSERT INTO equipes (nom, description, manager_id, statut)
                VALUES (:nom, :description, :manager_id, :statut)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $equipe->getNom(),
            ':description' => $equipe->getDescription(),
            ':manager_id' => $equipe->getManagerId(),
            ':statut' => $equipe->getStatut()
        ]);
        
        $equipe->setId($this->db->lastInsertId());
        return $equipe;
    }
    
    /**
     * Met à jour une équipe
     */
    public function update(Equipe $equipe) {
        $sql = "UPDATE equipes 
                SET nom = :nom, description = :description, manager_id = :manager_id, statut = :statut
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $equipe->getId(),
            ':nom' => $equipe->getNom(),
            ':description' => $equipe->getDescription(),
            ':manager_id' => $equipe->getManagerId(),
            ':statut' => $equipe->getStatut()
        ]);
    }
    
    /**
     * Supprime une équipe
     */
    public function delete($id) {
        // Vérifier si des utilisateurs utilisent cette équipe
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM utilisateurs WHERE equipe_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer cette équipe car elle est utilisée par des utilisateurs');
        }
        
        $stmt = $this->db->prepare("DELETE FROM equipes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

