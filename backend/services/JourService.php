<?php
require_once __DIR__ . '/../repository/JourRepository.php';
require_once __DIR__ . '/../models/Jour.php';
require_once __DIR__ . '/../mappers/JourMapper.php';

/**
 * Classe JourService - Service pour la gestion des jours
 * Retourne des DTOs pour l'API
 */
class JourService {
    private $jourRepository;
    
    public function __construct() {
        $this->jourRepository = new JourRepository();
    }
    
    /**
     * Récupère tous les jours (retourne des DTOs)
     */
    public function getAllJours() {
        $jours = $this->jourRepository->findAll();
        return JourMapper::toDTOArray($jours);
    }
    
    /**
     * Récupère un jour par son ID (retourne un DTO)
     */
    public function getJourById($id) {
        if (empty($id)) {
            throw new Exception('ID jour requis');
        }
        
        $jour = $this->jourRepository->findById($id);
        
        if (!$jour) {
            throw new Exception('Jour non trouvé');
        }
        
        return JourMapper::toDTO($jour);
    }
    
    /**
     * Crée un nouveau jour
     */
    public function createJour($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà
        $existing = $this->jourRepository->findByNom($data['nom']);
        if ($existing) {
            throw new Exception('Un jour avec ce nom existe déjà');
        }
        
        // Créer le jour
        $jour = new Jour([
            'nom' => $data['nom']
        ]);
        
        $createdJour = $this->jourRepository->create($jour);
        return JourMapper::toDTO($createdJour);
    }
    
    /**
     * Met à jour un jour
     */
    public function updateJour($id, $data) {
        $jour = $this->jourRepository->findById($id);
        
        if (!$jour) {
            throw new Exception('Jour non trouvé');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $jour->getNom()) {
            $existing = $this->jourRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Un jour avec ce nom existe déjà');
            }
            $jour->setNom($data['nom']);
        }
        
        $success = $this->jourRepository->update($jour);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedJour = $this->jourRepository->findById($id);
        return JourMapper::toDTO($updatedJour);
    }
    
    /**
     * Supprime un jour
     */
    public function deleteJour($id) {
        if (empty($id)) {
            throw new Exception('ID jour requis');
        }
        
        $jour = $this->jourRepository->findById($id);
        
        if (!$jour) {
            throw new Exception('Jour non trouvé');
        }
        
        try {
            $this->jourRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

