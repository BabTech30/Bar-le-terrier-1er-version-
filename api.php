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
header('X-Frame-Options: DENY');

// --- PUBLIC ENDPOINTS (no auth needed) ---
$action = $_GET['action'] ?? '';
if (in_array($action, ['public-gallery', 'public-announcements', 'public-reviews', 'public-carte', 'submit-review', 'subscribe-newsletter'])) {
    // Skip auth for public endpoints
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($action, ['submit-review', 'subscribe-newsletter'])) {
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Méthode non autorisée'], 405);
        }
    } elseif ($method !== 'GET') {
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
                usort($data, function($a, $b) { return strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0); });
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
                $data = array_values(array_filter($data, function($m) use ($id) { return $m['id'] !== $id; }));
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
                usort($data, function($a, $b) { return strtotime($b['date_resa'] ?? 0) - strtotime($a['date_resa'] ?? 0); });
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
                $data = array_values(array_filter($data, function($r) use ($id) { return $r['id'] !== $id; }));
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
                usort($data, function($a, $b) { return strtotime($a['date'] ?? 0) - strtotime($b['date'] ?? 0); });
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
                    'display' => sanitize($input['display'] ?? 'both'),
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
                        foreach (['title','date','time','type','display','description','status'] as $field) {
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
                $data = array_values(array_filter($data, function($e) use ($id) { return $e['id'] !== $id; }));
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
                usort($data, function($a, $b) { return strtotime($a['date'] ?? 0) - strtotime($b['date'] ?? 0); });
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
                $data = array_values(array_filter($data, function($p) use ($id) { return $p['id'] !== $id; }));
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
                usort($data, function($a, $b) { return strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0); });
                $total = array_sum(array_map(function($f) { return ($f['status'] === 'payé') ? floatval($f['amount'] ?? 0) : 0; }, $data));
                $pending = array_sum(array_map(function($f) { return ($f['status'] === 'envoyé' || $f['status'] === 'accepté') ? floatval($f['amount'] ?? 0) : 0; }, $data));
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
                $data = array_values(array_filter($data, function($f) use ($id) { return $f['id'] !== $id; }));
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
                usort($data, function($a, $b) { return strtotime($b['created'] ?? 0) - strtotime($a['created'] ?? 0); });
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
                $data = array_values(array_filter($data, function($p) use ($id) { return $p['id'] !== $id; }));
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
                usort($data, function($a, $b) { return strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0); });
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
                $data = array_values(array_filter($data, function($r) use ($id) { return $r['id'] !== $id; }));
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
                $data = array_values(array_filter($data, function($o) use ($id) { return $o['id'] !== $id; }));
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

            $newMessages = count(array_filter($messages, function($m) { return ($m['status'] ?? '') === 'nouveau'; }));
            $todayReservations = count(array_filter($reservations, function($r) use ($now) { return ($r['date_resa'] ?? '') === $now && ($r['status'] ?? '') === 'confirmée'; }));
            $weekMessages = count(array_filter($messages, function($m) use ($weekAgo) { return ($m['date'] ?? '') >= $weekAgo; }));
            $upcomingEvents = count(array_filter($events, function($e) use ($now) { return ($e['date'] ?? '') >= $now && ($e['status'] ?? '') === 'actif'; }));
            $pendingPosts = count(array_filter($social, function($s) { return ($s['status'] ?? '') === 'planifié'; }));
            $monthRevenue = array_sum(array_map(
                function($f) { return ($f['status'] === 'payé' && substr($f['date'] ?? '', 0, 7) === date('Y-m')) ? floatval($f['amount'] ?? 0) : 0; },
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
                'recent_messages' => array_slice(array_filter($messages, function($m) { return ($m['status'] ?? '') === 'nouveau'; }), 0, 5),
                'today_resas' => array_values(array_filter($reservations, function($r) use ($now) { return ($r['date_resa'] ?? '') === $now; })),
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
                usort($data, function($a, $b) { return ($a['order'] ?? 99) - ($b['order'] ?? 99); });
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
                // Validate image path is within uploads/gallery/
                if (!empty($photo['image']) && strpos($photo['image'], 'uploads/gallery/') !== 0) {
                    $photo['image'] = '';
                }
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
                // Supprimer le fichier image associé (avec validation du chemin)
                foreach ($data as $photo) {
                    if ($photo['id'] === $id && !empty($photo['image'])) {
                        $filePath = realpath(__DIR__ . '/' . $photo['image']);
                        $uploadsDir = realpath(UPLOADS_DIR . 'gallery/');
                        // Ne supprimer que si le fichier est dans uploads/gallery/
                        if ($filePath && $uploadsDir && strpos($filePath, $uploadsDir) === 0 && file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                $data = array_values(array_filter($data, function($p) use ($id) { return $p['id'] !== $id; }));
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
                usort($data, function($a, $b) { return ($a['order'] ?? 99) - ($b['order'] ?? 99); });
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $announcement = [
                    'id' => generateId(),
                    'title' => sanitize($input['title'] ?? ''),
                    'content' => sanitize($input['content'] ?? ''),
                    'type' => sanitize($input['type'] ?? 'info'),
                    'link' => preg_match('/^javascript:/i', $input['link'] ?? '') ? '' : sanitize($input['link'] ?? ''),
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
                            if (isset($input[$field])) {
                                $val = sanitize($input[$field]);
                                if ($field === 'link' && preg_match('/^javascript:/i', $input[$field])) $val = '';
                                $ann[$field] = $val;
                            }
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
                $data = array_values(array_filter($data, function($a) use ($id) { return $a['id'] !== $id; }));
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
        // CARTE / MENU (Admin — gestion de la carte)
        // ============================
        case 'carte':
            $data = loadData('carte');
            if ($method === 'GET') {
                usort($data, function($a, $b) { return ($a['order'] ?? 99) - ($b['order'] ?? 99); });
                jsonResponse(['data' => $data]);
            }
            if ($method === 'PUT') {
                // Sauvegarde complète de la carte
                $input = json_decode(file_get_contents('php://input'), true);
                if (!isset($input['data']) || !is_array($input['data'])) {
                    jsonResponse(['error' => 'Données invalides'], 400);
                }
                saveData('carte', $input['data']);
                jsonResponse(['success' => true]);
            }
            if ($method === 'PATCH') {
                // Modifier un item dans une catégorie
                $input = json_decode(file_get_contents('php://input'), true);
                $catId = $input['category_id'] ?? '';
                $item = $input['item'] ?? null;
                $action_type = $input['type'] ?? 'update'; // update, add, delete

                foreach ($data as &$cat) {
                    if (($cat['id'] ?? '') === $catId) {
                        if ($action_type === 'add' && $item) {
                            $item['id'] = generateId();
                            $cat['items'][] = $item;
                        } elseif ($action_type === 'delete' && isset($input['item_id'])) {
                            $cat['items'] = array_values(array_filter($cat['items'] ?? [], function($i) use ($input) { return ($i['id'] ?? '') !== $input['item_id']; }));
                        } elseif ($action_type === 'update' && $item && isset($input['item_id'])) {
                            foreach ($cat['items'] as &$existing) {
                                if (($existing['id'] ?? '') === $input['item_id']) {
                                    $existing = array_merge($existing, $item);
                                    break;
                                }
                            }
                        } elseif ($action_type === 'update-category') {
                            // Mise à jour des infos de la catégorie
                            if (isset($input['category'])) $cat['category'] = sanitize($input['category']);
                            if (isset($input['subtitle'])) $cat['subtitle'] = sanitize($input['subtitle']);
                            if (isset($input['price_label'])) $cat['price_label'] = sanitize($input['price_label']);
                            if (isset($input['note'])) $cat['note'] = sanitize($input['note']);
                        }
                        break;
                    }
                }
                saveData('carte', $data);
                jsonResponse(['success' => true]);
            }
            jsonResponse(['error' => 'Méthode non supportée'], 405);
            break;

        // ============================
        // NEWSLETTER (Admin — gestion abonnés)
        // ============================
        case 'newsletter':
            $data = loadData('newsletter');
            if ($method === 'GET') {
                usort($data, function($a, $b) { return strtotime($b['subscribed'] ?? 0) - strtotime($a['subscribed'] ?? 0); });
                jsonResponse(['data' => $data, 'count' => count($data)]);
            }
            if ($method === 'DELETE') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? '';
                $data = array_values(array_filter($data, function($s) use ($id) { return ($s['id'] ?? '') !== $id; }));
                saveData('newsletter', $data);
                jsonResponse(['success' => true]);
            }
            jsonResponse(['error' => 'Méthode non supportée'], 405);
            break;

        // ============================
        // PUBLIC API (pas d'auth requise — géré séparément)
        // ============================
        case 'public-carte':
            $data = loadData('carte');
            usort($data, function($a, $b) { return ($a['order'] ?? 99) - ($b['order'] ?? 99); });
            jsonResponse(['data' => $data]);
            break;

        case 'public-gallery':
            $data = loadData('gallery');
            $visible = array_values(array_filter($data, function($p) { return ($p['visible'] ?? true); }));
            usort($visible, function($a, $b) { return ($a['order'] ?? 99) - ($b['order'] ?? 99); });
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
            usort($active, function($a, $b) { return ($a['order'] ?? 99) - ($b['order'] ?? 99); });
            jsonResponse(['data' => $active, 'count' => count($active)]);
            break;

        case 'public-reviews':
            $data = loadData('reviews');
            $visible = array_values(array_filter($data, function($r) { return ($r['visible'] ?? true); }));
            usort($visible, function($a, $b) { return strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0); });
            // Calculate average rating
            $avg = 0;
            if (count($visible) > 0) {
                $avg = round(array_sum(array_column($visible, 'rating')) / count($visible), 1);
            }
            jsonResponse(['data' => $visible, 'count' => count($visible), 'average' => $avg]);
            break;

        // ============================
        // SOUMISSION AVIS PUBLIC (formulaire visiteurs)
        // ============================
        case 'submit-review':
            $input = json_decode(file_get_contents('php://input'), true);

            // Anti-spam : champ honeypot (doit rester vide)
            if (!empty($input['website'] ?? '')) {
                // Bot détecté — répondre OK pour ne pas alerter le bot
                jsonResponse(['success' => true, 'message' => 'Merci pour votre avis !']);
            }

            // Rate limiting simple : max 3 avis par IP par heure
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $rateLimitFile = DATA_DIR . '_ratelimit_reviews.json';
            $rateData = file_exists($rateLimitFile) ? (json_decode(file_get_contents($rateLimitFile), true) ?: []) : [];
            $oneHourAgo = time() - 3600;
            // Nettoyer les anciennes entrées
            $rateData = array_filter($rateData, function($entry) use ($oneHourAgo) { return ($entry['time'] ?? 0) > $oneHourAgo; });
            $ipCount = count(array_filter($rateData, function($entry) use ($ip) { return ($entry['ip'] ?? '') === $ip; }));
            if ($ipCount >= 3) {
                jsonResponse(['error' => 'Vous avez déjà soumis plusieurs avis récemment. Réessayez plus tard.'], 429);
            }

            // Validation
            $client = trim($input['client'] ?? '');
            $comment = trim($input['comment'] ?? '');
            $rating = intval($input['rating'] ?? 0);

            if (empty($client) || mb_strlen($client) < 2) {
                jsonResponse(['error' => 'Veuillez indiquer votre nom (au moins 2 caractères).'], 400);
            }
            if (empty($comment) || mb_strlen($comment) < 10) {
                jsonResponse(['error' => 'Votre avis doit contenir au moins 10 caractères.'], 400);
            }
            if (mb_strlen($comment) > 1000) {
                jsonResponse(['error' => 'Votre avis ne peut pas dépasser 1000 caractères.'], 400);
            }
            if ($rating < 1 || $rating > 5) {
                jsonResponse(['error' => 'La note doit être entre 1 et 5.'], 400);
            }

            // Sauvegarder l'avis (visible = false par défaut → modération)
            $review = [
                'id' => generateId(),
                'client' => sanitize($client),
                'rating' => $rating,
                'comment' => sanitize($comment),
                'source' => 'site-web',
                'date' => date('Y-m-d'),
                'visible' => false, // Nécessite validation admin
                'created' => date('Y-m-d H:i:s'),
                'submitted_by' => 'visiteur',
            ];

            $data = loadData('reviews');
            $data[] = $review;
            saveData('reviews', $data);

            // Enregistrer pour le rate limiting
            $rateData[] = ['ip' => $ip, 'time' => time()];
            file_put_contents($rateLimitFile, json_encode(array_values($rateData)));

            jsonResponse(['success' => true, 'message' => 'Merci pour votre avis ! Il sera publié après validation.']);
            break;

        // ============================
        // INSCRIPTION NEWSLETTER
        // ============================
        case 'subscribe-newsletter':
            $input = json_decode(file_get_contents('php://input'), true);

            // Anti-spam : champ honeypot
            if (!empty($input['b_honey'] ?? '')) {
                jsonResponse(['success' => true, 'message' => 'Merci pour votre inscription !']);
            }

            $email = trim($input['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['error' => 'Veuillez entrer une adresse email valide.'], 400);
            }

            // Vérifier doublon
            $data = loadData('newsletter');
            $exists = false;
            foreach ($data as $sub) {
                if (strtolower($sub['email'] ?? '') === strtolower($email)) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                jsonResponse(['success' => true, 'message' => 'Vous êtes déjà inscrit !']);
            }

            $data[] = [
                'id' => generateId(),
                'email' => sanitize($email),
                'subscribed' => date('Y-m-d H:i:s'),
                'active' => true,
            ];
            saveData('newsletter', $data);

            jsonResponse(['success' => true, 'message' => 'Merci ! Vous recevrez nos prochaines actualités.']);
            break;

        default:
            jsonResponse(['error' => 'Action inconnue'], 404);
    }
} catch (Exception $e) {
    error_log('Le Terrier API error: ' . $e->getMessage());
    jsonResponse(['error' => 'Erreur serveur interne'], 500);
}
