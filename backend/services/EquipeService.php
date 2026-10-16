<?php
require_once __DIR__ . '/../repository/EquipeRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../models/Equipe.php';
require_once __DIR__ . '/../mappers/EquipeMapper.php';
require_once __DIR__ . '/../mappers/UserMapper.php';
require_once __DIR__ . '/ParametreService.php';

/**
 * Classe EquipeService - Service pour la gestion des équipes
 * Retourne des DTOs pour l'API
 */
class EquipeService {
    private $equipeRepository;
    private $userRepository;
    private $parametreService;
    
    public function __construct() {
        $this->equipeRepository = new EquipeRepository();
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
     * Récupère toutes les équipes (retourne des DTOs)
     */
    public function getAllEquipes() {
        $equipes = $this->equipeRepository->findAll();
        return EquipeMapper::toDTOArray($equipes);
    }
    
    /**
     * Récupère une équipe par son ID (retourne un DTO)
     */
    public function getEquipeById($id) {
        if (empty($id)) {
            throw new Exception('ID équipe requis');
        }
        
        $equipe = $this->equipeRepository->findById($id);
        
        if (!$equipe) {
            throw new Exception('Équipe non trouvée');
        }
        
        return EquipeMapper::toDTO($equipe);
    }
    
    /**
     * Récupère les utilisateurs d'une équipe
     */
    public function getUsersByEquipeId($equipeId) {
        if (empty($equipeId)) {
            throw new Exception('ID équipe requis');
        }
        
        $users = $this->equipeRepository->findUsersByEquipeId($equipeId);
        return $users;
    }
    
    /**
     * Crée une nouvelle équipe
     */
    public function createEquipe($data) {
        // Validation des données requises
        $required = ['nom'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le nom existe déjà
        $existing = $this->equipeRepository->findByNom($data['nom']);
        if ($existing) {
            throw new Exception('Une équipe avec ce nom existe déjà');
        }
        
        // Convertir le statut en ID si nécessaire
        $statutId = null;
        if (!empty($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
        }
        if (!$statutId) {
            $statutId = $this->convertStatutToId('ACTIF');
        }
        
        // Créer l'équipe (sans manager - sera assigné séparément)
        $equipe = new Equipe([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'manager_id' => null, // Manager assigné séparément
            'statut' => $statutId
        ]);
        
        $createdEquipe = $this->equipeRepository->create($equipe);
        return EquipeMapper::toDTO($createdEquipe);
    }
    
    /**
     * Met à jour une équipe
     */
    public function updateEquipe($id, $data) {
        $equipe = $this->equipeRepository->findById($id);
        
        if (!$equipe) {
            throw new Exception('Équipe non trouvée');
        }
        
        // Vérifier si le nom est modifié et s'il existe déjà
        if (isset($data['nom']) && $data['nom'] !== $equipe->getNom()) {
            $existing = $this->equipeRepository->findByNom($data['nom']);
            if ($existing) {
                throw new Exception('Une équipe avec ce nom existe déjà');
            }
            $equipe->setNom($data['nom']);
        }
        
        if (isset($data['description'])) {
            $equipe->setDescription($data['description']);
        }
        
        // Ne pas mettre à jour le manager ici - c'est géré séparément
        // Le manager est géré via updateEquipeManager()
        
        if (isset($data['statut'])) {
            $statutId = $this->convertStatutToId($data['statut']);
            if ($statutId) {
                $equipe->setStatut($statutId);
            }
        }
        
        $success = $this->equipeRepository->update($equipe);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        $updatedEquipe = $this->equipeRepository->findById($id);
        return EquipeMapper::toDTO($updatedEquipe);
    }
    
    /**
     * Supprime une équipe
     */
    public function deleteEquipe($id) {
        if (empty($id)) {
            throw new Exception('ID équipe requis');
        }
        
        $equipe = $this->equipeRepository->findById($id);
        
        if (!$equipe) {
            throw new Exception('Équipe non trouvée');
        }
        
        try {
            $this->equipeRepository->delete($id);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Ajoute un membre à une équipe
     */
    public function addMemberToEquipe($equipeId, $userId) {
        if (empty($equipeId)) {
            throw new Exception('ID équipe requis');
        }
        
        if (empty($userId)) {
            throw new Exception('ID utilisateur requis');
        }
        
        // Vérifier que l'équipe existe
        $equipe = $this->equipeRepository->findById($equipeId);
        if (!$equipe) {
            throw new Exception('Équipe non trouvée');
        }
        
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Vérifier si l'utilisateur est déjà dans cette équipe
        $existingUsers = $this->equipeRepository->findUsersByEquipeId($equipeId);
        foreach ($existingUsers as $existingUser) {
            if ($existingUser->getId() == $userId) {
                throw new Exception('Cet utilisateur est déjà membre de cette équipe');
            }
        }
        
        // Ajouter l'utilisateur à l'équipe
        $success = $this->userRepository->updateEquipeId($userId, $equipeId);
        
        if (!$success) {
            throw new Exception('Erreur lors de l\'ajout du membre');
        }
        
        return true;
    }
    
    /**
     * Retire un membre d'une équipe
     */
    public function removeMemberFromEquipe($equipeId, $userId) {
        if (empty($equipeId)) {
            throw new Exception('ID équipe requis');
        }
        
        if (empty($userId)) {
            throw new Exception('ID utilisateur requis');
        }
        
        // Vérifier que l'utilisateur est bien dans cette équipe
        $users = $this->equipeRepository->findUsersByEquipeId($equipeId);
        $userInEquipe = false;
        foreach ($users as $user) {
            if ($user->getId() == $userId) {
                $userInEquipe = true;
                break;
            }
        }
        
        if (!$userInEquipe) {
            throw new Exception('Cet utilisateur n\'est pas membre de cette équipe');
        }
        
        // Retirer l'utilisateur de l'équipe (mettre equipe_id à NULL)
        $success = $this->userRepository->updateEquipeId($userId, null);
        
        if (!$success) {
            throw new Exception('Erreur lors du retrait du membre');
        }
        
        return true;
    }
    
    /**
     * Récupère les membres d'une équipe (retourne des DTOs)
     */
    public function getEquipeMembers($equipeId) {
        if (empty($equipeId)) {
            throw new Exception('ID équipe requis');
        }
        
        $users = $this->equipeRepository->findUsersByEquipeId($equipeId);
        return array_map(function($user) {
            return UserMapper::toDTO($user);
        }, $users);
    }
    
    /**
     * Met à jour uniquement le manager d'une équipe
     */
    public function updateEquipeManager($equipeId, $managerId) {
        if (empty($equipeId)) {
            throw new Exception('ID équipe requis');
        }
        
        $equipe = $this->equipeRepository->findById($equipeId);
        
        if (!$equipe) {
            throw new Exception('Équipe non trouvée');
        }
        
        // Vérifier si le manager existe (si fourni)
        if (!empty($managerId)) {
            $manager = $this->userRepository->findById($managerId);
            if (!$manager) {
                throw new Exception('Manager non trouvé');
            }
        }
        
        // Mettre à jour uniquement le manager
        $equipe->setManagerId($managerId ?: null);
        
        $success = $this->equipeRepository->update($equipe);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du manager');
        }
        
        $updatedEquipe = $this->equipeRepository->findById($equipeId);
        return EquipeMapper::toDTO($updatedEquipe);
    }
}

