<?php
require_once __DIR__ . '/../models/Jour.php';
require_once __DIR__ . '/../dto/JourDTO.php';

/**
 * Mapper pour convertir entre Jour (modèle) et JourDTO
 */
class JourMapper {
    /**
     * Convertit un modèle Jour en JourDTO
     */
    public static function toDTO(Jour $jour) {
        return new JourDTO([
            'id' => $jour->getId(),
            'nom' => $jour->getNom(),
            'createdAt' => $jour->getCreatedAt(),
            'updatedAt' => $jour->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un JourDTO en modèle Jour
     */
    public static function toModel(JourDTO $dto) {
        return new Jour([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de Jour en tableau de JourDTO
     */
    public static function toDTOArray(array $jours) {
        return array_map(function($jour) {
            if ($jour instanceof Jour) {
                return self::toDTO($jour);
            }
            return null;
        }, array_filter($jours));
    }
}

