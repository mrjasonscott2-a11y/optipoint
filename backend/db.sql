-- =============================================
-- OptiPoint - Base de données complète
-- Importer via phpMyAdmin (base optipoint_db) ou : mysql -u root < db.sql
-- =============================================

CREATE DATABASE IF NOT EXISTS optipoint_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE optipoint_db;

-- Suppression des anciennes tables (ordre inverse des dépendances)
SET FOREIGN_KEY_CHECKS = 0;
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
SET FOREIGN_KEY_CHECKS = 1;

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

-- Compte administrateur unique (mot de passe : admin123)
-- Créez les autres comptes directement depuis l'interface Admin > Employés
INSERT INTO utilisateurs (matricule, nom, email, mot_de_passe, service, horaire, role_id, statut, date_embauche, telephone, manager_id) VALUES
('admin', 'Administrateur Système', 'admin@optipoint.com', '$2y$10$wVPI7UbCSu3THO1c0JslweukFQ7Kbp5ielOzS0VuW2v0n2atDXDY.', 'Administration', '08:00-17:00', 1, 'actif', '2023-01-15', '+241660000001', NULL);

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

-- =============================================

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
