<?php
require_once __DIR__ . '/../repository/SiteRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../models/Site.php';
require_once __DIR__ . '/../mappers/SiteMapper.php';
require_once __DIR__ . '/ParametreService.php';

/**
 * Classe SiteService - Service pour la gestion des sites
 * Retourne des DTOs pour l'API
 */
class SiteService {
    private $siteRepository;
    private $userRepository;
    private $parametreService;
    
    public function __construct() {
        $this->siteRepository = new SiteRepository();
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
     * Récupère tous les sites (retourne des DTOs)
     */
    public function getAllSites() {
        $sites = $this->siteRepository->findAll();
        return SiteMapper::toDTOArray($sites);
    }
    
    /**
     * Récupère un site par son ID (retourne un DTO)
     */
    public function getSiteById($id) {
        if (empty($id)) {
            throw new Exception('ID site requis');
        }
        
        $site = $this->siteRepository->findById($id);
        
        if (!$site) {
            throw new Exception('Site non trouvé');
        }
        
        return SiteMapper::toDTO($site);
    }
    
    /**
     * Crée un nouveau site
     */
    public function createSite($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà
        $existing = $this->siteRepository->findByNom($data['nom']);
        if ($existing) {
            throw new Exception('Un site avec ce nom existe déjà');
        }
        
        // Convertir le statut en ID si nécessaire
        $statutId = null;
        if (!empty($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
        }
        if (!$statutId) {
            $statutId = $this->convertStatutToId('ACTIF');
        }
        
        // Créer le site (sans manager - sera assigné séparément)
        $site = new Site([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'manager_id' => null, // Manager assigné séparément
            'statut' => $statutId
        ]);
        
        $createdSite = $this->siteRepository->create($site);
        return SiteMapper::toDTO($createdSite);
    }
    
    /**
     * Met à jour un site
     */
    public function updateSite($id, $data) {
        $site = $this->siteRepository->findById($id);
        
        if (!$site) {
            throw new Exception('Site non trouvé');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $site->getNom()) {
            $existing = $this->siteRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Un site avec ce nom existe déjà');
            }
            $site->setNom($data['nom']);
        }
        
        if (isset($data['description'])) {
            $site->setDescription($data['description']);
        }
        
        // Ne pas mettre à jour le manager ici - c'est géré séparément
        // Le manager est géré via updateSiteManager()
        
        if (isset($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
            if ($statutId) {
                $site->setStatut($statutId);
            }
        }
        
        $success = $this->siteRepository->update($site);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedSite = $this->siteRepository->findById($id);
        return SiteMapper::toDTO($updatedSite);
    }
    
    /**
     * Supprime un site
     */
    public function deleteSite($id) {
        if (empty($id)) {
            throw new Exception('ID site requis');
        }
        
        $site = $this->siteRepository->findById($id);
        
        if (!$site) {
            throw new Exception('Site non trouvé');
        }
        
        try {
            $this->siteRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Met à jour uniquement le manager d'un site
     */
    public function updateSiteManager($siteId, $managerId) {
        if (empty($siteId)) {
            throw new Exception('ID site requis');
        }
        
        $site = $this->siteRepository->findById($siteId);
        
        if (!$site) {
            throw new Exception('Site non trouvé');
        }
        
        // Vérifier si le manager existe (si fourni)
        if (!empty($managerId)) {
            $manager = $this->userRepository->findById($managerId);
            if (!$manager) {
                throw new Exception('Manager non trouvé');
            }
        }
        
        // Mettre à jour uniquement le manager
        $site->setManagerId($managerId ?: null);
        
        $success = $this->siteRepository->update($site);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du manager');
        }
        
        $updatedSite = $this->siteRepository->findById($siteId);
        return SiteMapper::toDTO($updatedSite);
    }
}

