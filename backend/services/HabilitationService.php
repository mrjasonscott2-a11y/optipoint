<?php
require_once __DIR__ . '/../repository/HabilitationRepository.php';
require_once __DIR__ . '/../models/Habilitation.php';
require_once __DIR__ . '/../mappers/HabilitationMapper.php';

/**
 * Classe HabilitationService - Service pour la gestion des habilitations
 * Retourne des DTOs pour l'API
 */
class HabilitationService {
    private $habilitationRepository;
    
    public function __construct() {
        $this->habilitationRepository = new HabilitationRepository();
    }
    
    /**
     * Récupère toutes les habilitations (retourne des DTOs)
     */
    public function getAllHabilitations() {
        $habilitations = $this->habilitationRepository->findAll();
        return HabilitationMapper::toDTOArray($habilitations);
    }
    
    /**
     * Récupère une habilitation par son ID (retourne un DTO)
     */
    public function getHabilitationById($id) {
        if (empty($id)) {
            throw new Exception('ID habilitation requis');
        }
        
        $habilitation = $this->habilitationRepository->findById($id);
        
        if (!$habilitation) {
            throw new Exception('Habilitation non trouvée');
        }
        
        return HabilitationMapper::toDTO($habilitation);
    }
    
    /**
     * Récupère les habilitations d'un profil
     */
    public function getHabilitationsByProfilId($profilId) {
        if (empty($profilId)) {
            throw new Exception('ID profil requis');
        }
        
        $habilitations = $this->habilitationRepository->findByProfilId($profilId);
        return HabilitationMapper::toDTOArray($habilitations);
    }
    
    /**
     * Crée une nouvelle habilitation
     */
    public function createHabilitation($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà
        $existing = $this->habilitationRepository->findByNom($data['nom']);
        if ($existing) {
            throw new Exception('Une habilitation avec ce nom existe déjà');
        }
        
        // Créer l'habilitation
        $habilitation = new Habilitation([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null
        ]);
        
        $createdHabilitation = $this->habilitationRepository->create($habilitation);
        return HabilitationMapper::toDTO($createdHabilitation);
    }
    
    /**
     * Met à jour une habilitation
     */
    public function updateHabilitation($id, $data) {
        $habilitation = $this->habilitationRepository->findById($id);
        
        if (!$habilitation) {
            throw new Exception('Habilitation non trouvée');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $habilitation->getNom()) {
            $existing = $this->habilitationRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Une habilitation avec ce nom existe déjà');
            }
            $habilitation->setNom($data['nom']);
        }
        
        if (isset($data['description'])) {
            $habilitation->setDescription($data['description']);
        }
        
        $success = $this->habilitationRepository->update($habilitation);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedHabilitation = $this->habilitationRepository->findById($id);
        return HabilitationMapper::toDTO($updatedHabilitation);
    }
    
    /**
     * Supprime une habilitation
     */
    public function deleteHabilitation($id) {
        if (empty($id)) {
            throw new Exception('ID habilitation requis');
        }
        
        $habilitation = $this->habilitationRepository->findById($id);
        
        if (!$habilitation) {
            throw new Exception('Habilitation non trouvée');
        }
        
        try {
            $this->habilitationRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Associe des habilitations à un profil
     */
    public function attachHabilitationsToProfil($profilId, $habilitationIds) {
        if (empty($profilId)) {
            throw new Exception('ID profil requis');
        }
        
        if (!is_array($habilitationIds)) {
            $habilitationIds = [];
        }
        
        return $this->habilitationRepository->attachToProfil($profilId, $habilitationIds);
    }
}

