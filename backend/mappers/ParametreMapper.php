<?php
require_once __DIR__ . '/../models/Parametre.php';
require_once __DIR__ . '/../dto/ParametreDTO.php';
require_once __DIR__ . '/TypeParametreMapper.php';

/**
 * Mapper pour convertir entre Parametre (modèle) et ParametreDTO
 */
class ParametreMapper {
    /**
     * Convertit un modèle Parametre en ParametreDTO
     */
    public static function toDTO(Parametre $parametre) {
        // Convertir le type de paramètre en DTO si disponible
        $typeParametreDTO = null;
        $typeParametre = $parametre->getTypeParametre();
        
        if ($typeParametre) {
            if ($typeParametre instanceof TypeParametre) {
                $typeParametreDTO = TypeParametreMapper::toDTO($typeParametre);
            } elseif (is_array($typeParametre)) {
                $typeParametreDTO = $typeParametre;
            }
        }
        
        return new ParametreDTO([
            'id' => $parametre->getId(),
            'nom' => $parametre->getNom(),
            'code' => $parametre->getCode(),
            'description' => $parametre->getDescription(),
            'typeParametreId' => $parametre->getTypeParametreId(),
            'typeParametre' => $typeParametreDTO,
            'createdAt' => $parametre->getCreatedAt(),
            'updatedAt' => $parametre->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un ParametreDTO en modèle Parametre
     */
    public static function toModel(ParametreDTO $dto) {
        $parametre = new Parametre([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'code' => $dto->code,
            'description' => $dto->description,
            'type_parametre_id' => $dto->typeParametreId,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
        
        // Convertir le type de paramètre si présent
        if (isset($dto->typeParametre)) {
            if ($dto->typeParametre instanceof TypeParametreDTO) {
                $parametre->setTypeParametre(TypeParametreMapper::toModel($dto->typeParametre));
            } elseif (is_array($dto->typeParametre)) {
                $typeParametre = new TypeParametre($dto->typeParametre);
                $parametre->setTypeParametre($typeParametre);
            }
        }
        
        return $parametre;
    }
    
    /**
     * Convertit un tableau de Parametre en tableau de ParametreDTO
     */
    public static function toDTOArray(array $parametres) {
        return array_map(function($parametre) {
            if ($parametre instanceof Parametre) {
                return self::toDTO($parametre);
            }
            return null;
        }, array_filter($parametres));
    }
}

