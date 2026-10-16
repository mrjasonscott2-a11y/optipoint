<?php
require_once __DIR__ . '/../models/Site.php';
require_once __DIR__ . '/../dto/SiteDTO.php';
require_once __DIR__ . '/UserMapper.php';

/**
 * Mapper pour convertir entre Site (modèle) et SiteDTO
 */
class SiteMapper {
    /**
     * Convertit un modèle Site en SiteDTO
     */
    public static function toDTO(Site $site) {
        // Convertir le manager en DTO si disponible
        $managerDTO = null;
        $manager = $site->getManager();
        
        if ($manager) {
            if ($manager instanceof User) {
                $managerDTO = UserMapper::toDTO($manager, []);
            } elseif (is_array($manager)) {
                $managerDTO = $manager;
            }
        }
        
        // Extraire le nom du manager
        $managerNom = $site->getManagerNom();
        if (!$managerNom && $manager) {
            if ($manager instanceof User) {
                $managerNom = $manager->getNom();
            } elseif (is_array($manager) && isset($manager['nom'])) {
                $managerNom = $manager['nom'];
            }
        }
        
        return new SiteDTO([
            'id' => $site->getId(),
            'nom' => $site->getNom(),
            'description' => $site->getDescription(),
            'managerId' => $site->getManagerId(),
            'manager' => $managerDTO,
            'managerNom' => $managerNom,
            'statut' => $site->getStatut(),
            'statutNom' => $site->getStatutNom(),
            'createdAt' => $site->getCreatedAt(),
            'updatedAt' => $site->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un SiteDTO en modèle Site
     */
    public static function toModel(SiteDTO $dto) {
        return new Site([
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
     * Convertit un tableau de Site en tableau de SiteDTO
     */
    public static function toDTOArray(array $sites) {
        return array_map(function($site) {
            if ($site instanceof Site) {
                return self::toDTO($site);
            }
            return null;
        }, array_filter($sites));
    }
}

