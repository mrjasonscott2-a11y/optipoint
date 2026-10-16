<?php
require_once __DIR__ . '/../repository/PlanningRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/SiteRepository.php';
require_once __DIR__ . '/../repository/JourRepository.php';
require_once __DIR__ . '/../models/Planning.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../mappers/PlanningMapper.php';

/**
 * Classe PlanningService - Service pour la gestion des plannings
 * Contient la logique métier pour les plannings
 * Retourne des DTOs pour l'API
 */
class PlanningService {
    private $planningRepository;
    private $userRepository;
    private $siteRepository;
    private $jourRepository;
    
    public function __construct() {
        $this->planningRepository = new PlanningRepository();
        $this->userRepository = new UserRepository();
        $this->siteRepository = new SiteRepository();
        $this->jourRepository = new JourRepository();
    }
    
    /**
     * Récupère les plannings avec filtres (retourne des DTOs)
     */
    public function getPlannings($filters = []) {
        $plannings = $this->planningRepository->findAll($filters);
        return PlanningMapper::toDTOArray($plannings);
    }
    
    /**
     * Récupère un planning par son ID (retourne un DTO)
     */
    public function getPlanningById($id) {
        if (empty($id)) {
            throw new Exception('ID planning requis');
        }
        
        $planning = $this->planningRepository->findById($id);
        
        if (!$planning) {
            throw new Exception('Planning non trouvé');
        }
        
        return PlanningMapper::toDTO($planning);
    }
    
    /**
     * Crée un nouveau planning
     */
    public function createPlanning($data) {
        // Validation des données requises
        $required = ['user_id', 'site_id', 'jour_id', 'date_debut', 'date_fin', 'heure_debut', 'heure_fin'];
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
        
        // Vérifier que le site existe
        $site = $this->siteRepository->findById($data['site_id']);
        if (!$site) {
            throw new Exception('Site non trouvé');
        }
        
        // Vérifier que le jour existe
        $jour = $this->jourRepository->findById($data['jour_id']);
        if (!$jour) {
            throw new Exception('Jour non trouvé');
        }
        
        // Valider les dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_debut'])) {
            throw new Exception('Format de date_debut invalide. Format attendu: YYYY-MM-DD');
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_fin'])) {
            throw new Exception('Format de date_fin invalide. Format attendu: YYYY-MM-DD');
        }
        
        // Vérifier que date_fin >= date_debut
        if ($data['date_fin'] < $data['date_debut']) {
            throw new Exception('La date de fin doit être supérieure ou égale à la date de début');
        }
        
        // Valider les heures
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['heure_debut'])) {
            throw new Exception('Format d\'heure_debut invalide. Format attendu: HH:MM ou HH:MM:SS');
        }
        
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['heure_fin'])) {
            throw new Exception('Format d\'heure_fin invalide. Format attendu: HH:MM ou HH:MM:SS');
        }
        
        // Vérifier que heure_fin > heure_debut (si même jour)
        if ($data['date_debut'] === $data['date_fin']) {
            if ($data['heure_fin'] <= $data['heure_debut']) {
                throw new Exception('L\'heure de fin doit être supérieure à l\'heure de début');
            }
        }
        
        // Créer le planning
        $planning = new Planning([
            'utilisateur_id' => $data['user_id'],
            'site_id' => $data['site_id'],
            'jour_id' => $data['jour_id'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'description' => $data['description'] ?? null,
            'lieu' => $data['lieu'] ?? null
        ]);
        
        $createdPlanning = $this->planningRepository->create($planning);
        return PlanningMapper::toDTO($createdPlanning);
    }
    
    /**
     * Met à jour un planning
     */
    public function updatePlanning($id, $data) {
        if (empty($id)) {
            throw new Exception('ID planning requis');
        }
        
        $planning = $this->planningRepository->findById($id);
        if (!$planning) {
            throw new Exception('Planning non trouvé');
        }
        
        // Mettre à jour les champs fournis
        if (isset($data['user_id'])) {
            $user = $this->userRepository->findById($data['user_id']);
            if (!$user) {
                throw new Exception('Utilisateur non trouvé');
            }
            $planning->setUtilisateurId($data['user_id']);
        }
        if (isset($data['site_id'])) {
            $site = $this->siteRepository->findById($data['site_id']);
            if (!$site) {
                throw new Exception('Site non trouvé');
            }
            $planning->setSiteId($data['site_id']);
        }
        if (isset($data['jour_id'])) {
            $jour = $this->jourRepository->findById($data['jour_id']);
            if (!$jour) {
                throw new Exception('Jour non trouvé');
            }
            $planning->setJourId($data['jour_id']);
        }
        if (isset($data['date_debut'])) {
            $planning->setDateDebut($data['date_debut']);
        }
        if (isset($data['date_fin'])) {
            $planning->setDateFin($data['date_fin']);
        }
        if (isset($data['heure_debut'])) {
            $planning->setHeureDebut($data['heure_debut']);
        }
        if (isset($data['heure_fin'])) {
            $planning->setHeureFin($data['heure_fin']);
        }
        if (isset($data['description'])) {
            $planning->setDescription($data['description']);
        }
        if (isset($data['lieu'])) {
            $planning->setLieu($data['lieu']);
        }
        
        $success = $this->planningRepository->update($planning);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedPlanning = $this->planningRepository->findById($id);
        return PlanningMapper::toDTO($updatedPlanning);
    }
    
    /**
     * Supprime un planning
     */
    public function deletePlanning($id) {
        if (empty($id)) {
            throw new Exception('ID planning requis');
        }
        
        $planning = $this->planningRepository->findById($id);
        if (!$planning) {
            throw new Exception('Planning non trouvé');
        }
        
        $success = $this->planningRepository->delete($id);
        
        if (!$success) {
            throw new Exception('Erreur lors de la suppression');
        }
        
        return true;
    }
}

