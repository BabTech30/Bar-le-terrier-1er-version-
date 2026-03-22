<?php
/**
 * LE TERRIER — PAGE DE TEST
 * ============================================================
 * Tester les avis et l'API sans toucher aux données de production.
 * Accès : https://www.bar-le-terrier.fr/staging/
 * ============================================================
 */
session_name('lt_staging');
session_start();

// --- Config staging (données isolées) ---
define('DATA_DIR', __DIR__ . '/data/');
define('CSRF_TOKEN_NAME', 'lt_staging_csrf');

date_default_timezone_set('Europe/Paris');

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

$dataFiles = ['reviews'];
foreach ($dataFiles as $file) {
    $path = DATA_DIR . $file . '.json';
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
    }
}

function loadData(string $file): array {
    $path = DATA_DIR . $file . '.json';
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?: [];
}
function saveData(string $file, array $data): bool {
    return file_put_contents(DATA_DIR . $file . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}
function generateId(): string { return bin2hex(random_bytes(8)); }
function sanitize(string $input): string { return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'); }

// CSRF
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION[CSRF_TOKEN_NAME];

// --- API interne (même logique que api.php) ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['api'];

    // CSRF check pour POST/PATCH/DELETE
    if (in_array($method, ['POST', 'PATCH', 'DELETE'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide — token reçu: "' . substr($token, 0, 16) . '...", attendu: "' . substr($_SESSION[CSRF_TOKEN_NAME] ?? '', 0, 16) . '..."']);
            exit;
        }
    }

    if ($action === 'reviews') {
        $data = loadData('reviews');

        if ($method === 'GET') {
            usort($data, fn($a, $b) => strtotime($b['date'] ?? 0) - strtotime($a['date'] ?? 0));
            echo json_encode(['data' => $data, 'count' => count($data)]);
            exit;
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
            $saved = saveData('reviews', $data);
            echo json_encode(['success' => $saved, 'review' => $review, 'debug' => [
                'data_dir' => DATA_DIR,
                'file_exists' => file_exists(DATA_DIR . 'reviews.json'),
                'is_writable' => is_writable(DATA_DIR . 'reviews.json'),
                'total_reviews' => count($data),
            ]]);
            exit;
        }
        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
            $data = array_values(array_filter($data, fn($r) => $r['id'] !== $id));
            saveData('reviews', $data);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(['error' => 'Action inconnue: ' . $action]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>TEST — Le Terrier (Staging)</title>
<style>
:root{--bg:#1a0a10;--surface:#241218;--surface2:#2e1620;--border:rgba(200,164,92,.12);--or:#C8A45C;--creme:#F5F0E8;--text:#F5F0E8;--text-dim:rgba(245,240,232,.5);--green:#4CAF50;--red:#f44336;--radius:6px}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Courier New',monospace;font-size:14px;padding:2rem;max-width:800px;margin:0 auto}
h1{color:var(--or);font-size:1.3rem;margin-bottom:.3rem}
.banner{background:rgba(255,152,0,.15);border:1px solid rgba(255,152,0,.3);color:#FF9800;padding:.6rem 1rem;font-size:.8rem;margin-bottom:1.5rem;border-radius:var(--radius)}
.section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem}
.section h2{color:var(--or);font-size:1rem;margin-bottom:1rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:.8rem}
label{font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);display:block;margin-bottom:.3rem}
input,select,textarea{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:.5rem .7rem;font-family:inherit;font-size:.8rem;border-radius:var(--radius);outline:none}
input:focus,select:focus,textarea:focus{border-color:var(--or)}
textarea{min-height:80px;resize:vertical}
.btn{padding:.5rem 1.2rem;border:none;border-radius:var(--radius);font-family:inherit;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:all .2s;margin-right:.5rem}
.btn--primary{background:var(--or);color:var(--bg)}
.btn--danger{background:transparent;border:1px solid rgba(244,67,54,.3);color:var(--red)}
table{width:100%;border-collapse:collapse;margin-top:1rem}
th{background:var(--surface2);padding:.5rem .7rem;text-align:left;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);border-bottom:1px solid var(--border)}
td{padding:.5rem .7rem;border-bottom:1px solid var(--border);font-size:.8rem}
.log{background:#0a0505;border:1px solid var(--border);border-radius:var(--radius);padding:1rem;font-size:.75rem;color:var(--green);max-height:300px;overflow-y:auto;margin-top:1rem;white-space:pre-wrap}
.log .err{color:var(--red)}
.log .warn{color:#FF9800}
.log .info{color:var(--text-dim)}
</style>
</head>
<body>

<h1>Le Terrier — Mode Test</h1>
<p style="color:var(--text-dim);font-size:.75rem;margin-bottom:1rem">Environnement isolé — les données ici ne touchent PAS la production</p>
<div class="banner">MODE TEST — Les avis créés ici sont stockés dans /staging/data/ (séparé de la prod)</div>

<!-- FORMULAIRE -->
<div class="section">
    <h2>Ajouter un avis (test)</h2>
    <div class="form-row">
        <div><label>Nom du client</label><input id="rv-client" value="Marie Test"></div>
        <div><label>Note (1-5)</label><select id="rv-rating"><option value="5">★★★★★</option><option value="4">★★★★☆</option><option value="3">★★★☆☆</option></select></div>
    </div>
    <div class="form-row">
        <div><label>Source</label><select id="rv-source"><option value="google">Google</option><option value="tripadvisor">TripAdvisor</option><option value="instagram">Instagram</option></select></div>
        <div><label>Date</label><input type="date" id="rv-date" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div><label>Commentaire</label><textarea id="rv-comment">Super ambiance, cocktails incroyables !</textarea></div>
    <div style="margin-top:.8rem">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
            <input type="checkbox" id="rv-visible" checked> Visible sur le site
        </label>
    </div>
    <div style="margin-top:1rem">
        <button class="btn btn--primary" onclick="testSaveReview()">Sauvegarder l'avis</button>
        <button class="btn btn--danger" onclick="testClearAll()">Vider les données test</button>
    </div>
</div>

<!-- RÉSULTATS -->
<div class="section">
    <h2>Avis enregistrés</h2>
    <table>
        <thead><tr><th>Note</th><th>Client</th><th>Commentaire</th><th>Source</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody id="reviews-list"><tr><td colspan="6" style="text-align:center;color:var(--text-dim)">Chargement...</td></tr></tbody>
    </table>
</div>

<!-- LOG TECHNIQUE -->
<div class="section">
    <h2>Journal technique</h2>
    <p style="font-size:.7rem;color:var(--text-dim);margin-bottom:.5rem">Chaque appel API est loggé ici — si quelque chose ne marche pas, l'erreur apparaîtra en rouge</p>
    <div class="log" id="log"></div>
</div>

<script>
const API = '?api=';
const CSRF_TOKEN = '<?= $csrfToken ?>';
const logEl = document.getElementById('log');

function log(msg, type='info') {
    const time = new Date().toLocaleTimeString('fr-FR');
    logEl.innerHTML += '<span class="'+type+'">['+time+'] '+msg+'</span>\n';
    logEl.scrollTop = logEl.scrollHeight;
}

async function api(action, method='GET', body=null) {
    const opts = {method, headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN}};
    if (body) opts.body = JSON.stringify(body);

    const url = API + action;
    log('→ ' + method + ' ' + url + (body ? ' body=' + JSON.stringify(body).substring(0,100) : ''));

    try {
        const res = await fetch(url, opts);
        const text = await res.text();
        log('← Status: ' + res.status + ' | Réponse: ' + text.substring(0, 200), res.ok ? 'info' : 'err');

        try {
            const data = JSON.parse(text);
            if (!res.ok) {
                log('ERREUR API: ' + (data.error || 'Status ' + res.status), 'err');
                if (res.status === 401) log('→ Cause probable: session expirée. Rechargez la page.', 'warn');
                if (res.status === 403) log('→ Cause probable: token CSRF invalide. Rechargez la page.', 'warn');
                return {data:[], error: data.error};
            }
            if (data.debug) log('Debug serveur: ' + JSON.stringify(data.debug), 'warn');
            return data;
        } catch(e) {
            log('ERREUR: La réponse n\'est pas du JSON valide: ' + text.substring(0, 100), 'err');
            log('→ Cause probable: erreur PHP sur le serveur (vérifiez les logs PHP)', 'err');
            return {data:[], error: 'Réponse non-JSON'};
        }
    } catch(e) {
        log('ERREUR RÉSEAU: ' + e.message, 'err');
        return {data:[], error: e.message};
    }
}

async function loadReviews() {
    log('--- Chargement des avis ---');
    const d = await api('reviews');
    const stars = n => '★'.repeat(n) + '☆'.repeat(5-n);
    const el = document.getElementById('reviews-list');
    if (d.error) {
        el.innerHTML = '<tr><td colspan="6" style="color:var(--red)">Erreur: ' + d.error + '</td></tr>';
        return;
    }
    el.innerHTML = (d.data||[]).map(r => `
        <tr>
            <td style="color:var(--or)">${stars(r.rating||5)}</td>
            <td>${r.client}</td>
            <td>${r.comment?.substring(0,40) || '—'}${r.comment?.length > 40 ? '…' : ''}</td>
            <td>${r.source||'—'}</td>
            <td>${r.date||'—'}</td>
            <td><button class="btn btn--danger" onclick="deleteReview('${r.id}')" style="padding:.2rem .5rem;font-size:.6rem">×</button></td>
        </tr>
    `).join('') || '<tr><td colspan="6" style="text-align:center;color:var(--text-dim)">Aucun avis — créez-en un ci-dessus</td></tr>';
    log('✓ ' + (d.data||[]).length + ' avis affichés');
}

async function testSaveReview() {
    log('--- Sauvegarde d\'un avis ---');
    const body = {
        client: document.getElementById('rv-client').value,
        rating: document.getElementById('rv-rating').value,
        comment: document.getElementById('rv-comment').value,
        source: document.getElementById('rv-source').value,
        date: document.getElementById('rv-date').value,
        visible: document.getElementById('rv-visible').checked,
    };
    log('Données envoyées: ' + JSON.stringify(body));
    const result = await api('reviews', 'POST', body);
    if (result.error) {
        log('ÉCHEC de la sauvegarde: ' + result.error, 'err');
        return;
    }
    log('✓ Avis sauvegardé avec succès !', 'info');
    loadReviews();
}

async function deleteReview(id) {
    log('--- Suppression avis ' + id + ' ---');
    await api('reviews', 'DELETE', {id});
    loadReviews();
}

async function testClearAll() {
    if (!confirm('Vider toutes les données test ?')) return;
    const d = await api('reviews');
    for (const r of (d.data||[])) {
        await api('reviews', 'DELETE', {id: r.id});
    }
    loadReviews();
}

// Init
log('=== Environnement de test initialisé ===');
log('CSRF Token: ' + CSRF_TOKEN.substring(0, 16) + '...');
log('Session: active');
loadReviews();
</script>
</body>
</html>
