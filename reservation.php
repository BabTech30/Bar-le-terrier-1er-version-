<?php
/**
 * LE TERRIER — Formulaire de Réservation (public)
 * ============================================================
 * → Envoie un email de confirmation à l'équipe
 * → Stocke la réservation dans le dashboard
 * ============================================================
 */

require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

// Anti-spam honeypot
if (!empty($_POST['b_honey'] ?? '')) {
    jsonResponse(['success' => true, 'message' => 'Réservation enregistrée !']);
}

// Rate limiting
$rateLimitFile = DATA_DIR . 'ratelimit_resa.json';
$rateLimits = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$now = time();
$rateLimits = array_filter($rateLimits, fn($t) => $t > ($now - 120));
if (isset($rateLimits[$ip])) {
    jsonResponse(['error' => 'Veuillez patienter 2 minutes avant une nouvelle demande.'], 429);
}
$rateLimits[$ip] = $now;
file_put_contents($rateLimitFile, json_encode($rateLimits));

// Données
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = sanitize($_POST['phone'] ?? '');
$date = sanitize($_POST['date'] ?? '');
$time = sanitize($_POST['time'] ?? '');
$guests = intval($_POST['guests'] ?? 2);
$message = sanitize($_POST['message'] ?? '');

// Validation
if (empty($name) || !$email || empty($date) || empty($time)) {
    jsonResponse(['error' => 'Nom, email, date et heure sont requis.'], 400);
}

if ($guests < 1 || $guests > 60) {
    jsonResponse(['error' => 'Nombre de personnes invalide.'], 400);
}

// Email à l'équipe
$dateFormatted = date('d/m/Y', strtotime($date));
$htmlBody = "
<div style='font-family:Georgia,serif;max-width:600px;margin:0 auto;padding:20px;background:#FFF8F0;'>
  <div style='background:#5C0A1E;color:#F5F0E8;padding:20px;text-align:center;'>
    <h2 style='margin:0;font-size:20px;color:#C8A45C;'>Nouvelle réservation — Le Terrier</h2>
  </div>
  <div style='padding:20px;'>
    <table style='width:100%;border-collapse:collapse;'>
      <tr><td style='padding:8px;color:#666;'>Nom</td><td style='padding:8px;font-weight:bold;'>{$name}</td></tr>
      <tr><td style='padding:8px;color:#666;'>Date</td><td style='padding:8px;font-weight:bold;'>{$dateFormatted}</td></tr>
      <tr><td style='padding:8px;color:#666;'>Heure</td><td style='padding:8px;font-weight:bold;'>{$time}</td></tr>
      <tr><td style='padding:8px;color:#666;'>Personnes</td><td style='padding:8px;font-weight:bold;'>{$guests}</td></tr>
      <tr><td style='padding:8px;color:#666;'>Téléphone</td><td style='padding:8px;'>{$phone}</td></tr>
      <tr><td style='padding:8px;color:#666;'>Email</td><td style='padding:8px;'><a href='mailto:{$email}'>{$email}</a></td></tr>" .
    ($message ? "<tr><td style='padding:8px;color:#666;'>Notes</td><td style='padding:8px;'>{$message}</td></tr>" : "") .
    "</table>
  </div>
  <div style='background:#3A0612;color:#C8A45C;padding:12px;text-align:center;font-size:12px;'>
    barleterrier.fr · " . date('d/m/Y à H:i') . "
  </div>
</div>";

$emailSent = sendEmail(CONTACT_EMAIL, "Réservation {$dateFormatted} — {$name} ({$guests} pers.)", $htmlBody, $email);

// Stocker
$reservation = [
    'id' => generateId(),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'date_resa' => $date,
    'time' => $time,
    'guests' => $guests,
    'message' => $message,
    'status' => 'en attente',
    'notes' => '',
    'date' => date('Y-m-d H:i:s'),
    'ip' => $ip,
];

$reservations = loadData('reservations');
$reservations[] = $reservation;
saveData('reservations', $reservations);

jsonResponse(['success' => true, 'message' => "Demande de réservation enregistrée pour le {$dateFormatted} à {$time}. Nous confirmerons rapidement !"]);
