<?php
/**
 * LE TERRIER — Configuration Dashboard
 * ============================================================
 * IMPORTANT : Modifier les valeurs ci-dessous avant déploiement
 * ============================================================
 */

// --- AUTHENTIFICATION ---
// Mot de passe du dashboard (changer IMMÉDIATEMENT après installation)
// Pour générer un hash : php -r "echo password_hash('votre_mot_de_passe', PASSWORD_DEFAULT);"
define('ADMIN_USER', 'admin');
define('ADMIN_HASH', '$2y$12$p3uaCURigq/9AXYkppw5C.J4M8Xq02MQ1h8Qy7o/Mk8CvIdFngf22');

// --- EMAIL ---
define('CONTACT_EMAIL', 'barleterrier@gmail.com');
define('SITE_NAME', 'Le Terrier');
define('SITE_URL', 'https://barleterrier.fr');

// --- CHEMINS ---
define('DATA_DIR', __DIR__ . '/data/');
define('ADMIN_DIR', __DIR__ . '/');

// --- SÉCURITÉ ---
define('SESSION_LIFETIME', 3600 * 8); // 8 heures
define('CSRF_TOKEN_NAME', 'lt_csrf');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// --- TIMEZONE ---
date_default_timezone_set('Europe/Paris');

// --- INITIALISATION ---
// Créer le dossier data s'il n'existe pas
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Initialiser les fichiers JSON s'ils n'existent pas
$dataFiles = ['messages', 'reservations', 'events', 'social', 'finances', 'stats', 'boutique', 'reviews', 'observations'];
foreach ($dataFiles as $file) {
    $path = DATA_DIR . $file . '.json';
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
    }
}

// --- FONCTIONS UTILITAIRES ---

function loadData(string $file): array {
    $path = DATA_DIR . $file . '.json';
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function saveData(string $file, array $data): bool {
    $path = DATA_DIR . $file . '.json';
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function generateId(): string {
    return bin2hex(random_bytes(8));
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sendEmail(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool {
    $headers = "From: Le Terrier <noreply@barleterrier.fr>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: Le Terrier Dashboard\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: $replyTo\r\n";
    }
    return mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $htmlBody, $headers);
}

function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
