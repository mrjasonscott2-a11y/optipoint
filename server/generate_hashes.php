<?php
/**
 * OptiPoint - Générateur de mots de passe hashés
 * 
 * Ce script génère les mots de passe hashés pour les utilisateurs de démo.
 * Exécutez-le UNE SEULE FOIS après avoir importé la base de données.
 * URL : http://localhost/optipoint/server/generate_hashes.php
 */

require_once 'config.php';

// Supprimer les anciens utilisateurs de démo
$pdo->exec("DELETE FROM utilisateurs WHERE matricule IN ('admin','RESP001','MGR001','MGR002','MGR003','EMP001','EMP002','EMP003','EMP004','EMP005','EMP006')");

// Mot de passe par défaut pour tous les comptes
$defaultPassword = 'admin123';

// Utilisateurs de démonstration
$utilisateurs = [
    ['admin', 'Administrateur Système', 'admin@optipoint.com', 'Administration', '08:00-17:00', 1, 'actif', '2023-01-15', '+241000000001', null],
    ['RESP001', 'Marie Dubois', 'marie.dubois@optipoint.com', 'Direction', '08:00-17:00', 2, 'actif', '2023-02-01', '+241000000002', 1],
    ['MGR001', 'Jean Martin', 'jean.martin@optipoint.com', 'IT', '08:00-17:00', 3, 'actif', '2023-03-10', '+241000000003', 2],
    ['MGR002', 'Sophie Lambert', 'sophie.lambert@optipoint.com', 'Commercial', '09:00-18:00', 3, 'actif', '2023-03-15', '+241000000004', 2],
    ['MGR003', 'Thomas Bernard', 'thomas.bernard@optipoint.com', 'RH', '08:00-17:00', 3, 'actif', '2023-04-01', '+241000000005', 2],
    ['EMP001', 'Pierre Durand', 'pierre.durand@optipoint.com', 'IT', '08:00-17:00', 4, 'actif', '2023-05-10', '+241000000006', 3],
    ['EMP002', 'Julie Moreau', 'julie.moreau@optipoint.com', 'IT', '08:00-17:00', 4, 'actif', '2023-06-01', '+241000000007', 3],
    ['EMP003', 'Marc Petit', 'marc.petit@optipoint.com', 'Commercial', '09:00-18:00', 4, 'actif', '2023-06-15', '+241000000008', 4],
    ['EMP004', 'Claire Rousseau', 'claire.rousseau@optipoint.com', 'Commercial', '09:00-18:00', 4, 'actif', '2023-07-01', '+241000000009', 4],
    ['EMP005', 'Nicolas Garcia', 'nicolas.garcia@optipoint.com', 'RH', '08:00-17:00', 4, 'actif', '2023-07-20', '+241000000010', 5],
    ['EMP006', 'Anne Lefevre', 'anne.lefevre@optipoint.com', 'RH', '08:00-17:00', 4, 'inactif', '2023-08-01', '+241000000011', 5],
];

echo "<!DOCTYPE html><html><head><title>OptiPoint - Génération des mots de passe</title>";
echo "<style>body{font-family:monospace;max-width:800px;margin:50px auto;padding:20px;background:#1e293b;color:#e2e8f0;}h1{color:#3b82f6;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{padding:8px 12px;text-align:left;border-bottom:1px solid #334155;}th{background:#334155;color:#94a3b8;}.ok{color:#22c55e;}.warn{color:#f59e0b;}</style></head><body>";
echo "<h1>🔐 OptiPoint - Générateur de mots de passe</h1>";

try {
    $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (matricule, nom, email, mot_de_passe, service, horaire, role_id, statut, date_embauche, telephone, manager_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    
    echo "<p>Mot de passe par défaut : <strong>admin123</strong></p>";
    echo "<p>Hash bcrypt : <code>" . htmlspecialchars($hash) . "</code></p>";
    echo "<h2>Utilisateurs créés :</h2>";
    echo "<table><tr><th>Matricule</th><th>Nom</th><th>Rôle</th><th>Service</th><th>Statut</th></tr>";
    
    foreach ($utilisateurs as $u) {
        list($matricule, $nom, $email, $service, $horaire, $roleId, $statut, $dateEmbauche, $tel, $managerId) = $u;
        
        // Hash unique pour chaque utilisateur (plus sécurisé)
        $userHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        $stmt->execute([$matricule, $nom, $email, $userHash, $service, $horaire, $roleId, $statut, $dateEmbauche, $tel, $managerId]);
        
        $roles = [1 => 'Admin', 2 => 'Responsable', 3 => 'Manager', 4 => 'Employé'];
        $roleName = $roles[$roleId] ?? 'Inconnu';
        
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($matricule) . "</code></td>";
        echo "<td>" . htmlspecialchars($nom) . "</td>";
        echo "<td>" . $roleName . "</td>";
        echo "<td>" . htmlspecialchars($service) . "</td>";
        echo "<td class='" . ($statut === 'actif' ? 'ok' : 'warn') . "'>" . $statut . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<h2>✅ Terminé avec succès !</h2>";
    echo "<p>Vous pouvez maintenant vous connecter avec n'importe quel compte ci-dessus.</p>";
    echo "<p><a href='../login.html' style='color:#3b82f6;'>→ Aller à la page de connexion</a></p>";
    
} catch (Exception $e) {
    echo "<p class='warn'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
