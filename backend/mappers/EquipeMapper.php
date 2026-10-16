<?php
require_once __DIR__ . '/../models/Equipe.php';
require_once __DIR__ . '/../dto/EquipeDTO.php';
require_once __DIR__ . '/UserMapper.php';

/**
 * Mapper pour convertir entre Equipe (modèle) et EquipeDTO
 */
class EquipeMapper {
    /**
     * Convertit un modèle Equipe en EquipeDTO
     */
    public static function toDTO(Equipe $equipe) {
        // Convertir le manager en DTO si disponible
        $managerDTO = null;
        $manager = $equipe->getManager();
        
        if ($manager) {
            if ($manager instanceof User) {
                $managerDTO = UserMapper::toDTO($manager, []);
            } elseif (is_array($manager)) {
                $managerDTO = $manager;
            }
        }
        
        // Extraire le nom du manager
        $managerNom = $equipe->getManagerNom();
        if (!$managerNom && $manager) {
            if ($manager instanceof User) {
                $managerNom = $manager->getNom();
            } elseif (is_array($manager) && isset($manager['nom'])) {
                $managerNom = $manager['nom'];
            }
        }
        
        return new EquipeDTO([
            'id' => $equipe->getId(),
            'nom' => $equipe->getNom(),
            'description' => $equipe->getDescription(),
            'managerId' => $equipe->getManagerId(),
            'manager' => $managerDTO,
            'managerNom' => $managerNom,
            'statut' => $equipe->getStatut(),
            'statutNom' => $equipe->getStatutNom(),
            'createdAt' => $equipe->getCreatedAt(),
            'updatedAt' => $equipe->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un EquipeDTO en modèle Equipe
     */
    public static function toModel(EquipeDTO $dto) {
        return new Equipe([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'description' => $dto->description,
            'manager_id' => $dto->managerId,
            'statut' => $dto->statut,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de Equipe en tableau de EquipeDTO
     */
    public static function toDTOArray(array $equipes) {
        return array_map(function($equipe) {
            if ($equipe instanceof Equipe) {
                return self::toDTO($equipe);
            }
            return null;
        }, array_filter($equipes));
    }
}

