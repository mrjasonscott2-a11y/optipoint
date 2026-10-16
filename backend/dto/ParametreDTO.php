<?php
/**
 * DTO (Data Transfer Object) pour Parametre
 */
class ParametreDTO {
    public $id;
    public $nom;
    public $code;
    public $description;
    public $typeParametreId;
    public $typeParametre; // TypeParametreDTO ou array
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
        $typeParametreArray = null;
        if ($this->typeParametre) {
            if (is_object($this->typeParametre) && method_exists($this->typeParametre, 'toArray')) {
                $typeParametreArray = $this->typeParametre->toArray();
            } elseif (is_array($this->typeParametre)) {
                $typeParametreArray = $this->typeParametre;
            }
        }
        
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'code' => $this->code,
            'description' => $this->description,
            'type_parametre_id' => $this->typeParametreId,
            'type_parametre' => $typeParametreArray,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

