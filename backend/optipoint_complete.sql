-- ============================================
-- Base de données OptiPoint - Script complet
-- ============================================
-- Ce script crée toutes les tables avec leurs relations
-- Ordre de création respecte les dépendances de clés étrangères
-- ============================================

CREATE DATABASE IF NOT EXISTS optipoint_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE optipoint_db;

-- ============================================
-- Table des Types de Paramètres
-- ============================================
CREATE TABLE IF NOT EXISTS type_parametres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Paramètres
-- ============================================
CREATE TABLE IF NOT EXISTS parametres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    type_parametre_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type_parametre (type_parametre_id),
    INDEX idx_code (code),
    INDEX idx_nom_type (nom, type_parametre_id),
    FOREIGN KEY (type_parametre_id) REFERENCES type_parametres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Équipes
-- ============================================
CREATE TABLE IF NOT EXISTS equipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    manager_id INT NULL,
    statut INT NULL, -- Clé étrangère vers parametres (type: statut)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_manager (manager_id),
    INDEX idx_statut (statut),
    FOREIGN KEY (statut) REFERENCES parametres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Profils
-- ============================================
CREATE TABLE IF NOT EXISTS profils (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Habilitations
-- ============================================
CREATE TABLE IF NOT EXISTS habilitations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table de liaison Profil-Habilitations (Many-to-Many)
-- ============================================
CREATE TABLE IF NOT EXISTS profil_habilitations (
    profil_id INT NOT NULL,
    habilitation_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (profil_id, habilitation_id),
    INDEX idx_profil (profil_id),
    INDEX idx_habilitation (habilitation_id),
    FOREIGN KEY (profil_id) REFERENCES profils(id) ON DELETE CASCADE,
    FOREIGN KEY (habilitation_id) REFERENCES habilitations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Services
-- ============================================
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    responsable_id INT NULL,
    statut INT NULL, -- Clé étrangère vers parametres (type: statut)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_responsable (responsable_id),
    INDEX idx_statut (statut),
    FOREIGN KEY (statut) REFERENCES parametres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Sites
-- ============================================
CREATE TABLE IF NOT EXISTS sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    manager_id INT NULL,
    statut INT NULL, -- Clé étrangère vers parametres (type: statut)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_manager (manager_id),
    INDEX idx_statut (statut),
    FOREIGN KEY (statut) REFERENCES parametres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Jours
-- ============================================
CREATE TABLE IF NOT EXISTS jours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Utilisateurs
-- ============================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricule VARCHAR(50) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    service_id INT NULL,
    equipe_id INT NULL,
    horaire VARCHAR(20) DEFAULT '08:00-17:00',
    profil_id INT NULL,
    statut INT NULL, -- Clé étrangère vers parametres (type: statut)
    date_embauche DATE NULL,
    telephone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_matricule (matricule),
    INDEX idx_email (email),
    INDEX idx_service (service_id),
    INDEX idx_equipe (equipe_id),
    INDEX idx_profil (profil_id),
    INDEX idx_statut (statut),
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE SET NULL,
    FOREIGN KEY (profil_id) REFERENCES profils(id) ON DELETE SET NULL,
    FOREIGN KEY (statut) REFERENCES parametres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter les clés étrangères pour manager_id dans equipes et sites après création de utilisateurs
ALTER TABLE equipes ADD FOREIGN KEY (manager_id) REFERENCES utilisateurs(id) ON DELETE SET NULL;
ALTER TABLE sites ADD FOREIGN KEY (manager_id) REFERENCES utilisateurs(id) ON DELETE SET NULL;
ALTER TABLE services ADD FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id) ON DELETE SET NULL;

-- ============================================
-- Table des Plannings
-- ============================================
CREATE TABLE IF NOT EXISTS plannings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    site_id INT NULL,
    jour_id INT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    description TEXT NULL,
    lieu VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_site (site_id),
    INDEX idx_jour (jour_id),
    INDEX idx_date_debut (date_debut),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL,
    FOREIGN KEY (jour_id) REFERENCES jours(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Tâches
-- ============================================
CREATE TABLE IF NOT EXISTS taches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    planning_id INT NOT NULL, -- Relation Many-to-One avec Planning
    statut INT NULL, -- Clé étrangère vers parametres (type: statut)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_planning (planning_id),
    INDEX idx_statut (statut),
    FOREIGN KEY (planning_id) REFERENCES plannings(id) ON DELETE CASCADE,
    FOREIGN KEY (statut) REFERENCES parametres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Pointages
-- ============================================
CREATE TABLE IF NOT EXISTS pointages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    type_pointage INT NULL, -- Clé étrangère vers parametres (type: type de pointage)
    date_pointage DATE NOT NULL,
    heure TIME NOT NULL,
    localisation VARCHAR(255) NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    mode_travail INT NULL, -- Clé étrangère vers parametres (type: mode de travail)
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilisateur_date (utilisateur_id, date_pointage),
    INDEX idx_date_pointage (date_pointage),
    INDEX idx_mode_travail (mode_travail),
    INDEX idx_type_pointage (type_pointage),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (mode_travail) REFERENCES parametres(id) ON DELETE SET NULL,
    FOREIGN KEY (type_pointage) REFERENCES parametres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Rapports
-- ============================================
CREATE TABLE IF NOT EXISTS rapports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    total_heures DECIMAL(5,2) NOT NULL,
    jours_presents INT NOT NULL,
    jours_absents INT NOT NULL,
    fichier_path VARCHAR(255) NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_periode (periode_debut, periode_fin),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des Logs Admin
-- ============================================
CREATE TABLE IF NOT EXISTS logs_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Données par défaut
-- ============================================

-- Types de paramètres
INSERT INTO type_parametres (nom, code, description) VALUES
('Statut', 'TYPPARAM0001', 'Statut des entités (actif, inactif, suspendu)'),
('Mode de travail', 'TYPPARAM0002', 'Mode de travail (bureau, télétravail, client)'),
('Type de pointage', 'TYPPARAM0003', 'Type de pointage (arrivée, départ)'),
('Statut tâche', 'TYPPARAM0004', 'Statut des tâches (à faire, en cours, terminé)')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Paramètres - Statuts
INSERT INTO parametres (nom, code, type_parametre_id, description) VALUES
('ACTIF', 'PARAM0001', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0001'), 'Statut actif'),
('INACTIF', 'PARAM0002', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0001'), 'Statut inactif'),
('SUSPENDU', 'PARAM0003', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0001'), 'Statut suspendu')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Paramètres - Modes de travail
INSERT INTO parametres (nom, code, type_parametre_id, description) VALUES
('BUREAU', 'PARAM0004', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0002'), 'Travail au bureau'),
('TELE TRAVAIL', 'PARAM0005', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0002'), 'Télétravail'),
('CLIENT', 'PARAM0006', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0002'), 'Travail chez le client')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Paramètres - Types de pointage
INSERT INTO parametres (nom, code, type_parametre_id, description) VALUES
('ARRIVEE', 'PARAM0007', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0003'), 'Pointage d''arrivée'),
('DEPART', 'PARAM0008', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0003'), 'Pointage de départ')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Paramètres - Statuts de tâches
INSERT INTO parametres (nom, code, type_parametre_id, description) VALUES
('A_FAIRE', 'PARAM0009', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0004'), 'Tâche à faire'),
('EN_COURS', 'PARAM0010', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0004'), 'Tâche en cours'),
('TERMINE', 'PARAM0011', (SELECT id FROM type_parametres WHERE code = 'TYPPARAM0004'), 'Tâche terminée')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Jours de la semaine
INSERT INTO jours (nom) VALUES
('Lundi'),
('Mardi'),
('Mercredi'),
('Jeudi'),
('Vendredi'),
('Samedi'),
('Dimanche')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Profils par défaut
INSERT INTO profils (nom, description) VALUES
('admin', 'Administrateur système'),
('manager', 'Manager'),
('employe', 'Employé')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Habilitations par défaut
INSERT INTO habilitations (nom, code, description) VALUES
('Gestion des employés', 'HAB001', 'Permet de gérer les employés'),
('Gestion des pointages', 'HAB002', 'Permet de gérer les pointages'),
('Gestion du planning', 'HAB003', 'Permet de gérer le planning'),
('Gestion des rapports', 'HAB004', 'Permet de générer et consulter les rapports'),
('Gestion des services', 'HAB005', 'Permet de gérer les services'),
('Gestion des profils', 'HAB006', 'Permet de gérer les profils'),
('Gestion des habilitations', 'HAB007', 'Permet de gérer les habilitations'),
('Gestion des paramètres', 'HAB008', 'Permet de gérer les paramètres')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Associer toutes les habilitations au profil admin
INSERT INTO profil_habilitations (profil_id, habilitation_id)
SELECT p.id, h.id
FROM profils p, habilitations h
WHERE p.nom = 'admin'
ON DUPLICATE KEY UPDATE profil_id=VALUES(profil_id);

-- Services par défaut
INSERT INTO services (nom, description, statut) VALUES
('IT', 'Service Informatique', (SELECT id FROM parametres WHERE code = 'PARAM0001')),
('RH', 'Ressources Humaines', (SELECT id FROM parametres WHERE code = 'PARAM0001')),
('Comptabilité', 'Service Comptable', (SELECT id FROM parametres WHERE code = 'PARAM0001')),
('Commercial', 'Service Commercial', (SELECT id FROM parametres WHERE code = 'PARAM0001')),
('Production', 'Service Production', (SELECT id FROM parametres WHERE code = 'PARAM0001'))
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Utilisateur admin par défaut (mot de passe: admin123 - à changer en production)
INSERT INTO utilisateurs (matricule, nom, email, mot_de_passe, service_id, profil_id, statut, date_embauche) VALUES
('ADMIN001', 'Administrateur', 'admin@optipoint.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 (SELECT id FROM services WHERE nom = 'IT' LIMIT 1),
 (SELECT id FROM profils WHERE nom = 'admin' LIMIT 1),
 (SELECT id FROM parametres WHERE code = 'PARAM0001'),
 CURDATE())
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- ============================================
-- Fin du script
-- ============================================

