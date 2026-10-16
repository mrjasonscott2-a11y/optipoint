<?php
require_once __DIR__ . '/../models/Tache.php';
require_once __DIR__ . '/../dto/TacheDTO.php';

/**
 * Mapper pour convertir entre Tache (modèle) et TacheDTO
 */
class TacheMapper {
    /**
     * Convertit un modèle Tache ou un tableau en TacheDTO
     */
    public static function toDTO($tache, $additionalData = []) {
        // Si c'est un tableau (depuis le repository avec JOIN)
        if (is_array($tache)) {
            return new TacheDTO([
                'id' => $tache['id'] ?? null,
                'nom' => $tache['nom'] ?? null,
                'description' => $tache['description'] ?? null,
                'planningId' => $tache['planning_id'] ?? null,
                'planningDescription' => $tache['planning_description'] ?? null,
                'planningDate' => $tache['planning_date'] ?? null,
                'statut' => $tache['statut'] ?? null,
                'statutNom' => $tache['statut_nom'] ?? null,
                'createdAt' => $tache['created_at'] ?? null,
                'updatedAt' => $tache['updated_at'] ?? null
            ]);
        }
        
        // Si c'est un objet Tache
        if ($tache instanceof Tache) {
            $dto = new TacheDTO([
                'id' => $tache->getId(),
                'nom' => $tache->getNom(),
                'description' => $tache->getDescription(),
                'planningId' => $tache->getPlanningId(),
                'statut' => $tache->getStatut(),
                'statutNom' => $tache->getStatutNom(),
                'createdAt' => $tache->getCreatedAt(),
                'updatedAt' => $tache->getUpdatedAt()
            ]);
            
            // Ajouter les données supplémentaires (noms des relations)
            if (isset($additionalData['planning_description']) || $tache->getPlanningDescription()) {
                $dto->planningDescription = $additionalData['planning_description'] ?? $tache->getPlanningDescription();
            }
            if (isset($additionalData['planning_date']) || $tache->getPlanningDate()) {
                $dto->planningDate = $additionalData['planning_date'] ?? $tache->getPlanningDate();
            }
            
            return $dto;
        }
        
        return null;
    }
    
    /**
     * Convertit un TacheDTO en modèle Tache
     */
    public static function toModel(TacheDTO $dto) {
        return new Tache([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'description' => $dto->description,
            'planning_id' => $dto->planningId,
            'statut' => $dto->statut,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de Tache ou de tableaux en tableau de TacheDTO
     */
    public static function toDTOArray(array $taches, $additionalDataMap = []) {
        return array_map(function($tache) use ($additionalDataMap) {
            // Peut être un tableau (depuis repository) ou un objet Tache
            if (is_array($tache)) {
                return self::toDTO($tache);
            } elseif ($tache instanceof Tache) {
                $tacheId = $tache->getId();
                $additionalData = $additionalDataMap[$tacheId] ?? [];
                return self::toDTO($tache, $additionalData);
            }
            return null;
        }, array_filter($taches));
    }
}

