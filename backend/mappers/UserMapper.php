<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../dto/UserDTO.php';

/**
 * Mapper pour convertir entre User (modèle) et UserDTO
 */
class UserMapper {
    /**
     * Convertit un modèle User en UserDTO
     */
    public static function toDTO(User $user, $additionalData = []) {
        $profilNom = null;
        if(isset($additionalData['profil_nom'])){
            $profilNom = $additionalData['profil_nom'];
        }

        $dto = new UserDTO([
            'id' => $user->getId(),
            'matricule' => $user->getMatricule(),
            'nom' => $user->getNom(),
            'email' => $user->getEmail(),
            'serviceId' => $user->getServiceId(),
            'horaire' => $user->getHoraire(),
            'role' => $profilNom,
            'profilId' => $user->getProfilId(),
            'statut' => $user->getStatut(),
            'statutNom' => $user->getStatutNom(),
            'dateEmbauche' => $user->getDateEmbauche(),
            'telephone' => $user->getTelephone(),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt()
        ]);
        
        // Ajouter les données supplémentaires (noms des relations)
        if (isset($additionalData['service_nom'])) {
            $dto->serviceNom = $additionalData['service_nom'];
        }
        if (isset($additionalData['profil_nom'])) {
            $dto->profilNom = $additionalData['profil_nom'];
        }
        
        return $dto;
    }
    
    /**
     * Convertit un UserDTO en modèle User
     */
    public static function toModel(UserDTO $dto) {
        return new User([
            'id' => $dto->id,
            'matricule' => $dto->matricule,
            'nom' => $dto->nom,
            'email' => $dto->email,
            'service' => $dto->service,
            'service_id' => $dto->serviceId,
            'horaire' => $dto->horaire,
            'role' => $dto->role,
            'profil_id' => $dto->profilId,
            'statut' => $dto->statut,
            'date_embauche' => $dto->dateEmbauche,
            'telephone' => $dto->telephone,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt
        ]);
    }
    
    /**
     * Convertit un tableau de User en tableau de UserDTO
     */
    public static function toDTOArray(array $users, $additionalDataMap = []) {
        return array_map(function($user) use ($additionalDataMap) {
            if ($user instanceof User) {
                $userId = $user->getId();
                $additionalData = $additionalDataMap[$userId] ?? [];
                return self::toDTO($user, $additionalData);
            }
            return null;
        }, $users);
    }
}

