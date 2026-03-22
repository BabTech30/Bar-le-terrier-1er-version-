<?php
/**
 * LE TERRIER — Diagnostic complet
 * ============================================================
 * Accéder via : https://www.bar-le-terrier.fr/diagnostic.php
 * SÉCURITÉ : Accessible uniquement si connecté au dashboard.
 * Pour désactiver : créer un fichier data/.disable_diagnostic
 * ============================================================
 */
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Protection : désactivation par fichier flag
if (file_exists(DATA_DIR . '.disable_diagnostic')) {
    die('<h1>Diagnostic désactivé</h1><p>Supprimez le fichier <code>data/.disable_diagnostic</code> pour réactiver.</p>');
}

// Protection : accessible uniquement si connecté au dashboard
$isAuth = isset($_SESSION['lt_admin_auth']) && $_SESSION['lt_admin_auth'] === true;
if (!$isAuth) {
    die('<h1>Accès refusé</h1><p>Connectez-vous d\'abord au <a href="/admin/">dashboard</a>, puis revenez ici.</p>');
}

$checks = [];

// --- 1. PHP Version ---
$checks[] = [
    'name' => 'Version PHP',
    'value' => PHP_VERSION,
    'ok' => version_compare(PHP_VERSION, '7.4', '>='),
    'detail' => 'PHP 7.4+ requis (pour les arrow functions)'
];

// --- 2. Session ---
$checks[] = [
    'name' => 'Session active',
    'value' => session_id() ?: 'AUCUNE',
    'ok' => !empty(session_id()),
    'detail' => 'La session doit être active pour que l\'API fonctionne'
];

$checks[] = [
    'name' => 'Session auth',
    'value' => $_SESSION['lt_admin_auth'] ? 'OUI' : 'NON',
    'ok' => $_SESSION['lt_admin_auth'] === true,
    'detail' => 'Doit être TRUE pour que les appels API fonctionnent'
];

$checks[] = [
    'name' => 'Token CSRF en session',
    'value' => !empty($_SESSION[CSRF_TOKEN_NAME]) ? substr($_SESSION[CSRF_TOKEN_NAME], 0, 16) . '...' : 'ABSENT',
    'ok' => !empty($_SESSION[CSRF_TOKEN_NAME]),
    'detail' => 'Le token CSRF doit exister pour que les POST/PATCH/DELETE fonctionnent'
];

$checks[] = [
    'name' => 'Session cookie path',
    'value' => ini_get('session.cookie_path') ?: '/',
    'ok' => ini_get('session.cookie_path') === '/' || ini_get('session.cookie_path') === '',
    'detail' => 'Doit être "/" pour que /admin/ et /api.php partagent la même session'
];

$checks[] = [
    'name' => 'Session save path',
    'value' => session_save_path() ?: ini_get('session.save_path') ?: 'défaut système',
    'ok' => true,
    'detail' => 'Info: où PHP stocke les sessions'
];

// --- 3. Dossier data/ ---
$checks[] = [
    'name' => 'DATA_DIR existe',
    'value' => DATA_DIR,
    'ok' => is_dir(DATA_DIR),
    'detail' => 'Le dossier data/ doit exister'
];

$checks[] = [
    'name' => 'DATA_DIR accessible en écriture',
    'value' => is_writable(DATA_DIR) ? 'OUI' : 'NON (chmod 755 ou 777 sur data/)',
    'ok' => is_writable(DATA_DIR),
    'detail' => 'PHP doit pouvoir écrire dans data/'
];

// --- 4. Fichiers JSON ---
$dataFiles = ['messages', 'reservations', 'events', 'social', 'finances', 'stats', 'boutique', 'reviews', 'observations', 'gallery', 'announcements', 'newsletter', 'carte'];
foreach ($dataFiles as $file) {
    $path = DATA_DIR . $file . '.json';
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $writable = $exists && is_writable($path);
    $content = $readable ? file_get_contents($path) : '';
    $decoded = $readable ? json_decode($content, true) : null;
    $validJson = $decoded !== null || $content === '[]';
    $count = is_array($decoded) ? count($decoded) : 0;
    $size = $exists ? filesize($path) : 0;

    $status = 'OK';
    $isOk = true;
    if (!$exists) { $status = 'FICHIER MANQUANT'; $isOk = false; }
    elseif (!$writable) { $status = 'NON ACCESSIBLE EN ÉCRITURE'; $isOk = false; }
    elseif (!$validJson) { $status = 'JSON INVALIDE — erreur: ' . json_last_error_msg(); $isOk = false; }
    else { $status = "$count éléments ($size octets)"; }

    $checks[] = [
        'name' => "data/$file.json",
        'value' => $status,
        'ok' => $isOk,
        'detail' => $writable ? 'Permissions OK' : 'Faire: chmod 666 data/' . $file . '.json'
    ];
}

// --- 5. Test d'écriture réel ---
$testFile = DATA_DIR . '_diagnostic_test.json';
$writeOk = @file_put_contents($testFile, json_encode(['test' => true, 'time' => date('c')]));
$readBack = $writeOk ? json_decode(file_get_contents($testFile), true) : null;
@unlink($testFile);

$checks[] = [
    'name' => 'Test écriture/lecture JSON',
    'value' => ($writeOk && $readBack && $readBack['test'] === true) ? 'OK' : 'ÉCHEC',
    'ok' => $writeOk && $readBack && $readBack['test'] === true,
    'detail' => 'Simule la sauvegarde d\'un avis : écriture + relecture du fichier'
];

// --- 6. Test API simulé ---
// Simuler un appel POST reviews comme le ferait le JS
$testReview = [
    'id' => 'diag_test_' . bin2hex(random_bytes(4)),
    'client' => 'Test Diagnostic',
    'rating' => 5,
    'comment' => 'Ceci est un test automatique du diagnostic',
    'source' => 'diagnostic',
    'date' => date('Y-m-d'),
    'visible' => false,
    'created' => date('Y-m-d H:i:s'),
];

$reviewsData = loadData('reviews');
$countBefore = count($reviewsData);
$reviewsData[] = $testReview;
$saved = saveData('reviews', $reviewsData);

// Re-lire pour vérifier
$reviewsReload = loadData('reviews');
$countAfter = count($reviewsReload);
$found = false;
foreach ($reviewsReload as $r) {
    if ($r['id'] === $testReview['id']) { $found = true; break; }
}

// Nettoyer : supprimer le test
$reviewsReload = array_values(array_filter($reviewsReload, fn($r) => $r['id'] !== $testReview['id']));
saveData('reviews', $reviewsReload);

$checks[] = [
    'name' => 'Test sauvegarde avis (complet)',
    'value' => $saved && $found ? "OK — Avant: $countBefore → Après ajout: $countAfter → Nettoyé" : 'ÉCHEC',
    'ok' => $saved && $found,
    'detail' => $saved && $found
        ? 'Le cycle complet fonctionne : loadData → ajout → saveData → relecture → trouvé ✓'
        : 'BUG TROUVÉ : ' . (!$saved ? 'saveData() a échoué (permissions ?)' : 'L\'avis sauvé n\'a pas été retrouvé (JSON corrompu ?)')
];

// --- 7. Vérifier que api.php existe et est accessible ---
$apiPath = __DIR__ . '/api.php';
$checks[] = [
    'name' => 'api.php existe',
    'value' => file_exists($apiPath) ? 'OUI (' . $apiPath . ')' : 'NON',
    'ok' => file_exists($apiPath),
    'detail' => 'Le fichier API doit exister à la racine du site'
];

// --- 8. Vérifier config.php DATA_DIR vs realpath ---
$checks[] = [
    'name' => 'DATA_DIR (realpath)',
    'value' => realpath(DATA_DIR) ?: 'INTROUVABLE',
    'ok' => realpath(DATA_DIR) !== false,
    'detail' => 'Chemin physique réel vers data/'
];

$checks[] = [
    'name' => '__DIR__ (ce script)',
    'value' => __DIR__,
    'ok' => true,
    'detail' => 'Répertoire physique du script actuel'
];

// --- 9. Contenu actuel de reviews.json ---
$reviewsNow = loadData('reviews');
$checks[] = [
    'name' => 'Avis actuels dans reviews.json',
    'value' => count($reviewsNow) . ' avis',
    'ok' => count($reviewsNow) > 0,
    'detail' => count($reviewsNow) > 0
        ? 'Dernier avis : ' . ($reviewsNow[count($reviewsNow)-1]['client'] ?? '?') . ' (' . ($reviewsNow[count($reviewsNow)-1]['date'] ?? '?') . ')'
        : 'Le fichier est vide — ajoutez des avis via le dashboard pour qu\'ils apparaissent sur le site'
];

// --- 10. Avis visibles vs masqués ---
$visibleReviews = array_filter($reviewsNow, fn($r) => ($r['visible'] ?? true));
$hiddenReviews = array_filter($reviewsNow, fn($r) => !($r['visible'] ?? true));
$pendingReviews = array_filter($reviewsNow, fn($r) => ($r['submitted_by'] ?? '') === 'visiteur' && !($r['visible'] ?? true));
$checks[] = [
    'name' => 'Avis visibles sur le site',
    'value' => count($visibleReviews) . ' visible(s), ' . count($hiddenReviews) . ' masqué(s)',
    'ok' => count($visibleReviews) > 0,
    'detail' => count($visibleReviews) === 0
        ? 'PROBLÈME : Aucun avis visible ! Le site affichera "Aucun avis". Allez dans le dashboard → Avis → cochez "Visible"'
        : 'OK — ces ' . count($visibleReviews) . ' avis sont affichés sur la page d\'accueil'
];

$checks[] = [
    'name' => 'Avis visiteurs en attente de modération',
    'value' => count($pendingReviews) . ' en attente',
    'ok' => true,
    'detail' => count($pendingReviews) > 0
        ? 'Allez dans le dashboard → Avis → cliquez "Afficher" pour valider les avis soumis par les visiteurs'
        : 'Aucun avis de visiteur en attente'
];

// --- 11. Test de l'endpoint public-reviews ---
$publicData = loadData('reviews');
$publicVisible = array_values(array_filter($publicData, fn($r) => ($r['visible'] ?? true)));
$checks[] = [
    'name' => 'Endpoint public-reviews',
    'value' => count($publicVisible) > 0 ? count($publicVisible) . ' avis retournés' : 'VIDE — rien ne s\'affiche sur le site',
    'ok' => count($publicVisible) > 0,
    'detail' => count($publicVisible) === 0
        ? 'L\'API retourne 0 avis visibles → le site affiche "Aucun avis". Solution : marquer des avis comme visibles dans le dashboard'
        : 'L\'API retournera ces avis au site public'
];

// --- 12. Vérifier shared.js pour le chargement des avis ---
$sharedJs = __DIR__ . '/shared.js';
$checks[] = [
    'name' => 'shared.js (chargement avis)',
    'value' => file_exists($sharedJs) ? 'Présent' : 'MANQUANT',
    'ok' => file_exists($sharedJs),
    'detail' => file_exists($sharedJs)
        ? (strpos(file_get_contents($sharedJs), 'public-reviews') !== false ? 'Contient le code de chargement des avis ✓' : 'ATTENTION : ne contient pas "public-reviews" — les avis ne seront pas chargés')
        : 'Le fichier shared.js est nécessaire pour afficher les avis dynamiquement'
];

// --- 13. Vérifier index.html pour le formulaire public ---
$indexHtml = __DIR__ . '/index.html';
$checks[] = [
    'name' => 'Formulaire avis public (index.html)',
    'value' => file_exists($indexHtml) && strpos(file_get_contents($indexHtml), 'review-modal') !== false ? 'Présent' : 'MANQUANT',
    'ok' => file_exists($indexHtml) && strpos(file_get_contents($indexHtml), 'review-modal') !== false,
    'detail' => 'Le formulaire permet aux visiteurs de soumettre des avis (soumis en modération)'
];

// --- AFFICHAGE ---
$hasErrors = count(array_filter($checks, fn($c) => !$c['ok']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Diagnostic — Le Terrier</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#1a0a10;color:#F5F0E8;font-family:'Courier New',monospace;font-size:14px;padding:2rem;max-width:900px;margin:0 auto}
h1{color:#C8A45C;margin-bottom:.5rem;font-size:1.3rem}
.summary{padding:1rem;border-radius:6px;margin-bottom:2rem;font-size:.9rem}
.summary--ok{background:rgba(76,175,80,.15);border:1px solid rgba(76,175,80,.3);color:#4CAF50}
.summary--err{background:rgba(244,67,54,.15);border:1px solid rgba(244,67,54,.3);color:#f44336}
table{width:100%;border-collapse:collapse;margin-bottom:2rem}
th{background:#2e1620;padding:.5rem .8rem;text-align:left;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(245,240,232,.5);border-bottom:1px solid rgba(200,164,92,.2)}
td{padding:.5rem .8rem;border-bottom:1px solid rgba(200,164,92,.1);font-size:.8rem;vertical-align:top}
.ok{color:#4CAF50}
.err{color:#f44336;font-weight:bold}
.detail{color:rgba(245,240,232,.4);font-size:.7rem;margin-top:.2rem}
.warn{background:rgba(244,67,54,.08)}
.footer{margin-top:2rem;padding-top:1rem;border-top:1px solid rgba(200,164,92,.2);font-size:.7rem;color:rgba(245,240,232,.3)}
</style>
</head>
<body>
<h1>Diagnostic Le Terrier</h1>
<p style="color:rgba(245,240,232,.5);margin-bottom:1.5rem;font-size:.75rem"><?= date('d/m/Y H:i:s') ?> — PHP <?= PHP_VERSION ?></p>

<div class="summary <?= $hasErrors ? 'summary--err' : 'summary--ok' ?>">
    <?php if ($hasErrors): ?>
        <?= $hasErrors ?> problème(s) détecté(s) — voir les lignes en rouge ci-dessous
    <?php else: ?>
        Tous les tests sont OK — le système fonctionne correctement côté serveur
    <?php endif; ?>
</div>

<table>
<thead><tr><th>Test</th><th>Résultat</th></tr></thead>
<tbody>
<?php foreach ($checks as $c): ?>
<tr class="<?= $c['ok'] ? '' : 'warn' ?>">
    <td>
        <?= htmlspecialchars($c['name']) ?>
        <div class="detail"><?= htmlspecialchars($c['detail']) ?></div>
    </td>
    <td class="<?= $c['ok'] ? 'ok' : 'err' ?>">
        <?= htmlspecialchars($c['value']) ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div style="background:#2e1620;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <p style="color:#C8A45C;font-size:.8rem;margin-bottom:.5rem">Test manuel rapide :</p>
    <p style="font-size:.75rem;color:rgba(245,240,232,.6)">
        1. Ouvrez la console du navigateur (F12 → Console) sur le dashboard<br>
        2. Collez ce code et appuyez Entrée :<br>
        <code style="display:block;background:#1a0a10;padding:.5rem;margin:.5rem 0;color:#4CAF50;font-size:.75rem;border-radius:4px">
fetch('/api.php?action=reviews').then(r=>{console.log('Status:',r.status);return r.text()}).then(t=>console.log('Réponse:',t)).catch(e=>console.error('Erreur:',e))
        </code>
        3. Notez le Status et la Réponse — ça montrera exactement ce que l'API retourne<br><br>
        Puis testez un POST :<br>
        <code style="display:block;background:#1a0a10;padding:.5rem;margin:.5rem 0;color:#4CAF50;font-size:.75rem;border-radius:4px">
fetch('/api.php?action=reviews',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({client:'Test Console',rating:5,comment:'Test depuis la console',source:'test',date:'2026-03-08',visible:false})}).then(r=>{console.log('Status:',r.status);return r.text()}).then(t=>console.log('Réponse:',t)).catch(e=>console.error('Erreur:',e))
        </code>
        Si le Status est 200 et la réponse contient "success", le problème est dans le JS du dashboard.<br>
        Si le Status est 401 ou 403, le problème est la session ou le CSRF.
    </p>
</div>

<p class="footer">
    ⚠️ Supprimez ce fichier après diagnostic : <code>rm diagnostic.php</code><br>
    Ce fichier contient des informations sensibles sur votre serveur.
</p>
</body>
</html>
