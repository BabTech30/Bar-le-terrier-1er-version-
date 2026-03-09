<?php
/**
 * LE TERRIER — Formulaire de Contact (public)
 * ============================================================
 * Reçoit les soumissions du formulaire contact.html
 * → Envoie un email à barleterrier@gmail.com
 * → Stocke le message dans le dashboard
 * ============================================================
 */

require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

// Anti-spam : honeypot check
if (!empty($_POST['b_honey'] ?? '') || !empty($_POST['website'] ?? '')) {
    // Bot détecté, répondre success silencieusement
    jsonResponse(['success' => true, 'message' => 'Message envoyé !']);
}

// Rate limiting simple (1 soumission par IP toutes les 60 secondes)
$rateLimitFile = DATA_DIR . 'ratelimit.json';
$rateLimits = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$now = time();

// Nettoyer les entrées expirées
$rateLimits = array_filter($rateLimits, fn($t) => $t > ($now - 60));

if (isset($rateLimits[$ip])) {
    jsonResponse(['error' => 'Veuillez patienter avant de renvoyer un message.'], 429);
}
$rateLimits[$ip] = $now;
file_put_contents($rateLimitFile, json_encode($rateLimits));

// Récupérer et valider les données
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? 'Message depuis le site');
$message = sanitize($_POST['message'] ?? '');
$guests = sanitize($_POST['guests'] ?? '');
$date = sanitize($_POST['date'] ?? '');

// Validation
if (empty($name) || !$email || empty($message)) {
    jsonResponse(['error' => 'Nom, email et message sont requis.'], 400);
}

if (strlen($message) > 5000 || strlen($name) > 200) {
    jsonResponse(['error' => 'Message trop long.'], 400);
}

// Construire l'email HTML
$htmlBody = "
<div style='font-family:Georgia,serif;max-width:600px;margin:0 auto;padding:20px;background:#FFF8F0;'>
  <div style='background:#5C0A1E;color:#F5F0E8;padding:20px;text-align:center;'>
    <h2 style='margin:0;font-size:20px;color:#C8A45C;'>Nouveau message — Le Terrier</h2>
  </div>
  <div style='padding:20px;'>
    <p><strong>De :</strong> {$name}</p>
    <p><strong>Email :</strong> <a href='mailto:{$email}'>{$email}</a></p>" .
    ($phone ? "<p><strong>Téléphone :</strong> <a href='tel:{$phone}'>{$phone}</a></p>" : "") .
    ($date ? "<p><strong>Date souhaitée :</strong> {$date}</p>" : "") .
    ($guests ? "<p><strong>Nombre de personnes :</strong> {$guests}</p>" : "") .
    "<p><strong>Sujet :</strong> {$subject}</p>
    <hr style='border:1px solid #C8A45C;margin:15px 0;'>
    <p style='white-space:pre-wrap;'>{$message}</p>
  </div>
  <div style='background:#3A0612;color:#C8A45C;padding:12px;text-align:center;font-size:12px;'>
    Envoyé depuis barleterrier.fr · " . date('d/m/Y à H:i') . "
  </div>
</div>";

// Envoyer l'email
$emailSent = sendEmail(CONTACT_EMAIL, "Contact : {$subject} — {$name}", $htmlBody, $email);

// Stocker dans le dashboard
$messageData = [
    'id' => generateId(),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'subject' => $subject,
    'message' => $message,
    'guests' => $guests,
    'date_resa' => $date,
    'date' => date('Y-m-d H:i:s'),
    'status' => 'nouveau',
    'ip' => $ip,
    'email_sent' => $emailSent,
];

$messages = loadData('messages');
$messages[] = $messageData;
saveData('messages', $messages);

// Réponse
if ($emailSent) {
    jsonResponse(['success' => true, 'message' => 'Message envoyé ! Nous vous répondrons rapidement.']);
} else {
    // Email échoué mais message sauvegardé
    jsonResponse(['success' => true, 'message' => 'Message reçu ! Nous vous contacterons rapidement.']);
}
