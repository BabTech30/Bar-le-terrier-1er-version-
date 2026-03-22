<?php
/**
 * LE TERRIER — Webhook de déploiement automatique
 * =================================================
 * GitHub appelle ce script à chaque push/merge sur main.
 * Le script fait un "git pull" pour mettre à jour le site,
 * puis copie les données de data-deploy/ vers data/.
 *
 * INSTALLATION (une seule fois) :
 * 1. Cloner le repo sur O2switch via SSH
 * 2. Ajouter ce webhook dans GitHub > Settings > Webhooks
 *    URL : https://www.bar-le-terrier.fr/webhook-deploy.php
 *    Secret : (la valeur de WEBHOOK_SECRET ci-dessous)
 */

// ============================================================
// CONFIGURATION — Changez le secret ci-dessous !
// ============================================================
define('WEBHOOK_SECRET', 'terrier-webhook-secret-2026');

// ============================================================
// SÉCURITÉ : Vérification de la signature GitHub
// ============================================================
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (empty($signature)) {
    http_response_code(403);
    die('Signature manquante.');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Signature invalide.');
}

// ============================================================
// VÉRIFICATION : Seulement les push sur main
// ============================================================
$data = json_decode($payload, true);
$ref = $data['ref'] ?? '';

if ($ref !== 'refs/heads/main') {
    echo 'Branche ignorée : ' . htmlspecialchars($ref);
    exit;
}

// ============================================================
// DÉPLOIEMENT
// ============================================================
$logFile = __DIR__ . '/deploy.log';
$output = [];
$timestamp = date('Y-m-d H:i:s');

// 1. Git pull
$repoDir = __DIR__;
$commands = [
    "cd " . escapeshellarg($repoDir) . " && git fetch origin main 2>&1",
    "cd " . escapeshellarg($repoDir) . " && git reset --hard origin/main 2>&1",
];

$log = "[$timestamp] Déploiement déclenché par GitHub\n";

foreach ($commands as $cmd) {
    $result = shell_exec($cmd);
    $log .= "  > $cmd\n  $result\n";
    $output[] = $result;
}

// 2. Copier data-deploy/ vers data/ (si des fichiers existent)
$deployDir = __DIR__ . '/data-deploy/';
$dataDir = __DIR__ . '/data/';

if (is_dir($deployDir)) {
    $files = glob($deployDir . '*.json');
    foreach ($files as $srcPath) {
        $filename = basename($srcPath);
        $destPath = $dataDir . $filename;

        $content = file_get_contents($srcPath);
        $decoded = json_decode($content, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            $log .= "  ⚠ $filename : JSON invalide, ignoré\n";
            continue;
        }

        // Backup
        if (file_exists($destPath)) {
            $backupDir = $dataDir . 'backups/';
            if (!is_dir($backupDir)) mkdir($backupDir, 0750, true);
            copy($destPath, $backupDir . pathinfo($filename, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '.json');
        }

        file_put_contents($destPath, $content);
        $log .= "  ✓ $filename déployé\n";
    }
}

$log .= "  ✓ Déploiement terminé\n\n";

// 3. Écrire le log
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);

// Réponse à GitHub
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'timestamp' => $timestamp]);
