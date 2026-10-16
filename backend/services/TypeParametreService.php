<?php
require_once __DIR__ . '/../repository/TypeParametreRepository.php';
require_once __DIR__ . '/../models/TypeParametre.php';
require_once __DIR__ . '/../mappers/TypeParametreMapper.php';

/**
 * Classe TypeParametreService - Service pour la gestion des types de paramètres
 * Retourne des DTOs pour l'API
 */
class TypeParametreService {
    private $typeParametreRepository;
    
    public function __construct() {
        $this->typeParametreRepository = new TypeParametreRepository();
    }
    
    /**
     * Récupère tous les types de paramètres (retourne des DTOs)
     */
    public function getAllTypeParametres() {
        $typeParametres = $this->typeParametreRepository->findAll();
        return TypeParametreMapper::toDTOArray($typeParametres);
    }
    
    /**
     * Récupère un type de paramètre par son ID (retourne un DTO)
     */
    public function getTypeParametreById($id) {
        if (empty($id)) {
            throw new Exception('ID type paramètre requis');
        }
        
        $typeParametre = $this->typeParametreRepository->findById($id);
        
        if (!$typeParametre) {
            throw new Exception('Type de paramètre non trouvé');
        }
        
        return TypeParametreMapper::toDTO($typeParametre);
    }
    
    /**
     * Crée un nouveau type de paramètre
     */
    public function createTypeParametre($data) {
        // Validation des données requises
        $required = ['nom', 'code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le code existe déjà
        $existing = $this->typeParametreRepository->findByCode($data['code']);
        if ($existing) {
            throw new Exception('Un type de paramètre avec ce code existe déjà');
        }
        
        // Créer le type de paramètre
        $typeParametre = new TypeParametre([
            'nom' => $data['nom'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null
        ]);
        
        $createdTypeParametre = $this->typeParametreRepository->create($typeParametre);
        return TypeParametreMapper::toDTO($createdTypeParametre);
    }
    
    /**
     * Met à jour un type de paramètre
     */
    public function updateTypeParametre($id, $data) {
        $typeParametre = $this->typeParametreRepository->findById($id);
        
        if (!$typeParametre) {
            throw new Exception('Type de paramètre non trouvé');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $typeParametre->getNom()) {
            $existing = $this->typeParametreRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Un type de paramètre avec ce nom existe déjà');
            }
            $typeParametre->setNom($data['nom']);
        }
        
        // Vérifier si le code est modifié et s'il existe déjà
        if (isset($data['code']) && $data['code'] !== $typeParametre->getCode()) {
            $existing = $this->typeParametreRepository->findByCode($data['code']);
            if ($existing) {
                throw new Exception('Un type de paramètre avec ce code existe déjà');
            }
            $typeParametre->setCode($data['code']);
        }
        
        if (isset($data['description'])) {
            $typeParametre->setDescription($data['description']);
        }
        
        $success = $this->typeParametreRepository->update($typeParametre);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedTypeParametre = $this->typeParametreRepository->findById($id);
        return TypeParametreMapper::toDTO($updatedTypeParametre);
    }
    
    /**
     * Supprime un type de paramètre
     */
    public function deleteTypeParametre($id) {
        if (empty($id)) {
            throw new Exception('ID type paramètre requis');
        }
        
        $typeParametre = $this->typeParametreRepository->findById($id);
        
        if (!$typeParametre) {
            throw new Exception('Type de paramètre non trouvé');
        }
        
        try {
            $this->typeParametreRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

