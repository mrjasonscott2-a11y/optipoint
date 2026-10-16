<?php
require_once __DIR__ . '/../repository/ServiceRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../mappers/ServiceMapper.php';
require_once __DIR__ . '/ParametreService.php';

/**
 * Classe SrvService - Service pour la gestion des services
 * Retourne des DTOs pour l'API
 */
class SrvService {
    private $serviceRepository;
    private $userRepository;
    private $parametreService;
    
    public function __construct() {
        $this->serviceRepository = new ServiceRepository();
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
     * Récupère tous les services (retourne des DTOs)
     */
    public function getAllServices() {
        $services = $this->serviceRepository->findAll();
        return ServiceMapper::toDTOArray($services);
    }
    
    /**
     * Récupère un service par son ID (retourne un DTO)
     */
    public function getServiceById($id) {
        if (empty($id)) {
            throw new Exception('ID service requis');
        }
        
        $service = $this->serviceRepository->findById($id);
        
        if (!$service) {
            throw new Exception('Service non trouvé');
        }
        
        return ServiceMapper::toDTO($service);
    }
    
    /**
     * Crée un nouveau service
     */
    public function createService($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà
        $existing = $this->serviceRepository->findByNom($data['nom']);
        if ($existing) {
            throw new Exception('Un service avec ce nom existe déjà');
        }
        
        // Convertir le statut en ID si nécessaire
        $statutId = null;
        if (!empty($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
        }
        if (!$statutId) {
            $statutId = $this->convertStatutToId('ACTIF');
        }
        
        // Créer le service (sans responsable - sera assigné séparément)
        $service = new Service([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'responsable_id' => null, // Responsable assigné séparément
            'statut' => $statutId
        ]);
        
        $createdService = $this->serviceRepository->create($service);
        return ServiceMapper::toDTO($createdService);
    }
    
    /**
     * Met à jour un service
     */
    public function updateService($id, $data) {
        $service = $this->serviceRepository->findById($id);
        
        if (!$service) {
            throw new Exception('Service non trouvé');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $service->getNom()) {
            $existing = $this->serviceRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Un service avec ce nom existe déjà');
            }
            $service->setNom($data['nom']);
        }
        
        if (isset($data['description'])) {
            $service->setDescription($data['description']);
        }
        
        // Ne pas mettre à jour le responsable ici - c'est géré séparément
        // Le responsable est géré via updateServiceResponsable()
        
        if (isset($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
            if ($statutId) {
                $service->setStatut($statutId);
            }
        }
        
        $success = $this->serviceRepository->update($service);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedService = $this->serviceRepository->findById($id);
        return ServiceMapper::toDTO($updatedService);
    }
    
    /**
     * Supprime un service
     */
    public function deleteService($id) {
        if (empty($id)) {
            throw new Exception('ID service requis');
        }
        
        $service = $this->serviceRepository->findById($id);
        
        if (!$service) {
            throw new Exception('Service non trouvé');
        }
        
        try {
            $this->serviceRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Met à jour uniquement le responsable d'un service
     */
    public function updateServiceResponsable($serviceId, $responsableId) {
        if (empty($serviceId)) {
            throw new Exception('ID service requis');
        }
        
        $service = $this->serviceRepository->findById($serviceId);
        
        if (!$service) {
            throw new Exception('Service non trouvé');
        }
        
        // Vérifier si le responsable existe (si fourni)
        if (!empty($responsableId)) {
            $responsable = $this->userRepository->findById($responsableId);
            if (!$responsable) {
                throw new Exception('Responsable non trouvé');
            }
        }
        
        // Mettre à jour uniquement le responsable
        $service->setResponsableId($responsableId ?: null);
        
        $success = $this->serviceRepository->update($service);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du responsable');
        }
        
        $updatedService = $this->serviceRepository->findById($serviceId);
        return ServiceMapper::toDTO($updatedService);
    }
}

