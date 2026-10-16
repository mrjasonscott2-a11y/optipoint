<?php
/**
 * Classe Pointage - Modèle pour les pointages
 */
class Pointage {
    private $id;
    private $utilisateurId;
    private $typePointage; // ID du paramètre type_pointage
    private $typePointageNom; // Nom du paramètre type_pointage (chargé via JOIN)
    private $datePointage;
    private $heure;
    private $localisation;
    private $latitude;
    private $longitude;
    private $modeTravail;
    private $modeTravailNom; // Nom du paramètre mode_travail (chargé via JOIN)
    private $notes;
    private $createdAt;
    
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
    public function getUtilisateurId() { return $this->utilisateurId; }
    public function getTypePointage() { return $this->typePointage; }
    public function getTypePointageNom() { return $this->typePointageNom; }
    public function getDatePointage() { return $this->datePointage; }
    public function getHeure() { return $this->heure; }
    public function getLocalisation() { return $this->localisation; }
    public function getLatitude() { return $this->latitude; }
    public function getLongitude() { return $this->longitude; }
    public function getModeTravail() { return $this->modeTravail; }
    public function getModeTravailNom() { return $this->modeTravailNom; }
    public function getNotes() { return $this->notes; }
    public function getCreatedAt() { return $this->createdAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setUtilisateurId($utilisateurId) { $this->utilisateurId = $utilisateurId; return $this; }
    public function setTypePointage($typePointage) { $this->typePointage = $typePointage; return $this; }
    public function setTypePointageNom($typePointageNom) { $this->typePointageNom = $typePointageNom; return $this; }
    public function setDatePointage($datePointage) { $this->datePointage = $datePointage; return $this; }
    public function setHeure($heure) { $this->heure = $heure; return $this; }
    public function setLocalisation($localisation) { $this->localisation = $localisation; return $this; }
    public function setLatitude($latitude) { $this->latitude = $latitude; return $this; }
    public function setLongitude($longitude) { $this->longitude = $longitude; return $this; }
    public function setModeTravail($modeTravail) { $this->modeTravail = $modeTravail; return $this; }
    public function setModeTravailNom($modeTravailNom) { $this->modeTravailNom = $modeTravailNom; return $this; }
    public function setNotes($notes) { $this->notes = $notes; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    
    /**
     * Convertit l'objet en tableau
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
            'created_at' => $this->createdAt
        ];
    }
}

