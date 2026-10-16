<?php
/**
 * OptiPoint - Réinitialisation du mot de passe admin
 *
 * Le fichier db.sql crée déjà le compte admin avec le mot de passe « admin123 ».
 * Ce script est OPTIONNEL : il ne sert qu'à réinitialiser le mot de passe
 * de l'administrateur si vous l'avez perdu ou modifié.
 * URL : http://localhost/optipoint/backend/reset_admin.php
 */

require_once __DIR__ . '/config.php';

$adminPassword = 'admin123';

echo "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'><title>OptiPoint - Réinitialisation admin</title>";
echo "<style>body{font-family:monospace;max-width:700px;margin:50px auto;padding:20px;background:#1e293b;color:#e2e8f0;}h1{color:#3b82f6;}.ok{color:#22c55e;}.err{color:#ef4444;}a{color:#3b82f6;}</style></head><body>";
echo "<h1>🔑 OptiPoint - Réinitialisation du mot de passe admin</h1>";

try {
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE matricule = 'admin'");
    $stmt->execute([password_hash($adminPassword, PASSWORD_DEFAULT)]);

    if ($stmt->rowCount() >= 0) {
        echo "<p class='ok'>✅ Mot de passe de l'administrateur réinitialisé avec succès.</p>";
        echo "<p>Identifiants : <code>admin</code> / <code>admin123</code></p>";
        echo "<p>⚠️ Pensez à changer ce mot de passe depuis l'interface une fois connecté.</p>";
        echo "<p><a href='../frontend/login.html'>→ Aller à la page de connexion</a></p>";
    } else {
        echo "<p class='err'>❌ Compte admin introuvable. Importez d'abord backend/db.sql.</p>";
    }
} catch (Exception $e) {
    echo "<p class='err'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>💡 Importez d'abord <code>backend/db.sql</code> dans la base <code>optipoint_db</code> via phpMyAdmin.</p>";
}

echo "</body></html>";
