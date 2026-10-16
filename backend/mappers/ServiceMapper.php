<?php
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../dto/ServiceDTO.php';

/**
 * Mapper pour convertir entre Service (modèle) et ServiceDTO
 */
class ServiceMapper {
    /**
     * Convertit un modèle Service ou un tableau en ServiceDTO
     */
    public static function toDTO($service) {
        // Si c'est déjà un tableau (depuis la base de données avec JOIN)
        if (is_array($service)) {
            return new ServiceDTO([
                'id' => $service['id'] ?? null,
                'nom' => $service['nom'] ?? null,
                'description' => $service['description'] ?? null,
                'responsableId' => $service['responsable_id'] ?? null,
                'statut' => $service['statut'] ?? null,
                'statutNom' => $service['statut_nom'] ?? null,
                'createdAt' => $service['created_at'] ?? null,
                'updatedAt' => $service['updated_at'] ?? null,
                'responsableNom' => $service['responsable_nom'] ?? null
            ]);
        }
        
        // Si c'est un objet Service
        if ($service instanceof Service) {
            return new ServiceDTO([
                'id' => $service->getId(),
                'nom' => $service->getNom(),
                'description' => $service->getDescription(),
                'responsableId' => $service->getResponsableId(),
                'responsableNom' => $service->getResponsableNom(),
                'statut' => $service->getStatut(),
                'statutNom' => $service->getStatutNom(),
                'createdAt' => $service->getCreatedAt(),
                'updatedAt' => $service->getUpdatedAt()
            ]);
        }
        
        return null;
    }
    
    /**
     * Convertit un ServiceDTO en modèle Service
     */
    public static function toModel(ServiceDTO $dto) {
        return new Service([
            'id' => $dto->id,
            'nom' => $dto->nom,
            'description' => $dto->description,
            'responsable_id' => $dto->responsableId,
            'statut' => $dto->statut,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de Service en tableau de ServiceDTO
     */
    public static function toDTOArray(array $services) {
        return array_map(function($service) {
            return self::toDTO($service);
        }, $services);
    }
}

