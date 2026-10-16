<?php
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../mappers/UserMapper.php';
require_once __DIR__ . '/../repository/ServiceRepository.php';
require_once __DIR__ . '/../repository/ProfilRepository.php';
require_once __DIR__ . '/ParametreService.php';

/**
 * Classe UserService - Service pour la gestion des utilisateurs
 * Contient la logique métier pour les utilisateurs
 * Retourne des DTOs pour l'API
 */
class UserService {
    private $userRepository;
    private $serviceRepo;
    private $profilRepo;
    private $parametreService;
    
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->profilRepo = new ProfilRepository();
        $this->parametreService = new ParametreService();
    }
    
    /**
     * Convertit un nom de statut en ID
     */
    private function convertStatutToId($statut) {
        if (empty($statut)) {
            return null;
        }
        // Si c'est déjà un ID numérique, le retourner
        if (is_numeric($statut)) {
            return (int)$statut;
        }
        // Sinon, convertir le nom en ID
        $statutId = $this->parametreService->convertNomToId(strtoupper($statut), 'TYPPARAM0001');
        return $statutId;
    }
    
    /**
     * Authentifie un utilisateur (retourne le modèle pour la session)
     */
    public function authenticate($matricule, $password) {
        if (empty($matricule) || empty($password)) {
            throw new Exception('Matricule et mot de passe requis');
        }
        $userDTO = null;
        $user = $this->userRepository->authenticate($matricule, $password);

        
        
        if (!$user) {
            throw new Exception('Matricule ou mot de passe incorrect');
        }

        $service = $this->serviceRepo->findById($user->getServiceId());
        $profil = $this->profilRepo->findById($user->getProfilId());
        $additionalData = [
            'service_nom' => $service->getNom(),
            'profil_nom' => $profil->getNom()
        ];
           
        $userDTO = UserMapper::toDTO($user, $additionalData);
        
        return $userDTO;
    }
    
    /**
     * Récupère tous les utilisateurs avec filtres (retourne des DTOs)
     */
    public function getAllUsers($filters = []) {
        $users = $this->userRepository->findAll($filters);
        
        // Convertir en DTOs avec données supplémentaires
        $dtoArray = [];
        foreach ($users as $user) {
            $service = null;
            $profil = null;
            if($user->getServiceId()){
                $service = $this->serviceRepo->findById($user->getServiceId());
            }
            if($user->getProfilId()){
                $profil = $this->profilRepo->findById($user->getProfilId());
            }

            
            $additionalData = [
                'service_nom' => $service ? $service->getNom() : null,
                'profil_nom' => $profil ? $profil->getNom() : null
            ];
            // Les données supplémentaires sont déjà dans le modèle grâce aux JOINs
            // if ($user->getService()) {
            //     $additionalData['service_nom'] = $user->getService();
            // }
            // if ($user->getRole()) {
            //     $additionalData['profil_nom'] = $user->getRole();
            // }
            $dtoArray[] = UserMapper::toDTO($user, $additionalData);
        }
        
        return $dtoArray;
    }
    
    /**
     * Récupère un utilisateur par son ID (retourne un DTO)k
     */
    public function getUserById($id) {
        if (empty($id)) {
            throw new Exception('ID utilisateur requis');
        }
        
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        $additionalData = [];
        // if ($user->getService()) {
        //     $additionalData['service_nom'] = $user->getService();
        // }
        // if ($user->getRole()) {
        //     $additionalData['profil_nom'] = $user->getRole();
        // }
        
        return UserMapper::toDTO($user, $additionalData);
    }
    
    /**
     * Récupère un utilisateur par son matricule (retourne un DTO)
     */
    public function getUserByMatricule($matricule) {
        if (empty($matricule)) {
            throw new Exception('Matricule requis');
        }
        
        $user = $this->userRepository->findByMatricule($matricule);
        
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        $additionalData = [];
        // if ($user->getService()) {
        //     $additionalData['service_nom'] = $user->getService();
        // }
        // if ($user->getRole()) {
        //     $additionalData['profil_nom'] = $user->getRole();
        // }
        
        return UserMapper::toDTO($user, $additionalData);
    }
    
    /**
     * Crée un nouvel utilisateur
     */
    public function createUser($data) {
        // Validation des données requises
        $required = ['matricule', 'nom', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ '$field' est requis");
            }
        }
        
        // Vérifier si le matricule existe déjà
        $existing = $this->userRepository->findByMatricule($data['matricule']);
        if ($existing) {
            throw new Exception('Un employé avec ce matricule existe déjà');
        }
        
        // Vérifier si l'email existe déjà
        $allUsers = $this->userRepository->findAll();
        foreach ($allUsers as $user) {
            if ($user->getEmail() === $data['email']) {
                throw new Exception('Un employé avec cet email existe déjà');
            }
        }
    
        
       
        
        // Service, profil et statut seront assignés séparément
        // Utiliser des valeurs par défaut pour la création
        $profilId = null;
        $profil = $this->profilRepo->findByNom('employe');
        $profilId = $profil ? $profil->getId() : 4; // ID par défaut
        
        // Par défaut, utiliser ACTIF
        $statutId = $this->convertStatutToId('ACTIF');
        
        // Créer l'utilisateur (sans service - sera assigné séparément)
        $user = new User([
            'matricule' => $data['matricule'],
            'nom' => $data['nom'],
            'email' => $data['email'],
            'mot_de_passe' => (new User())->hashPassword($data['password']),
            'service_id' => null, // Service assigné séparément
            'profil_id' => $profilId, // Profil par défaut (peut être changé séparément)
            'horaire' => $data['horaire'] ?? '08:00-17:00',
            'statut' => $statutId, // Statut par défaut ACTIF (peut être changé séparément)
            'telephone' => $data['telephone'] ?? null,
            'date_embauche' => $data['date_embauche'] ?? null
        ]);
        
        $createdUser = $this->userRepository->create($user);

        $service = $this->serviceRepo->findById($createdUser->getServiceId());
        $profil = $this->profilRepo->findById($createdUser->getProfilId());
        // $additionalData = [
        //     'service_nom' => $service->getNom(),
        //     'profil_nom' => $profil->getNom()
        // ];

        $additionalData = [];
        
        return UserMapper::toDTO($createdUser, $additionalData);
    }
    
    /**
     * Met à jour un utilisateur
     */
    public function updateUser($id, $matricule, $data) {
        // Récupérer l'utilisateur
        if ($id) {
            $user = $this->userRepository->findById($id);
        } elseif ($matricule) {
            $user = $this->userRepository->findByMatricule($matricule);
        } else {
            throw new Exception('ID ou matricule requis');
        }
        
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Préparer les données de mise à jour
        $updateData = [];
        
        if (isset($data['nom'])) {
            $updateData['nom'] = $data['nom'];
        }
        if (isset($data['email'])) {
            // Vérifier si l'email n'est pas déjà utilisé par un autre utilisateur
            $allUsers = $this->userRepository->findAll();
            foreach ($allUsers as $u) {
                if ($u->getId() != $user->getId() && $u->getEmail() === $data['email']) {
                    throw new Exception('Cet email est déjà utilisé par un autre utilisateur');
                }
            }
            $updateData['email'] = $data['email'];
        }
        // Ne pas mettre à jour service_id, profil_id et statut ici - ils sont gérés séparément
        // Le service est géré via updateUserService()
        // Le profil est géré via updateUserProfil()
        // Le statut est géré via updateUserStatut()
        
        if (isset($data['horaire'])) {
            $updateData['horaire'] = $data['horaire'];
        }
        if (isset($data['telephone'])) {
            $updateData['telephone'] = $data['telephone'];
        }
        if (isset($data['date_embauche'])) {
            $updateData['date_embauche'] = $data['date_embauche'];
        }
        if (isset($data['password'])) {
            if (strlen($data['password']) < 6) {
                throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
            }
            $updateData['mot_de_passe'] = (new User())->hashPassword($data['password']);
        }
        
        if (empty($updateData)) {
            throw new Exception('Aucune donnée à mettre à jour');
        }
        
        // Mettre à jour
        $success = $this->userRepository->updateByMatricule($user->getMatricule(), $updateData);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour');
        }
        
        // Récupérer l'utilisateur mis à jour avec les relations
        $updatedUser = $this->userRepository->findByMatricule($user->getMatricule());
        $additionalData = [];
        // if ($updatedUser->getService()) {
        //     $additionalData['service_nom'] = $updatedUser->getService();
        // }
        // if ($updatedUser->getRole()) {
        //     $additionalData['profil_nom'] = $updatedUser->getRole();
        // }
        
        $service = $this->serviceRepo->findById($updatedUser->getServiceId());
        $profil = $this->profilRepo->findById($updatedUser->getProfilId());
        $additionalData = [
            'service_nom' => $service ? $service->getNom() : null,
            'profil_nom' => $profil ? $profil->getNom() : null
        ];
        
        return UserMapper::toDTO($updatedUser, $additionalData);
    }
    
    /**
     * Met à jour uniquement le service d'un utilisateur
     */
    public function updateUserService($userId, $serviceId) {
        if (empty($userId)) {
            throw new Exception('ID utilisateur requis');
        }
        
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Vérifier si le service existe (si fourni)
        if (!empty($serviceId)) {
            $service = $this->serviceRepo->findById($serviceId);
            if (!$service) {
                throw new Exception('Service non trouvé');
            }
        }
        
        // Mettre à jour uniquement le service
        $success = $this->userRepository->updateByMatricule($user->getMatricule(), [
            'service_id' => $serviceId ?: null
        ]);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du service');
        }
        
        $updatedUser = $this->userRepository->findById($userId);
        $service = $this->serviceRepo->findById($updatedUser->getServiceId());
        $profil = $this->profilRepo->findById($updatedUser->getProfilId());
        $additionalData = [
            'service_nom' => $service ? $service->getNom() : null,
            'profil_nom' => $profil ? $profil->getNom() : null
        ];
        
        return UserMapper::toDTO($updatedUser, $additionalData);
    }
    
    /**
     * Met à jour uniquement le profil d'un utilisateur
     */
    public function updateUserProfil($userId, $profilId) {
        if (empty($userId)) {
            throw new Exception('ID utilisateur requis');
        }
        
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Vérifier si le profil existe
        if (empty($profilId)) {
            throw new Exception('ID profil requis');
        }
        
        $profil = $this->profilRepo->findById($profilId);
        if (!$profil) {
            throw new Exception('Profil non trouvé');
        }
        
        // Mettre à jour uniquement le profil
        $success = $this->userRepository->updateByMatricule($user->getMatricule(), [
            'profil_id' => $profilId
        ]);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du profil');
        }
        
        $updatedUser = $this->userRepository->findById($userId);
        $service = $this->serviceRepo->findById($updatedUser->getServiceId());
        $profil = $this->profilRepo->findById($updatedUser->getProfilId());
        $additionalData = [
            'service_nom' => $service ? $service->getNom() : null,
            'profil_nom' => $profil ? $profil->getNom() : null
        ];
        
        return UserMapper::toDTO($updatedUser, $additionalData);
    }
    
    /**
     * Met à jour uniquement le statut d'un utilisateur
     */
    public function updateUserStatut($userId, $statut) {
        if (empty($userId)) {
            throw new Exception('ID utilisateur requis');
        }
        
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Convertir le statut en ID si nécessaire
        $statutId = $this->convertStatutToId($statut);
        if (!$statutId) {
            throw new Exception('Statut invalide');
        }
        
        // Mettre à jour uniquement le statut
        $success = $this->userRepository->updateByMatricule($user->getMatricule(), [
            'statut' => $statutId
        ]);
        
        if (!$success) {
            throw new Exception('Erreur lors de la mise à jour du statut');
        }
        
        $updatedUser = $this->userRepository->findById($userId);
        $service = $this->serviceRepo->findById($updatedUser->getServiceId());
        $profil = $this->profilRepo->findById($updatedUser->getProfilId());
        $additionalData = [
            'service_nom' => $service ? $service->getNom() : null,
            'profil_nom' => $profil ? $profil->getNom() : null
        ];
        
        return UserMapper::toDTO($updatedUser, $additionalData);
    }
    
    /**
     * Supprime un utilisateur
     */
    public function deleteUser($id, $matricule) {
        if ($id) {
            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new Exception('Utilisateur non trouvé');
            }
            $success = $this->userRepository->delete($id);
        } elseif ($matricule) {
            $user = $this->userRepository->findByMatricule($matricule);
            if (!$user) {
                throw new Exception('Utilisateur non trouvé');
            }
            // Empêcher la suppression de l'admin
            if ($user->getMatricule() === 'admin') {
                throw new Exception('Impossible de supprimer le compte administrateur principal');
            }
            $success = $this->userRepository->deleteByMatricule($matricule);
        } else {
            throw new Exception('ID ou matricule requis');
        }
        
        if (!$success) {
            throw new Exception('Erreur lors de la suppression');
        }
        
        return true;
    }
}

