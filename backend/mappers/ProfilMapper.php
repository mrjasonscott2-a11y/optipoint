<?php
require_once __DIR__ . '/../models/Profil.php';
require_once __DIR__ . '/../models/Habilitation.php';
require_once __DIR__ . '/../dto/ProfilDTO.php';
require_once __DIR__ . '/HabilitationMapper.php';

/**
 * Mapper pour convertir entre Profil (modèle) et ProfilDTO
 */
class ProfilMapper {
    /**
     * Convertit un modèle Profil en ProfilDTO
     */
    public static function toDTO(Profil $profil) {
        // Convertir les habilitations en DTOs
        $habilitations = $profil->getHabilitations();
        $habilitationsDTO = [];
        
        if (is_array($habilitations)) {
            foreach ($habilitations as $habilitation) {
                if ($habilitation instanceof Habilitation) {
                    $habilitationsDTO[] = HabilitationMapper::toDTO($habilitation);
                } elseif (is_array($habilitation)) {
                    $habilitationsDTO[] = $habilitation;
                }
            }
        }
        
        return new ProfilDTO([
            'id' => $profil->getId(),
            'nom' => $profil->getNom(),
            'description' => $profil->getDescription(),
            'habilitations' => $habilitationsDTO,
            'createdAt' => $profil->getCreatedAt(),
            'updatedAt' => $profil->getUpdatedAt()
        ]);
    }
    
    /**
     * Convertit un ProfilDTO en modèle Profil
     */
    public static function toModel(ProfilDTO $dto) {
        $profil = new Profil([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'description' => $dto->description,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
        
        // Convertir les habilitations si présentes
        if (isset($dto->habilitations) && is_array($dto->habilitations)) {
            $habilitations = [];
            foreach ($dto->habilitations as $habilitation) {
                if ($habilitation instanceof HabilitationDTO) {
                    $habilitations[] = HabilitationMapper::toModel($habilitation);
                } elseif (is_array($habilitation) && isset($habilitation['id'])) {
                    // Si c'est juste un ID, on le garde tel quel
                    $habilitations[] = $habilitation['id'];
                } elseif (is_numeric($habilitation)) {
                    $habilitations[] = $habilitation;
                }
            }
            $profil->setHabilitations($habilitations);
        }
        
        return $profil;
    }
    
    /**
     * Convertit un tableau de Profil en tableau de ProfilDTO
     */
    public static function toDTOArray(array $profils) {
        return array_map(function($profil) {
            if ($profil instanceof Profil) {
                return self::toDTO($profil);
            }
            return null;
        }, $profils);
    }
}

