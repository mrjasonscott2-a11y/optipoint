<?php
/**
 * DTO (Data Transfer Object) pour Habilitation
 */
class HabilitationDTO {
    public $id;
    public $nom;
    public $description;
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

