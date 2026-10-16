<?php
require_once __DIR__ . '/../repository/TacheRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../models/Tache.php';
require_once __DIR__ . '/../mappers/TacheMapper.php';
require_once __DIR__ . '/ParametreService.php';

/**
 * Classe TacheService - Service pour la gestion des tâches
 * Retourne des DTOs pour l'API
 */
class TacheService {
    private $tacheRepository;
    private $userRepository;
    private $parametreService;
    
    public function __construct() {
        $this->tacheRepository = new TacheRepository();
        $this->userRepository = new UserRepository();
        $this->parametreService = new ParametreService();
    }
    
    /**
     * Convertit un nom de statut en ID
     */
    private function convertStatutToId($statut) {
        if (empty($statut)) {
            return null;
        }
        if (is_numeric($statut)) {
            return (int)$statut;
        }
        $statutId = $this->parametreService->convertNomToId(strtoupper($statut), 'TYPPARAM0001');
        return $statutId;
    }
    
    /**
     * Récupère toutes les tâches (retourne des DTOs)
     */
    public function getAllTaches($filters = []) {
        $taches = $this->tacheRepository->findAll($filters);
        
        // Préparer les données supplémentaires pour le mapper
        $additionalDataMap = [];
        foreach ($taches as $tache) {
            //$task = $this->tacheRepository->findById($tache['id']);
            $additionalDataMap[$tache['id']] = [];
            //$additionalDataMap[$tache->getId()] = [];
        }
        
        return TacheMapper::toDTOArray($taches, $additionalDataMap);
    }
    
    /**
     * Récupère une tâche par son ID (retourne un DTO)
     */
    public function getTacheById($id) {
        if (empty($id)) {
            throw new Exception('ID tâche requis');
        }
        
        $tache = $this->tacheRepository->findById($id);
        
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        // Le repository charge déjà planning_description et planning_date via JOIN
        return TacheMapper::toDTO($tache, []);
    }
    
    /**
     * Récupère les tâches d'un utilisateur
     */
    public function getTachesByUtilisateurId($utilisateurId) {
        if (empty($utilisateurId)) {
            throw new Exception('ID utilisateur requis');
        }
        
        $taches = $this->tacheRepository->findByUtilisateurId($utilisateurId);
        
        $additionalDataMap = [];
        foreach ($taches as $tache) {
            $additionalDataMap[$tache->getId()] = [];
        }
        
        return TacheMapper::toDTOArray($taches, $additionalDataMap);
    }
    
    /**
     * Crée une nouvelle tâche
     */
    public function createTache($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le planning existe (si fourni)
        $planningId = null;
        if (!empty($data['planning_id'])) {
            require_once __DIR__ . '/../repository/PlanningRepository.php';
            $planningRepo = new PlanningRepository();
            $planning = $planningRepo->findById($data['planning_id']);
            if (!$planning) {
                throw new Exception('Planning non trouvé');
            }
            $planningId = $data['planning_id'];
        }
        
        // Convertir le statut en ID si nécessaire
        $statutId = null;
        if (!empty($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
        }
        if (!$statutId) {
            $statutId = $this->convertStatutToId('A_FAIRE');
        }
        
        // Créer la tâche (sans planning - sera assigné séparément si nécessaire)
        $tache = new Tache([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'planning_id' => $planningId, // Peut être null
            'statut' => $statutId
        ]);
        
        $createdTache = $this->tacheRepository->create($tache);
        
        // Récupérer les données complètes avec JOIN
        $tacheWithData = $this->tacheRepository->findById($createdTache->getId());
        if ($tacheWithData) {
            // Le repository charge déjà planning_description et planning_date via JOIN
            return TacheMapper::toDTO($tacheWithData, []);
        }
        return TacheMapper::toDTO($createdTache, []);
    }
    
    /**
     * Met à jour une tâche
     */
    public function updateTache($id, $data) {
        $tache = $this->tacheRepository->findById($id);
        
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        if (isset($data['nom'])) {
            $tache->setNom($data['nom']);
        }
        
        if (isset($data['description'])) {
            $tache->setDescription($data['description']);
        }
        
        if (isset($data['planning_id'])) {
            require_once __DIR__ . '/../repository/PlanningRepository.php';
            $planningRepo = new PlanningRepository();
            if (!empty($data['planning_id'])) {
                $planning = $planningRepo->findById($data['planning_id']);
                if (!$planning) {
                    throw new Exception('Planning non trouvé');
                }
            }
            $tache->setPlanningId($data['planning_id'] ?: null);
        }
        
        if (isset($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
            if ($statutId) {
                $tache->setStatut($statutId);
            }
        }
        
        $success = $this->tacheRepository->update($tache);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedTache = $this->tacheRepository->findById($id);
        if ($updatedTache) {
            // Le repository charge déjà planning_description et planning_date via JOIN
            return TacheMapper::toDTO($updatedTache, []);
        }
        return TacheMapper::toDTO($updatedTache, []);
    }
    
    /**
     * Supprime une tâche
     */
    public function deleteTache($id) {
        if (empty($id)) {
            throw new Exception('ID tâche requis');
        }
        
        $tache = $this->tacheRepository->findById($id);
        
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        try {
            $this->tacheRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Démarre une tâche (passe le statut à EN_COURS)
     */
    public function demarrerTache($id) {
        if (empty($id)) {
            throw new Exception('ID tâche requis');
        }
        
        $tache = $this->tacheRepository->findById($id);
        
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        // Convertir le statut EN_COURS en ID
        $statut = $this->parametreService->getByCode('PARAM0010');
        if (!$statut) {
            throw new Exception('Statut EN_COURS non trouvé dans les paramètres');
        }
        
        // Mettre à jour uniquement le statut
        $tache->setStatut($statut->id);
        
        $success = $this->tacheRepository->update($tache);
        
        if (!$success) {
            throw new Exception('Erreur lors du démarrage de la tâche');
        }
        
        $updatedTache = $this->tacheRepository->findById($id);
        if ($updatedTache) {
            return TacheMapper::toDTO($updatedTache, []);
        }
        return TacheMapper::toDTO($updatedTache, []);
    }
    
    /**
     * Suspend une tâche (passe le statut à SUSPENDU)
     */
    public function suspendreTache($id) {
        if (empty($id)) {
            throw new Exception('ID tâche requis');
        }
        
        $tache = $this->tacheRepository->findById($id);
        
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        // Convertir le statut SUSPENDU en ID
        $statut = $this->parametreService->getByCode('PARAM0012');
        if (!$statut) {
            throw new Exception('Statut SUSPENDU non trouvé dans les paramètres');
        }
        
        // Mettre à jour uniquement le statut
        $tache->setStatut($statut->id);
        
        $success = $this->tacheRepository->update($tache);
        
        if (!$success) {
            throw new Exception('Erreur lors de la suspension de la tâche');
        }
        
        $updatedTache = $this->tacheRepository->findById($id);
        if ($updatedTache) {
            return TacheMapper::toDTO($updatedTache, []);
        }
        return TacheMapper::toDTO($updatedTache, []);
    }
    
    /**
     * Termine une tâche (passe le statut à TERMINE)
     */
    public function terminerTache($id) {
        if (empty($id)) {
            throw new Exception('ID tâche requis');
        }
        
        $tache = $this->tacheRepository->findById($id);
        
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        // Convertir le statut TERMINE en ID
        $statut = $this->parametreService->getByCode('PARAM0011');
        if (!$statut) {
            throw new Exception('Statut PARAM0011 non trouvé dans les paramètres');
        }
        
        // Mettre à jour uniquement le statut
        $tache->setStatut($statut->id);
        
        $success = $this->tacheRepository->update($tache);
        
        if (!$success) {
            throw new Exception('Erreur lors de la finalisation de la tâche');
        }
        
        $updatedTache = $this->tacheRepository->findById($id);
        if ($updatedTache) {
            return TacheMapper::toDTO($updatedTache, []);
        }
        return TacheMapper::toDTO($updatedTache, []);
    }
    
    /**
     * Récupère les tâches d'un planning
     */
    public function getTachesByPlanningId($planningId) {
        if (empty($planningId)) {
            throw new Exception('ID planning requis');
        }
        
        $taches = $this->tacheRepository->findByPlanningId($planningId);
        
        // Le repository retourne des tableaux, pas des objets
        $additionalDataMap = [];
        foreach ($taches as $tache) {
            $additionalDataMap[$tache['id']] = [];
        }
        
        return TacheMapper::toDTOArray($taches, $additionalDataMap);
    }
    
    /**
     * Récupère les tâches disponibles (sans planning ou avec un autre planning)
     */
    public function getAvailableTaches($excludePlanningId = null) {
        $allTachesData = $this->tacheRepository->findAll();
        
        // Filtrer les tâches disponibles
        $availableTachesData = [];
        foreach ($allTachesData as $tacheData) {
            $planningId = $tacheData['planning_id'] ?? null;
            // Inclure les tâches sans planning ou avec un planning différent
            if (empty($planningId) || ($excludePlanningId && $planningId != $excludePlanningId)) {
                $availableTachesData[] = $tacheData;
            }
        }
        
        // Convertir en DTOs
        $additionalDataMap = [];
        foreach ($availableTachesData as $tacheData) {
            $additionalDataMap[$tacheData['id']] = [];
        }
        
        return TacheMapper::toDTOArray($availableTachesData, $additionalDataMap);
    }
    
    /**
     * Assign une tâche à un planning
     */
    public function assignTacheToPlanning($tacheId, $planningId) {
        if (empty($tacheId)) {
            throw new Exception('ID tâche requis');
        }
        
        if (empty($planningId)) {
            throw new Exception('ID planning requis');
        }
        
        $tache = $this->tacheRepository->findById($tacheId);
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        // Vérifier que le planning existe
        require_once __DIR__ . '/../repository/PlanningRepository.php';
        $planningRepo = new PlanningRepository();
        $planning = $planningRepo->findById($planningId);
        if (!$planning) {
            throw new Exception('Planning non trouvé');
        }
        
        // Assigner la tâche au planning
        $tache->setPlanningId($planningId);
        
        $success = $this->tacheRepository->update($tache);
        if (!$success) {
            throw new Exception('Erreur lors de l\'assignation de la tâche au planning');
        }
        
        $updatedTache = $this->tacheRepository->findById($tacheId);
        if ($updatedTache) {
            return TacheMapper::toDTO($updatedTache, []);
        }
        return TacheMapper::toDTO($updatedTache, []);
    }
    
    /**
     * Retire une tâche d'un planning (met planning_id à null)
     */
    public function removeTacheFromPlanning($tacheId) {
        if (empty($tacheId)) {
            throw new Exception('ID tâche requis');
        }
        
        $tache = $this->tacheRepository->findById($tacheId);
        if (!$tache) {
            throw new Exception('Tâche non trouvée');
        }
        
        // Retirer la tâche du planning
        $tache->setPlanningId(null);
        
        $success = $this->tacheRepository->update($tache);
        if (!$success) {
            throw new Exception('Erreur lors du retrait de la tâche du planning');
        }
        
        $updatedTache = $this->tacheRepository->findById($tacheId);
        if ($updatedTache) {
            return TacheMapper::toDTO($updatedTache, []);
        }
        return TacheMapper::toDTO($updatedTache, []);
    }
}

