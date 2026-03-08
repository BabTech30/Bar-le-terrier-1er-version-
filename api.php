<?php
/**
 * LE TERRIER — API Dashboard
 * ============================================================
 * Endpoints REST pour toutes les opérations du dashboard
 * ============================================================
 */

session_start();
require_once __DIR__ . '/config.php';

// --- CORS & HEADERS ---
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// --- PUBLIC ENDPOINTS (no auth needed) ---
$action = $_GET['action'] ?? '';
if (in_array($action, ['public-gallery', 'public-announcements'])) {
    // Skip auth for public read-only endpoints
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'GET') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }
} else {
    // --- AUTH CHECK ---
    if (!isset($_SESSION['lt_admin_auth']) || $_SESSION['lt_admin_auth'] !== true) {
        jsonResponse(['error' => 'Non autorisé'], 401);
    }

    // Refresh session
    $_SESSION['lt_admin_last'] = time();

    // --- CSRF CHECK for state-changing requests ---
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PATCH', 'DELETE'])) {
        // Upload uses multipart form, CSRF token in POST field
        if ($action === 'upload') {
            $csrfToken = $_POST['csrf_token'] ?? '';
        } else {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(['error' => 'Token CSRF invalide'], 403);
        }
    }
}

// Reset action for routing (already set above)
$method = $_SERVER['REQUEST_METHOD'];

// --- ROUTING ---
$entity = $_GET['entity'] ?? '';

try {
    switch ($action) {

        // ============================
        // MESSAGES (Contact form submissions)
        // ============================
        case 'messages':
            $data = loadData('messages');
            if ($method === 'GET') {
                // Sort by date desc
                usort($data, fn($a, $b) => strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $status = $input['status'] ?? '';
                foreach ($data as &$msg) {
                    if ($msg['id'] === $id) {
                        $msg['status'] = $status;
                        $msg['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('messages', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($m) => $m['id'] !== $id));
                saveData('messages', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // RESERVATIONS
        // ============================
        case 'reservations':
            $data = loadData('reservations');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => strtotime($b['date_resa'] ?? 0) - strtotime($a['date_resa'] ?? 0));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$resa) {
                    if ($resa['id'] === $id) {
                        $resa['status'] = $input['status'] ?? $resa['status'];
                        $resa['notes'] = $input['notes'] ?? $resa['notes'] ?? '';
                        $resa['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('reservations', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($r) => $r['id'] !== $id));
                saveData('reservations', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // EVENTS (Événements)
        // ============================
        case 'events':
            $data = loadData('events');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => strtotime($a['date'] ?? 0) - strtotime($b['date'] ?? 0));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $event = [
                    'id' => generateId(),
                    'title' => sanitize($input['title'] ?? ''),
                    'date' => sanitize($input['date'] ?? ''),
                    'time' => sanitize($input['time'] ?? ''),
                    'type' => sanitize($input['type'] ?? 'special'),
                    'description' => sanitize($input['description'] ?? ''),
                    'status' => 'actif',
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $event;
                saveData('events', $data);
                jsonResponse(['success' => true, 'event' => $event]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$evt) {
                    if ($evt['id'] === $id) {
                        foreach (['title','date','time','type','description','status'] as $field) {
                            if (isset($input[$field])) $evt[$field] = sanitize($input[$field]);
                        }
                        $evt['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('events', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($e) => $e['id'] !== $id));
                saveData('events', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // SOCIAL (Calendrier éditorial)
        // ============================
        case 'social':
            $data = loadData('social');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => strtotime($a['date'] ?? 0) - strtotime($b['date'] ?? 0));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $post = [
                    'id' => generateId(),
                    'date' => sanitize($input['date'] ?? ''),
                    'platform' => sanitize($input['platform'] ?? 'instagram'),
                    'type' => sanitize($input['type'] ?? 'photo'),
                    'caption' => sanitize($input['caption'] ?? ''),
                    'hashtags' => sanitize($input['hashtags'] ?? ''),
                    'status' => sanitize($input['status'] ?? 'brouillon'),
                    'notes' => sanitize($input['notes'] ?? ''),
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $post;
                saveData('social', $data);
                jsonResponse(['success' => true, 'post' => $post]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$post) {
                    if ($post['id'] === $id) {
                        foreach (['date','platform','type','caption','hashtags','status','notes'] as $field) {
                            if (isset($input[$field])) $post[$field] = sanitize($input[$field]);
                        }
                        $post['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('social', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($p) => $p['id'] !== $id));
                saveData('social', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // FINANCES (Devis / Traiteur)
        // ============================
        case 'finances':
            $data = loadData('finances');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0));
                $total = array_sum(array_map(fn($f) => ($f['status'] === 'payé') ? floatval($f['amount'] ?? 0) : 0, $data));
                $pending = array_sum(array_map(fn($f) => ($f['status'] === 'envoyé' || $f['status'] === 'accepté') ? floatval($f['amount'] ?? 0) : 0, $data));
                jsonResponse(['data' => $data, 'count' => count($data), 'total_paid' => $total, 'total_pending' => $pending]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $quote = [
                    'id' => generateId(),
                    'ref' => 'DEV-' . date('Ym') . '-' . str_pad(count($data) + 1, 3, '0', STR_PAD_LEFT),
                    'client' => sanitize($input['client'] ?? ''),
                    'description' => sanitize($input['description'] ?? ''),
                    'date' => sanitize($input['date'] ?? date('Y-m-d')),
                    'date_event' => sanitize($input['date_event'] ?? ''),
                    'amount' => floatval($input['amount'] ?? 0),
                    'tva_rate' => floatval($input['tva_rate'] ?? 10),
                    'guests' => intval($input['guests'] ?? 0),
                    'status' => 'brouillon',
                    'notes' => sanitize($input['notes'] ?? ''),
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $quote;
                saveData('finances', $data);
                jsonResponse(['success' => true, 'quote' => $quote]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$quote) {
                    if ($quote['id'] === $id) {
                        foreach (['client','description','date','date_event','amount','tva_rate','guests','status','notes'] as $field) {
                            if (isset($input[$field])) {
                                $quote[$field] = in_array($field, ['amount','tva_rate']) ? floatval($input[$field]) : 
                                                 ($field === 'guests' ? intval($input[$field]) : sanitize($input[$field]));
                            }
                        }
                        $quote['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('finances', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($f) => $f['id'] !== $id));
                saveData('finances', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // BOUTIQUE (Produits)
        // ============================
        case 'boutique':
            $data = loadData('boutique');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => strtotime($b['created'] ?? 0) - strtotime($a['created'] ?? 0));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $product = [
                    'id' => generateId(),
                    'name' => sanitize($input['name'] ?? ''),
                    'category' => sanitize($input['category'] ?? 'accessoire'),
                    'price' => floatval($input['price'] ?? 0),
                    'stock' => intval($input['stock'] ?? 0),
                    'description' => sanitize($input['description'] ?? ''),
                    'image' => sanitize($input['image'] ?? ''),
                    'status' => sanitize($input['status'] ?? 'actif'),
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $product;
                saveData('boutique', $data);
                jsonResponse(['success' => true, 'product' => $product]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$product) {
                    if ($product['id'] === $id) {
                        foreach (['name','category','description','image','status'] as $field) {
                            if (isset($input[$field])) $product[$field] = sanitize($input[$field]);
                        }
                        if (isset($input['price'])) $product['price'] = floatval($input['price']);
                        if (isset($input['stock'])) $product['stock'] = intval($input['stock']);
                        $product['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('boutique', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($p) => $p['id'] !== $id));
                saveData('boutique', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // REVIEWS (Avis clients)
        // ============================
        case 'reviews':
            $data = loadData('reviews');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $review = [
                    'id' => generateId(),
                    'client' => sanitize($input['client'] ?? ''),
                    'rating' => max(1, min(5, intval($input['rating'] ?? 5))),
                    'comment' => sanitize($input['comment'] ?? ''),
                    'source' => sanitize($input['source'] ?? 'google'),
                    'date' => sanitize($input['date'] ?? date('Y-m-d')),
                    'visible' => (bool)($input['visible'] ?? true),
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $review;
                saveData('reviews', $data);
                jsonResponse(['success' => true, 'review' => $review]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$review) {
                    if ($review['id'] === $id) {
                        foreach (['client','comment','source','date'] as $field) {
                            if (isset($input[$field])) $review[$field] = sanitize($input[$field]);
                        }
                        if (isset($input['rating'])) $review['rating'] = max(1, min(5, intval($input['rating'])));
                        if (isset($input['visible'])) $review['visible'] = (bool)$input['visible'];
                        $review['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('reviews', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($r) => $r['id'] !== $id));
                saveData('reviews', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // OBSERVATIONS (Notes internes)
        // ============================
        case 'observations':
            $data = loadData('observations');
            if ($method === 'GET') {
                $priorityOrder = ['haute' => 0, 'moyenne' => 1, 'basse' => 2];
                usort($data, function($a, $b) use ($priorityOrder) {
                    $pa = $priorityOrder[$a['priority'] ?? 'moyenne'] ?? 1;
                    $pb = $priorityOrder[$b['priority'] ?? 'moyenne'] ?? 1;
                    if ($a['status'] === 'fait' && $b['status'] !== 'fait') return 1;
                    if ($a['status'] !== 'fait' && $b['status'] === 'fait') return -1;
                    return $pa - $pb;
                });
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $obs = [
                    'id' => generateId(),
                    'note' => sanitize($input['note'] ?? ''),
                    'category' => sanitize($input['category'] ?? 'general'),
                    'priority' => sanitize($input['priority'] ?? 'moyenne'),
                    'status' => 'en attente',
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $obs;
                saveData('observations', $data);
                jsonResponse(['success' => true, 'observation' => $obs]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$obs) {
                    if ($obs['id'] === $id) {
                        foreach (['note','category','priority','status'] as $field) {
                            if (isset($input[$field])) $obs[$field] = sanitize($input[$field]);
                        }
                        $obs['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('observations', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($o) => $o['id'] !== $id));
                saveData('observations', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // STATS (Dashboard overview)
        // ============================
        case 'stats':
            $messages = loadData('messages');
            $reservations = loadData('reservations');
            $events = loadData('events');
            $social = loadData('social');
            $finances = loadData('finances');

            $now = date('Y-m-d');
            $weekAgo = date('Y-m-d', strtotime('-7 days'));

            $newMessages = count(array_filter($messages, fn($m) => ($m['status'] ?? '') === 'nouveau'));
            $todayReservations = count(array_filter($reservations, fn($r) => ($r['date_resa'] ?? '') === $now && ($r['status'] ?? '') === 'confirmée'));
            $weekMessages = count(array_filter($messages, fn($m) => ($m['date'] ?? '') >= $weekAgo));
            $upcomingEvents = count(array_filter($events, fn($e) => ($e['date'] ?? '') >= $now && ($e['status'] ?? '') === 'actif'));
            $pendingPosts = count(array_filter($social, fn($s) => ($s['status'] ?? '') === 'planifié'));
            $monthRevenue = array_sum(array_map(
                fn($f) => ($f['status'] === 'payé' && substr($f['date'] ?? '', 0, 7) === date('Y-m')) ? floatval($f['amount'] ?? 0) : 0,
                $finances
            ));

            jsonResponse([
                'new_messages' => $newMessages,
                'today_reservations' => $todayReservations,
                'week_messages' => $weekMessages,
                'upcoming_events' => $upcomingEvents,
                'pending_posts' => $pendingPosts,
                'month_revenue' => $monthRevenue,
                'total_messages' => count($messages),
                'total_reservations' => count($reservations),
                'recent_messages' => array_slice(array_filter($messages, fn($m) => ($m['status'] ?? '') === 'nouveau'), 0, 5),
                'today_resas' => array_values(array_filter($reservations, fn($r) => ($r['date_resa'] ?? '') === $now)),
            ]);
            break;

        // ============================
        // GENERATE (Générateur de posts)
        // ============================
        case 'generate':
            if ($method !== 'POST') jsonResponse(['error' => 'Méthode non autorisée'], 405);
            $input = json_decode(file_get_contents('php://input'), true);
            $type = $input['type'] ?? 'cocktail';

            // Générateur de posts basé sur des templates
            $templates = [
                'cocktail' => [
                    "Ce soir au comptoir, {name} vous attend.\n{description}\n\nMercredi → Dimanche · 17h – 00h\n\n#LeTerrierBar #Cocktails #Nîmes #Gard #Codognan #BarCocktails #Speakeasy",
                    "Vous l'avez goûté, le {name} ?\n{description}\n\nOn vous attend au repaire.\n\n#LeTerrierBar #CocktailSignature #Nîmes #Occitanie #Tapas #ArtDeCocktail",
                    "{name} — {description}\n\nParfait pour commencer la soirée. Ou la finir.\n\n#LeTerrierBar #Mixologie #Nîmes #Gard #SpeakeasyMéditerranéen",
                ],
                'tapas' => [
                    "Du nouveau sur la planche.\n{name} — {description}\n\nÀ partager. Ou pas.\n\n#LeTerrierBar #Tapas #Nîmes #Méditerranée #Gastronomie #Gard",
                    "{name}\n{description}\n\nLe genre de plat qui ne fait pas long feu sur la table.\n\n#LeTerrierBar #TapasMéditerranéennes #Nîmes #Codognan #Foodie",
                ],
                'event' => [
                    "{name}\n{description}\n\n{date} · Dès {time}\nRéservation recommandée : 07 63 51 93 63\n\n#LeTerrierBar #Événement #Nîmes #Gard #SortirNîmes",
                    "Ce {day}, c'est {name} au Terrier.\n{description}\n\nOn vous garde une place ?\n\n#LeTerrierBar #Soirée #Nîmes #Occitanie #NightLife",
                ],
                'ambiance' => [
                    "Lumière tamisée, verre plein, musique douce.\nLe Terrier, mercredi → dimanche, 17h – 00h.\n\nEntrez dans le repaire.\n\n#LeTerrierBar #Ambiance #Speakeasy #Nîmes #Gard #CocktailBar",
                    "Le repaire est ouvert.\n\n26 place de la République, Codognan\nMercredi → Dimanche · 17h – 00h\n\n#LeTerrierBar #Nîmes #Gard #Occitanie #BarAmbiance",
                ],
            ];

            $pool = $templates[$type] ?? $templates['ambiance'];
            $template = $pool[array_rand($pool)];

            // Replace placeholders
            $name = $input['name'] ?? 'Le Terrier';
            $description = $input['description'] ?? '';
            $date = $input['event_date'] ?? '';
            $time = $input['event_time'] ?? '20h';
            $days = ['Monday'=>'lundi','Tuesday'=>'mardi','Wednesday'=>'mercredi','Thursday'=>'jeudi','Friday'=>'vendredi','Saturday'=>'samedi','Sunday'=>'dimanche'];
            $day = $date ? ($days[date('l', strtotime($date))] ?? '') : '';

            $caption = str_replace(
                ['{name}', '{description}', '{date}', '{time}', '{day}'],
                [$name, $description, $date, $time, $day],
                $template
            );

            jsonResponse(['caption' => $caption, 'type' => $type]);
            break;

        // ============================
        // GALLERY (Gestion des photos)
        // ============================
        case 'gallery':
            $data = loadData('gallery');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => ($a['order'] ?? 99) - ($b['order'] ?? 99));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $photo = [
                    'id' => generateId(),
                    'title' => sanitize($input['title'] ?? ''),
                    'caption' => sanitize($input['caption'] ?? ''),
                    'category' => sanitize($input['category'] ?? 'ambiance'),
                    'image' => sanitize($input['image'] ?? ''),
                    'visible' => (bool)($input['visible'] ?? true),
                    'order' => intval($input['order'] ?? count($data)),
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $photo;
                saveData('gallery', $data);
                jsonResponse(['success' => true, 'photo' => $photo]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$photo) {
                    if ($photo['id'] === $id) {
                        foreach (['title','caption','category','image'] as $field) {
                            if (isset($input[$field])) $photo[$field] = sanitize($input[$field]);
                        }
                        if (isset($input['visible'])) $photo['visible'] = (bool)$input['visible'];
                        if (isset($input['order'])) $photo['order'] = intval($input['order']);
                        $photo['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('gallery', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                // Supprimer le fichier image associé
                foreach ($data as $photo) {
                    if ($photo['id'] === $id && !empty($photo['image'])) {
                        $filePath = __DIR__ . '/' . $photo['image'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                $data = array_values(array_filter($data, fn($p) => $p['id'] !== $id));
                saveData('gallery', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // ANNOUNCEMENTS (Annonces)
        // ============================
        case 'announcements':
            $data = loadData('announcements');
            if ($method === 'GET') {
                usort($data, fn($a, $b) => ($a['order'] ?? 99) - ($b['order'] ?? 99));
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $announcement = [
                    'id' => generateId(),
                    'title' => sanitize($input['title'] ?? ''),
                    'content' => sanitize($input['content'] ?? ''),
                    'type' => sanitize($input['type'] ?? 'info'),
                    'link' => sanitize($input['link'] ?? ''),
                    'link_text' => sanitize($input['link_text'] ?? ''),
                    'active' => (bool)($input['active'] ?? true),
                    'expires' => sanitize($input['expires'] ?? ''),
                    'order' => intval($input['order'] ?? count($data)),
                    'created' => date('Y-m-d H:i:s'),
                ];
                $data[] = $announcement;
                saveData('announcements', $data);
                jsonResponse(['success' => true, 'announcement' => $announcement]);
            }
            if ($method === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                foreach ($data as &$ann) {
                    if ($ann['id'] === $id) {
                        foreach (['title','content','type','link','link_text','expires'] as $field) {
                            if (isset($input[$field])) $ann[$field] = sanitize($input[$field]);
                        }
                        if (isset($input['active'])) $ann['active'] = (bool)$input['active'];
                        if (isset($input['order'])) $ann['order'] = intval($input['order']);
                        $ann['updated'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                saveData('announcements', $data);
                jsonResponse(['success' => true]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, fn($a) => $a['id'] !== $id));
                saveData('announcements', $data);
                jsonResponse(['success' => true]);
            }
            break;

        // ============================
        // UPLOAD (Images galerie)
        // ============================
        case 'upload':
            if ($method !== 'POST') jsonResponse(['error' => 'Méthode non autorisée'], 405);

            if (empty($_FILES['image'])) {
                jsonResponse(['error' => 'Aucun fichier envoyé'], 400);
            }

            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['error' => 'Erreur upload: ' . $file['error']], 400);
            }
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                jsonResponse(['error' => 'Fichier trop volumineux (max 5 Mo)'], 400);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
                jsonResponse(['error' => 'Type de fichier non autorisé (JPG, PNG, WebP uniquement)'], 400);
            }

            $ext = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $filename = 'gallery-' . bin2hex(random_bytes(8)) . '.' . $ext;
            $destPath = UPLOADS_DIR . 'gallery/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                jsonResponse(['error' => 'Erreur lors de la sauvegarde'], 500);
            }

            jsonResponse([
                'success' => true,
                'url' => 'uploads/gallery/' . $filename,
                'filename' => $filename,
            ]);
            break;

        // ============================
        // PUBLIC API (pas d'auth requise — géré séparément)
        // ============================
        case 'public-gallery':
            $data = loadData('gallery');
            $visible = array_values(array_filter($data, fn($p) => ($p['visible'] ?? true)));
            usort($visible, fn($a, $b) => ($a['order'] ?? 99) - ($b['order'] ?? 99));
            jsonResponse(['data' => $visible, 'count' => count($visible)]);
            break;

        case 'public-announcements':
            $data = loadData('announcements');
            $now = date('Y-m-d');
            $active = array_values(array_filter($data, function($a) use ($now) {
                if (!($a['active'] ?? false)) return false;
                if (!empty($a['expires']) && $a['expires'] < $now) return false;
                return true;
            }));
            usort($active, fn($a, $b) => ($a['order'] ?? 99) - ($b['order'] ?? 99));
            jsonResponse(['data' => $active, 'count' => count($active)]);
            break;

        default:
            jsonResponse(['error' => 'Action inconnue: ' . $action], 404);
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
}
