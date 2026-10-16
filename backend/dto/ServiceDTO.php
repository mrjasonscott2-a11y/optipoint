<?php
/**
 * DTO (Data Transfer Object) pour Service
 */
class ServiceDTO {
    public $id;
    public $nom;
    public $description;
    public $responsableId;
    public $statut;
    public $statutNom; // Nom du paramètre statut
    public $createdAt;
    public $updatedAt;
    
    // Champs supplémentaires pour l'affichage
    public $responsableNom;
    
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
            'nom' => $this->nom,
            'description' => $this->description,
            'responsable_id' => $this->responsableId,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'responsable_nom' => $this->responsableNom
        ];
    }
}

