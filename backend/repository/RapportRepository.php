<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Classe RapportRepository - Repository pour les rapports
 */
class RapportRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Génère un rapport pour un utilisateur sur une période donnée
     */
    public function generateRapport($userId, $dateDebut, $dateFin) {
        // Récupérer les pointages pour la période
        $stmt = $this->db->prepare("
            SELECT 
                date_pointage,
                MIN(CASE WHEN type = 'arrivee' THEN heure END) as arrivee,
                MAX(CASE WHEN type = 'depart' THEN heure END) as depart,
                GROUP_CONCAT(DISTINCT mode_travail) as modes
            FROM pointages 
            WHERE utilisateur_id = :user_id 
            AND date_pointage BETWEEN :date_debut AND :date_fin
            GROUP BY date_pointage
            ORDER BY date_pointage DESC
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);
        $pointages = $stmt->fetchAll();
        
        // Calculer les totaux
        $joursPresents = count($pointages);
        $totalHeures = 0;
        
        foreach ($pointages as &$pointage) {
            if ($pointage['arrivee'] && $pointage['depart']) {
                $arrivee = new DateTime($pointage['arrivee']);
                $depart = new DateTime($pointage['depart']);
                $diff = $depart->diff($arrivee);
                $heures = $diff->h + ($diff->i / 60);
                $pointage['heures'] = round($heures, 2);
                $totalHeures += $heures;
            } else {
                $pointage['heures'] = 0;
            }
        }
        
        return [
            'periode_debut' => $dateDebut,
            'periode_fin' => $dateFin,
            'pointages' => $pointages,
            'jours_presents' => $joursPresents,
            'total_heures' => round($totalHeures, 2)
        ];
    }
}

