<?php
/**
 * Classe Site - Modèle pour les Sites (rôles)
 */
class Site {
    private $id;
    private $nom;
    private $description;
    private $statut;
    private $statutNom; // Nom du paramètre statut (chargé via JOIN)
    private $managerId;
    private $manager; // Objet Manager (chargé via JOIN)
    private $managerNom; // Nom du manager (chargé via JOIN pour affichage simplifié)
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
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getStatut() { return $this->statut; }
    public function getStatutNom() { return $this->statutNom; }
    public function getManagerId() { return $this->managerId; }
    public function getManager() { return $this->manager; }
    public function getManagerNom() { return $this->managerNom; }
    public function getDescription() { return $this->description; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setNom($nom) { $this->nom = $nom; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setStatutNom($statutNom) { $this->statutNom = $statutNom; return $this; }
    public function setManagerId($managerId) { $this->managerId = $managerId; return $this; }
    public function setManager($manager) { $this->manager = $manager; return $this; }
    public function setManagerNom($managerNom) { $this->managerNom = $managerNom; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    
   
    
    
    
    /**
     * Convertit l'objet en tableau
     */
    public function toArray() {
       
        
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'manager_id' => $this->managerId,
            'manager' => $this->manager,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

