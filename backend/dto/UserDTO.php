<?php
/**
 * DTO (Data Transfer Object) pour User
 * Utilisé pour transférer les données entre les couches sans exposer le modèle interne
 */
class UserDTO {
    public $id;
    public $matricule;
    public $nom;
    public $email;
    public $service;
    public $serviceId;
    public $horaire;
    public $role;
    public $profilId;
    public $statut;
    public $statutNom; // Nom du paramètre statut
    public $dateEmbauche;
    public $telephone;
    public $createdAt;
    public $updatedAt;
    
    // Champs supplémentaires pour l'affichage
    public $serviceNom;
    public $profilNom;
    
    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Convertit le DTO en tableau
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'email' => $this->email,
            'service' => $this->service ?? $this->serviceNom,
            'service_id' => $this->serviceId,
            'horaire' => $this->horaire,
            'role' => $this->profilNom,
            'profil_id' => $this->profilId,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'date_embauche' => $this->dateEmbauche,
            'telephone' => $this->telephone,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

