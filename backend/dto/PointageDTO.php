<?php
/**
 * DTO (Data Transfer Object) pour Pointage
 */
class PointageDTO {
    public $id;
    public $utilisateurId;
    public $typePointage; // ID du paramètre type_pointage
    public $typePointageNom; // Nom du paramètre type_pointage
    public $datePointage;
    public $heure;
    public $localisation;
    public $latitude;
    public $longitude;
    public $modeTravail;
    public $modeTravailNom; // Nom du paramètre mode_travail
    public $notes;
    public $createdAt;
    
    // Champs supplémentaires pour l'affichage
    public $employeNom;
    public $employeMatricule;
    public $employeService;
    
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
            'type_pointage' => $this->typePointage,
            'type_pointage_nom' => $this->typePointageNom,
            'date_pointage' => $this->datePointage,
            'heure' => $this->heure,
            'localisation' => $this->localisation,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'mode_travail' => $this->modeTravail,
            'mode_travail_nom' => $this->modeTravailNom,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'employe_nom' => $this->employeNom,
            'employe_matricule' => $this->employeMatricule,
            'employe_service' => $this->employeService
        ];
    }
}

