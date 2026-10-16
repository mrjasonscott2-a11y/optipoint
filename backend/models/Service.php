<?php
/**
 * Classe Service - Modèle pour les services
 */
class Service {
    private $id;
    private $nom;
    private $description;
    private $responsableId;
    private $responsableNom; // Nom du responsable (chargé via JOIN pour affichage simplifié)
    private $statut;
    private $statutNom; // Nom du paramètre statut (chargé via JOIN)
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
    public function getDescription() { return $this->description; }
    public function getResponsableId() { return $this->responsableId; }
    public function getResponsableNom() { return $this->responsableNom; }
    public function getStatut() { return $this->statut; }
    public function getStatutNom() { return $this->statutNom; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setNom($nom) { $this->nom = $nom; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setResponsableId($responsableId) { $this->responsableId = $responsableId; return $this; }
    public function setResponsableNom($responsableNom) { $this->responsableNom = $responsableNom; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setStatutNom($statutNom) { $this->statutNom = $statutNom; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    
    /**
     * Convertit l'objet en tableau
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
            'updated_at' => $this->updatedAt
        ];
    }
}

