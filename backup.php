<?php
/**
 * LE TERRIER — Backup & Restore des données
 * ============================================================
 * Accès : https://www.bar-le-terrier.fr/admin/backup.php
 * ============================================================
 */
session_start();
require_once __DIR__ . '/config.php';

// Protection
$isAuth = isset($_SESSION['lt_admin_auth']) && $_SESSION['lt_admin_auth'] === true;
if (!$isAuth) {
    die('<h1>Accès refusé</h1><p><a href="/admin/">Connectez-vous d\'abord</a></p>');
}

$backupDir = DATA_DIR . 'backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$dataFiles = ['messages', 'reservations', 'events', 'social', 'finances', 'boutique', 'reviews', 'observations', 'gallery', 'announcements'];
$message = '';

// --- ACTION : CRÉER UN BACKUP ---
if (isset($_POST['backup']) && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupSubDir = $backupDir . $timestamp . '/';
    mkdir($backupSubDir, 0755, true);

    $count = 0;
    foreach ($dataFiles as $file) {
        $src = DATA_DIR . $file . '.json';
        if (file_exists($src)) {
            copy($src, $backupSubDir . $file . '.json');
            $count++;
        }
    }
    $message = "Backup créé : $count fichiers sauvegardés dans data/backups/$timestamp/";
}

// --- ACTION : RESTAURER UN BACKUP ---
if (isset($_POST['restore']) && verifyCsrfToken($_POST['csrf'] ?? '')) {
    $restoreDir = $backupDir . basename($_POST['restore']) . '/';
    if (is_dir($restoreDir)) {
        $count = 0;
        foreach ($dataFiles as $file) {
            $src = $restoreDir . $file . '.json';
            if (file_exists($src)) {
                copy($src, DATA_DIR . $file . '.json');
                $count++;
            }
        }
        $message = "Restauration effectuée : $count fichiers restaurés depuis " . basename($_POST['restore']);
    } else {
        $message = "Erreur : backup introuvable";
    }
}

// --- LISTER LES BACKUPS ---
$backups = [];
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $dir = $backupDir . $entry;
        if (is_dir($dir)) {
            $files = glob($dir . '/*.json');
            $totalSize = array_sum(array_map('filesize', $files));
            $backups[] = [
                'name' => $entry,
                'files' => count($files),
                'size' => round($totalSize / 1024, 1),
            ];
        }
    }
}
rsort($backups);

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Backup — Le Terrier</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#1a0a10;color:#F5F0E8;font-family:'Courier New',monospace;font-size:14px;padding:2rem;max-width:700px;margin:0 auto}
h1{color:#C8A45C;margin-bottom:1.5rem;font-size:1.3rem}
.msg{padding:.8rem 1rem;border-radius:6px;margin-bottom:1.5rem;font-size:.85rem;background:rgba(76,175,80,.15);border:1px solid rgba(76,175,80,.3);color:#4CAF50}
.section{background:#241218;border:1px solid rgba(200,164,92,.12);border-radius:6px;padding:1.5rem;margin-bottom:1.5rem}
.section h2{color:#C8A45C;font-size:1rem;margin-bottom:1rem}
.btn{padding:.5rem 1.2rem;border:none;border-radius:6px;font-family:inherit;font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:all .2s}
.btn--primary{background:#C8A45C;color:#1a0a10}
.btn--ghost{background:transparent;border:1px solid rgba(200,164,92,.2);color:rgba(245,240,232,.5)}
table{width:100%;border-collapse:collapse}
th{background:#2e1620;padding:.5rem .8rem;text-align:left;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(245,240,232,.5)}
td{padding:.5rem .8rem;border-bottom:1px solid rgba(200,164,92,.1);font-size:.8rem}
.current{margin-top:1rem}
.current p{font-size:.75rem;color:rgba(245,240,232,.5);margin-bottom:.3rem}
a{color:#C8A45C}
</style>
</head>
<body>
<h1>Backup & Restauration</h1>
<p style="margin-bottom:1.5rem"><a href="/admin/">← Retour au dashboard</a></p>

<?php if ($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="section">
    <h2>Créer un backup</h2>
    <p style="font-size:.75rem;color:rgba(245,240,232,.5);margin-bottom:1rem">Sauvegarde tous les fichiers JSON (avis, réservations, messages, etc.)</p>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <button type="submit" name="backup" value="1" class="btn btn--primary">Sauvegarder maintenant</button>
    </form>
    <div class="current">
        <p>État actuel des données :</p>
        <?php foreach ($dataFiles as $file):
            $path = DATA_DIR . $file . '.json';
            $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
            $count = is_array($data) ? count($data) : 0;
        ?>
        <p><?= $file ?>.json : <strong><?= $count ?> éléments</strong></p>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($backups): ?>
<div class="section">
    <h2>Backups disponibles</h2>
    <table>
        <thead><tr><th>Date</th><th>Fichiers</th><th>Taille</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['name']) ?></td>
            <td><?= $b['files'] ?></td>
            <td><?= $b['size'] ?> Ko</td>
            <td>
                <form method="POST" style="display:inline" onsubmit="return confirm('Restaurer ce backup ? Les données actuelles seront écrasées.')">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <button type="submit" name="restore" value="<?= htmlspecialchars($b['name']) ?>" class="btn btn--ghost">Restaurer</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="section">
    <h2>Aucun backup</h2>
    <p style="font-size:.8rem;color:rgba(245,240,232,.5)">Créez votre premier backup ci-dessus.</p>
</div>
<?php endif; ?>

</body>
</html>
