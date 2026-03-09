<?php
/**
 * PATCH — Ajoute l'endpoint public-events à api.php
 * Usage : uploadez ce fichier sur le serveur, puis ouvrez-le dans le navigateur.
 * https://bar-le-terrier.fr/patch-events.php
 * SUPPRIMEZ CE FICHIER APRÈS UTILISATION.
 */

$file = __DIR__ . '/api.php';
$code = file_get_contents($file);

if ($code === false) {
    die('ERREUR : impossible de lire api.php');
}

// Vérifier si déjà patché
if (strpos($code, "'public-events'") !== false) {
    die('OK — public-events est déjà présent dans api.php. Rien à faire. Supprimez ce fichier.');
}

$errors = [];

// 1. Ajouter 'public-events' dans la liste des endpoints publics
$old1 = "'public-carte', 'submit-review'";
$new1 = "'public-carte', 'public-events', 'submit-review'";
if (strpos($code, $old1) !== false) {
    $code = str_replace($old1, $new1, $code);
} else {
    $errors[] = "Liste des endpoints publics non trouvée";
}

// 2. Ajouter le case 'public-events' avant 'public-gallery'
$anchor = "case 'public-gallery':";
$patch = "case 'public-events':
            \$data = loadData('events');
            \$now = date('Y-m-d');
            \$active = array_values(array_filter(\$data, function(\$e) use (\$now) {
                if ((\$e['status'] ?? '') !== 'actif') return false;
                if (!empty(\$e['date']) && \$e['date'] < \$now) return false;
                return true;
            }));
            usort(\$active, function(\$a, \$b) { return strtotime(\$a['date'] ?? 0) - strtotime(\$b['date'] ?? 0); });
            jsonResponse(['data' => \$active, 'count' => count(\$active)]);
            break;

        ";

if (strpos($code, $anchor) !== false) {
    $code = str_replace($anchor, $patch . $anchor, $code);
} else {
    $errors[] = "case 'public-gallery' non trouvé";
}

if (!empty($errors)) {
    die('ERREUR : ' . implode(', ', $errors));
}

// Sauvegarder
$backup = $file . '.bak.' . date('YmdHis');
copy($file, $backup);

if (file_put_contents($file, $code) !== false) {
    echo "SUCCÈS ! api.php a été mis à jour.<br>";
    echo "Backup créé : " . basename($backup) . "<br><br>";
    echo "<strong>IMPORTANT : supprimez patch-events.php du serveur maintenant.</strong><br><br>";
    echo '<a href="/api.php?action=public-events">Tester le endpoint</a>';
} else {
    die('ERREUR : impossible d\'écrire api.php');
}
