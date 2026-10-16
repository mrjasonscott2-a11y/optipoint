<?php
/**
 * OptiPoint - Configuration de la base de données
 * Ce fichier est inclus par api.php à la racine
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration base de données (XAMPP/Laragon)
define('DB_HOST', 'localhost');
define('DB_NAME', 'optipoint_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

/**
 * Vérifie l'authentification et optionnellement le rôle
 * @param int|null $requiredRole - role_id minimum requis (1=admin, 2=responsable, 3=manager, 4=employé)
 * @return int - L'ID de l'utilisateur
 */
function checkAuth($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }

    if ($requiredRole !== null) {
        $role = getUserRole();
        if ($role === null || $role > $requiredRole) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès interdit pour ce rôle']);
            exit;
        }
    }

    return $_SESSION['user_id'];
}

/**
 * Renvoie le role_id de l'utilisateur connecté SANS bloquer (pas d'exit)
 * @return int|null
 */
function getUserRole() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT role_id FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ? (int)$user['role_id'] : null;
}

/**
 * Teste si l'utilisateur connecté a au moins un rôle (sans exit)
 * @param int $requiredRole
 * @return bool
 */
function hasRole($requiredRole) {
    $role = getUserRole();
    return ($role !== null && $role <= $requiredRole);
}

/**
 * Récupère les informations complètes de l'utilisateur connecté
 */
function getCurrentUser() {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    $user = $_SESSION['user'];
    unset($user['mot_de_passe']);
    return $user;
}

/**
 * Enregistre une action dans les logs admin
 * Ne bloque jamais le flux principal si la journalisation échoue
 */
function logAction($userId, $action, $details = '') {
    global $pdo;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO logs_admin (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        error_log('logAction échoué : ' . $e->getMessage());
    }
}

/**
 * Retourne une réponse JSON et arrête l'exécution
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
