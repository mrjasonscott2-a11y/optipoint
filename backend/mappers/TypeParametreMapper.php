<?php
require_once __DIR__ . '/../models/TypeParametre.php';
require_once __DIR__ . '/../dto/TypeParametreDTO.php';

/**
 * Mapper pour convertir entre TypeParametre (modèle) et TypeParametreDTO
 */
class TypeParametreMapper {
    /**
     * Convertit un modèle TypeParametre en TypeParametreDTO
     */
    public static function toDTO(TypeParametre $typeParametre) {
        return new TypeParametreDTO([
            'id' => $typeParametre->getId(),
            'nom' => $typeParametre->getNom(),
            'code' => $typeParametre->getCode(),
            'description' => $typeParametre->getDescription(),
            'createdAt' => $typeParametre->getCreatedAt(),
            'updatedAt' => $typeParametre->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un TypeParametreDTO en modèle TypeParametre
     */
    public static function toModel(TypeParametreDTO $dto) {
        return new TypeParametre([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'code' => $dto->code,
            'description' => $dto->description,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de TypeParametre en tableau de TypeParametreDTO
     */
    public static function toDTOArray(array $typeParametres) {
        return array_map(function($typeParametre) {
            if ($typeParametre instanceof TypeParametre) {
                return self::toDTO($typeParametre);
            }
            return null;
        }, array_filter($typeParametres));
    }
}

