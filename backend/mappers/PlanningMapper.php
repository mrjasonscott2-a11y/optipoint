<?php
require_once __DIR__ . '/../models/Planning.php';
require_once __DIR__ . '/../dto/PlanningDTO.php';

/**
 * Mapper pour convertir entre Planning (modèle) et PlanningDTO
 */
class PlanningMapper {
    /**
     * Convertit un modèle Planning en PlanningDTO
     */
    public static function toDTO($planning, $additionalData = []) {
        // Si c'est déjà un tableau
        if (is_array($planning)) {
            return new PlanningDTO([
                'id' => $planning['id'] ?? null,
                'utilisateurId' => $planning['utilisateur_id'] ?? null,
                'siteId' => $planning['site_id'] ?? null,
                'jourId' => $planning['jour_id'] ?? null,
                'dateDebut' => $planning['date_debut'] ?? null,
                'dateFin' => $planning['date_fin'] ?? null,
                'heureDebut' => $planning['heure_debut'] ?? null,
                'heureFin' => $planning['heure_fin'] ?? null,
                'description' => $planning['description'] ?? null,
                'lieu' => $planning['lieu'] ?? null,
                'createdAt' => $planning['created_at'] ?? null,
                'utilisateurNom' => $planning['utilisateur_nom'] ?? null,
                'utilisateurMatricule' => $planning['utilisateur_matricule'] ?? null,
                'siteNom' => $planning['site_nom'] ?? null,
                'jourNom' => $planning['jour_nom'] ?? null
            ]);
        }
        
        // Si c'est un objet Planning
        if ($planning instanceof Planning) {
            $dto = new PlanningDTO([
                'id' => $planning->getId(),
                'utilisateurId' => $planning->getUtilisateurId(),
                'siteId' => $planning->getSiteId(),
                'jourId' => $planning->getJourId(),
                'dateDebut' => $planning->getDateDebut(),
                'dateFin' => $planning->getDateFin(),
                'heureDebut' => $planning->getHeureDebut(),
                'heureFin' => $planning->getHeureFin(),
                'description' => $planning->getDescription(),
                'lieu' => $planning->getLieu(),
                'createdAt' => $planning->getCreatedAt()
            ]);
            
            // Ajouter les données supplémentaires
            if (isset($additionalData['utilisateur_nom']) || $planning->getUtilisateurNom()) {
                $dto->utilisateurNom = $additionalData['utilisateur_nom'] ?? $planning->getUtilisateurNom();
            }
            if (isset($additionalData['utilisateur_matricule'])) {
                $dto->utilisateurMatricule = $additionalData['utilisateur_matricule'];
            }
            if (isset($additionalData['site_nom']) || $planning->getSiteNom()) {
                $dto->siteNom = $additionalData['site_nom'] ?? $planning->getSiteNom();
            }
            if (isset($additionalData['jour_nom']) || $planning->getJourNom()) {
                $dto->jourNom = $additionalData['jour_nom'] ?? $planning->getJourNom();
            }
            
            return $dto;
        }
        
        return null;
    }
    
    /**
     * Convertit un PlanningDTO en modèle Planning
     */
    public static function toModel(PlanningDTO $dto) {
        return new Planning([
            'id' => $dto->id,
            'utilisateur_id' => $dto->utilisateurId,
            'site_id' => $dto->siteId,
            'jour_id' => $dto->jourId,
            'date_debut' => $dto->dateDebut,
            'date_fin' => $dto->dateFin,
            'heure_debut' => $dto->heureDebut,
            'heure_fin' => $dto->heureFin,
            'description' => $dto->description,
            'lieu' => $dto->lieu,
            'created_at' => $dto->createdAt
        ]);
    }
    
    /**
     * Convertit un tableau de Planning en tableau de PlanningDTO
     */
    public static function toDTOArray(array $plannings) {
        return array_map(function($planning) {
            return self::toDTO($planning);
        }, $plannings);
    }
}

