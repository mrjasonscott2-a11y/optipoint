<?php
/**
 * DTO (Data Transfer Object) pour Profil
 */
class ProfilDTO {
    public $id;
    public $nom;
    public $description;
    public $habilitations; // Array of HabilitationDTO objects or arrays
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
        $habilitationsArray = [];
        if (is_array($this->habilitations)) {
            foreach ($this->habilitations as $habilitation) {
                if (is_object($habilitation) && method_exists($habilitation, 'toArray')) {
                    $habilitationsArray[] = $habilitation->toArray();
                } elseif (is_array($habilitation)) {
                    $habilitationsArray[] = $habilitation;
                } else {
                    $habilitationsArray[] = ['id' => $habilitation];
                }
            }
        }
        
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'habilitations' => $habilitationsArray,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

