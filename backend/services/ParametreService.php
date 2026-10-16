<?php
require_once __DIR__ . '/../repository/ParametreRepository.php';
require_once __DIR__ . '/../models/Parametre.php';
require_once __DIR__ . '/../mappers/ParametreMapper.php';

/**
 * Classe ParametreService - Service pour la gestion des paramètres
 * Retourne des DTOs pour l'API
 */
class ParametreService {
    private $parametreRepository;
    
    public function __construct() {
        $this->parametreRepository = new ParametreRepository();
    }
    
    /**
     * Récupère tous les paramètres (retourne des DTOs)
     */
    public function getAllParametres($filters = []) {
        $parametres = $this->parametreRepository->findAll($filters);
        return ParametreMapper::toDTOArray($parametres);
    }
    
    /**
     * Récupère un paramètre par son ID (retourne un DTO)
     */
    public function getParametreById($id) {
        if (empty($id)) {
            throw new Exception('ID paramètre requis');
        }
        
        $parametre = $this->parametreRepository->findById($id);
        
        if (!$parametre) {
            throw new Exception('Paramètre non trouvé');
        }
        
        return ParametreMapper::toDTO($parametre);
    }

     /**
     * Récupère un paramètre par son CODE (retourne un DTO)
     */
    public function getByCode($code) {
        if (empty($code)) {
            throw new Exception('CODE paramètre requis');
        }
        
        $parametre = $this->parametreRepository->findByCode($code);
        
        if (!$parametre) {
            throw new Exception('Paramètre non trouvé');
        }
        
        return ParametreMapper::toDTO($parametre);
    }
    
    /**
     * Récupère les paramètres par type
     */
    public function getParametresByTypeId($typeParametreId) {
        if (empty($typeParametreId)) {
            throw new Exception('ID type paramètre requis');
        }
        
        $parametres = $this->parametreRepository->findByTypeParametreId($typeParametreId);
        return ParametreMapper::toDTOArray($parametres);
    }
    
    /**
     * Crée un nouveau paramètre
     */
    public function createParametre($data) {
        // Validation des données requises
        $required = ['nom', 'code', 'type_parametre_id'];
        foreach ($required as $field) {
            if (empty($data[$field]) && $data[$field] !== '0' && $data[$field] !== 0) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà pour ce type
        $existing = $this->parametreRepository->findByNom($data['nom']);
        if ($existing && $existing->getTypeParametreId() == $data['type_parametre_id']) {
            throw new Exception('Un paramètre avec ce nom existe déjà pour ce type');
        }
        
        // Créer le paramètre
        $parametre = new Parametre([
            'nom' => $data['nom'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'type_parametre_id' => $data['type_parametre_id']
        ]);
        
        $createdParametre = $this->parametreRepository->create($parametre);
        return ParametreMapper::toDTO($createdParametre);
    }
    
    /**
     * Met à jour un paramètre
     */
    public function updateParametre($id, $data) {
        $parametre = $this->parametreRepository->findById($id);
        
        if (!$parametre) {
            throw new Exception('Paramètre non trouvé');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $parametre->getNom()) {
            $existing = $this->parametreRepository->findByNom($data['nom']);
            $typeId = $data['type_parametre_id'] ?? $parametre->getTypeParametreId();
            if ($existing && $existing->getTypeParametreId() == $typeId && $existing->getId() != $id) {
                throw new Exception('Un paramètre avec ce nom existe déjà pour ce type');
            }
            $parametre->setNom($data['nom']);
        }
        
        if (isset($data['code'])) {
            $parametre->setCode($data['code']);
        }
        
        if (isset($data['description'])) {
            $parametre->setDescription($data['description']);
        }
        
        if (isset($data['type_parametre_id'])) {
            $parametre->setTypeParametreId($data['type_parametre_id']);
        }
        
        $success = $this->parametreRepository->update($parametre);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedParametre = $this->parametreRepository->findById($id);
        return ParametreMapper::toDTO($updatedParametre);
    }
    
    /**
     * Supprime un paramètre
     */
    public function deleteParametre($id) {
        if (empty($id)) {
            throw new Exception('ID paramètre requis');
        }
        
        $parametre = $this->parametreRepository->findById($id);
        
        if (!$parametre) {
            throw new Exception('Paramètre non trouvé');
        }
        
        try {
            $this->parametreRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Trouve un paramètre par nom et type (pour conversion nom -> ID)
     */
    public function findParametreByNomAndType($nom, $typeParametreCode) {
        return $this->parametreRepository->findByNomAndType($nom, $typeParametreCode);
    }
    
    /**
     * Convertit un nom de paramètre en ID
     * @param string $nom Le nom du paramètre (ex: 'ACTIF', 'BUREAU')
     * @param string $typeCode Le code du type de paramètre (ex: 'TYPPARAM0001' pour statut)
     * @return int|null L'ID du paramètre ou null si non trouvé
     */
    public function convertNomToId($nom, $typeCode) {
        $parametre = $this->parametreRepository->findByNomAndType($nom, $typeCode);
        return $parametre ? $parametre->getId() : null;
    }
}

