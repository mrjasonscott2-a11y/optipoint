<?php
/**
 * Classe Profil - Modèle pour les profils (rôles)
 */
class Profil {
    private $id;
    private $nom;
    private $description;
    private $habilitations; // Array of Habilitation objects
    private $createdAt;
    private $updatedAt;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
        // Initialiser habilitations comme tableau vide si non défini
        if ($this->habilitations === null) {
            $this->habilitations = [];
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
    public function getDescription() { return $this->description; }
    public function getHabilitations() { return $this->habilitations ?? []; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setNom($nom) { $this->nom = $nom; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setHabilitations($habilitations) { 
        // Accepter un tableau d'objets Habilitation ou un tableau d'IDs
        if (is_array($habilitations)) {
            $this->habilitations = $habilitations;
        } else {
            $this->habilitations = [];
        }
        return $this; 
    }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    
    /**
     * Ajoute une habilitation au profil
     */
    public function addHabilitation($habilitation) {
        if (!is_array($this->habilitations)) {
            $this->habilitations = [];
        }
        $this->habilitations[] = $habilitation;
        return $this;
    }
    
    /**
     * Retire une habilitation du profil
     */
    public function removeHabilitation($habilitationId) {
        if (is_array($this->habilitations)) {
            $this->habilitations = array_filter($this->habilitations, function($h) use ($habilitationId) {
                if (is_object($h) && method_exists($h, 'getId')) {
                    return $h->getId() != $habilitationId;
                }
                return $h != $habilitationId;
            });
        }
        return $this;
    }
    
    /**
     * Convertit l'objet en tableau
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

