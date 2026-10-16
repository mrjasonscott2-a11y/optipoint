# OptiPoint - Système de Pointage et Gestion du Temps

## 🎯 Présentation

OptiPoint est une application web professionnelle de pointage d'employés avec géolocalisation, conçue pour les entreprises souhaitant automatiser et sécuriser la gestion des présences, des congés et des plannings.

## ✨ Fonctionnalités

- **Pointage intelligent** : Arrivée/Départ avec géolocalisation GPS et adresse réelle (reverse geocoding)
- **Gestion multi-rôles** : Admin, Responsable, Manager, Employé — tous peuvent pointer
- **Gestion des équipes** : Création, affectation de managers et de membres (admin + responsable)
- **Congés & absences** : Demandes, validation hiérarchique, suivi
- **Planning** : Attribution des plannings
- **Rapports** : Statistiques en temps réel, graphiques Chart.js, export CSV
- **Paramètres** : Configuration complète (horaires, seuils, lieux autorisés)
- **Journal d'administration** : Traçabilité des actions

## 🏗️ Architecture

```
optipoint/
├── index.html                    # Redirection vers frontend/login.html
├── README.md
├── frontend/                     # Interface utilisateur
│   ├── login.html                # Page de connexion
│   ├── admin.html                # Dashboard Admin
│   ├── admin-employes.html      # Gestion employés (CRUD)
│   ├── admin-equipes.html       # Gestion des équipes (CRUD + membres)
│   ├── admin-pointages.html     # Historique des pointages
│   ├── admin-planning.html      # Gestion des plannings
│   ├── admin-rapports.html      # Rapports avancés
│   ├── admin-parametres.html    # Paramètres système
│   ├── dashboard-responsable.html + 6 pages responsable
│   ├── dashboard-manager.html + 5 pages manager
│   ├── dashboard.html            # Accueil employé
│   ├── pointage.html             # Pointage géolocalisé (tous rôles)
│   ├── planning.html / rapport.html
│   ├── css/                      # style.css, admin.css, auth.css
│   └── js/                       # app.js, auth.js + scripts par page
└── backend/                      # API + base de données
    ├── api.php                   # ✅ API REST ACTIVE (utilisée par le frontend)
    ├── config.php                # ✅ Connexion PDO + fonctions utilitaires
    ├── db.sql                    # ✅ Schéma de la base active (optipoint_db)
    ├── reset_admin.php           # ✅ Réinitialisation mot de passe admin (optionnel)
    │
    │   # ── Architecture OOP legacy (conservée pour référence) ──
    ├── index.php                 # Ancien point d'entrée API OOP
    ├── autoload.php              # Autoloader des classes
    ├── optipoint_complete.sql    # Ancien schéma complet (tables legacy)
    ├── config/ dto/ mappers/ models/ repository/ services/
    └── README.md                 # Documentation des deux architectures
```

## 🚀 Installation (XAMPP)

### Prérequis
- [XAMPP](https://www.apachefriends.org/) installé (Apache + MySQL + PHP 7.4+)

### Étapes

1. **Copier le projet** dans `C:\xampp\htdocs\`

2. **Démarrer Apache et MySQL** depuis le panneau de contrôle XAMPP

3. **Créer la base de données**
   - Ouvrir http://localhost/phpmyadmin
   - Créer la base `optipoint_db` (interclassement utf8mb4_unicode_ci)
   - Onglet **Importer** → sélectionner `backend/db.sql` → **Exécuter**

4. **Se connecter**
   - Ouvrir : http://localhost/optipoint/frontend/login.html
   - Compte par défaut : **admin / admin123**
   - `backend/reset_admin.php` est optionnel (réinitialisation du mot de passe admin)

## 👥 Comptes

Le système démarre avec un **unique compte administrateur** (`admin` / `admin123`).

**Créez tous les autres comptes** (responsables, managers, employés) depuis l'interface :
**Administration → Employés → Nouvel Employé** — chaque compte est enregistré
automatiquement en base et peut se connecter immédiatement.

Organisation recommandée :
1. L'admin crée les **responsables** et les **managers** (avec rattachement)
2. L'admin ou le responsable crée les **équipes** et y affecte les managers (Équipes → Nouvelle équipe)
3. Les membres sont ajoutés aux équipes via le bouton **Membres**

## 🔧 Technologies

- **Backend** : PHP 7.4+ / PDO / MySQL (MariaDB)
- **Frontend** : HTML5, CSS3, JavaScript Vanilla (Fetch API)
- **Graphiques** : Chart.js — **Icônes** : Font Awesome 6 — **Police** : Poppins

## 📡 API REST

Toutes les requêtes passent par `backend/api.php?action=xxx`

| Action | Méthode | Description |
|--------|---------|-------------|
| `login` / `logout` | POST | Authentification |
| `user` / `users` / `managers` | GET*/CRUD | Utilisateurs |
| `pointage` | GET/POST/DELETE | Pointages géolocalisés |
| `planning` | GET/CRUD | Plannings |
| `rapport` / `stats` | GET | Rapports et KPI par rôle |
| `equipes` / `equipe_membres` | GET/CRUD | Équipes et affectations |
| `conges` | GET/POST/PUT | Congés + validation |
| `lieux` / `parametres` / `logs` / `export` | GET/CRUD | Configuration & divers |

## 📋 Rôles et permissions

| Rôle | ID | Permissions |
|------|----|-------------|
| Admin | 1 | Accès complet à tous les modules |
| Responsable | 2 | Voit tous les employés, gère les équipes, valide les congés |
| Manager | 3 | Gestion de son équipe (pointages, congés, planning) |
| Employé | 4 | Pointage, consultation de ses propres données |

## 📄 Licence

© 2026 OptiPoint — Tous droits réservés.
