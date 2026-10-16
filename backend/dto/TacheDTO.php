<?php
/**
 * DTO (Data Transfer Object) pour Tache
 */
class TacheDTO {
    public $id;
    public $nom;
    public $description;
    public $planningId; // Relation Many-to-One avec Planning
    public $planningDescription; // Description du planning (chargé via JOIN)
    public $planningDate; // Date du planning (chargé via JOIN)
    public $statut;
    public $statutNom;
    public $createdAt;
    public $updatedAt;
    
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
            'planning_id' => $this->planningId,
            'planning_description' => $this->planningDescription,
            'planning_date' => $this->planningDate,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

