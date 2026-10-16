<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Classe UserRepository - Repository pour les utilisateurs
 */
class UserRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un utilisateur par son matricule
     */
    public function findByMatricule($matricule) {
        $stmt = $this->db->prepare("SELECT u.*, s.nom as service_nom, p.nom as profil_nom, 
                                    e.nom as equipe_nom, statut_param.nom as statut_nom
                                FROM utilisateurs u 
                                LEFT JOIN services s ON u.service_id = s.id 
                                LEFT JOIN profils p ON u.profil_id = p.id 
                                LEFT JOIN equipes e ON u.equipe_id = e.id
                                LEFT JOIN parametres statut_param ON u.statut = statut_param.id
                                WHERE u.matricule = :matricule");
        $stmt->execute([':matricule' => $matricule]);
        $data = $stmt->fetch();
        if ($data) {
            $user = new User($data);
            if (isset($data['statut_nom'])) {
                $user->setStatutNom($data['statut_nom']);
            }
            return $user;
        }
        return null;
    }
    
    /**
     * Trouve un utilisateur par son ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT u.*, s.nom as service_nom, p.nom as profil_nom,
                                    e.nom as equipe_nom, statut_param.nom as statut_nom
                                    FROM utilisateurs u 
                                    LEFT JOIN services s ON u.service_id = s.id 
                                    LEFT JOIN profils p ON u.profil_id = p.id 
                                    LEFT JOIN equipes e ON u.equipe_id = e.id
                                    LEFT JOIN parametres statut_param ON u.statut = statut_param.id
                                    WHERE u.id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            $user = new User($data);
            if (isset($data['statut_nom'])) {
                $user->setStatutNom($data['statut_nom']);
            }
            return $user;
        }
        return null;
    }
    
    /**
     * Authentifie un utilisateur
     */
    public function authenticate($matricule, $password) {
        $user = $this->findByMatricule($matricule);
        if ($user && $user->verifyPassword($password)) {
            // Vérifier que le statut est actif (via le nom du paramètre)
            $statutNom = $user->getStatutNom();
            if ($statutNom === 'ACTIF' || $statutNom === 'actif') {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Récupère tous les utilisateurs avec filtres optionnels
     */
    public function findAll($filters = []) {
        $sql = "SELECT u.*, s.nom as service_nom, p.nom as profil_nom,
                e.nom as equipe_nom, statut_param.nom as statut_nom
                FROM utilisateurs u 
                LEFT JOIN services s ON u.service_id = s.id 
                LEFT JOIN profils p ON u.profil_id = p.id 
                LEFT JOIN equipes e ON u.equipe_id = e.id
                LEFT JOIN parametres statut_param ON u.statut = statut_param.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['service_id'])) {
            $sql .= " AND u.service_id = :service_id";
            $params[':service_id'] = $filters['service_id'];
        }
        
        if (isset($filters['statut'])) {
            // Si c'est un ID, utiliser directement
            if (is_numeric($filters['statut'])) {
                $sql .= " AND u.statut = :statut";
                $params[':statut'] = $filters['statut'];
            } else {
                // Si c'est un nom, joindre avec parametres
                $sql .= " AND statut_param.nom = :statut_nom";
                $params[':statut_nom'] = strtoupper($filters['statut']);
            }
        }
        
        if (isset($filters['equipe_id'])) {
            $sql .= " AND u.equipe_id = :equipe_id";
            $params[':equipe_id'] = $filters['equipe_id'];
        }
        
        $sql .= " ORDER BY u.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        return array_map(function($data) {
            $user = new User($data);
            if (isset($data['statut_nom'])) {
                $user->setStatutNom($data['statut_nom']);
            }
            return $user;
        }, $results);
    }
    
    /**
     * Crée un nouvel utilisateur
     */
    public function create(User $user) {
        // Utiliser service_id et profil_id si disponibles, sinon utiliser service et role pour compatibilité
        $serviceId = $user->getServiceId();
        $profilId = $user->getProfilId();
        $equipeId = $user->getEquipeId();
        
        $sql = "INSERT INTO utilisateurs 
                (matricule, nom, email, mot_de_passe, service_id, equipe_id, horaire, profil_id, statut, telephone, date_embauche)
                VALUES (:matricule, :nom, :email, :mot_de_passe, :service_id, :equipe_id, :horaire, :profil_id, :statut, :telephone, :date_embauche)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':matricule' => $user->getMatricule(),
            ':nom' => $user->getNom(),
            ':email' => $user->getEmail(),
            ':mot_de_passe' => $user->getMotDePasse(),
            ':service_id' => $serviceId,
            ':equipe_id' => $equipeId,
            ':horaire' => $user->getHoraire() ?? '08:00-17:00',
            ':profil_id' => $profilId,
            ':statut' => $user->getStatut() ?? 'actif',
            ':telephone' => $user->getTelephone(),
            ':date_embauche' => $user->getDateEmbauche()
        ]);
        
        $user->setId($this->db->lastInsertId());
        return $user;
    }
    
    /**
     * Met à jour un utilisateur
     */
    public function update(User $user) {
        $fields = [];
        $params = [':id' => $user->getId()];
        
        if ($user->getNom() !== null) {
            $fields[] = "nom = :nom";
            $params[':nom'] = $user->getNom();
        }
        if ($user->getEmail() !== null) {
            $fields[] = "email = :email";
            $params[':email'] = $user->getEmail();
        }
       
        if ($user->getServiceId() !== null) {
            $fields[] = "service_id = :service_id";
            $params[':service_id'] = $user->getServiceId();
        }
        if ($user->getEquipeId() !== null) {
            $fields[] = "equipe_id = :equipe_id";
            $params[':equipe_id'] = $user->getEquipeId();
        }
        if ($user->getHoraire() !== null) {
            $fields[] = "horaire = :horaire";
            $params[':horaire'] = $user->getHoraire();
        }
       
        if ($user->getProfilId() !== null) {
            $fields[] = "profil_id = :profil_id";
            $params[':profil_id'] = $user->getProfilId();
        }
        if ($user->getStatut() !== null) {
            $fields[] = "statut = :statut";
            $params[':statut'] = $user->getStatut();
        }
        if ($user->getTelephone() !== null) {
            $fields[] = "telephone = :telephone";
            $params[':telephone'] = $user->getTelephone();
        }
        if ($user->getDateEmbauche() !== null) {
            $fields[] = "date_embauche = :date_embauche";
            $params[':date_embauche'] = $user->getDateEmbauche();
        }
        if ($user->getMotDePasse() !== null) {
            $fields[] = "mot_de_passe = :mot_de_passe";
            $params[':mot_de_passe'] = $user->getMotDePasse();
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE utilisateurs SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Met à jour un utilisateur par matricule
     */
    public function updateByMatricule($matricule, $data) {
        $user = $this->findByMatricule($matricule);
        if (!$user) {
            return false;
        }
        
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($this->toCamelCase($key));
            if (method_exists($user, $method)) {
                $user->$method($value);
            }
        }
        
        return $this->update($user);
    }
    
    /**
     * Supprime un utilisateur
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Supprime un utilisateur par matricule
     */
    public function deleteByMatricule($matricule) {
        $user = $this->findByMatricule($matricule);
        if ($user) {
            return $this->delete($user->getId());
        }
        return false;
    }
    
    /**
     * Met à jour uniquement l'equipe_id d'un utilisateur
     */
    public function updateEquipeId($userId, $equipeId) {
        $sql = "UPDATE utilisateurs SET equipe_id = :equipe_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':equipe_id' => $equipeId
        ]);
    }
    
    private function toCamelCase($string) {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
}

