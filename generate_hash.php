<?php
/**
 * TEMPORAIRE — Generateur de hash pour config.php
 * SUPPRIMER CE FICHIER APRES UTILISATION
 */
$password = 'LeTerrierAdmin2026!';
$hash = password_hash($password, PASSWORD_DEFAULT);
$currentHash = '$2y$12$p3uaCURigq/9AXYkppw5C.J4M8Xq02MQ1h8Qy7o/Mk8CvIdFngf22';

echo "<h2>Generateur de hash — Le Terrier</h2>";
echo "<p><strong>PHP version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Mot de passe:</strong> $password</p>";
echo "<hr>";
echo "<p><strong>Hash actuel dans config.php:</strong><br><code>$currentHash</code></p>";
echo "<p><strong>Test du hash actuel:</strong> " . (password_verify($password, $currentHash) ? '✅ OK' : '❌ ECHEC') . "</p>";
echo "<hr>";
echo "<p><strong>Nouveau hash genere sur CE serveur:</strong><br><code>$hash</code></p>";
echo "<p><strong>Test du nouveau hash:</strong> " . (password_verify($password, $hash) ? '✅ OK' : '❌ ECHEC') . "</p>";
echo "<hr>";
echo "<p style='color:red'><strong>⚠️ IMPORTANT: Supprimez ce fichier apres utilisation!</strong></p>";
echo "<p>Si le hash actuel affiche ❌ ECHEC, copiez le nouveau hash ci-dessus et remplacez-le dans <code>config.php</code> ligne 13.</p>";
