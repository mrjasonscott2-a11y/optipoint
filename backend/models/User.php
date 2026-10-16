<?php
/**
 * Classe User - Modèle pour les utilisateurs
 */
class User {
    private $id;
    private $matricule;
    private $nom;
    private $email;
    private $motDePasse;
    private $serviceId;
    private $equipeId; // Relation Many-to-One avec Equipe
    private $horaire;
    private $profilId;
    private $statut;
    private $statutNom; // Nom du paramètre statut (chargé via JOIN)
    private $dateEmbauche;
    private $telephone;
    private $createdAt;
    private $updatedAt;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydrate l'objet avec les données
     */
    private function hydrate($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($this->toCamelCase($key));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
    
    /**
     * Convertit snake_case en camelCase
     */
    private function toCamelCase($string) {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getMatricule() { return $this->matricule; }
    public function getNom() { return $this->nom; }
    public function getEmail() { return $this->email; }
    public function getMotDePasse() { return $this->motDePasse; }
    public function getServiceId() { return $this->serviceId; }
    public function getEquipeId() { return $this->equipeId; }
    public function getHoraire() { return $this->horaire; }
    public function getProfilId() { return $this->profilId; }
    public function getStatut() { return $this->statut; }
    public function getStatutNom() { return $this->statutNom; }
    public function getDateEmbauche() { return $this->dateEmbauche; }
    public function getTelephone() { return $this->telephone; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; return $this; }
    public function setMatricule($matricule) { $this->matricule = $matricule; return $this; }
    public function setNom($nom) { $this->nom = $nom; return $this; }
    public function setEmail($email) { $this->email = $email; return $this; }
    public function setMotDePasse($motDePasse) { $this->motDePasse = $motDePasse; return $this; }
    public function setServiceId($serviceId) { $this->serviceId = $serviceId; return $this; }
    public function setEquipeId($equipeId) { $this->equipeId = $equipeId; return $this; }
    public function setHoraire($horaire) { $this->horaire = $horaire; return $this; }
    public function setProfilId($profilId) { $this->profilId = $profilId; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setStatutNom($statutNom) { $this->statutNom = $statutNom; return $this; }
    public function setDateEmbauche($dateEmbauche) { $this->dateEmbauche = $dateEmbauche; return $this; }
    public function setTelephone($telephone) { $this->telephone = $telephone; return $this; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
    
    /**
     * Convertit l'objet en tableau (sans le mot de passe)
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'email' => $this->email,
            'service_id' => $this->serviceId,
            'equipe_id' => $this->equipeId,
            'horaire' => $this->horaire,
            'profil_id' => $this->profilId,
            'statut' => $this->statut,
            'statut_nom' => $this->statutNom,
            'date_embauche' => $this->dateEmbauche,
            'telephone' => $this->telephone,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
    
    /**
     * Hash le mot de passe
     */
    public function hashPassword($password) {
        return hash('sha256', $password);
    }
    
    /**
     * Vérifie le mot de passe
     */
    public function verifyPassword($password) {
        return $this->motDePasse === $this->hashPassword($password);
    }
}

