<?php
/**
 * Backend API - Approche orientée objet avec couche Service
 * Architecture: Controller -> Service -> Repository -> Database
 */

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger l'autoloader
require_once __DIR__ . '/autoload.php';

// Charger Database explicitement pour s'assurer qu'il est disponible
require_once __DIR__ . '/config/Database.php';

// Initialiser les Services
$userService = new UserService();
$pointageService = new PointageService();
$planningService = new PlanningService();
$rapportService = new RapportService();
$profilService = new ProfilService();
$serviceService = new SrvService();
$habilitationService = new HabilitationService();
$typeParametreService = new TypeParametreService();
$parametreService = new ParametreService();
$equipeService = new EquipeService();
$tacheService = new TacheService();
$siteService = new SiteService();
$jourService = new JourService();

// Récupérer les données de la requête
$input = json_decode(file_get_contents('php://input'), true);
$queryParams = $_GET;

$response = [];

try {
    $action = $input['action'] ?? $queryParams['action'] ?? null;

    
    
    if (!$action) {
        $response = [
            'success' => false,
            'message' => 'Aucune action spécifiée'
        ];
    } else {
        switch ($action) {
            case 'login':
                if (isset($input['matricule'], $input['password'])) {
                    try {
                        $user = $userService->authenticate($input['matricule'], $input['password']);
                        
                        session_start();
                        $_SESSION['user'] = $user->toArray();
                        
                        $response = [
                            'success' => true,
                            'user' => $user->toArray(),
                            'message' => 'Connexion réussie'
                        ];
                    } catch (Exception $e) {
                        echo $e->getMessage();
                        $response = [
                            'success' => false,
                            'message' => $e->getMessage()
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Matricule et mot de passe requis'
                    ];
                }
                break;

            case 'get_pointages':
                try {
                    $filters = [];
                    if (isset($input['user_id']) || isset($queryParams['user_id'])) {
                        $filters['user_id'] = $input['user_id'] ?? $queryParams['user_id'];
                    }
                    if (isset($input['date_debut']) || isset($queryParams['date_debut'])) {
                        $filters['date_debut'] = $input['date_debut'] ?? $queryParams['date_debut'];
                    }
                    if (isset($input['date_fin']) || isset($queryParams['date_fin'])) {
                        $filters['date_fin'] = $input['date_fin'] ?? $queryParams['date_fin'];
                    }
                    
                    $pointages = $pointageService->getPointages($filters);
                    
                    $response = [
                        'success' => true,
                        'pointages' => $pointages
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_pointage':
                try {
                    $pointage = $pointageService->createPointage([
                        'user_id' => $input['user_id'] ?? null,
                        'type' => $input['type'] ?? null,
                        'date_pointage' => $input['date_pointage'] ?? null,
                        'heure' => $input['heure'] ?? null,
                        'localisation' => $input['localisation'] ?? null,
                        'latitude' => $input['latitude'] ?? null,
                        'longitude' => $input['longitude'] ?? null,
                        'mode_travail' => $input['mode_travail'] ?? 'bureau',
                        'notes' => $input['notes'] ?? null
                    ]);
                    
                    $pointageId = $pointage instanceof PointageDTO ? $pointage->id : ($pointage ? $pointage->getId() : null);
                    $response = [
                        'success' => true,
                        'message' => 'Pointage enregistré avec succès',
                        'pointage' => $pointage instanceof PointageDTO ? $pointage->toArray() : ($pointage ? $pointage->toArray() : null),
                        'id' => $pointageId
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_planning':
                try {
                    $filters = [];
                    if (isset($input['user_id']) || isset($queryParams['user_id'])) {
                        $filters['user_id'] = $input['user_id'] ?? $queryParams['user_id'];
                    }
                    if (isset($input['date_debut']) || isset($queryParams['date_debut'])) {
                        $filters['date_debut'] = $input['date_debut'] ?? $queryParams['date_debut'];
                    }
                    if (isset($input['date_fin']) || isset($queryParams['date_fin'])) {
                        $filters['date_fin'] = $input['date_fin'] ?? $queryParams['date_fin'];
                    }
                    
                    $plannings = $planningService->getPlannings($filters);
                    $planningsArray = array_map(function($p) {
                        return $p instanceof PlanningDTO ? $p->toArray() : $p;
                    }, $plannings);
                    
                    $response = [
                        'success' => true,
                        'plannings' => $planningsArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_planning':
                try {
                    $planning = $planningService->createPlanning([
                        'user_id' => $input['user_id'] ?? null,
                        'site_id' => $input['site_id'] ?? null,
                        'jour_id' => $input['jour_id'] ?? null,
                        'date_debut' => $input['date_debut'] ?? null,
                        'date_fin' => $input['date_fin'] ?? null,
                        'heure_debut' => $input['heure_debut'] ?? null,
                        'heure_fin' => $input['heure_fin'] ?? null,
                        'description' => $input['description'] ?? null,
                        'lieu' => $input['lieu'] ?? null
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Planning créé avec succès',
                        'planning' => $planning instanceof PlanningDTO ? $planning->toArray() : $planning,
                        'id' => $planning instanceof PlanningDTO ? $planning->id : $planning->getId()
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_planning':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    if (!$id) {
                        throw new Exception('ID planning requis');
                    }
                    
                    $planning = $planningService->updatePlanning($id, [
                        'user_id' => $input['user_id'] ?? null,
                        'site_id' => $input['site_id'] ?? null,
                        'jour_id' => $input['jour_id'] ?? null,
                        'date_debut' => $input['date_debut'] ?? null,
                        'date_fin' => $input['date_fin'] ?? null,
                        'heure_debut' => $input['heure_debut'] ?? null,
                        'heure_fin' => $input['heure_fin'] ?? null,
                        'description' => $input['description'] ?? null,
                        'lieu' => $input['lieu'] ?? null
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Planning mis à jour avec succès',
                        'planning' => $planning instanceof PlanningDTO ? $planning->toArray() : $planning
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_planning':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    if (!$id) {
                        throw new Exception('ID planning requis');
                    }
                    
                    $planningService->deletePlanning($id);
                    $response = [
                        'success' => true,
                        'message' => 'Planning supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_rapport':
                try {
                    $userId = $input['user_id'] ?? $queryParams['user_id'] ?? null;
                    $dateDebut = $input['date_debut'] ?? $queryParams['date_debut'] ?? null;
                    $dateFin = $input['date_fin'] ?? $queryParams['date_fin'] ?? null;
                    
                    $rapport = $rapportService->generateRapport($userId, $dateDebut, $dateFin);
                    
                    $response = [
                        'success' => true,
                        'rapport' => $rapport
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_employes':
                try {
                    $filters = [];
                    if (isset($input['service']) || isset($queryParams['service'])) {
                        $filters['service'] = $input['service'] ?? $queryParams['service'];
                    }
                    if (isset($input['statut']) || isset($queryParams['statut'])) {
                        $filters['statut'] = $input['statut'] ?? $queryParams['statut'];
                    }
                    
                    $employes = $userService->getAllUsers($filters);
                    $employesArray = array_map(function($e) {
                        return $e instanceof UserDTO ? $e->toArray() : $e;
                    }, $employes);
                    
                    $response = [
                        'success' => true,
                        'employes' => $employesArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_employe':
                try {
                    $user = $userService->createUser([
                        'matricule' => $input['matricule'] ?? null,
                        'nom' => $input['nom'] ?? null,
                        'email' => $input['email'] ?? null,
                        'password' => $input['password'] ?? null,
                        'service_id' => $input['service_id'] ?? $input['service'] ?? null,
                        'horaire' => $input['horaire'] ?? '08:00-17:00',
                        'profil_id' => $input['profil_id'] ?? $input['role'] ?? null,
                        'statut' => $input['statut'] ?? 'actif',
                        'telephone' => $input['telephone'] ?? null,
                        'date_embauche' => $input['date_embauche'] ?? null
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Employé créé avec succès',
                        'user' => $user instanceof UserDTO ? $user->toArray() : $user,
                        'id' => $user instanceof UserDTO ? $user->id : $user->getId()
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_employe':
                try {
                    $id = $input['id'] ?? null;
                    $matricule = $input['matricule'] ?? null;
                    
                    $user = $userService->updateUser($id, $matricule, $input);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Employé mis à jour avec succès',
                        'user' => $user instanceof UserDTO ? $user->toArray() : $user
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_user_service':
                try {
                    $userId = $input['user_id'] ?? null;
                    $serviceId = $input['service_id'] ?? null;
                    
                    if (!$userId) {
                        throw new Exception('ID utilisateur requis');
                    }
                    
                    $user = $userService->updateUserService($userId, $serviceId);
                    $response = [
                        'success' => true,
                        'message' => $serviceId ? 'Service assigné avec succès' : 'Service retiré avec succès',
                        'user' => $user instanceof UserDTO ? $user->toArray() : $user
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_user_profil':
                try {
                    $userId = $input['user_id'] ?? null;
                    $profilId = $input['profil_id'] ?? null;
                    
                    if (!$userId || !$profilId) {
                        throw new Exception('ID utilisateur et ID profil requis');
                    }
                    
                    $user = $userService->updateUserProfil($userId, $profilId);
                    $response = [
                        'success' => true,
                        'message' => 'Profil assigné avec succès',
                        'user' => $user instanceof UserDTO ? $user->toArray() : $user
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_user_statut':
                try {
                    $userId = $input['user_id'] ?? null;
                    $statut = $input['statut'] ?? null;
                    
                    if (!$userId || !$statut) {
                        throw new Exception('ID utilisateur et statut requis');
                    }
                    
                    $user = $userService->updateUserStatut($userId, $statut);
                    $response = [
                        'success' => true,
                        'message' => 'Statut mis à jour avec succès',
                        'user' => $user instanceof UserDTO ? $user->toArray() : $user
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'delete_employe':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $matricule = $input['matricule'] ?? $queryParams['matricule'] ?? null;
                    
                    $userService->deleteUser($id, $matricule);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Employé supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_dashboard_stats':
                try {
                    $userId = $input['user_id'] ?? $queryParams['user_id'] ?? null;
                    
                    $today = date('Y-m-d');
                    $firstDay = date('Y-m-01');
                    $lastDay = date('Y-m-t');
                    
                    $stats = $pointageService->getStats($userId, $firstDay, $lastDay);
                    $recentPointages = $pointageService->getRecentPointages($userId, 10);
                    $recentPointagesArray = array_map(function($p) {
                        return $p instanceof PointageDTO ? $p->toArray() : $p;
                    }, $recentPointages);
                    
                    $response = [
                        'success' => true,
                        'stats' => $stats,
                        'recent_pointages' => $recentPointagesArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_all_pointages':
                try {
                    $filters = [];
                    if (isset($input['date_debut']) || isset($queryParams['date_debut'])) {
                        $filters['date_debut'] = $input['date_debut'] ?? $queryParams['date_debut'];
                    }
                    if (isset($input['date_fin']) || isset($queryParams['date_fin'])) {
                        $filters['date_fin'] = $input['date_fin'] ?? $queryParams['date_fin'];
                    }
                    if (isset($input['employe_id']) || isset($queryParams['employe_id'])) {
                        $filters['employe_id'] = $input['employe_id'] ?? $queryParams['employe_id'];
                    }
                    
                    $pointages = $pointageService->getPointages($filters);
                    $pointagesArray = array_map(function($p) {
                        return $p instanceof PointageDTO ? $p->toArray() : $p;
                    }, $pointages);
                    
                    $response = [
                        'success' => true,
                        'pointages' => $pointagesArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_pointage':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    
                    $pointageService->deletePointage($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Pointage supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            // Gestion des Profils
            case 'get_profils':
                try {
                    $profils = $profilService->getAllProfils();
                    $profilsArray = array_map(function($p) {
                        return $p instanceof ProfilDTO ? $p->toArray() : $p;
                    }, $profils);
                    
                    $response = [
                        'success' => true,
                        'profils' => $profilsArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_profil':
                try {
                    $profil = $profilService->createProfil([
                        'nom' => $input['nom'] ?? null,
                        'description' => $input['description'] ?? null,
                        'habilitations' => $input['habilitations'] ?? []
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Profil créé avec succès',
                        'profil' => $profil instanceof ProfilDTO ? $profil->toArray() : $profil,
                        'id' => $profil instanceof ProfilDTO ? $profil->id : $profil->getId()
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_profil':
                try {
                    $id = $input['id'] ?? null;
                    
                    $profil = $profilService->updateProfil($id, $input);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Profil mis à jour avec succès',
                        'profil' => $profil instanceof ProfilDTO ? $profil->toArray() : $profil
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_profil':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    
                    $profilService->deleteProfil($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Profil supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            // Gestion des Services
            case 'get_services':
                try {
                    $services = $serviceService->getAllServices();
                    $servicesArray = array_map(function($s) {
                        return $s instanceof ServiceDTO ? $s->toArray() : $s;
                    }, $services);
                    
                    $response = [
                        'success' => true,
                        'services' => $servicesArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_service':
                try {
                    $service = $serviceService->createService([
                        'nom' => $input['nom'] ?? null,
                        'description' => $input['description'] ?? null,
                        'responsable_id' => $input['responsable_id'] ?? null,
                        'statut' => $input['statut'] ?? 'actif'
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Service créé avec succès',
                        'service' => $service instanceof ServiceDTO ? $service->toArray() : $service,
                        'id' => $service instanceof ServiceDTO ? $service->id : ($service ? $service->getId() : null)
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_service':
                try {
                    $id = $input['id'] ?? null;
                    
                    $service = $serviceService->updateService($id, $input);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Service mis à jour avec succès',
                        'service' => $service instanceof ServiceDTO ? $service->toArray() : $service
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_service':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    
                    $serviceService->deleteService($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Service supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_service_responsable':
                try {
                    $serviceId = $input['service_id'] ?? null;
                    $responsableId = $input['responsable_id'] ?? null;
                    
                    if (!$serviceId) {
                        throw new Exception('ID service requis');
                    }
                    
                    $service = $serviceService->updateServiceResponsable($serviceId, $responsableId);
                    $response = [
                        'success' => true,
                        'message' => $responsableId ? 'Responsable assigné avec succès' : 'Responsable retiré avec succès',
                        'service' => $service instanceof ServiceDTO ? $service->toArray() : $service
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            // Gestion des Habilitations
            case 'get_habilitations':
                try {
                    $habilitations = $habilitationService->getAllHabilitations();
                    $habilitationsArray = array_map(function($h) {
                        return $h instanceof HabilitationDTO ? $h->toArray() : $h;
                    }, $habilitations);
                    
                    $response = [
                        'success' => true,
                        'habilitations' => $habilitationsArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_habilitations_by_profil':
                try {
                    $profilId = $input['profil_id'] ?? $queryParams['profil_id'] ?? null;
                    
                    $habilitations = $habilitationService->getHabilitationsByProfilId($profilId);
                    $habilitationsArray = array_map(function($h) {
                        return $h instanceof HabilitationDTO ? $h->toArray() : $h;
                    }, $habilitations);
                    
                    $response = [
                        'success' => true,
                        'habilitations' => $habilitationsArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_habilitation':
                try {
                    $habilitation = $habilitationService->createHabilitation([
                        'nom' => $input['nom'] ?? null,
                        'description' => $input['description'] ?? null
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Habilitation créée avec succès',
                        'habilitation' => $habilitation instanceof HabilitationDTO ? $habilitation->toArray() : $habilitation,
                        'id' => $habilitation instanceof HabilitationDTO ? $habilitation->id : $habilitation->getId()
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_habilitation':
                try {
                    $id = $input['id'] ?? null;
                    
                    $habilitation = $habilitationService->updateHabilitation($id, $input);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Habilitation mise à jour avec succès',
                        'habilitation' => $habilitation instanceof HabilitationDTO ? $habilitation->toArray() : $habilitation
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_habilitation':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    
                    $habilitationService->deleteHabilitation($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Habilitation supprimée avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            // Gestion des Types de Paramètres
            case 'get_type_parametres':
                try {
                    $typeParametres = $typeParametreService->getAllTypeParametres();
                    $typeParametresArray = array_map(function($tp) {
                        return $tp instanceof TypeParametreDTO ? $tp->toArray() : $tp;
                    }, $typeParametres);
                    
                    $response = [
                        'success' => true,
                        'type_parametres' => $typeParametresArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_type_parametre':
                try {
                    $typeParametre = $typeParametreService->createTypeParametre([
                        'nom' => $input['nom'] ?? null,
                        'code' => $input['code'] ?? null,
                        'description' => $input['description'] ?? null
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Type de paramètre créé avec succès',
                        'type_parametre' => $typeParametre instanceof TypeParametreDTO ? $typeParametre->toArray() : $typeParametre,
                        'id' => $typeParametre instanceof TypeParametreDTO ? $typeParametre->id : $typeParametre->getId()
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_type_parametre':
                try {
                    $id = $input['id'] ?? null;
                    
                    $typeParametre = $typeParametreService->updateTypeParametre($id, $input);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Type de paramètre mis à jour avec succès',
                        'type_parametre' => $typeParametre instanceof TypeParametreDTO ? $typeParametre->toArray() : $typeParametre
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_type_parametre':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    
                    $typeParametreService->deleteTypeParametre($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Type de paramètre supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            // Gestion des Paramètres
            case 'get_parametres':
                try {
                    $filters = [];
                    if (isset($input['type_parametre_id']) || isset($queryParams['type_parametre_id'])) {
                        $filters['type_parametre_id'] = $input['type_parametre_id'] ?? $queryParams['type_parametre_id'];
                    }
                    
                    $parametres = $parametreService->getAllParametres($filters);
                    $parametresArray = array_map(function($p) {
                        return $p instanceof ParametreDTO ? $p->toArray() : $p;
                    }, $parametres);
                    
                    $response = [
                        'success' => true,
                        'parametres' => $parametresArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'get_parametres_by_type':
                try {
                    $typeParametreId = $input['type_parametre_id'] ?? $queryParams['type_parametre_id'] ?? null;
                    
                    $parametres = $parametreService->getParametresByTypeId($typeParametreId);
                    $parametresArray = array_map(function($p) {
                        return $p instanceof ParametreDTO ? $p->toArray() : $p;
                    }, $parametres);
                    
                    $response = [
                        'success' => true,
                        'parametres' => $parametresArray
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'create_parametre':
                try {
                    $parametre = $parametreService->createParametre([
                        'nom' => $input['nom'] ?? null,
                        'code' => $input['code'] ?? null,
                        'description' => $input['description'] ?? null,
                        'type_parametre_id' => $input['type_parametre_id'] ?? null
                    ]);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Paramètre créé avec succès',
                        'parametre' => $parametre instanceof ParametreDTO ? $parametre->toArray() : $parametre,
                        'id' => $parametre instanceof ParametreDTO ? $parametre->id : $parametre->getId()
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'update_parametre':
                try {
                    $id = $input['id'] ?? null;
                    
                    $parametre = $parametreService->updateParametre($id, $input);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Paramètre mis à jour avec succès',
                        'parametre' => $parametre instanceof ParametreDTO ? $parametre->toArray() : $parametre
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            case 'delete_parametre':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    
                    $parametreService->deleteParametre($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Paramètre supprimé avec succès'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                break;

            // ========== GESTION DES ÉQUIPES ==========
            case 'get_equipes':
                try {
                    $equipes = $equipeService->getAllEquipes();
                    $response = [
                        'success' => true,
                        'equipes' => array_map(function($e) {
                            return $e instanceof EquipeDTO ? $e->toArray() : $e;
                        }, $equipes)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_equipe':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $equipe = $equipeService->getEquipeById($id);
                    $response = [
                        'success' => true,
                        'equipe' => $equipe instanceof EquipeDTO ? $equipe->toArray() : $equipe
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_equipe_members':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $members = $equipeService->getEquipeMembers($id);
                    $response = [
                        'success' => true,
                        'members' => array_map(function($m) {
                            return $m instanceof UserDTO ? $m->toArray() : $m;
                        }, $members)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'add_equipe_member':
                try {
                    $equipeId = $input['equipe_id'] ?? null;
                    $userId = $input['user_id'] ?? null;
                    
                    if (!$equipeId || !$userId) {
                        throw new Exception('ID équipe et ID utilisateur requis');
                    }
                    
                    $equipeService->addMemberToEquipe($equipeId, $userId);
                    $response = [
                        'success' => true,
                        'message' => 'Membre ajouté à l\'équipe avec succès'
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'remove_equipe_member':
                try {
                    $equipeId = $input['equipe_id'] ?? null;
                    $userId = $input['user_id'] ?? null;
                    
                    if (!$equipeId || !$userId) {
                        throw new Exception('ID équipe et ID utilisateur requis');
                    }
                    
                    $equipeService->removeMemberFromEquipe($equipeId, $userId);
                    $response = [
                        'success' => true,
                        'message' => 'Membre retiré de l\'équipe avec succès'
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_equipe_manager':
                try {
                    $equipeId = $input['equipe_id'] ?? null;
                    $managerId = $input['manager_id'] ?? null;
                    
                    if (!$equipeId) {
                        throw new Exception('ID équipe requis');
                    }
                    
                    $equipe = $equipeService->updateEquipeManager($equipeId, $managerId);
                    $response = [
                        'success' => true,
                        'message' => $managerId ? 'Manager assigné avec succès' : 'Manager retiré avec succès',
                        'equipe' => $equipe instanceof EquipeDTO ? $equipe->toArray() : $equipe
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'create_equipe':
                try {
                    $equipe = $equipeService->createEquipe($input);
                    $response = [
                        'success' => true,
                        'message' => 'Équipe créée avec succès',
                        'equipe' => $equipe instanceof EquipeDTO ? $equipe->toArray() : $equipe
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_equipe':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $equipe = $equipeService->updateEquipe($id, $input);
                    $response = [
                        'success' => true,
                        'message' => 'Équipe mise à jour avec succès',
                        'equipe' => $equipe instanceof EquipeDTO ? $equipe->toArray() : $equipe
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'delete_equipe':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $equipeService->deleteEquipe($id);
                    $response = ['success' => true, 'message' => 'Équipe supprimée avec succès'];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            // ========== GESTION DES TÂCHES ==========
            case 'get_taches':
                try {
                    $filters = [];
                    if (isset($input['utilisateur_id']) || isset($queryParams['utilisateur_id'])) {
                        $filters['utilisateur_id'] = $input['utilisateur_id'] ?? $queryParams['utilisateur_id'];
                    }
                    $taches = $tacheService->getAllTaches($filters);
                    $response = [
                        'success' => true,
                        'taches' => array_map(function($t) {
                            return $t instanceof TacheDTO ? $t->toArray() : $t;
                        }, $taches)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_tache':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $tache = $tacheService->getTacheById($id);
                    $response = [
                        'success' => true,
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_user_taches':
                try {
                    $utilisateurId = $input['utilisateur_id'] ?? $queryParams['utilisateur_id'] ?? null;
                    $taches = $tacheService->getTachesByUtilisateurId($utilisateurId);
                    $response = [
                        'success' => true,
                        'taches' => array_map(function($t) {
                            return $t instanceof TacheDTO ? $t->toArray() : $t;
                        }, $taches)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_taches_by_planning':
                try {
                    $planningId = $input['planning_id'] ?? $queryParams['planning_id'] ?? null;
                    if (!$planningId) {
                        throw new Exception('ID planning requis');
                    }
                    $taches = $tacheService->getTachesByPlanningId($planningId);
                    $response = [
                        'success' => true,
                        'taches' => array_map(function($t) {
                            return $t instanceof TacheDTO ? $t->toArray() : $t;
                        }, $taches)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_available_taches':
                try {
                    $planningId = $input['planning_id'] ?? $queryParams['planning_id'] ?? null;
                    $taches = $tacheService->getAvailableTaches($planningId);
                    $response = [
                        'success' => true,
                        'taches' => array_map(function($t) {
                            return $t instanceof TacheDTO ? $t->toArray() : $t;
                        }, $taches)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'assign_tache_to_planning':
                try {
                    $tacheId = $input['tache_id'] ?? null;
                    $planningId = $input['planning_id'] ?? null;
                    if (!$tacheId || !$planningId) {
                        throw new Exception('ID tâche et ID planning requis');
                    }
                    $tache = $tacheService->assignTacheToPlanning($tacheId, $planningId);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche assignée au planning avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'remove_tache_from_planning':
                try {
                    $tacheId = $input['tache_id'] ?? null;
                    if (!$tacheId) {
                        throw new Exception('ID tâche requis');
                    }
                    $tache = $tacheService->removeTacheFromPlanning($tacheId);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche retirée du planning avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'create_tache':
                try {
                    $tache = $tacheService->createTache($input);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche créée avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_tache':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $tache = $tacheService->updateTache($id, $input);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche mise à jour avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'delete_tache':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $tacheService->deleteTache($id);
                    $response = ['success' => true, 'message' => 'Tâche supprimée avec succès'];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'demarrer_tache':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    if (!$id) {
                        throw new Exception('ID tâche requis');
                    }
                    $tache = $tacheService->demarrerTache($id);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche démarrée avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'suspendre_tache':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    if (!$id) {
                        throw new Exception('ID tâche requis');
                    }
                    $tache = $tacheService->suspendreTache($id);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche suspendue avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'terminer_tache':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    if (!$id) {
                        throw new Exception('ID tâche requis');
                    }
                    $tache = $tacheService->terminerTache($id);
                    $response = [
                        'success' => true,
                        'message' => 'Tâche terminée avec succès',
                        'tache' => $tache instanceof TacheDTO ? $tache->toArray() : $tache
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            // ========== GESTION DES SITES ==========
            case 'get_sites':
                try {
                    $sites = $siteService->getAllSites();
                    $response = [
                        'success' => true,
                        'sites' => array_map(function($s) {
                            return $s instanceof SiteDTO ? $s->toArray() : $s;
                        }, $sites)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_site':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $site = $siteService->getSiteById($id);
                    $response = [
                        'success' => true,
                        'site' => $site instanceof SiteDTO ? $site->toArray() : $site
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'create_site':
                try {
                    $site = $siteService->createSite($input);
                    $response = [
                        'success' => true,
                        'message' => 'Site créé avec succès',
                        'site' => $site instanceof SiteDTO ? $site->toArray() : $site
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_site':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $site = $siteService->updateSite($id, $input);
                    $response = [
                        'success' => true,
                        'message' => 'Site mis à jour avec succès',
                        'site' => $site instanceof SiteDTO ? $site->toArray() : $site
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'delete_site':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $siteService->deleteSite($id);
                    $response = ['success' => true, 'message' => 'Site supprimé avec succès'];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_site_manager':
                try {
                    $siteId = $input['site_id'] ?? null;
                    $managerId = $input['manager_id'] ?? null;
                    
                    if (!$siteId) {
                        throw new Exception('ID site requis');
                    }
                    
                    $site = $siteService->updateSiteManager($siteId, $managerId);
                    $response = [
                        'success' => true,
                        'message' => $managerId ? 'Manager assigné avec succès' : 'Manager retiré avec succès',
                        'site' => $site instanceof SiteDTO ? $site->toArray() : $site
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            // ========== GESTION DES JOURS ==========
            case 'get_jours':
                try {
                    $jours = $jourService->getAllJours();
                    $response = [
                        'success' => true,
                        'jours' => array_map(function($j) {
                            return $j instanceof JourDTO ? $j->toArray() : $j;
                        }, $jours)
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'get_jour':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $jour = $jourService->getJourById($id);
                    $response = [
                        'success' => true,
                        'jour' => $jour instanceof JourDTO ? $jour->toArray() : $jour
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'create_jour':
                try {
                    $jour = $jourService->createJour($input);
                    $response = [
                        'success' => true,
                        'message' => 'Jour créé avec succès',
                        'jour' => $jour instanceof JourDTO ? $jour->toArray() : $jour
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'update_jour':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $jour = $jourService->updateJour($id, $input);
                    $response = [
                        'success' => true,
                        'message' => 'Jour mis à jour avec succès',
                        'jour' => $jour instanceof JourDTO ? $jour->toArray() : $jour
                    ];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            case 'delete_jour':
                try {
                    $id = $input['id'] ?? $queryParams['id'] ?? null;
                    $jourService->deleteJour($id);
                    $response = ['success' => true, 'message' => 'Jour supprimé avec succès'];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;

            default:
                $response = [
                    'success' => false,
                    'message' => 'Action non reconnue: ' . $action
                ];
        }
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
    $response = [
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>
