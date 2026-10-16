<?php
require_once __DIR__ . '/../repository/RapportRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';

/**
 * Classe RapportService - Service pour la gestion des rapports
 * Contient la logique métier pour les rapports
 */
class RapportService {
    private $rapportRepository;
    private $userRepository;
    
    public function __construct() {
        $this->rapportRepository = new RapportRepository();
        $this->userRepository = new UserRepository();
    }
    
    /**
     * Génère un rapport pour un utilisateur sur une période donnée
     */
    public function generateRapport($userId, $dateDebut, $dateFin) {
        // Validation des paramètres
        if (empty($userId)) {
            throw new Exception('User ID requis');
        }
        
        if (empty($dateDebut)) {
            throw new Exception('Date de début requise');
        }
        
        if (empty($dateFin)) {
            throw new Exception('Date de fin requise');
        }
        
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }
        
        // Valider les formats de date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
            throw new Exception('Format de date_debut invalide. Format attendu: YYYY-MM-DD');
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            throw new Exception('Format de date_fin invalide. Format attendu: YYYY-MM-DD');
        }
        
        // Vérifier que date_fin >= date_debut
        if ($dateFin < $dateDebut) {
            throw new Exception('La date de fin doit être supérieure ou égale à la date de début');
        }
        
        // Générer le rapport
        return $this->rapportRepository->generateRapport($userId, $dateDebut, $dateFin);
    }
    
    /**
     * Génère un rapport pour plusieurs utilisateurs
     */
    public function generateRapportMultiple($userIds, $dateDebut, $dateFin) {
        if (empty($userIds) || !is_array($userIds)) {
            throw new Exception('Liste d\'utilisateurs requise');
        }
        
        $rapports = [];
        foreach ($userIds as $userId) {
            try {
                $rapport = $this->generateRapport($userId, $dateDebut, $dateFin);
                $user = $this->userRepository->findById($userId);
                $rapport['user'] = $user ? $user->toArray() : null;
                $rapports[] = $rapport;
            } catch (Exception $e) {
                // Continuer avec les autres utilisateurs même si un échoue
                $rapports[] = [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $rapports;
    }
}

