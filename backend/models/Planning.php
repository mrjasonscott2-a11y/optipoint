<?php
/**
 * Classe Planning - Modèle pour les plannings
 */
class Planning {
    private $id;
    private $utilisateurId;
    private $siteId; // Relation Many-to-One avec Site
    private $jourId; // Relation Many-to-One avec Jour
    private $dateDebut;
    private $dateFin;
    private $heureDebut;
    private $heureFin;
    private $description;
    private $lieu;
    private $createdAt;
    
    // Propriétés pour les noms (chargés via JOIN)
    private $siteNom;
    private $jourNom;
    private $utilisateurNom;
    
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
    public function getSiteId() { return $this->siteId; }
    public function getJourId() { return $this->jourId; }
    public function getDateDebut() { return $this->dateDebut; }
    public function getDateFin() { return $this->dateFin; }
    public function getHeureDebut() { return $this->heureDebut; }
    public function getHeureFin() { return $this->heureFin; }
    public function getDescription() { return $this->description; }
    public function getLieu() { return $this->lieu; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getSiteNom() { return $this->siteNom; }
    public function getJourNom() { return $this->jourNom; }
    public function getUtilisateurNom() { return $this->utilisateurNom; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setUtilisateurId($utilisateurId) { $this->utilisateurId = $utilisateurId; return $this; }
    public function setSiteId($siteId) { $this->siteId = $siteId; return $this; }
    public function setJourId($jourId) { $this->jourId = $jourId; return $this; }
    public function setDateDebut($dateDebut) { $this->dateDebut = $dateDebut; return $this; }
    public function setDateFin($dateFin) { $this->dateFin = $dateFin; return $this; }
    public function setHeureDebut($heureDebut) { $this->heureDebut = $heureDebut; return $this; }
    public function setHeureFin($heureFin) { $this->heureFin = $heureFin; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setLieu($lieu) { $this->lieu = $lieu; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setSiteNom($siteNom) { $this->siteNom = $siteNom; return $this; }
    public function setJourNom($jourNom) { $this->jourNom = $jourNom; return $this; }
    public function setUtilisateurNom($utilisateurNom) { $this->utilisateurNom = $utilisateurNom; return $this; }
    
    /**
     * Convertit l'objet en tableau
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
            'site_nom' => $this->siteNom,
            'jour_nom' => $this->jourNom,
            'utilisateur_nom' => $this->utilisateurNom
        ];
    }
}

