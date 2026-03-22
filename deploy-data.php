<?php
/**
 * LE TERRIER — Déploiement des données
 * =====================================
 * Copie les fichiers JSON de data-deploy/ vers data/
 * Utile après un git pull pour mettre à jour la carte, events, etc.
 *
 * USAGE :
 *   https://www.bar-le-terrier.fr/deploy-data.php?key=VOTRE_CLE
 *
 * La clé est définie ci-dessous (DEPLOY_KEY).
 * Après utilisation, les fichiers dans data-deploy/ restent intacts
 * pour servir de référence.
 */

// --- CONFIGURATION ---
// Changez cette clé par une valeur secrète de votre choix
define('DEPLOY_KEY', 'terrier-deploy-2026');

// --- SÉCURITÉ ---
header('Content-Type: text/html; charset=utf-8');

$key = $_GET['key'] ?? '';
if (!hash_equals(DEPLOY_KEY, $key)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title></head>';
    echo '<body style="font-family:sans-serif;text-align:center;padding:4rem;color:#5C0A1E;">';
    echo '<h1>Accès refusé</h1><p>Clé de déploiement invalide.</p></body></html>';
    exit;
}

// --- DÉPLOIEMENT ---
$deployDir = __DIR__ . '/data-deploy/';
$dataDir   = __DIR__ . '/data/';

if (!is_dir($deployDir)) {
    echo '<p style="color:red;">Erreur : le dossier data-deploy/ n\'existe pas.</p>';
    exit;
}

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0750, true);
}

$files = glob($deployDir . '*.json');
$results = [];

foreach ($files as $srcPath) {
    $filename = basename($srcPath);
    $destPath = $dataDir . $filename;

    // Lire et valider le JSON source
    $content = file_get_contents($srcPath);
    $decoded = json_decode($content, true);

    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        $results[] = ['file' => $filename, 'status' => 'erreur', 'message' => 'JSON invalide : ' . json_last_error_msg()];
        continue;
    }

    // Backup de l'ancien fichier
    if (file_exists($destPath)) {
        $backupDir = $dataDir . 'backups/';
        if (!is_dir($backupDir)) mkdir($backupDir, 0750, true);
        $backupName = pathinfo($filename, PATHINFO_FILENAME) . '_' . date('Y-m-d_His') . '.json';
        copy($destPath, $backupDir . $backupName);
    }

    // Écriture
    $written = file_put_contents($destPath, $content);

    if ($written !== false) {
        $results[] = ['file' => $filename, 'status' => 'ok', 'message' => 'Déployé (' . count($decoded) . ' entrées)'];
    } else {
        $results[] = ['file' => $filename, 'status' => 'erreur', 'message' => 'Impossible d\'écrire dans data/'];
    }
}

// --- RAPPORT ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déploiement — Le Terrier</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a0a10; color: #f5f0e8; padding: 2rem; max-width: 600px; margin: 0 auto; }
        h1 { color: #c8a96e; font-size: 1.5rem; }
        .result { padding: .75rem 1rem; margin: .5rem 0; border-radius: 8px; }
        .ok { background: rgba(76,175,80,.15); border-left: 4px solid #4caf50; }
        .erreur { background: rgba(244,67,54,.15); border-left: 4px solid #f44336; }
        .file { font-weight: bold; color: #c8a96e; }
        .message { opacity: .8; font-size: .9em; }
        .footer { margin-top: 2rem; opacity: .5; font-size: .85em; text-align: center; }
        a { color: #c8a96e; }
    </style>
</head>
<body>
    <h1>Déploiement des données</h1>
    <?php if (empty($results)): ?>
        <p>Aucun fichier JSON trouvé dans <code>data-deploy/</code>.</p>
    <?php else: ?>
        <?php foreach ($results as $r): ?>
            <div class="result <?= $r['status'] ?>">
                <span class="file"><?= htmlspecialchars($r['file']) ?></span><br>
                <span class="message"><?= htmlspecialchars($r['message']) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <p class="footer">
        <?= date('d/m/Y H:i:s') ?> · <a href="/carte">Voir la carte</a> · <a href="/">Dashboard</a>
    </p>
</body>
</html>
