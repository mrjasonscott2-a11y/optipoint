# Backend OptiPoint

> ## ⚠️ Important : deux systèmes coexistent dans ce dossier
>
> | Fichier | Rôle | État |
> |---------|------|------|
> | **`api.php`** + **`config.php`** + **`db.sql`** | ✅ **API ACTIVE** utilisée par l'application `frontend/` | Fonctionnelle — base `optipoint_db` |
> | **`index.php`** + `config/`, `dto/`, `mappers/`, `models/`, `repository/`, `services/`, `autoload.php` | 📦 Architecture OOP originale (legacy) | Conservée intacte — nécessite l'ancien schéma `optipoint_complete.sql` |
>
> - L'application appelle **`backend/api.php`** (procédural, session PHP, 4 rôles).
> - L'architecture OOP ci-dessous est l'ancienne version, conservée pour référence et évolution future. Elle cible les tables de `optipoint_complete.sql` (profils, habilitations, sites, jours, tâches...) qui n'existent pas dans la base actuelle.

---

# Architecture OOP - OptiPoint Backend (legacy)

## Structure

```
backend/
├── api.php                   # ✅ API ACTIVE (utilisée par le frontend)
├── config.php                # ✅ Connexion PDO de l'API active
├── db.sql                    # ✅ Schéma de la base active (optipoint_db)
├── reset_admin.php           # ✅ Réinitialisation mot de passe admin
│
│   # ── Architecture OOP legacy (conservée) ──
├── index.php                 # Ancien point d'entrée API OOP
├── autoload.php              # Autoloader des classes
├── optipoint_complete.sql    # Ancien schéma complet (tables legacy)
├── config/
│   └── Database.php          # Classe Singleton pour la connexion PDO
├── dto/                      # Data Transfer Objects
├── mappers/                  # Mappers DTO ↔ Modèles
├── models/                   # Modèles (entités)
├── repository/               # Accès aux données
└── services/                 # Logique métier
```

## Architecture en 3 couches

### 1. Controller (index.php)
- Point d'entrée de l'API
- Reçoit les requêtes HTTP
- Appelle les Services
- Retourne les réponses JSON

### 2. Services (services/)
- Contiennent la **logique métier**
- Valident les données
- Gèrent les règles de l'application
- Appellent les Repository pour l'accès aux données

### 3. Repository (repository/)
- Gèrent uniquement l'**accès aux données**
- Opérations CRUD sur la base de données
- Pas de logique métier

### 4. Models (models/)
- Représentent les entités de la base de données
- Propriétés privées avec getters/setters
- Méthodes utilitaires (toArray(), hydrate())

### 5. Database (config/Database.php)
- Pattern Singleton pour la connexion PDO
- Une seule instance partagée

## Flux de données

```
Requête HTTP → Controller (index.php) 
              → Service (logique métier + validation)
              → Repository (accès données)
              → Database (PDO)
              → Retour inverse avec les données
```

## Utilisation

### Exemple : Créer un utilisateur via le Service

```php
require_once 'autoload.php';

$userService = new UserService();

try {
    $user = $userService->createUser([
        'matricule' => 'EMP001',
        'nom' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'password' => 'password123',
        'service' => 'IT',
        'role' => 'employe'
    ]);
    
    echo "Utilisateur créé : " . $user->getNom();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
```

### Exemple : Authentification via le Service

```php
$userService = new UserService();

try {
    $user = $userService->authenticate('EMP001', 'password123');
    echo "Connecté : " . $user->getNom();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
```

### Exemple : Créer un pointage via le Service

```php
$pointageService = new PointageService();

try {
    $pointage = $pointageService->createPointage([
        'user_id' => 1,
        'type' => 'arrivee',
        'date_pointage' => '2024-01-15',
        'heure' => '08:00',
        'localisation' => 'Bureau principal',
        'mode_travail' => 'bureau'
    ]);
    
    echo "Pointage créé avec l'ID : " . $pointage->getId();
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
```

## Avantages de cette architecture

1. **Séparation des responsabilités** : 
   - Controller : Gestion des requêtes HTTP
   - Service : Logique métier et validation
   - Repository : Accès aux données uniquement
   - Model : Représentation des entités

2. **Maintenabilité** : 
   - Code organisé en couches claires
   - Facile à comprendre et modifier
   - Tests unitaires simplifiés

3. **Réutilisabilité** : 
   - Les services peuvent être utilisés par différents contrôleurs
   - Les Repository sont indépendants de la logique métier

4. **Sécurité** : 
   - Validation centralisée dans les Services
   - Requêtes préparées via PDO
   - Gestion d'erreurs cohérente

5. **Évolutivité** : 
   - Facile d'ajouter de nouvelles fonctionnalités
   - Modification d'une couche n'affecte pas les autres
   - Architecture scalable

## Validation et gestion d'erreurs

Tous les Services valident les données et lancent des exceptions en cas d'erreur :
- Validation des champs requis
- Validation des formats (dates, heures, emails)
- Vérification de l'existence des entités liées
- Messages d'erreur clairs et explicites

Le Controller capture ces exceptions et les retourne dans la réponse JSON.

## Configuration

Modifiez les constantes dans `config/Database.php` pour adapter la configuration :

```php
private const HOST = "localhost";
private const DBNAME = "optipoint_db";
private const USERNAME = "root";
private const PASSWORD = "";
```

