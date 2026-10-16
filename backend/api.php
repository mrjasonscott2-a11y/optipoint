<?php
/**
 * OptiPoint - API REST centralisée
 * Point d'entrée unique pour toutes les requêtes frontend
 * Toutes les actions sont routées via le paramètre ?action=xxx
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

// ========================================
// ROUTAGE DES ACTIONS
// ========================================
switch ($action) {

    // ========================================
    // AUTHENTIFICATION
    // ========================================
    case 'login':
        if ($method !== 'POST') jsonResponse(['error' => 'Méthode non autorisée'], 405);
        $matricule = $input['matricule'] ?? '';
        $password = $input['password'] ?? '';
        if (empty($matricule) || empty($password)) {
            jsonResponse(['error' => 'Matricule et mot de passe requis'], 400);
        }
        $stmt = $pdo->prepare("SELECT u.*, r.nom as role_nom FROM utilisateurs u LEFT JOIN roles r ON u.role_id = r.id WHERE u.matricule = ? AND u.statut = 'actif'");
        $stmt->execute([$matricule]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            jsonResponse(['error' => 'Matricule ou mot de passe incorrect'], 401);
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        unset($user['mot_de_passe']);
        logAction($user['id'], 'Connexion', "Connexion réussie");
        jsonResponse(['success' => true, 'user' => $user]);
        break;

    case 'logout':
        $userId = $_SESSION['user_id'] ?? null;
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'user':
        $userId = checkAuth();
        $user = getCurrentUser();
        if ($user) jsonResponse($user);
        else jsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        break;

    // ========================================
    // GESTION DES UTILISATEURS (Admin)
    // ========================================
    case 'users':
        // Lecture : admin + responsable ; Écriture : admin uniquement
        $userId = checkAuth(2);
        $isAdmin = hasRole(1);
        if ($method === 'GET') {
            $params = [];
            $sql = "SELECT u.id, u.matricule, u.nom, u.email, u.service, u.horaire, u.role_id, u.statut, u.date_embauche, u.telephone, u.manager_id, r.nom as role_nom FROM utilisateurs u LEFT JOIN roles r ON u.role_id = r.id WHERE 1=1";
            // Le responsable (role 2) voit tous les utilisateurs non-admin
            // (employés, managers et autres responsables créés par l'admin)
            if (!$isAdmin) {
                $sql .= " AND u.role_id <> 1";
            }
            // Filtres optionnels
            if (!empty($_GET['role_id'])) { $sql .= " AND u.role_id = ?"; $params[] = $_GET['role_id']; }
            if (!empty($_GET['service'])) { $sql .= " AND u.service = ?"; $params[] = $_GET['service']; }
            if (!empty($_GET['statut'])) { $sql .= " AND u.statut = ?"; $params[] = $_GET['statut']; }
            if (!empty($_GET['search'])) { $sql .= " AND (u.nom LIKE ? OR u.matricule LIKE ? OR u.email LIKE ?)"; $s = "%".$_GET['search']."%"; $params[] = $s; $params[] = $s; $params[] = $s; }
            if (!empty($_GET['equipe_id'])) {
                $sql .= " AND u.id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id = ?)";
                $params[] = $_GET['equipe_id'];
            }
            if (!empty($_GET['departement'])) { $sql .= " AND u.service = ?"; $params[] = $_GET['departement']; }
            $sql .= " ORDER BY u.id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'POST') {
            $userId = checkAuth(1); // Création : admin uniquement
            $fields = ['matricule','nom','email','password','service','horaire','role_id','statut','date_embauche','telephone','manager_id'];
            $data = [];
            foreach ($fields as $f) {
                if (isset($input[$f]) && $input[$f] !== '') $data[$f] = $input[$f];
            }
            if (empty($data['matricule']) || empty($data['nom']) || empty($data['email']) || empty($data['password'])) {
                jsonResponse(['error' => 'Matricule, nom, email et mot de passe requis'], 400);
            }
            // Vérifier doublon matricule/email
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE matricule = ? OR email = ?");
            $check->execute([$data['matricule'], $data['email']]);
            if ($check->fetch()) jsonResponse(['error' => 'Matricule ou email déjà utilisé'], 400);
            $data['mot_de_passe'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
            $data['horaire'] = $data['horaire'] ?? '08:00-17:00';
            $data['role_id'] = $data['role_id'] ?? 4;
            $data['statut'] = $data['statut'] ?? 'actif';
            $data['date_embauche'] = $data['date_embauche'] ?? date('Y-m-d');
            $cols = implode(', ', array_keys($data));
            $vals = implode(', ', array_fill(0, count($data), '?'));
            $stmt = $pdo->prepare("INSERT INTO utilisateurs ($cols) VALUES ($vals)");
            $stmt->execute(array_values($data));
            $newId = $pdo->lastInsertId();
            logAction($userId, 'Création utilisateur', "Utilisateur {$data['nom']} ({$data['matricule']}) créé");
            jsonResponse(['success' => true, 'id' => $newId]);
        } elseif ($method === 'PUT') {
            $userId = checkAuth(1); // Modification : admin uniquement
            $id = $input['id'] ?? 0;
            if (!$id) jsonResponse(['error' => 'ID requis'], 400);
            $fields = ['nom','email','service','horaire','role_id','statut','telephone','manager_id'];
            $sets = []; $params = [];
            foreach ($fields as $f) {
                if (isset($input[$f])) { $sets[] = "$f = ?"; $params[] = $input[$f]; }
            }
            if (!empty($input['password'])) {
                $sets[] = "mot_de_passe = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            if (empty($sets)) jsonResponse(['error' => 'Aucune donnée à mettre à jour'], 400);
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE utilisateurs SET " . implode(', ', $sets) . " WHERE id = ?");
            $stmt->execute($params);
            logAction($userId, 'Modification utilisateur', "Utilisateur ID $id modifié");
            jsonResponse(['success' => true]);
        } elseif ($method === 'DELETE') {
            $userId = checkAuth(1); // Suppression : admin uniquement
            $id = $input['id'] ?? 0;
            if (!$id) jsonResponse(['error' => 'ID requis'], 400);
            if ($id == $userId) jsonResponse(['error' => 'Impossible de supprimer votre propre compte'], 400);
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id]);
            logAction($userId, 'Suppression utilisateur', "Utilisateur ID $id supprimé");
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // POINTAGES
    // ========================================
    case 'pointage':
        $userId = checkAuth();
        if ($method === 'POST') {
            $type = $input['type'] ?? '';
            $mode = $input['mode'] ?? 'bureau';
            $location = $input['location'] ?? '';
            $latitude = $input['latitude'] ?? null;
            $longitude = $input['longitude'] ?? null;
            $notes = $input['notes'] ?? '';
            if (!in_array($type, ['arrivee', 'depart'])) jsonResponse(['error' => 'Type invalide'], 400);
            $today = date('Y-m-d');
            $time = date('H:i:s');
            // Vérifier s'il n'y a pas déjà un pointage du même type aujourd'hui
            $check = $pdo->prepare("SELECT id FROM pointages WHERE utilisateur_id = ? AND date_pointage = ? AND type = ?");
            $check->execute([$userId, $today, $type]);
            if ($check->fetch()) jsonResponse(['error' => 'Un pointage de ' . $type . ' existe déjà aujourd\'hui'], 400);
            // Déterminer le statut (retard si arrivée après le seuil)
            $statut = 'normal';
            if ($type === 'arrivee') {
                $user = $pdo->prepare("SELECT horaire FROM utilisateurs WHERE id = ?");
                $user->execute([$userId]);
                $u = $user->fetch();
                $horaire = $u['horaire'] ?? '08:00-17:00';
                $heureDebut = explode('-', $horaire)[0] ?? '08:00';
                list($h, $m) = explode(':', $heureDebut);
                $seuil = sprintf('%02d:%02d:%02d', $h, intval($m) + 15, 0); // +15 min
                if ($time > $seuil) $statut = 'retard';
            }
            $stmt = $pdo->prepare("INSERT INTO pointages (utilisateur_id, type, date_pointage, heure, localisation, latitude, longitude, mode_travail, notes, statut) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$userId, $type, $today, $time, $location, $latitude, $longitude, $mode, $notes, $statut]);
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'heure' => $time, 'statut' => $statut]);
        } elseif ($method === 'GET') {
            $params = [];
            $sql = "SELECT p.*, u.nom as employe_nom, u.matricule, u.service FROM pointages p LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id WHERE 1=1";
            $isAdmin = hasRole(1);
            $isManager = hasRole(3);
            if (!$isAdmin) {
                if ($isManager) {
                    $sql .= " AND (p.utilisateur_id = ? OR p.utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN (SELECT id FROM equipes WHERE manager_id = ?)))";
                    $params[] = $userId; $params[] = $userId;
                } else {
                    $sql .= " AND p.utilisateur_id = ?";
                    $params[] = $userId;
                }
            }
            if (!empty($_GET['user_id'])) { $sql .= " AND p.utilisateur_id = ?"; $params[] = $_GET['user_id']; }
            if (!empty($_GET['date'])) { $sql .= " AND p.date_pointage = ?"; $params[] = $_GET['date']; }
            if (!empty($_GET['date_debut'])) { $sql .= " AND p.date_pointage >= ?"; $params[] = $_GET['date_debut']; }
            if (!empty($_GET['date_fin'])) { $sql .= " AND p.date_pointage <= ?"; $params[] = $_GET['date_fin']; }
            if (!empty($_GET['type'])) { $sql .= " AND p.type = ?"; $params[] = $_GET['type']; }
            if (!empty($_GET['mode_travail'])) { $sql .= " AND p.mode_travail = ?"; $params[] = $_GET['mode_travail']; }
            elseif (!empty($_GET['mode'])) { $sql .= " AND p.mode_travail = ?"; $params[] = $_GET['mode']; }
            $sql .= " ORDER BY p.date_pointage DESC, p.heure DESC";
            if (!empty($_GET['limit'])) { $sql .= " LIMIT " . intval($_GET['limit']); }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'DELETE') {
            $userId = checkAuth(1);
            $id = $input['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM pointages WHERE id = ?");
            $stmt->execute([$id]);
            logAction($userId, 'Suppression pointage', "Pointage ID $id supprimé");
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // PLANNINGS
    // ========================================
    case 'planning':
        $userId = checkAuth();
        if ($method === 'GET') {
            $params = [];
            $sql = "SELECT p.*, u.nom as employe_nom FROM plannings p LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id WHERE 1=1";
            $isAdmin = hasRole(1);
            $isManager = hasRole(3);
            if (!$isAdmin) {
                if ($isManager) {
                    $sql .= " AND (p.utilisateur_id = ? OR p.utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN (SELECT equipe_id FROM equipes WHERE manager_id = ?)))";
                    $params[] = $userId; $params[] = $userId;
                } else {
                    $sql .= " AND p.utilisateur_id = ?";
                    $params[] = $userId;
                }
            }
            if (!empty($_GET['user_id'])) { $sql .= " AND p.utilisateur_id = ?"; $params[] = $_GET['user_id']; }
            if (!empty($_GET['date_debut'])) { $sql .= " AND p.date_fin >= ?"; $params[] = $_GET['date_debut']; }
            if (!empty($_GET['date_fin'])) { $sql .= " AND p.date_debut <= ?"; $params[] = $_GET['date_fin']; }
            $sql .= " ORDER BY p.date_debut ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'POST') {
            $required = ['utilisateur_id','date_debut','date_fin','description'];
            foreach ($required as $r) {
                if (empty($input[$r])) jsonResponse(['error' => "Champ $r requis"], 400);
            }
            $stmt = $pdo->prepare("INSERT INTO plannings (utilisateur_id, date_debut, date_fin, heure_debut, heure_fin, description, lieu, type_activite, couleur) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $input['utilisateur_id'], $input['date_debut'], $input['date_fin'],
                $input['heure_debut'] ?? null, $input['heure_fin'] ?? null, $input['description'],
                $input['lieu'] ?? '', $input['type_activite'] ?? 'bureau', $input['couleur'] ?? '#4A6FA5'
            ]);
            if (checkAuth(1)) logAction($userId, 'Création planning', "Planning créé pour utilisateur ID {$input['utilisateur_id']}");
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        } elseif ($method === 'PUT') {
            $id = $input['id'] ?? 0;
            if (!$id) jsonResponse(['error' => 'ID requis'], 400);
            $fields = ['date_debut','date_fin','heure_debut','heure_fin','description','lieu','type_activite','couleur'];
            $sets = []; $params = [];
            foreach ($fields as $f) {
                if (isset($input[$f])) { $sets[] = "$f = ?"; $params[] = $input[$f]; }
            }
            if (empty($sets)) jsonResponse(['error' => 'Aucune donnée à mettre à jour'], 400);
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE plannings SET " . implode(', ', $sets) . " WHERE id = ?");
            $stmt->execute($params);
            jsonResponse(['success' => true]);
        } elseif ($method === 'DELETE') {
            $id = $input['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM plannings WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // RAPPORTS
    // ========================================
    case 'rapport':
        $userId = checkAuth();
        if ($method === 'GET') {
            $debut = $_GET['debut'] ?? date('Y-m-01');
            $fin = $_GET['fin'] ?? date('Y-m-t');
            $userFilter = $_GET['user_id'] ?? null;
            $isAdmin = hasRole(1);
            $isManager = hasRole(3);
            $params = [];
            $sql = "SELECT p.date_pointage, u.id as utilisateur_id, u.nom as employe_nom,
                           MIN(CASE WHEN p.type = 'arrivee' THEN p.heure END) as arrivee,
                           MAX(CASE WHEN p.type = 'depart' THEN p.heure END) as depart,
                           GROUP_CONCAT(DISTINCT p.mode_travail) as modes,
                           MAX(p.localisation) as localisation
                    FROM pointages p
                    LEFT JOIN utilisateurs u ON p.utilisateur_id = u.id
                    WHERE p.date_pointage BETWEEN ? AND ?";
            $params[] = $debut;
            $params[] = $fin;
            if ($userFilter) {
                $sql .= " AND p.utilisateur_id = ?";
                $params[] = $userFilter;
            } elseif (!$isAdmin) {
                if ($isManager) {
                    $sql .= " AND (p.utilisateur_id = ? OR p.utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN (SELECT equipe_id FROM equipes WHERE manager_id = ?)))";
                    $params[] = $userId; $params[] = $userId;
                } else {
                    $sql .= " AND p.utilisateur_id = ?";
                    $params[] = $userId;
                }
            }
            $sql .= " GROUP BY p.date_pointage, u.id, u.nom ORDER BY p.date_pointage DESC, u.nom";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            $totalHeures = 0;
            $joursPresents = 0;
            $retards = 0;
            foreach ($data as &$row) {
                if ($row['arrivee'] && $row['depart']) {
                    $arr = new DateTime($row['arrivee']);
                    $dep = new DateTime($row['depart']);
                    $diff = $dep->diff($arr);
                    $heures = $diff->h + $diff->i/60;
                    $row['heures'] = round($heures, 2);
                    $totalHeures += $heures;
                    $joursPresents++;
                } else {
                    $row['heures'] = 0;
                    if ($row['arrivee']) $joursPresents++;
                }
                // Compter retards
                if ($row['arrivee'] && $row['arrivee'] > '08:15:00') $retards++;
            }
            jsonResponse([
                'pointages' => $data,
                'total_heures' => round($totalHeures, 2),
                'jours_presents' => $joursPresents,
                'retards' => $retards
            ]);
        }
        break;

    // ========================================
    // STATISTIQUES
    // ========================================
    case 'stats':
        $userId = checkAuth();
        if ($method !== 'GET') jsonResponse(['error' => 'Méthode non autorisée'], 405);
        $isAdmin = hasRole(1);
        $isResponsable = hasRole(2);
        $isManager = hasRole(3);
        $today = date('Y-m-d');
        $debutSemaine = date('Y-m-d', strtotime('monday this week'));
        
        if ($isAdmin) {
            // Stats globales admin
            $totalEmp = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role_id > 1 AND statut = 'actif'")->fetchColumn();
            $pointagesToday = $pdo->prepare("SELECT COUNT(DISTINCT utilisateur_id) FROM pointages WHERE date_pointage = ? AND type = 'arrivee'");
            $pointagesToday->execute([$today]);
            $pointagesToday = $pointagesToday->fetchColumn();
            $retardsToday = $pdo->prepare("SELECT COUNT(*) FROM pointages WHERE date_pointage = ? AND type = 'arrivee' AND heure > '08:15:00'");
            $retardsToday->execute([$today]);
            $retardsToday = $retardsToday->fetchColumn();
            $teletravailStmt = $pdo->prepare("SELECT COUNT(DISTINCT utilisateur_id) FROM pointages WHERE date_pointage = ? AND type = 'arrivee' AND mode_travail = 'teletravail'");
            $teletravailStmt->execute([$today]);
            $teletravailToday = $teletravailStmt->fetchColumn();
            $congesEnAttente = $pdo->query("SELECT COUNT(*) FROM conges WHERE statut = 'en_attente'")->fetchColumn();
            // Stats par service
            $statsService = $pdo->query("SELECT service, COUNT(*) as effectif FROM utilisateurs WHERE role_id > 1 AND statut = 'actif' GROUP BY service")->fetchAll();
            // Pointages des 7 derniers jours
            $last7 = $pdo->prepare("SELECT DATE(date_pointage) as jour, COUNT(DISTINCT utilisateur_id) as presents FROM pointages WHERE date_pointage >= DATE_SUB(?, INTERVAL 7 DAY) AND type = 'arrivee' GROUP BY DATE(date_pointage) ORDER BY jour");
            $last7->execute([$today]);
            $last7 = $last7->fetchAll();
            jsonResponse([
                'total_employes' => intval($totalEmp),
                'pointages_aujourdhui' => intval($pointagesToday),
                'absents_aujourdhui' => max(0, intval($totalEmp) - intval($pointagesToday)),
                'retards_aujourdhui' => intval($retardsToday),
                'teletravail_aujourdhui' => intval($teletravailToday),
                'conges_en_attente' => intval($congesEnAttente),
                'stats_service' => $statsService,
                'last_7_days' => $last7
            ]);
        } elseif ($isResponsable) {
            // Stats responsable : toute sa hiérarchie (managers directs + leurs employés)
            $scopeSql = "(SELECT id FROM utilisateurs WHERE manager_id = ?
                         UNION
                         SELECT id FROM utilisateurs WHERE manager_id IN (SELECT id FROM utilisateurs WHERE manager_id = ?))";
            $scopeParams = [$userId, $userId];

            $totalCollab = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif' AND id IN ($scopeSql)");
            $totalCollab->execute($scopeParams);
            $totalCollab = intval($totalCollab->fetchColumn());

            $presents = $pdo->prepare("SELECT COUNT(DISTINCT utilisateur_id) FROM pointages WHERE date_pointage = ? AND type = 'arrivee' AND utilisateur_id IN ($scopeSql)");
            $presents->execute(array_merge([$today], $scopeParams));
            $presents = intval($presents->fetchColumn());

            $retardsCount = $pdo->prepare("SELECT COUNT(*) FROM pointages WHERE date_pointage = ? AND type = 'arrivee' AND heure > '08:15:00' AND utilisateur_id IN ($scopeSql)");
            $retardsCount->execute(array_merge([$today], $scopeParams));
            $retardsCount = intval($retardsCount->fetchColumn());

            $congesEnAttente = $pdo->prepare("SELECT COUNT(*) FROM conges WHERE statut = 'en_attente' AND utilisateur_id IN ($scopeSql)");
            $congesEnAttente->execute($scopeParams);
            $congesEnAttente = intval($congesEnAttente->fetchColumn());

            $statsService = $pdo->prepare("SELECT service, COUNT(*) as effectif FROM utilisateurs WHERE statut = 'actif' AND id IN ($scopeSql) GROUP BY service");
            $statsService->execute($scopeParams);
            $statsService = $statsService->fetchAll();

            $last7 = $pdo->prepare("SELECT DATE(date_pointage) as jour, COUNT(DISTINCT utilisateur_id) as presents FROM pointages WHERE date_pointage >= DATE_SUB(?, INTERVAL 7 DAY) AND type = 'arrivee' AND utilisateur_id IN ($scopeSql) GROUP BY DATE(date_pointage) ORDER BY jour");
            $last7->execute(array_merge([$today], $scopeParams));
            $last7 = $last7->fetchAll();

            jsonResponse([
                'total_collaborateurs' => $totalCollab,
                'presents_aujourdhui' => $presents,
                'taux_presence' => $totalCollab > 0 ? round($presents / $totalCollab * 100, 1) : 0,
                'retards_aujourdhui' => $retardsCount,
                'absents_aujourdhui' => max(0, $totalCollab - $presents),
                'conges_en_attente' => $congesEnAttente,
                'stats_service' => $statsService,
                'last_7_days' => $last7
            ]);
        } elseif ($isManager) {
            // Stats manager (ses équipes)
            $equipes = $pdo->prepare("SELECT id FROM equipes WHERE manager_id = ?");
            $equipes->execute([$userId]);
            $equipeIds = $equipes->fetchAll(PDO::FETCH_COLUMN);
            $totalCollab = 0;
            $presents = 0;
            $retardsCount = 0;
            $congesEnAttente = 0;
            if (count($equipeIds) > 0) {
                $inPlace = implode(',', array_fill(0, count($equipeIds), '?'));
                $totalCollab = $pdo->prepare("SELECT COUNT(DISTINCT utilisateur_id) FROM utilisateur_equipes WHERE equipe_id IN ($inPlace)");
                $totalCollab->execute($equipeIds);
                $totalCollab = $totalCollab->fetchColumn();
                $presents = $pdo->prepare("SELECT COUNT(DISTINCT utilisateur_id) FROM pointages WHERE date_pointage = ? AND type = 'arrivee' AND utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN ($inPlace))");
                $presents->execute(array_merge([$today], $equipeIds));
                $presents = $presents->fetchColumn();
                $retardsCount = $pdo->prepare("SELECT COUNT(*) FROM pointages WHERE date_pointage = ? AND type = 'arrivee' AND heure > '08:15:00' AND utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN ($inPlace))");
                $retardsCount->execute(array_merge([$today], $equipeIds));
                $retardsCount = $retardsCount->fetchColumn();
                $congesEnAttente = $pdo->prepare("SELECT COUNT(*) FROM conges WHERE statut = 'en_attente' AND utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN ($inPlace))");
                $congesEnAttente->execute($equipeIds);
                $congesEnAttente = $congesEnAttente->fetchColumn();
            }
            jsonResponse([
                'total_collaborateurs' => intval($totalCollab),
                'presents_aujourdhui' => intval($presents),
                'taux_presence' => $totalCollab > 0 ? round(intval($presents) / intval($totalCollab) * 100, 1) : 0,
                'retards_aujourdhui' => intval($retardsCount),
                'absents_aujourdhui' => max(0, intval($totalCollab) - intval($presents)),
                'conges_en_attente' => intval($congesEnAttente)
            ]);
        } else {
            // Stats employé
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pointages WHERE utilisateur_id = ? AND date_pointage = ? AND type = 'arrivee'");
            $stmt->execute([$userId, $today]);
            $todayPointages = $stmt->fetchColumn();
            // Pointages de la semaine
            $stmtWeek = $pdo->prepare("SELECT date_pointage, MIN(CASE WHEN type='arrivee' THEN heure END) as arrivee, MAX(CASE WHEN type='depart' THEN heure END) as depart FROM pointages WHERE utilisateur_id = ? AND date_pointage >= ? GROUP BY date_pointage ORDER BY date_pointage DESC");
            $stmtWeek->execute([$userId, $debutSemaine]);
            $weekData = $stmtWeek->fetchAll();
            $totalHours = 0;
            foreach ($weekData as $w) {
                if ($w['arrivee'] && $w['depart']) {
                    $a = new DateTime($w['arrivee']); $d = new DateTime($w['depart']);
                    $diff = $d->diff($a); $totalHours += $diff->h + $diff->i/60;
                }
            }
            jsonResponse([
                'today_pointages' => intval($todayPointages),
                'heures_semaine' => round($totalHours, 2),
                'jours_semaine' => count($weekData)
            ]);
        }
        break;

    // ========================================
    // ÉQUIPES
    // ========================================
    case 'equipes':
        $userId = checkAuth();
        if ($method === 'GET') {
            $params = [];
            $sql = "SELECT e.*, (SELECT COUNT(*) FROM utilisateur_equipes ue WHERE ue.equipe_id = e.id) as nb_membres FROM equipes e WHERE 1=1";
            if (!empty($_GET['manager_id'])) { $sql .= " AND e.manager_id = ?"; $params[] = $_GET['manager_id']; }
            if (!empty($_GET['departement'])) { $sql .= " AND e.departement = ?"; $params[] = $_GET['departement']; }
            $sql .= " ORDER BY e.nom";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $equipes = $stmt->fetchAll();
            // Pour chaque équipe, récupérer les membres
            foreach ($equipes as &$eq) {
                $membres = $pdo->prepare("SELECT u.id, u.nom, u.matricule, u.service, ue.role_equipe FROM utilisateurs u JOIN utilisateur_equipes ue ON u.id = ue.utilisateur_id WHERE ue.equipe_id = ? ORDER BY u.nom");
                $membres->execute([$eq['id']]);
                $eq['membres'] = $membres->fetchAll();
            }
            jsonResponse($equipes);
        } elseif ($method === 'POST') {
            $userId = checkAuth(2); // Admin + Responsable
            if (empty($input['nom'])) jsonResponse(['error' => 'Nom requis'], 400);
            $stmt = $pdo->prepare("INSERT INTO equipes (nom, description, manager_id, departement) VALUES (?,?,?,?)");
            $stmt->execute([$input['nom'], $input['description'] ?? '', $input['manager_id'] ?: null, $input['departement'] ?? '']);
            $newId = $pdo->lastInsertId();
            logAction($userId, 'Création équipe', "Équipe '{$input['nom']}' créée");
            jsonResponse(['success' => true, 'id' => $newId]);
        } elseif ($method === 'PUT') {
            $userId = checkAuth(2); // Admin + Responsable
            $id = $input['id'] ?? 0;
            $sets = []; $params = [];
            foreach (['nom','description','manager_id','departement'] as $f) {
                if (isset($input[$f])) { $sets[] = "$f = ?"; $params[] = ($f === 'manager_id' && $input[$f] === '') ? null : $input[$f]; }
            }
            if (empty($sets)) jsonResponse(['error' => 'Aucune donnée'], 400);
            $params[] = $id;
            $pdo->prepare("UPDATE equipes SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            logAction($userId, 'Modification équipe', "Équipe ID $id modifiée");
            jsonResponse(['success' => true]);
        } elseif ($method === 'DELETE') {
            $userId = checkAuth(2); // Admin + Responsable
            $pdo->prepare("DELETE FROM utilisateur_equipes WHERE equipe_id = ?")->execute([$input['id'] ?? 0]);
            $pdo->prepare("DELETE FROM equipes WHERE id = ?")->execute([$input['id'] ?? 0]);
            logAction($userId, 'Suppression équipe', "Équipe ID {$input['id']} supprimée");
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // MEMBRES D'ÉQUIPE (affectations)
    // ========================================
    case 'equipe_membres':
        $userId = checkAuth(2); // Admin + Responsable
        if ($method === 'POST') {
            $equipeId = intval($input['equipe_id'] ?? 0);
            $utilisateurId = intval($input['utilisateur_id'] ?? 0);
            if (!$equipeId || !$utilisateurId) jsonResponse(['error' => 'equipe_id et utilisateur_id requis'], 400);
            // Éviter le doublon
            $check = $pdo->prepare("SELECT 1 FROM utilisateur_equipes WHERE utilisateur_id = ? AND equipe_id = ?");
            $check->execute([$utilisateurId, $equipeId]);
            if ($check->fetch()) jsonResponse(['error' => 'Cet utilisateur est déjà dans cette équipe'], 400);
            $stmt = $pdo->prepare("INSERT INTO utilisateur_equipes (utilisateur_id, equipe_id, date_affectation, role_equipe) VALUES (?,?,CURDATE(),?)");
            $stmt->execute([$utilisateurId, $equipeId, $input['role_equipe'] ?? 'membre']);
            logAction($userId, 'Ajout membre équipe', "Utilisateur ID $utilisateurId ajouté à l'équipe ID $equipeId");
            jsonResponse(['success' => true]);
        } elseif ($method === 'DELETE') {
            $equipeId = intval($input['equipe_id'] ?? 0);
            $utilisateurId = intval($input['utilisateur_id'] ?? 0);
            if (!$equipeId || !$utilisateurId) jsonResponse(['error' => 'equipe_id et utilisateur_id requis'], 400);
            $stmt = $pdo->prepare("DELETE FROM utilisateur_equipes WHERE utilisateur_id = ? AND equipe_id = ?");
            $stmt->execute([$utilisateurId, $equipeId]);
            logAction($userId, 'Retrait membre équipe', "Utilisateur ID $utilisateurId retiré de l'équipe ID $equipeId");
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
        break;

    // ========================================
    // CONGÉS / TÉLÉTRAVAIL
    // ========================================
    case 'conges':
        $userId = checkAuth();
        if ($method === 'GET') {
            $params = [];
            $sql = "SELECT c.*, u.nom as employe_nom, u.matricule, u.service, t.nom as traitant_nom FROM conges c LEFT JOIN utilisateurs u ON c.utilisateur_id = u.id LEFT JOIN utilisateurs t ON c.traite_par = t.id WHERE 1=1";
            $isAdmin = hasRole(1);
            $isResponsable = hasRole(2);
            $isManager = hasRole(3);
            if (!$isAdmin) {
                if ($isResponsable) {
                    // Responsable : congés de toute sa hiérarchie
                    $sql .= " AND (c.utilisateur_id IN (SELECT id FROM utilisateurs WHERE manager_id = ? UNION SELECT id FROM utilisateurs WHERE manager_id IN (SELECT id FROM utilisateurs WHERE manager_id = ?)))";
                    $params[] = $userId; $params[] = $userId;
                } elseif ($isManager) {
                    $sql .= " AND (c.utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN (SELECT id FROM equipes WHERE manager_id = ?)) OR c.traite_par = ?)";
                    $params[] = $userId; $params[] = $userId;
                } else {
                    $sql .= " AND c.utilisateur_id = ?";
                    $params[] = $userId;
                }
            }
            if (!empty($_GET['statut'])) { $sql .= " AND c.statut = ?"; $params[] = $_GET['statut']; }
            if (!empty($_GET['user_id'])) { $sql .= " AND c.utilisateur_id = ?"; $params[] = $_GET['user_id']; }
            $sql .= " ORDER BY c.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'POST') {
            // Créer une demande
            $required = ['utilisateur_id','type_demande','date_debut','date_fin'];
            foreach ($required as $r) {
                if (empty($input[$r])) jsonResponse(['error' => "Champ $r requis"], 400);
            }
            $stmt = $pdo->prepare("INSERT INTO conges (utilisateur_id, type_demande, date_debut, date_fin, motif) VALUES (?,?,?,?,?)");
            $stmt->execute([$input['utilisateur_id'], $input['type_demande'], $input['date_debut'], $input['date_fin'], $input['motif'] ?? '']);
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        } elseif ($method === 'PUT') {
            // Valider/refuser un congé
            $id = $input['id'] ?? 0;
            $statut = $input['statut'] ?? '';
            if (!in_array($statut, ['approuve','refuse'])) jsonResponse(['error' => 'Statut invalide'], 400);
            $stmt = $pdo->prepare("UPDATE conges SET statut = ?, traite_par = ?, date_traitement = NOW(), commentaire_manager = ? WHERE id = ?");
            $stmt->execute([$statut, $userId, $input['commentaire'] ?? '', $id]);
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // ALERTES
    // ========================================
    case 'alertes':
        $userId = checkAuth();
        if ($method === 'GET') {
            $isAdmin = hasRole(1);
            $isManager = hasRole(3);
            $sql = "SELECT a.*, e.nom as equipe_nom FROM alertes a LEFT JOIN equipes e ON a.equipe_id = e.id WHERE 1=1";
            $params = [];
            if (!$isAdmin) {
                if ($isManager) {
                    $sql .= " AND (a.utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN (SELECT equipe_id FROM equipes WHERE manager_id = ?)) OR a.equipe_id IN (SELECT id FROM equipes WHERE manager_id = ?) OR a.utilisateur_id IS NULL)";
                    $params[] = $userId; $params[] = $userId;
                } else {
                    $sql .= " AND (a.utilisateur_id = ? OR a.utilisateur_id IS NULL)";
                    $params[] = $userId;
                }
            }
            if (isset($_GET['non_lue']) && $_GET['non_lue'] === '1') { $sql .= " AND a.lue = FALSE"; }
            $sql .= " ORDER BY a.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'PUT') {
            // Marquer comme lue
            $id = $input['id'] ?? 0;
            $pdo->prepare("UPDATE alertes SET lue = TRUE WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true]);
        } elseif ($method === 'POST') {
            // Créer une alerte (admin)
            checkAuth(1);
            $stmt = $pdo->prepare("INSERT INTO alertes (type, niveau, titre, description, utilisateur_id, equipe_id) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$input['type'] ?? 'autre', $input['niveau'] ?? 'moyen', $input['titre'] ?? '', $input['description'] ?? '', $input['utilisateur_id'] ?? null, $input['equipe_id'] ?? null]);
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        }
        break;

    // ========================================
    // MESSAGES
    // ========================================
    case 'messages':
        $userId = checkAuth();
        if ($method === 'GET') {
            $params = [];
            $sql = "SELECT m.*, e.nom as expediteur_nom, e.avatar as expediteur_avatar FROM messages m LEFT JOIN utilisateurs e ON m.expediteur_id = e.id WHERE 1=1";
            if (!empty($_GET['expediteur_id'])) { $sql .= " AND m.expediteur_id = ?"; $params[] = $_GET['expediteur_id']; }
            if (!empty($_GET['destinataire_id'])) { $sql .= " AND m.destinataire_id = ?"; $params[] = $_GET['destinataire_id']; }
            if (!empty($_GET['conversation'])) {
                // Conversation entre 2 utilisateurs
                $otherId = intval($_GET['conversation']);
                $sql .= " AND ((m.expediteur_id = ? AND m.destinataire_id = ?) OR (m.expediteur_id = ? AND m.destinataire_id = ?))";
                $params[] = $userId; $params[] = $otherId; $params[] = $otherId; $params[] = $userId;
            }
            $sql .= " ORDER BY m.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'POST') {
            $stmt = $pdo->prepare("INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu) VALUES (?,?,?,?)");
            $stmt->execute([$userId, $input['destinataire_id'] ?? 0, $input['sujet'] ?? '', $input['contenu'] ?? '']);
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        } elseif ($method === 'PUT') {
            // Marquer comme lu
            $id = $input['id'] ?? 0;
            $pdo->prepare("UPDATE messages SET lu = TRUE WHERE id = ? AND destinataire_id = ?")->execute([$id, $userId]);
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // LIEUX AUTORISÉS
    // ========================================
    case 'lieux':
        $userId = checkAuth();
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM lieux_autorises WHERE actif = TRUE ORDER BY nom");
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'POST') {
            checkAuth(1);
            $stmt = $pdo->prepare("INSERT INTO lieux_autorises (nom, adresse, latitude, longitude, rayon_metres) VALUES (?,?,?,?,?)");
            $stmt->execute([$input['nom'], $input['adresse'] ?? '', $input['latitude'], $input['longitude'], $input['rayon_metres'] ?? 100]);
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        } elseif ($method === 'DELETE') {
            checkAuth(1);
            $pdo->prepare("DELETE FROM lieux_autorises WHERE id = ?")->execute([$input['id'] ?? 0]);
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // PARAMÈTRES
    // ========================================
    case 'parametres':
        $userId = checkAuth();
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT cle, valeur, categorie FROM parametres ORDER BY categorie, cle");
            $params = [];
            foreach ($stmt->fetchAll() as $row) {
                $params[$row['cle']] = $row['valeur'];
            }
            jsonResponse($params);
        } elseif ($method === 'PUT') {
            checkAuth(1);
            $stmt = $pdo->prepare("INSERT INTO parametres (cle, valeur, categorie) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)");
            foreach ($input as $cle => $valeur) {
                $stmt->execute([$cle, $valeur, $input['_categorie'] ?? 'general']);
            }
            logAction($userId, 'Modification paramètres', "Paramètres mis à jour");
            jsonResponse(['success' => true]);
        }
        break;

    // ========================================
    // LOGS ADMIN
    // ========================================
    case 'logs':
        $userId = checkAuth(1);
        if ($method === 'GET') {
            $sql = "SELECT l.*, u.nom as admin_nom FROM logs_admin l LEFT JOIN utilisateurs u ON l.admin_id = u.id ORDER BY l.created_at DESC";
            if (!empty($_GET['limit'])) { $sql .= " LIMIT " . intval($_GET['limit']); }
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            jsonResponse($stmt->fetchAll());
        }
        break;

    // ========================================
    // EXPORT CSV
    // ========================================
    case 'export':
        $userId = checkAuth();
        $isAdmin = hasRole(1);
        $isManager = hasRole(3);
        $type = $_GET['type'] ?? 'pointages';
        $debut = $_GET['debut'] ?? date('Y-m-01');
        $fin = $_GET['date_fin'] ?? date('Y-m-t');

        if ($type === 'pointages') {
            $params = [$debut, $fin];
            $roleFilter = '';
            if (!$isAdmin) {
                if ($isManager) {
                    $roleFilter = " AND (p.utilisateur_id = ? OR p.utilisateur_id IN (SELECT utilisateur_id FROM utilisateur_equipes WHERE equipe_id IN (SELECT id FROM equipes WHERE manager_id = ?)))";
                    $params[] = $userId; $params[] = $userId;
                } else {
                    $roleFilter = " AND p.utilisateur_id = ?";
                    $params[] = $userId;
                }
            }
            $stmt = $pdo->prepare("SELECT p.date_pointage, u.nom, u.matricule, u.service, p.type, p.heure, p.mode_travail, p.localisation, p.statut FROM pointages p JOIN utilisateurs u ON p.utilisateur_id = u.id WHERE p.date_pointage BETWEEN ? AND ?" . $roleFilter . " ORDER BY p.date_pointage DESC, u.nom, p.heure");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $csv = "Date,Employé,Matricule,Service,Type,Heure,Mode,Localisation,Statut\n";
            foreach ($rows as $r) {
                $csv .= implode(',', array_map(function($v) { return '"' . str_replace('"', '""', $v ?? '') . '"'; }, $r)) . "\n";
            }
            jsonResponse(['csv' => $csv, 'filename' => 'pointages_' . $debut . '_au_' . $fin . '.csv']);
        } elseif ($type === 'users') {
            checkAuth(1); // Export des utilisateurs : admin uniquement
            $stmt = $pdo->query("SELECT matricule, nom, email, service, horaire, statut, date_embauche, telephone FROM utilisateurs ORDER BY id");
            $rows = $stmt->fetchAll();
            $csv = "Matricule,Nom,Email,Service,Horaire,Statut,Date Embauche,Telephone\n";
            foreach ($rows as $r) {
                $csv .= implode(',', array_map(function($v) { return '"' . str_replace('"', '""', $v ?? '') . '"'; }, $r)) . "\n";
            }
            jsonResponse(['csv' => $csv, 'filename' => 'utilisateurs_' . date('Y-m-d') . '.csv']);
        }
        break;

    // ========================================
    // UTILISATEURS (lecture pour non-admin, avec filtres rôle)
    // ========================================
    case 'managers':
        $userId = checkAuth();
        $stmt = $pdo->query("SELECT id, matricule, nom, email, service FROM utilisateurs WHERE role_id = 3 AND statut = 'actif' ORDER BY nom");
        jsonResponse($stmt->fetchAll());
        break;

    default:
        jsonResponse(['error' => 'Action inconnue : ' . $action], 404);
}
