# OptiPoint - Système de Pointage et Gestion du Temps

## 🎯 Présentation

OptiPoint est une application web professionnelle de pointage d'employés avec géolocalisation, conçue pour les entreprises souhaitant automatiser et sécuriser la gestion des présences, des congés et des plannings.

## ✨ Fonctionnalités

- **Pointage intelligent** : Arrivée/Départ avec détection géolocalisation GPS
- **Gestion multi-rôles** : Admin, Responsable, Manager, Employé
- **Congés & absences** : Demandes, validation hiérarchique, suivi
- **Planning** : Attribution des plannings par équipe
- **Rapports** : Statistiques en temps réel, graphiques, export CSV
- **Paramètres** : Configuration complète (horaires, seuils, lieux autorisés)
- **Journal d'administration** : Traçabilité de toutes les actions

## 🏗️ Architecture

```
optipoint/
├── api.php                        # API REST (point d'entrée unique)
├── login.html                     # Page de connexion
├── admin.html                     # Dashboard Admin
├── admin-employes.html           # Gestion employés (CRUD)
├── admin-pointages.html          # Historique des pointages
├── admin-planning.html           # Gestion des plannings
├── admin-rapports.html           # Rapports avancés
├── admin-parametres.html         # Paramètres système
├── dashboard-responsable.html    # Dashboard Responsable
├── responsable-employes.html    # Vue employés
├── responsable-pointages.html   # Pointages (lecture)
├── responsable-conges.html       # Validation des congés
├── responsable-equipes.html      # Visualisation équipes
├── responsable-planning.html     # Planning (lecture)
├── responsable-rapports.html     # Rapports
├── dashboard-manager.html        # Dashboard Manager
├── manager-employes.html         # Mon équipe
├── manager-pointages.html        # Pointages équipe
├── manager-conges.html           # Congés (validation)
├── manager-planning.html        # Planning équipe
├── manager-rapports.html         # Rapports équipe
├── dashboard.html                # Dashboard Employé
├── pointage.html                 # Page de pointage (géoloc)
├── planning.html                 # Mon planning
├── rapport.html                  # Mes rapports
├── css/
│   ├── style.css                 # Styles globaux + sidebar
│   └── admin.css                 # Styles spécifiques (dashboard, cards, etc.)
├── js/
│   ├── app.js                     # Fonctions communes (apiCall, showToast, etc.)
│   ├── auth.js                    # Login/logout/redirect
│   ├── admin.js / admin-*.js     # Scripts admin
│   ├── responsable.js / *.js      # Scripts responsable
│   ├── manager.js / *.js          # Scripts manager
│   ├── dashboard.js / *.js       # Scripts employé
│   └── rapport.js / pointage.js / planning.js
├── server/
│   ├── config.php                 # Configuration DB + fonctions utilitaires
│   ├── db.sql                     # Schéma complet + données de démo
│   └── generate_hashes.php        # Générateur de mots de passe
└── .htaccess                      # Protection fichiers serveur
```

## 🚀 Installation (XAMPP)

### Prérequis
- [XAMPP](https://www.apachefriends.org/) installé (Apache + MySQL + PHP 7.4+)

### Étapes

1. **Copier le projet**
   ```
   Copier le dossier "optipoint" dans : C:\xampp\htdocs\
   ```

2. **Démarrer XAMPP**
   - Lancer Apache et MySQL depuis le panneau de contrôle XAMPP

3. **Créer la base de données**
   - Ouvrir http://localhost/phpmyadmin
   - Cliquer sur "Nouveau" → Nom : `optipoint_db` → Créer
   - Aller dans l'onglet "Importer"
   - Sélectionner le fichier `C:\xampp\htdocs\optipoint\server\db.sql`
   - Cliquer sur "Exécuter"

4. **Générer les mots de passe**
   - Ouvrir dans le navigateur : http://localhost/optipoint/server/generate_hashes.php
   - Vous devriez voir les 11 utilisateurs créés
   - ⚠️ Exécuter cette page **une seule fois**

5. **Se connecter**
   - Ouvrir : http://localhost/optipoint/login.html
   - Utiliser les comptes de démonstration (voir ci-dessous)

## 👥 Comptes de démonstration

| Matricule | Mot de passe | Rôle | Dashboard |
|----------|-------------|------|-----------|
| `admin` | `admin123` | Administrateur | `admin.html` |
| `RESP001` | `admin123` | Responsable | `dashboard-responsable.html` |
| `MGR001` | `admin123` | Manager IT | `dashboard-manager.html` |
| `MGR002` | `admin123` | Manager Commercial | `dashboard-manager.html` |
| `MGR003` | `admin123` | Manager RH | `dashboard-manager.html` |
| `EMP001` | `admin123` | Employé IT | `dashboard.html` |
| `EMP002` | `admin123` | Employé IT | `dashboard.html` |
| `EMP003` | `admin123` | Employé Commercial | `dashboard.html` |
| `EMP004` | `admin123` | Employé Commercial | `dashboard.html` |
| `EMP005` | `admin123` | Employé RH | `dashboard.html` |
| `EMP006` | `admin123` | Employé RH (inactif) | `dashboard.html` |

## 🔧 Technologies

- **Backend** : PHP 7.4+ avec PDO / MySQL
- **Frontend** : HTML5, CSS3, JavaScript Vanilla (Fetch API)
- **Graphiques** : Chart.js (CDN)
- **Icônes** : Font Awesome 6 (CDN)
- **Polices** : Google Poppins (CDN)
- **Serveur** : Apache (XAMPP)

## 📡 API REST

Toutes les requêtes passent par `api.php?action=xxx`

| Action | Méthode | Description |
|--------|---------|-------------|
| `login` | POST | Authentification |
| `logout` | POST | Déconnexion |
| `user` | GET | Utilisateur courant |
| `users` | GET/POST/PUT/DELETE | CRUD utilisateurs |
| `pointage` | GET/POST/DELETE | CRUD pointages |
| `planning` | GET/POST/PUT/DELETE | CRUD plannings |
| `rapport` | GET | Rapport statistique |
| `stats` | GET | KPI par rôle |
| `equipes` | GET/POST/PUT/DELETE | CRUD équipes |
| `conges` | GET/POST/PUT | Congés + validation |
| `lieux` | GET/POST/DELETE | Lieux autorisés |
| `parametres` | GET/PUT | Configuration |
| `logs` | GET | Journaux admin |
| `export` | GET | Export CSV |

## 📋 Rôles et permissions

| Rôle | ID | Permissions |
|------|----|-------------|
| Admin | 1 | Accès complet à tous les modules |
| Responsable | 2 | Vue + validation (employés, congés, pointages) |
| Manager | 3 | Gestion de son équipe (pointages, congés, planning) |
| Employé | 4 | Pointage, consultation de ses propres données |

## 📄 Licence

© 2025 OptiPoint - Tous droits réservés.
