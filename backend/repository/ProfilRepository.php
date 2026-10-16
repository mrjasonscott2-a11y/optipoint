<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Profil.php';
require_once __DIR__ . '/../models/Habilitation.php';
require_once __DIR__ . '/HabilitationRepository.php';

/**
 * Classe ProfilRepository - Repository pour les profils
 */
class ProfilRepository {
    private $db;
    private $habilitationRepository;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->habilitationRepository = new HabilitationRepository();
    }
    
    /**
     * Trouve un profil par son ID avec ses habilitations
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM profils WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $profil = new Profil($data);
        // Charger les habilitations
        $habilitations = $this->habilitationRepository->findByProfilId($id);
        $profil->setHabilitations($habilitations);
        
        return $profil;
    }
    
    /**
     * Trouve un profil par son nom avec ses habilitations
     */
    public function findByNom($nom) {
        $stmt = $this->db->prepare("SELECT * FROM profils WHERE nom = :nom");
        $stmt->execute([':nom' => $nom]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $profil = new Profil($data);
        // Charger les habilitations
        $habilitations = $this->habilitationRepository->findByProfilId($data['id']);
        $profil->setHabilitations($habilitations);
        
        return $profil;
    }
    
    /**
     * Récupère tous les profils avec leurs habilitations
     */
    public function findAll() {
        $stmt = $this->db->prepare("SELECT * FROM profils ORDER BY nom ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $profil = new Profil($data);
            // Charger les habilitations
            $habilitations = $this->habilitationRepository->findByProfilId($data['id']);
            $profil->setHabilitations($habilitations);
            return $profil;
        }, $results);
    }
    
    /**
     * Crée un nouveau profil
     */
    public function create(Profil $profil) {
        $sql = "INSERT INTO profils (nom, description)
                VALUES (:nom, :description)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $profil->getNom(),
            ':description' => $profil->getDescription()
        ]);
        
        $profilId = $this->db->lastInsertId();
        $profil->setId($profilId);
        
        // Associer les habilitations si fournies
        $habilitations = $profil->getHabilitations();
        if (!empty($habilitations)) {
            $habilitationIds = [];
            foreach ($habilitations as $habilitation) {
                if (is_object($habilitation) && method_exists($habilitation, 'getId')) {
                    $habilitationIds[] = $habilitation->getId();
                } elseif (is_numeric($habilitation)) {
                    $habilitationIds[] = $habilitation;
                } elseif (is_array($habilitation) && isset($habilitation['id'])) {
                    $habilitationIds[] = $habilitation['id'];
                }
            }
            if (!empty($habilitationIds)) {
                $this->habilitationRepository->attachToProfil($profilId, $habilitationIds);
            }
        }
        
        return $profil;
    }
    
    /**
     * Met à jour un profil et ses habilitations
     */
    public function update(Profil $profil) {
        $sql = "UPDATE profils 
                SET nom = :nom, description = :description
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':id' => $profil->getId(),
            ':nom' => $profil->getNom(),
            ':description' => $profil->getDescription()
        ]);
        
        if ($success) {
            // Mettre à jour les habilitations
            $habilitations = $profil->getHabilitations();
            $habilitationIds = [];
            foreach ($habilitations as $habilitation) {
                if (is_object($habilitation) && method_exists($habilitation, 'getId')) {
                    $habilitationIds[] = $habilitation->getId();
                } elseif (is_numeric($habilitation)) {
                    $habilitationIds[] = $habilitation;
                } elseif (is_array($habilitation) && isset($habilitation['id'])) {
                    $habilitationIds[] = $habilitation['id'];
                }
            }
            $this->habilitationRepository->attachToProfil($profil->getId(), $habilitationIds);
        }
        
        return $success;
    }
    
    /**
     * Supprime un profil
     */
    public function delete($id) {
        // Vérifier si des utilisateurs utilisent ce profil
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM utilisateurs WHERE profil_id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Impossible de supprimer ce profil car il est utilisé par des utilisateurs');
        }
        
        // Supprimer les associations avec les habilitations
        $this->habilitationRepository->detachFromProfil($id);
        
        // Supprimer le profil
        $stmt = $this->db->prepare("DELETE FROM profils WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

