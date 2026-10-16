<?php
/**
 * DTO (Data Transfer Object) pour Jour
 */
class JourDTO {
    public $id;
    public $nom;
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

