<?php
require_once __DIR__ . '/../repository/PointageRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../models/Pointage.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../mappers/PointageMapper.php';
require_once __DIR__ . '/ParametreService.php';

/**
 * Classe PointageService - Service pour la gestion des pointages
 * Contient la logique métier pour les pointages
 * Retourne des DTOs pour l'API
 */
class PointageService {
    private $pointageRepository;
    private $userRepository;
    private $parametreService;
    
    public function __construct() {
        $this->pointageRepository = new PointageRepository();
        $this->userRepository = new UserRepository();
        $this->parametreService = new ParametreService();
    }
    
    /**
     * Convertit un nom de mode de travail en ID
     */
    private function convertModeTravailToId($mode) {
        if (empty($mode)) {
            return null;
        }
        if (is_numeric($mode)) {
            return (int)$mode;
        }
        // Mapper les anciens noms vers les nouveaux
        $modeMap = [
            'bureau' => 'BUREAU',
            'teletravail' => 'TELE TRAVAIL',
            'client' => 'CLIENT'
        ];
        $modeNom = $modeMap[strtolower($mode)] ?? strtoupper($mode);
        $modeId = $this->parametreService->convertNomToId($modeNom, 'TYPPARAM0002');
        return $modeId;
    }
    
    /**
     * Convertit un nom de type de pointage en ID
     */
    private function convertTypePointageToId($type) {
        if (empty($type)) {
            return null;
        }
        if (is_numeric($type)) {
            return (int)$type;
        }
        // Mapper les anciens noms vers les nouveaux
        $typeMap = [
            'arrivee' => 'ARRIVEE',
            'depart' => 'DEPART'
        ];
        $typeNom = $typeMap[strtolower($type)] ?? strtoupper($type);
        $typeId = $this->parametreService->convertNomToId($typeNom, 'TYPPARAM0003');
        return $typeId;
    }
    
    /**
     * Récupère les pointages avec filtres (retourne des DTOs)
     */
    public function getPointages($filters = []) {
        $pointages = $this->pointageRepository->findAll($filters);
        return PointageMapper::toDTOArray($pointages);
    }
    
    /**
     * Récupère un pointage par son ID (retourne un DTO)
     */
    public function getPointageById($id) {
        if (empty($id)) {
            throw new Exception('ID pointage requis');
        }
        
        // Récupérer avec les relations (findAll utilise des JOINs)
        $pointages = $this->pointageRepository->findAll();
        foreach ($pointages as $p) {
            if (is_array($p) && ($p['id'] ?? null) == $id) {
                return PointageMapper::toDTO($p);
            } elseif ($p instanceof Pointage && $p->getId() == $id) {
                return PointageMapper::toDTO($p);
            }
        }
        
        // Si non trouvé dans findAll, essayer findById
        $pointage = $this->pointageRepository->findById($id);
        if (!$pointage) {
            throw new Exception('Pointage non trouvé');
        }
        
        return PointageMapper::toDTO($pointage);
    }
    
    /**
     * Crée un nouveau pointage
     */
    public function createPointage($data) {
        // Validation des données requises
        $required = ['user_id', 'type', 'date_pointage', 'heure'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($data['user_id']);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Convertir le type de pointage en ID si nécessaire
        $typePointageId = null;
        if (!empty($data['type'])) {
            $typePointageId = $this->convertTypePointageToId($data['type']);
        }
        if (!$typePointageId) {
            throw new Exception('Type de pointage invalide. Doit être "arrivee" ou "depart"');
        }
        
        // Valider le format de la date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_pointage'])) {
            throw new Exception('Format de date invalide. Format attendu: YYYY-MM-DD');
        }
        
        // Valider le format de l'heure
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['heure'])) {
            throw new Exception('Format d\'heure invalide. Format attendu: HH:MM ou HH:MM:SS');
        }
        
        // Convertir le mode de travail en ID si nécessaire
        $modeTravailId = null;
        if (!empty($data['mode_travail'])) {
            $modeTravailId = $this->convertModeTravailToId($data['mode_travail']);
        }
        if (!$modeTravailId) {
            $modeTravailId = $this->convertModeTravailToId('bureau');
        }
        
        // Créer le pointage
        $pointage = new Pointage([
            'utilisateur_id' => $data['user_id'],
            'type_pointage' => $typePointageId,
            'date_pointage' => $data['date_pointage'],
            'heure' => $data['heure'],
            'localisation' => $data['localisation'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'mode_travail' => $modeTravailId,
            'notes' => $data['notes'] ?? null
        ]);
        
        try {
            $createdPointage = $this->pointageRepository->create($pointage);
            
            // Récupérer le pointage créé avec les relations
            $pointages = $this->pointageRepository->findAll();
            foreach ($pointages as $p) {
                if (is_array($p) && ($p['id'] ?? null) == $createdPointage->getId()) {
                    return PointageMapper::toDTO($p);
                } elseif ($p instanceof Pointage && $p->getId() == $createdPointage->getId()) {
                    return PointageMapper::toDTO($p);
                }
            }
            
            return PointageMapper::toDTO($createdPointage);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Supprime un pointage
     */
    public function deletePointage($id) {
        if (empty($id)) {
            throw new Exception('ID pointage requis');
        }
        
        $pointage = $this->pointageRepository->findById($id);
        if (!$pointage) {
            throw new Exception('Pointage non trouvé');
        }
        
        $success = $this->pointageRepository->delete($id);
        
        if (!$success) {
            throw new Exception('Erreur lors de la suppression');
        }
        
        return true;
    }
    
    /**
     * Récupère les statistiques de pointage pour un utilisateur
     */
    public function getStats($userId, $dateDebut, $dateFin) {
        if (empty($userId)) {
            throw new Exception('User ID requis');
        }
        
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        return $this->pointageRepository->getStats($userId, $dateDebut, $dateFin);
    }
    
    /**
     * Récupère les pointages récents pour un utilisateur
     */
    public function getRecentPointages($userId, $limit = 10) {
        if (empty($userId)) {
            throw new Exception('User ID requis');
        }
        
        $today = date('Y-m-d');
        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
        
        $pointages = $this->pointageRepository->findAll([
            'user_id' => $userId,
            'date_debut' => $sevenDaysAgo,
            'date_fin' => $today
        ]);
        
        $dtoArray = PointageMapper::toDTOArray($pointages);
        return array_slice($dtoArray, 0, $limit);
    }
}

