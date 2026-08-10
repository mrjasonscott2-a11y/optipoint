-- =============================================
-- OptiPoint - Base de données complète
-- Importer via phpMyAdmin ou : mysql -u root < db.sql
-- =============================================

DROP TABLE IF EXISTS logs_admin;
DROP TABLE IF EXISTS parametres;
DROP TABLE IF EXISTS lieux_autorises;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conges;
DROP TABLE IF EXISTS alertes;
DROP TABLE IF EXISTS utilisateur_equipes;
DROP TABLE IF EXISTS pointages;
DROP TABLE IF EXISTS plannings;
DROP TABLE IF EXISTS rapports;
DROP TABLE IF EXISTS equipes;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS roles;

CREATE DATABASE IF NOT EXISTS optipoint_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE optipoint_db;

-- =============================================
-- ROLES
-- =============================================
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (id, nom, description, permissions) VALUES
(1, 'admin', 'Administrateur système - Accès complet', '["gestion_utilisateurs","gestion_equipes","gestion_plannings","gestion_rapports","gestion_parametres","validation_conges","acces_logs","export_donnees"]'),
(2, 'responsable', 'Responsable de département', '["gestion_departement","validation_conges","rapports_departement","gestion_plannings","gestion_affectations","gestion_equipes"]'),
(3, 'manager', 'Manager d''équipe', '["gestion_collaborateurs","validation_pointages","rapports_equipe","gestion_absences","validation_conges_equipe"]'),
(4, 'employe', 'Employé standard', '["pointage","consulter_planning","consulter_rapport","demander_conge"]');

-- =============================================
-- UTILISATEURS
-- =============================================
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricule VARCHAR(50) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    service VARCHAR(50) NOT NULL,
    horaire VARCHAR(20) DEFAULT '08:00-17:00',
    role_id INT DEFAULT 4,
    statut ENUM('actif','inactif','suspendu') DEFAULT 'actif',
    date_embauche DATE,
    telephone VARCHAR(20),
    manager_id INT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (manager_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_service (service),
    INDEX idx_statut (statut),
    INDEX idx_role (role_id)
) ENGINE=InnoDB;

-- Utilisateurs de démo (mots de passe insérés via generate_hashes.php)
INSERT INTO utilisateurs (matricule, nom, email, mot_de_passe, service, horaire, role_id, statut, date_embauche, telephone, manager_id) VALUES
('admin', 'Administrateur Système', 'admin@optipoint.com', '', 'Administration', '08:00-17:00', 1, 'actif', '2023-01-15', '+241660000001', NULL),
('RESP001', 'Marie Dubois', 'marie.dubois@optipoint.com', '', 'Direction', '08:00-17:00', 2, 'actif', '2023-02-01', '+241660000002', NULL),
('MGR001', 'Jean Martin', 'jean.martin@optipoint.com', '', 'Informatique', '08:00-17:00', 3, 'actif', '2023-03-10', '+241660000003', 2),
('MGR002', 'Sophie Lambert', 'sophie.lambert@optipoint.com', '', 'Commercial', '09:00-18:00', 3, 'actif', '2023-03-15', '+241660000004', 2),
('MGR003', 'Thomas Bernard', 'thomas.bernard@optipoint.com', '', 'Ressources Humaines', '08:00-17:00', 3, 'actif', '2023-04-01', '+241660000005', 2),
('EMP001', 'Pierre Durand', 'pierre.durand@optipoint.com', '', 'Informatique', '08:00-17:00', 4, 'actif', '2023-05-10', '+241660000006', 3),
('EMP002', 'Julie Moreau', 'julie.moreau@optipoint.com', '', 'Informatique', '08:00-17:00', 4, 'actif', '2023-06-01', '+241660000007', 3),
('EMP003', 'Marc Petit', 'marc.petit@optipoint.com', '', 'Commercial', '09:00-18:00', 4, 'actif', '2023-06-15', '+241660000008', 4),
('EMP004', 'Claire Rousseau', 'claire.rousseau@optipoint.com', '', 'Commercial', '09:00-18:00', 4, 'actif', '2023-07-01', '+241660000009', 4),
('EMP005', 'Nicolas Garcia', 'nicolas.garcia@optipoint.com', '', 'Ressources Humaines', '08:00-17:00', 4, 'actif', '2023-07-20', '+241660000010', 5),
('EMP006', 'Anne Lefevre', 'anne.lefevre@optipoint.com', '', 'Ressources Humaines', '08:00-17:00', 4, 'inactif', '2023-08-01', '+241660000011', 5);

-- =============================================
-- ÉQUIPES
-- =============================================
CREATE TABLE equipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT DEFAULT NULL,
    departement VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO equipes (id, nom, description, manager_id, departement) VALUES
(1, 'Équipe Développement', 'Développement et support informatique', 3, 'Informatique'),
(2, 'Équipe Commerciale', 'Ventes et relations clients', 4, 'Commercial'),
(3, 'Équipe RH', 'Gestion des ressources humaines', 5, 'Ressources Humaines');

-- =============================================
-- UTILISATEURS-ÉQUIPES (affectations)
-- =============================================
CREATE TABLE utilisateur_equipes (
    utilisateur_id INT NOT NULL,
    equipe_id INT NOT NULL,
    date_affectation DATE NOT NULL,
    role_equipe ENUM('membre','lead') DEFAULT 'membre',
    PRIMARY KEY (utilisateur_id, equipe_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO utilisateur_equipes (utilisateur_id, equipe_id, date_affectation, role_equipe) VALUES
(6, 1, '2023-05-10', 'membre'),   -- Pierre → Dev
(7, 1, '2023-06-01', 'membre'),   -- Julie → Dev
(8, 2, '2023-06-15', 'membre'),   -- Marc → Commercial
(9, 2, '2023-07-01', 'membre'),   -- Claire → Commercial
(10, 3, '2023-07-20', 'membre'),  -- Nicolas → RH
(11, 3, '2023-08-01', 'membre');  -- Anne → RH

-- =============================================
-- POINTAGES
-- =============================================
CREATE TABLE pointages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    type ENUM('arrivee','depart') NOT NULL,
    date_pointage DATE NOT NULL,
    heure TIME NOT NULL,
    localisation VARCHAR(255),
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    mode_travail ENUM('bureau','teletravail','client') DEFAULT 'bureau',
    notes TEXT,
    statut ENUM('normal','retard') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_date (utilisateur_id, date_pointage),
    INDEX idx_date (date_pointage),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

-- Pointages de démo (7 derniers jours)
INSERT INTO pointages (utilisateur_id, type, date_pointage, heure, localisation, mode_travail, statut) VALUES
-- Pierre (id=6) - semaine type
(6, 'arrivee', CURDATE() - INTERVAL 0 DAY, '07:55:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(6, 'depart', CURDATE() - INTERVAL 0 DAY, '17:05:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(6, 'arrivee', CURDATE() - INTERVAL 1 DAY, '08:02:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(6, 'depart', CURDATE() - INTERVAL 1 DAY, '17:30:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(6, 'arrivee', CURDATE() - INTERVAL 2 DAY, '08:22:00', 'Bureau principal - Libreville', 'bureau', 'retard'),
(6, 'depart', CURDATE() - INTERVAL 2 DAY, '17:10:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(6, 'arrivee', CURDATE() - INTERVAL 3 DAY, '07:48:00', 'Domicile', 'teletravail', 'normal'),
(6, 'depart', CURDATE() - INTERVAL 3 DAY, '16:50:00', 'Domicile', 'teletravail', 'normal'),
(6, 'arrivee', CURDATE() - INTERVAL 4 DAY, '07:50:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(6, 'depart', CURDATE() - INTERVAL 4 DAY, '17:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
-- Julie (id=7)
(7, 'arrivee', CURDATE() - INTERVAL 0 DAY, '08:10:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(7, 'depart', CURDATE() - INTERVAL 0 DAY, '17:15:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(7, 'arrivee', CURDATE() - INTERVAL 1 DAY, '07:45:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(7, 'depart', CURDATE() - INTERVAL 1 DAY, '17:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(7, 'arrivee', CURDATE() - INTERVAL 2 DAY, '08:30:00', 'Bureau principal - Libreville', 'bureau', 'retard'),
(7, 'depart', CURDATE() - INTERVAL 2 DAY, '17:20:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(7, 'arrivee', CURDATE() - INTERVAL 3 DAY, '08:00:00', 'Domicile', 'teletravail', 'normal'),
(7, 'depart', CURDATE() - INTERVAL 3 DAY, '17:05:00', 'Domicile', 'teletravail', 'normal'),
-- Marc (id=8) - horaire 09:00-18:00
(8, 'arrivee', CURDATE() - INTERVAL 0 DAY, '09:05:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(8, 'depart', CURDATE() - INTERVAL 0 DAY, '18:10:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(8, 'arrivee', CURDATE() - INTERVAL 1 DAY, '09:20:00', 'Client - Owendo', 'client', 'retard'),
(8, 'depart', CURDATE() - INTERVAL 1 DAY, '18:30:00', 'Client - Owendo', 'client', 'normal'),
(8, 'arrivee', CURDATE() - INTERVAL 2 DAY, '09:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(8, 'depart', CURDATE() - INTERVAL 2 DAY, '18:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
-- Claire (id=9)
(9, 'arrivee', CURDATE() - INTERVAL 0 DAY, '08:55:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(9, 'depart', CURDATE() - INTERVAL 0 DAY, '18:05:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(9, 'arrivee', CURDATE() - INTERVAL 1 DAY, '09:10:00', 'Bureau principal - Libreville', 'bureau', 'retard'),
(9, 'depart', CURDATE() - INTERVAL 1 DAY, '18:15:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
-- Nicolas (id=10)
(10, 'arrivee', CURDATE() - INTERVAL 0 DAY, '07:50:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(10, 'depart', CURDATE() - INTERVAL 0 DAY, '17:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(10, 'arrivee', CURDATE() - INTERVAL 1 DAY, '08:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(10, 'depart', CURDATE() - INTERVAL 1 DAY, '17:05:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
-- Managers
(3, 'arrivee', CURDATE() - INTERVAL 0 DAY, '07:45:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(3, 'depart', CURDATE() - INTERVAL 0 DAY, '17:30:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(4, 'arrivee', CURDATE() - INTERVAL 0 DAY, '08:50:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(4, 'depart', CURDATE() - INTERVAL 0 DAY, '18:05:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(5, 'arrivee', CURDATE() - INTERVAL 0 DAY, '08:00:00', 'Bureau principal - Libreville', 'bureau', 'normal'),
(5, 'depart', CURDATE() - INTERVAL 0 DAY, '17:10:00', 'Bureau principal - Libreville', 'bureau', 'normal');

-- =============================================
-- PLANNINGS
-- =============================================
CREATE TABLE plannings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    heure_debut TIME DEFAULT NULL,
    heure_fin TIME DEFAULT NULL,
    description TEXT,
    lieu VARCHAR(100) DEFAULT '',
    type_activite VARCHAR(30) DEFAULT 'bureau',
    couleur VARCHAR(7) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_dates (date_debut, date_fin)
) ENGINE=InnoDB;

-- Plannings de démo
INSERT INTO plannings (utilisateur_id, date_debut, date_fin, heure_debut, heure_fin, description, lieu, type_activite, couleur) VALUES
(6, CURDATE(), CURDATE() + INTERVAL 4 DAY, '08:00:00', '17:00:00', 'Présence au bureau', 'Bureau principal', 'bureau', '#3b82f6'),
(7, CURDATE(), CURDATE() + INTERVAL 2 DAY, '08:00:00', '17:00:00', 'Présence au bureau', 'Bureau principal', 'bureau', '#3b82f6'),
(7, CURDATE() + INTERVAL 3 DAY, CURDATE() + INTERVAL 4 DAY, '08:00:00', '17:00:00', 'Télétravail', 'Domicile', 'teletravail', '#8b5cf6'),
(8, CURDATE(), CURDATE() + INTERVAL 4 DAY, '09:00:00', '18:00:00', 'Présence au bureau', 'Bureau principal', 'bureau', '#3b82f6'),
(10, CURDATE(), CURDATE() + INTERVAL 4 DAY, '08:00:00', '17:00:00', 'Présence au bureau', 'Bureau principal', 'bureau', '#3b82f6'),
(10, CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 6 DAY, NULL, NULL, 'Télétravail', 'Domicile', 'teletravail', '#8b5cf6');

-- =============================================
-- CONGÉS / TÉLÉTRAVAIL
-- =============================================
CREATE TABLE conges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    type_demande ENUM('conge_annuel','conge_maladie','teletravail','mission','autre') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    motif TEXT,
    statut ENUM('en_attente','approuve','refuse') DEFAULT 'en_attente',
    traite_par INT DEFAULT NULL,
    date_traitement DATETIME DEFAULT NULL,
    commentaire_manager TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (traite_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_dates (date_debut, date_fin)
) ENGINE=InnoDB;

-- Demandes de démo
INSERT INTO conges (utilisateur_id, type_demande, date_debut, date_fin, motif, statut) VALUES
(6, 'conge_annuel', CURDATE() + INTERVAL 14 DAY, CURDATE() + INTERVAL 18 DAY, 'Congé personnel', 'en_attente'),
(7, 'teletravail', CURDATE() + INTERVAL 7 DAY, CURDATE() + INTERVAL 7 DAY, 'Travaux à domicile', 'en_attente'),
(9, 'conge_maladie', CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 4 DAY, 'Consultation médicale', 'approuve'),
(6, 'conge_annuel', CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 25 DAY, 'Vacances familiales', 'approuve');

-- =============================================
-- RAPPORTS
-- =============================================
CREATE TABLE rapports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    total_heures DECIMAL(6,2) DEFAULT 0,
    jours_presents INT DEFAULT 0,
    jours_absents INT DEFAULT 0,
    fichier_path VARCHAR(255) DEFAULT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- ALERTES
-- =============================================
CREATE TABLE alertes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('retard','absence','productivite','securite','autre') NOT NULL,
    niveau ENUM('faible','moyen','eleve','critique') DEFAULT 'moyen',
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    utilisateur_id INT DEFAULT NULL,
    equipe_id INT DEFAULT NULL,
    lue BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_lue (lue),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

INSERT INTO alertes (type, niveau, titre, description, utilisateur_id, equipe_id, lue) VALUES
('retard', 'moyen', 'Retard détecté - Pierre Durand', 'Arrivée à 08:22 le ' DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%d/%m/%Y'), 6, 1, FALSE),
('retard', 'moyen', 'Retard détecté - Julie Moreau', 'Arrivée à 08:30 le ' DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%d/%m/%Y'), 7, 1, FALSE),
('retard', 'faible', 'Retard détecté - Marc Petit', 'Arrivée à 09:20 en déplacement client', 8, 2, FALSE),
('absence', 'eleve', 'Absence non justifiée - Anne Lefevre', 'Employé inactif, aucune présence récente', 11, 3, FALSE),
('autre', 'faible', 'Nouvelle demande de congé', 'Pierre Durand demande un congé du ' DATE_FORMAT(CURDATE() + INTERVAL 14 DAY, '%d/%m/%Y') ' au ' DATE_FORMAT(CURDATE() + INTERVAL 18 DAY, '%d/%m/%Y'), 6, NULL, FALSE);

-- =============================================
-- MESSAGES
-- =============================================
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    sujet VARCHAR(200) DEFAULT '',
    contenu TEXT,
    lu BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_lu (lu),
    INDEX idx_conversation (expediteur_id, destinataire_id)
) ENGINE=InnoDB;

INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu, lu) VALUES
(3, 1, 'Mise à jour planning', 'Bonjour, le planning de l''équipe dev a été mis à jour pour la semaine prochaine.', FALSE),
(6, 3, 'Demande de télétravail', 'Bonjour Jean, est-ce que je peux faire du télétravail mercredi prochain ?', FALSE),
(1, 3, 'Réunion importante', 'Merci de préparer le rapport mensuel pour la réunion de vendredi.', TRUE);

-- =============================================
-- LIEUX AUTORISÉS
-- =============================================
CREATE TABLE lieux_autorises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) DEFAULT '',
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    rayon_metres INT DEFAULT 100,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO lieux_autorises (nom, adresse, latitude, longitude, rayon_metres) VALUES
('Bureau Principal', 'Centre-ville, Libreville, Gabon', 0.3925, 9.4438, 200),
('Bureau Secondaire', 'Owendo, Gabon', 0.3500, 9.5000, 150);

-- =============================================
-- PARAMÈTRES
-- =============================================
CREATE TABLE parametres (
    cle VARCHAR(100) PRIMARY KEY,
    valeur TEXT,
    categorie VARCHAR(50) DEFAULT 'general'
) ENGINE=InnoDB;

INSERT INTO parametres (cle, valeur, categorie) VALUES
('entreprise_nom', 'OptiPoint', 'general'),
('entreprise_adresse', 'Centre-ville, Libreville, Gabon', 'general'),
('horaire_defaut', '08:00-17:00', 'pointage'),
('seuil_retard_minutes', '15', 'pointage'),
('geolocalisation_active', 'true', 'pointage'),
('geolocalisation_obligatoire', 'false', 'pointage'),
('theme', 'light', 'general'),
('langue', 'fr', 'general'),
('pause_dejeuner', '12:00-13:00', 'pointage'),
('nb_jours_conges_annuel', '30', 'conges'),
('validation_pointage_auto', 'false', 'pointage');

-- =============================================
-- LOGS ADMIN
-- =============================================
CREATE TABLE logs_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =============================================
-- FIN - Exécuter ensuite generate_hashes.php
-- pour insérer les mots de passe bcrypt
-- URL : http://localhost/optipoint/server/generate_hashes.php
-- =============================================
