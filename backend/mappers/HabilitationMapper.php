<?php
require_once __DIR__ . '/../models/Habilitation.php';
require_once __DIR__ . '/../dto/HabilitationDTO.php';

/**
 * Mapper pour convertir entre Habilitation (modèle) et HabilitationDTO
 */
class HabilitationMapper {
    /**
     * Convertit un modèle Habilitation en HabilitationDTO
     */
    public static function toDTO(Habilitation $habilitation) {
        return new HabilitationDTO([
            'id' => $habilitation->getId(),
            'nom' => $habilitation->getNom(),
            'description' => $habilitation->getDescription(),
            'createdAt' => $habilitation->getCreatedAt(),
            'updatedAt' => $habilitation->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un HabilitationDTO en modèle Habilitation
     */
    public static function toModel(HabilitationDTO $dto) {
        return new Habilitation([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'description' => $dto->description,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de Habilitation en tableau de HabilitationDTO
     */
    public static function toDTOArray(array $habilitations) {
        return array_map(function($habilitation) {
            if ($habilitation instanceof Habilitation) {
                return self::toDTO($habilitation);
            }
            return null;
        }, array_filter($habilitations));
    }
}

