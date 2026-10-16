<?php
/**
 * Classe Parametre - Modèle pour les Parametres
 * Relation One-to-Many avec TypeParametre (un TypeParametre peut avoir plusieurs Parametres)
 */
class Parametre {
    private $id;
    private $nom;
    private $code;
    private $description;
    private $typeParametreId;
    private $typeParametre; // Objet TypeParametre (chargé via JOIN)
    private $createdAt;
    private $updatedAt;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    private function hydrate($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($this->toCamelCase($key));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
    
    private function toCamelCase($string) {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getCode() { return $this->code; }
    public function getDescription() { return $this->description; }
    public function getTypeParametreId() { return $this->typeParametreId; }
    public function getTypeParametre() { return $this->typeParametre; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setNom($nom) { $this->nom = $nom; return $this; }
    public function setCode($code) { $this->code = $code; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setTypeParametreId($typeParametreId) { $this->typeParametreId = $typeParametreId; return $this; }
    public function setTypeParametre($typeParametre) { $this->typeParametre = $typeParametre; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    
    /**
     * Convertit l'objet en tableau
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

