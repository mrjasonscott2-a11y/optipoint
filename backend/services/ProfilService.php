<?php
require_once __DIR__ . '/../repository/ProfilRepository.php';
require_once __DIR__ . '/../models/Profil.php';
require_once __DIR__ . '/../mappers/ProfilMapper.php';

/**
 * Classe ProfilService - Service pour la gestion des profils
 * Retourne des DTOs pour l'API
 */
class ProfilService {
    private $profilRepository;
    
    public function __construct() {
        $this->profilRepository = new ProfilRepository();
    }
    
    /**
     * Récupère tous les profils (retourne des DTOs)
     */
    public function getAllProfils() {
        $profils = $this->profilRepository->findAll();
        return ProfilMapper::toDTOArray($profils);
    }
    
    /**
     * Récupère un profil par son ID (retourne un DTO)
     */
    public function getProfilById($id) {
        if (empty($id)) {
            throw new Exception('ID profil requis');
        }
        
        $profil = $this->profilRepository->findById($id);
        
        if (!$profil) {
            throw new Exception('Profil non trouvé');
        }
        
        return ProfilMapper::toDTO($profil);
    }
    
    /**
     * Crée un nouveau profil
     */
    public function createProfil($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà
        $existing = $this->profilRepository->findByNom($data['nom']);
        if ($existing) {
            throw new Exception('Un profil avec ce nom existe déjà');
        }
        
        // Créer le profil
        $profil = new Profil([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null
        ]);
        
        // Gérer les habilitations si fournies
        if (isset($data['habilitations']) && is_array($data['habilitations'])) {
            $profil->setHabilitations($data['habilitations']);
        }
        
        $createdProfil = $this->profilRepository->create($profil);
        return ProfilMapper::toDTO($createdProfil);
    }
    
    /**
     * Met à jour un profil
     */
    public function updateProfil($id, $data) {
        $profil = $this->profilRepository->findById($id);
        
        if (!$profil) {
            throw new Exception('Profil non trouvé');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $profil->getNom()) {
            $existing = $this->profilRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Un profil avec ce nom existe déjà');
            }
            $profil->setNom($data['nom']);
        }
        
        if (isset($data['description'])) {
            $profil->setDescription($data['description']);
        }
        
        // Gérer les habilitations si fournies
        if (isset($data['habilitations'])) {
            $profil->setHabilitations($data['habilitations']);
        }
        
        $success = $this->profilRepository->update($profil);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedProfil = $this->profilRepository->findById($id);
        return ProfilMapper::toDTO($updatedProfil);
    }
    
    /**
     * Supprime un profil
     */
    public function deleteProfil($id) {
        if (empty($id)) {
            throw new Exception('ID profil requis');
        }
        
        $profil = $this->profilRepository->findById($id);
        
        if (!$profil) {
            throw new Exception('Profil non trouvé');
        }
        
        try {
            $this->profilRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

