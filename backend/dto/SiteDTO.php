<?php
/**
 * DTO (Data Transfer Object) pour Site
 */
class SiteDTO {
    public $id;
    public $nom;
    public $description;
    public $managerId;
    public $manager; // UserDTO ou array
    public $managerNom; // Nom du manager (pour affichage simplifié)
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
        $managerArray = null;
        if ($this->manager) {
            if (is_object($this->manager) && method_exists($this->manager, 'toArray')) {
                $managerArray = $this->manager->toArray();
            } elseif (is_array($this->manager)) {
                $managerArray = $this->manager;
            }
        }
        
        // Extraire le nom du manager si disponible
        $managerNom = $this->managerNom;
        if (!$managerNom && $this->manager) {
            if (is_object($this->manager) && isset($this->manager->nom)) {
                $managerNom = $this->manager->nom;
            } elseif (is_array($this->manager) && isset($this->manager['nom'])) {
                $managerNom = $this->manager['nom'];
            } elseif (is_object($this->manager) && method_exists($this->manager, 'getNom')) {
                $managerNom = $this->manager->getNom();
            }
        }
        
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'manager_id' => $this->managerId,
            'manager' => $managerArray,
            'manager_nom' => $managerNom,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}

