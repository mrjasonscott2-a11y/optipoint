<?php
/**
 * Classe Tache - Modèle pour les Taches (rôles)
 */
class Tache {
    private $id;
    private $nom;
    private $description;
    private $planningId; // Relation Many-to-One avec Planning (au lieu de User)
    private $statut;
    private $statutNom; // Nom du paramètre statut (chargé via JOIN)
    private $createdAt;
    private $updatedAt;
    
    // Propriétés pour les noms (chargés via JOIN)
    private $planningDescription;
    private $planningDate;
    
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
    public function getPlanningId() { return $this->planningId; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getStatut() { return $this->statut; }
    public function getStatutNom() { return $this->statutNom; }
    public function getDescription() { return $this->description; }
    public function getPlanningDescription() { return $this->planningDescription; }
    public function getPlanningDate() { return $this->planningDate; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setNom($nom) { $this->nom = $nom; return $this; }
    public function setPlanningId($planningId) { $this->planningId = $planningId; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setStatutNom($statutNom) { $this->statutNom = $statutNom; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setPlanningDescription($planningDescription) { $this->planningDescription = $planningDescription; return $this; }
    public function setPlanningDate($planningDate) { $this->planningDate = $planningDate; return $this; }
    
   
    
    
    
    /**
     * Convertit l'objet en tableau
     */
    public function toArray() {
       
        
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'planning_id' => $this->planningId,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'planning_description' => $this->planningDescription,
            'planning_date' => $this->planningDate
        ];
    }
}

