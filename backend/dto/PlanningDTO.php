<?php
/**
 * DTO (Data Transfer Object) pour Planning
 */
class PlanningDTO {
    public $id;
    public $utilisateurId;
    public $siteId;
    public $jourId;
    public $dateDebut;
    public $dateFin;
    public $heureDebut;
    public $heureFin;
    public $description;
    public $lieu;
    public $createdAt;
    
    // Champs supplémentaires pour l'affichage
    public $utilisateurNom;
    public $utilisateurMatricule;
    public $siteNom;
    public $jourNom;
    
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
            'utilisateur_id' => $this->utilisateurId,
            'site_id' => $this->siteId,
            'jour_id' => $this->jourId,
            'date_debut' => $this->dateDebut,
            'date_fin' => $this->dateFin,
            'heure_debut' => $this->heureDebut,
            'heure_fin' => $this->heureFin,
            'description' => $this->description,
            'lieu' => $this->lieu,
            'created_at' => $this->createdAt,
            'utilisateur_nom' => $this->utilisateurNom,
            'utilisateur_matricule' => $this->utilisateurMatricule,
            'site_nom' => $this->siteNom,
            'jour_nom' => $this->jourNom
        ];
    }
}

