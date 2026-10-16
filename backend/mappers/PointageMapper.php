<?php
require_once __DIR__ . '/../models/Pointage.php';
require_once __DIR__ . '/../dto/PointageDTO.php';

/**
 * Mapper pour convertir entre Pointage (modèle) et PointageDTO
 */
class PointageMapper {
    /**
     * Convertit un modèle Pointage en PointageDTO
     */
    public static function toDTO($pointage, $additionalData = []) {
        // Si c'est déjà un tableau (depuis la base de données avec JOIN)
        if (is_array($pointage)) {
            $dto = new PointageDTO([
                'id' => $pointage['id'] ?? null,
                'utilisateurId' => $pointage['utilisateur_id'] ?? null,
                'typePointage' => $pointage['type_pointage'] ?? null,
                'typePointageNom' => $pointage['type_pointage_nom'] ?? null,
                'datePointage' => $pointage['date_pointage'] ?? null,
                'heure' => $pointage['heure'] ?? null,
                'localisation' => $pointage['localisation'] ?? null,
                'latitude' => $pointage['latitude'] ?? null,
                'longitude' => $pointage['longitude'] ?? null,
                'modeTravail' => $pointage['mode_travail'] ?? null,
                'modeTravailNom' => $pointage['mode_travail_nom'] ?? null,
                'notes' => $pointage['notes'] ?? null,
                'createdAt' => $pointage['created_at'] ?? null,
                'employeNom' => $pointage['employe_nom'] ?? null,
                'employeMatricule' => $pointage['employe_matricule'] ?? null,
                'employeService' => $pointage['employe_service'] ?? null
            ]);
            return $dto;
        }
        
        // Si c'est un objet Pointage
        if ($pointage instanceof Pointage) {
            $dto = new PointageDTO([
                'id' => $pointage->getId(),
                'utilisateurId' => $pointage->getUtilisateurId(),
                'typePointage' => $pointage->getTypePointage(),
                'typePointageNom' => $pointage->getTypePointageNom(),
                'datePointage' => $pointage->getDatePointage(),
                'heure' => $pointage->getHeure(),
                'localisation' => $pointage->getLocalisation(),
                'latitude' => $pointage->getLatitude(),
                'longitude' => $pointage->getLongitude(),
                'modeTravail' => $pointage->getModeTravail(),
                'modeTravailNom' => $pointage->getModeTravailNom(),
                'notes' => $pointage->getNotes(),
                'createdAt' => $pointage->getCreatedAt()
            ]);
            
            // Ajouter les données supplémentaires
            if (isset($additionalData['employe_nom'])) {
                $dto->employeNom = $additionalData['employe_nom'];
            }
            if (isset($additionalData['employe_matricule'])) {
                $dto->employeMatricule = $additionalData['employe_matricule'];
            }
            if (isset($additionalData['employe_service'])) {
                $dto->employeService = $additionalData['employe_service'];
            }
            
            return $dto;
        }
        
        return null;
    }
    
    /**
     * Convertit un PointageDTO en modèle Pointage
     */
    public static function toModel(PointageDTO $dto) {
        return new Pointage([
            'id' => $dto->id,
            'utilisateur_id' => $dto->utilisateurId,
            'type_pointage' => $dto->typePointage,
            'date_pointage' => $dto->datePointage,
            'heure' => $dto->heure,
            'localisation' => $dto->localisation,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
            'mode_travail' => $dto->modeTravail,
            'notes' => $dto->notes,
            'created_at' => $dto->createdAt
        ]);
    }
    
    /**
     * Convertit un tableau de données en tableau de PointageDTO
     */
    public static function toDTOArray(array $pointages) {
        return array_map(function($pointage) {
            return self::toDTO($pointage);
        }, $pointages);
    }
}

