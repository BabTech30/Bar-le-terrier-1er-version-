<?php
/**
 * LE TERRIER — Dashboard Admin
 * ============================================================
 * Interface complète de gestion : messages, réservations,
 * événements, réseaux sociaux, finances
 * ============================================================
 */
session_start();
require_once __DIR__ . '/config.php';

// --- HANDLE LOGIN ---
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lt_login'])) {
    // Brute-force protection
    $attempts = $_SESSION['lt_login_attempts'] ?? 0;
    $lockUntil = $_SESSION['lt_login_lock'] ?? 0;
    if ($lockUntil > time()) {
        $remaining = ceil(($lockUntil - time()) / 60);
        $loginError = 'Trop de tentatives. Réessayez dans ' . $remaining . ' min.';
    } else {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($user === ADMIN_USER && password_verify($pass, ADMIN_HASH)) {
            $_SESSION['lt_admin_auth'] = true;
            $_SESSION['lt_admin_last'] = time();
            unset($_SESSION['lt_login_attempts'], $_SESSION['lt_login_lock']);
            header('Location: /admin/');
            exit;
        } else {
            $attempts++;
            $_SESSION['lt_login_attempts'] = $attempts;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $_SESSION['lt_login_lock'] = time() + LOGIN_LOCKOUT_TIME;
                $_SESSION['lt_login_attempts'] = 0;
                $loginError = 'Trop de tentatives. Compte verrouillé 15 minutes.';
            } else {
                $loginError = 'Identifiants incorrects';
            }
        }
    }
}

// --- HANDLE LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin/');
    exit;
}

// --- CHECK AUTH ---
$isAuth = isset($_SESSION['lt_admin_auth']) && $_SESSION['lt_admin_auth'] === true;
if ($isAuth && (time() - ($_SESSION['lt_admin_last'] ?? 0)) > SESSION_LIFETIME) {
    session_destroy();
    $isAuth = false;
}

// --- LOGIN PAGE ---
if (!$isAuth): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Connexion — Le Terrier Admin</title>
<link rel="icon" type="image/svg+xml" href="../favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Josefin+Sans:wght@300;400&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#2a0610,#5C0A1E);font-family:'Josefin Sans',sans-serif;color:#F5F0E8}
.login{width:100%;max-width:380px;padding:2rem}
.login__logo{font-family:'Playfair Display',serif;font-size:1.8rem;text-align:center;color:#C8A45C;margin-bottom:.3rem}
.login__sub{text-align:center;font-size:.65rem;letter-spacing:.15em;text-transform:uppercase;opacity:.4;margin-bottom:2.5rem}
.login__field{margin-bottom:1.2rem}
.login__label{display:block;font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;opacity:.6;margin-bottom:.4rem}
.login__input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,.06);border:1px solid rgba(200,164,92,.2);color:#F5F0E8;font-family:inherit;font-size:.85rem;outline:none;transition:border .3s}
.login__input:focus{border-color:#C8A45C}
.login__btn{width:100%;padding:.85rem;background:#C8A45C;color:#3A0612;border:none;font-family:inherit;font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;cursor:pointer;transition:background .3s,transform .2s;margin-top:.5rem}
.login__btn:hover{background:#F5F0E8;transform:translateY(-1px)}
.login__error{background:rgba(220,50,50,.15);border:1px solid rgba(220,50,50,.3);color:#ff8080;padding:.6rem 1rem;font-size:.8rem;margin-bottom:1rem;text-align:center}
</style>
</head>
<body>
<div class="login">
  <p class="login__logo">Le Terrier</p>
  <p class="login__sub">Espace Gestion</p>
  <?php if ($loginError): ?><div class="login__error"><?= $loginError ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="lt_login" value="1">
    <div class="login__field">
      <label class="login__label">Identifiant</label>
      <input type="text" name="username" class="login__input" required autofocus>
    </div>
    <div class="login__field">
      <label class="login__label">Mot de passe</label>
      <input type="password" name="password" class="login__input" required>
    </div>
    <button type="submit" class="login__btn">Entrer dans le repaire</button>
  </form>
</div>
</body></html>
<?php exit; endif;
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Dashboard — Le Terrier</title>
<link rel="icon" type="image/svg+xml" href="../favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Josefin+Sans:wght@300;400&display=swap" rel="stylesheet">
<style>
:root{--bg:#1a0a10;--surface:#241218;--surface2:#2e1620;--border:rgba(200,164,92,.12);--or:#C8A45C;--or-dim:rgba(200,164,92,.5);--creme:#F5F0E8;--bordeaux:#5C0A1E;--text:#F5F0E8;--text-dim:rgba(245,240,232,.5);--green:#4CAF50;--orange:#FF9800;--red:#f44336;--blue:#42A5F5;--radius:6px}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Josefin Sans',sans-serif;font-size:14px;min-height:100vh;display:flex}
a{color:var(--or);text-decoration:none}

/* SIDEBAR */
.sidebar{width:220px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);padding:1.5rem 0;position:fixed;left:0;top:0;display:flex;flex-direction:column;z-index:100}
.sidebar__logo{font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--or);text-align:center;padding:0 1rem .2rem}
.sidebar__sub{text-align:center;font-size:.55rem;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim);margin-bottom:2rem}
.sidebar__nav{flex:1}
.sidebar__link{display:flex;align-items:center;gap:.7rem;padding:.7rem 1.2rem;color:var(--text-dim);font-size:.72rem;letter-spacing:.04em;cursor:pointer;transition:all .2s;border-left:3px solid transparent}
.sidebar__link:hover{color:var(--creme);background:rgba(200,164,92,.05)}
.sidebar__link.active{color:var(--or);background:rgba(200,164,92,.08);border-left-color:var(--or)}
.sidebar__link span{font-size:1rem}
.sidebar__footer{padding:.8rem 1.2rem;border-top:1px solid var(--border)}
.sidebar__logout{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);cursor:pointer;transition:color .2s}
.sidebar__logout:hover{color:var(--red)}
.sidebar__time{font-size:.6rem;color:var(--text-dim);margin-top:.3rem}

/* MAIN */
.main{margin-left:220px;flex:1;padding:2rem;min-height:100vh}
.main__header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.main__title{font-family:'Playfair Display',serif;font-size:1.5rem;color:var(--or)}
.main__date{font-size:.7rem;color:var(--text-dim);letter-spacing:.05em}

/* CARDS */
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem}
.card__label{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);margin-bottom:.4rem}
.card__value{font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--or)}
.card__sub{font-size:.7rem;color:var(--text-dim);margin-top:.2rem}
.card--green .card__value{color:var(--green)}
.card--orange .card__value{color:var(--orange)}
.card--blue .card__value{color:var(--blue)}

/* SECTIONS */
.section{display:none}
.section.active{display:block}

/* TABLES */
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{background:var(--surface2);padding:.7rem 1rem;text-align:left;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);border-bottom:1px solid var(--border)}
td{padding:.7rem 1rem;border-bottom:1px solid var(--border);font-size:.8rem;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover{background:rgba(200,164,92,.03)}

/* BADGES */
.badge{display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.6rem;letter-spacing:.04em;font-weight:400}
.badge--new{background:rgba(66,165,245,.15);color:var(--blue)}
.badge--pending{background:rgba(255,152,0,.15);color:var(--orange)}
.badge--confirmed{background:rgba(76,175,80,.15);color:var(--green)}
.badge--done{background:rgba(200,164,92,.15);color:var(--or)}
.badge--cancelled{background:rgba(244,67,54,.15);color:var(--red)}
.badge--draft{background:rgba(255,255,255,.08);color:var(--text-dim)}

/* BUTTONS */
.btn{padding:.5rem 1.2rem;border:none;border-radius:var(--radius);font-family:inherit;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:all .2s}
.btn--primary{background:var(--or);color:var(--bg)}
.btn--primary:hover{background:var(--creme)}
.btn--ghost{background:transparent;border:1px solid var(--border);color:var(--text-dim)}
.btn--ghost:hover{border-color:var(--or);color:var(--or)}
.btn--danger{background:transparent;border:1px solid rgba(244,67,54,.3);color:var(--red)}
.btn--danger:hover{background:rgba(244,67,54,.1)}
.btn--sm{padding:.3rem .7rem;font-size:.6rem}
.btn-group{display:flex;gap:.5rem;flex-wrap:wrap}

/* FORMS */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.form-group{display:flex;flex-direction:column;gap:.3rem}
.form-group--full{grid-column:1/-1}
.form-label{font-size:.6rem;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim)}
.form-input,.form-select,.form-textarea{background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:.6rem .8rem;font-family:inherit;font-size:.8rem;border-radius:var(--radius);outline:none;transition:border .2s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--or)}
.form-textarea{min-height:100px;resize:vertical}
.form-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%23C8A45C' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .8rem center}
.form-select option{background:var(--surface2);color:var(--text)}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center}
.modal-overlay.visible{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);width:95%;max-width:560px;max-height:90vh;overflow-y:auto;padding:2rem}
.modal__title{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--or);margin-bottom:1.5rem}
.modal__actions{display:flex;gap:.8rem;justify-content:flex-end;margin-top:1.5rem}

/* POST GENERATOR */
.gen-output{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;margin-top:1rem;white-space:pre-wrap;font-family:'Cormorant Garamond',serif;font-size:.95rem;line-height:1.6;min-height:100px}
.gen-actions{display:flex;gap:.5rem;margin-top:.8rem}

/* EMPTY STATE */
.empty{text-align:center;padding:3rem;color:var(--text-dim)}
.empty__icon{font-size:2.5rem;margin-bottom:.8rem;opacity:.3}
.empty__text{font-size:.85rem}

/* GALLERY CARDS */
.gallery-card{position:relative;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);background:var(--surface2);aspect-ratio:4/3}
.gallery-card img{width:100%;height:100%;object-fit:cover}
.gallery-card__overlay{position:absolute;inset:0;background:linear-gradient(transparent 50%,rgba(0,0,0,.8));display:flex;flex-direction:column;justify-content:flex-end;padding:.8rem;opacity:0;transition:opacity .2s}
.gallery-card:hover .gallery-card__overlay{opacity:1}
.gallery-card__title{font-family:'Playfair Display',serif;font-size:.85rem;color:#fff}
.gallery-card__caption{font-size:.7rem;color:rgba(255,255,255,.6)}
.gallery-card__actions{position:absolute;top:.5rem;right:.5rem;display:flex;gap:.3rem;opacity:0;transition:opacity .2s}
.gallery-card:hover .gallery-card__actions{opacity:1}
.gallery-card__placeholder{display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:var(--text-dim);font-size:.7rem;letter-spacing:.1em;text-transform:uppercase}
.gallery-card__badge{position:absolute;top:.5rem;left:.5rem;font-size:.55rem;padding:.15rem .4rem;border-radius:2px;text-transform:uppercase;letter-spacing:.08em;font-family:'Josefin Sans',sans-serif}
.gallery-card__badge--hidden{background:rgba(255,100,100,.2);color:#ff6464}
.gallery-card__badge--visible{background:rgba(100,255,100,.15);color:#64ff64}

/* DROP ZONE */
.dropzone{border:2px dashed var(--border);border-radius:var(--radius);padding:2rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s}
.dropzone:hover,.dropzone.dragover{border-color:var(--or);background:rgba(200,164,92,.05)}
.dropzone__text{font-size:.75rem;color:var(--text-dim);letter-spacing:.08em;text-transform:uppercase}

/* RESPONSIVE */
@media(max-width:768px){
  .sidebar{width:60px;padding:1rem 0}
  .sidebar__logo{font-size:.9rem;padding:0 .3rem}
  .sidebar__sub,.sidebar__link small,.sidebar__time{display:none}
  .sidebar__link{justify-content:center;padding:.7rem;border-left:none;border-right:3px solid transparent}
  .sidebar__link.active{border-right-color:var(--or);border-left:none}
  .sidebar__link span{font-size:1.2rem}
  .main{margin-left:60px;padding:1rem}
  .cards{grid-template-columns:repeat(2,1fr)}
  .form-grid{grid-template-columns:1fr}
}
@media(max-width:480px){
  .cards{grid-template-columns:1fr}
  .main__header{flex-direction:column;gap:.5rem;align-items:flex-start}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <p class="sidebar__logo">Le Terrier</p>
  <p class="sidebar__sub">Dashboard</p>
  <nav class="sidebar__nav">
    <div class="sidebar__link active" data-section="overview"><span>📊</span> <small>Vue d'ensemble</small></div>
    <div class="sidebar__link" data-section="messages"><span>📬</span> <small>Messages</small></div>
    <div class="sidebar__link" data-section="reservations"><span>📅</span> <small>Réservations</small></div>
    <div class="sidebar__link" data-section="events"><span>🎭</span> <small>Événements</small></div>
    <div class="sidebar__link" data-section="social"><span>📱</span> <small>Réseaux sociaux</small></div>
    <div class="sidebar__link" data-section="finances"><span>💰</span> <small>Finances</small></div>
    <div class="sidebar__link" data-section="carte"><span>🍽️</span> <small>Carte</small></div>
    <div class="sidebar__link" data-section="boutique"><span>🛍️</span> <small>Boutique</small></div>
    <div class="sidebar__link" data-section="reviews"><span>⭐</span> <small>Avis clients</small></div>
    <div class="sidebar__link" data-section="observations"><span>📋</span> <small>Observations</small></div>
    <div class="sidebar__link" data-section="gallery"><span>🖼️</span> <small>Galerie</small></div>
    <div class="sidebar__link" data-section="announcements"><span>📢</span> <small>Annonces</small></div>
    <div class="sidebar__link" data-section="newsletter"><span>📧</span> <small>Newsletter</small></div>
  </nav>
  <div class="sidebar__footer">
    <div style="display:flex;gap:.4rem;margin-bottom:.6rem;flex-wrap:wrap">
      <a href="/diagnostic.php" style="font-size:.55rem;padding:.25rem .5rem;background:rgba(200,164,92,.1);border:1px solid rgba(200,164,92,.15);border-radius:4px;color:rgba(245,240,232,.5);text-decoration:none;letter-spacing:.05em;text-transform:uppercase;transition:all .2s" onmouseover="this.style.color='#C8A45C';this.style.borderColor='#C8A45C'" onmouseout="this.style.color='rgba(245,240,232,.5)';this.style.borderColor='rgba(200,164,92,.15)'">Diagnostic</a>
      <a href="/backup.php" style="font-size:.55rem;padding:.25rem .5rem;background:rgba(200,164,92,.1);border:1px solid rgba(200,164,92,.15);border-radius:4px;color:rgba(245,240,232,.5);text-decoration:none;letter-spacing:.05em;text-transform:uppercase;transition:all .2s" onmouseover="this.style.color='#C8A45C';this.style.borderColor='#C8A45C'" onmouseout="this.style.color='rgba(245,240,232,.5)';this.style.borderColor='rgba(200,164,92,.15)'">Backup</a>
      <a href="/staging/" style="font-size:.55rem;padding:.25rem .5rem;background:rgba(200,164,92,.1);border:1px solid rgba(200,164,92,.15);border-radius:4px;color:rgba(245,240,232,.5);text-decoration:none;letter-spacing:.05em;text-transform:uppercase;transition:all .2s" onmouseover="this.style.color='#C8A45C';this.style.borderColor='#C8A45C'" onmouseout="this.style.color='rgba(245,240,232,.5)';this.style.borderColor='rgba(200,164,92,.15)'">Test</a>
    </div>
    <a href="?logout=1" class="sidebar__logout">Déconnexion</a>
    <p class="sidebar__time" id="clock"></p>
  </div>
</aside>

<!-- MAIN CONTENT -->
<div class="main">

  <!-- ===== VUE D'ENSEMBLE ===== -->
  <div class="section active" id="sec-overview">
    <div class="main__header">
      <h1 class="main__title">Tableau de bord</h1>
      <span class="main__date" id="today-date"></span>
    </div>
    <div class="cards" id="stats-cards"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1rem">
      <div>
        <h3 style="font-family:'Playfair Display',serif;color:var(--or);font-size:1rem;margin-bottom:1rem">Messages récents</h3>
        <div class="table-wrap"><table><thead><tr><th>Nom</th><th>Sujet</th><th>Date</th></tr></thead><tbody id="recent-messages"></tbody></table></div>
      </div>
      <div>
        <h3 style="font-family:'Playfair Display',serif;color:var(--or);font-size:1rem;margin-bottom:1rem">Réservations du jour</h3>
        <div class="table-wrap"><table><thead><tr><th>Nom</th><th>Heure</th><th>Pers.</th><th>Statut</th></tr></thead><tbody id="today-resas"></tbody></table></div>
      </div>
    </div>
  </div>

  <!-- ===== MESSAGES ===== -->
  <div class="section" id="sec-messages">
    <div class="main__header">
      <h1 class="main__title">Messages</h1>
      <span class="main__date" id="msg-count"></span>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Statut</th><th>Nom</th><th>Email</th><th>Sujet</th><th>Date</th><th>Actions</th></tr></thead><tbody id="messages-list"></tbody></table></div>
  </div>

  <!-- ===== RÉSERVATIONS ===== -->
  <div class="section" id="sec-reservations">
    <div class="main__header">
      <h1 class="main__title">Réservations</h1>
      <span class="main__date" id="resa-count"></span>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Statut</th><th>Date</th><th>Heure</th><th>Nom</th><th>Pers.</th><th>Tél.</th><th>Actions</th></tr></thead><tbody id="reservations-list"></tbody></table></div>
  </div>

  <!-- ===== ÉVÉNEMENTS ===== -->
  <div class="section" id="sec-events">
    <div class="main__header">
      <h1 class="main__title">Événements</h1>
      <button class="btn btn--primary" onclick="openModal('event')">+ Nouvel événement</button>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Statut</th><th>Date</th><th>Type</th><th>Titre</th><th>Actions</th></tr></thead><tbody id="events-list"></tbody></table></div>
  </div>

  <!-- ===== RÉSEAUX SOCIAUX ===== -->
  <div class="section" id="sec-social">
    <div class="main__header">
      <h1 class="main__title">Réseaux sociaux</h1>
      <div class="btn-group">
        <button class="btn btn--primary" onclick="openModal('social')">+ Nouveau post</button>
        <button class="btn btn--ghost" onclick="openModal('generator')">Générateur IA</button>
      </div>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Statut</th><th>Date</th><th>Plateforme</th><th>Type</th><th>Légende</th><th>Actions</th></tr></thead><tbody id="social-list"></tbody></table></div>
  </div>

  <!-- ===== FINANCES ===== -->
  <div class="section" id="sec-finances">
    <div class="main__header">
      <h1 class="main__title">Finances & Devis</h1>
      <button class="btn btn--primary" onclick="openModal('finance')">+ Nouveau devis</button>
    </div>
    <div class="cards" id="finance-cards"></div>
    <div class="table-wrap"><table><thead><tr><th>Réf.</th><th>Client</th><th>Date event</th><th>Montant</th><th>Statut</th><th>Actions</th></tr></thead><tbody id="finances-list"></tbody></table></div>
  </div>

  <!-- ===== CARTE / MENU ===== -->
  <div class="section" id="sec-carte">
    <div class="main__header">
      <h1 class="main__title">Carte & Menu</h1>
      <button class="btn btn--primary" onclick="openCarteItemModal()">+ Ajouter un plat</button>
    </div>
    <p style="font-size:.75rem;color:var(--text-dim);margin-bottom:1rem">Gérez les catégories, plats, cocktails et prix de la carte. Les modifications sont visibles immédiatement sur le site.</p>
    <div id="carte-editor"></div>
  </div>

  <!-- ===== BOUTIQUE ===== -->
  <div class="section" id="sec-boutique">
    <div class="main__header">
      <h1 class="main__title">Boutique</h1>
      <button class="btn btn--primary" onclick="openModal('boutique')">+ Nouveau produit</button>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Statut</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actions</th></tr></thead><tbody id="boutique-list"></tbody></table></div>
  </div>

  <!-- ===== AVIS CLIENTS ===== -->
  <div class="section" id="sec-reviews">
    <div class="main__header">
      <h1 class="main__title">Avis clients</h1>
      <button class="btn btn--primary" onclick="openModal('review')">+ Ajouter un avis</button>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Note</th><th>Client</th><th>Avis</th><th>Source</th><th>Date</th><th>Visible</th><th>Actions</th></tr></thead><tbody id="reviews-list"></tbody></table></div>
  </div>

  <!-- ===== OBSERVATIONS ===== -->
  <div class="section" id="sec-observations">
    <div class="main__header">
      <h1 class="main__title">Observations & Notes</h1>
      <button class="btn btn--primary" onclick="openModal('observation')">+ Nouvelle note</button>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Priorité</th><th>Catégorie</th><th>Note</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead><tbody id="observations-list"></tbody></table></div>
  </div>

  <!-- ===== GALERIE ===== -->
  <div class="section" id="sec-gallery">
    <div class="main__header">
      <h1 class="main__title">Galerie photos</h1>
      <button class="btn btn--primary" onclick="openModal('gallery')">+ Ajouter une photo</button>
    </div>
    <p style="font-size:.75rem;color:var(--text-dim);margin-bottom:1rem">Les photos visibles apparaissent automatiquement sur la page Galerie du site.</p>
    <div id="gallery-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem"></div>
  </div>

  <!-- ===== ANNONCES ===== -->
  <div class="section" id="sec-announcements">
    <div class="main__header">
      <h1 class="main__title">Annonces & Infos</h1>
      <button class="btn btn--primary" onclick="openModal('announcement')">+ Nouvelle annonce</button>
    </div>
    <p style="font-size:.75rem;color:var(--text-dim);margin-bottom:1rem">Les annonces actives apparaissent sur la page d'accueil dans l'encadré "Ardoise du Terrier".</p>
    <div class="table-wrap"><table><thead><tr><th>Statut</th><th>Type</th><th>Titre</th><th>Contenu</th><th>Expire</th><th>Actions</th></tr></thead><tbody id="announcements-list"></tbody></table></div>
  </div>

  <!-- ===== NEWSLETTER ===== -->
  <div class="section" id="sec-newsletter">
    <div class="main__header">
      <h1 class="main__title">Newsletter</h1>
    </div>
    <p style="font-size:.75rem;color:var(--text-dim);margin-bottom:1rem">Les visiteurs qui s'inscrivent à la newsletter sont enregistrés ici. Vous pouvez exporter la liste ou envoyer un email groupé.</p>
    <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
      <button class="btn btn--primary" onclick="exportNewsletter()">Exporter CSV</button>
      <span id="nl-count" style="font-size:.8rem;color:var(--text-dim);align-self:center"></span>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Email</th><th>Date d'inscription</th><th>Statut</th><th>Actions</th></tr></thead><tbody id="newsletter-list"></tbody></table></div>
  </div>

</div>

<!-- MODAL -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal" id="modal-content"></div>
</div>

<script>
const API = '/api.php';
const CSRF_TOKEN = '<?= $csrfToken ?>';

// ===== XSS PROTECTION =====
function esc(s) {
  if (!s) return '';
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

// ===== NAVIGATION =====
document.querySelectorAll('.sidebar__link').forEach(link => {
  link.addEventListener('click', () => {
    document.querySelectorAll('.sidebar__link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    link.classList.add('active');
    const sec = document.getElementById('sec-' + link.dataset.section);
    if (sec) sec.classList.add('active');
    loadSection(link.dataset.section);
  });
});

// ===== CLOCK =====
function updateClock() {
  const now = new Date();
  const el = document.getElementById('clock');
  if (el) el.textContent = now.toLocaleDateString('fr-FR') + ' · ' + now.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
}
setInterval(updateClock, 30000); updateClock();

const todayEl = document.getElementById('today-date');
if (todayEl) todayEl.textContent = new Date().toLocaleDateString('fr-FR', {weekday:'long',day:'numeric',month:'long',year:'numeric'});

// ===== API HELPERS =====
async function api(action, method='GET', body=null) {
  const opts = {method, headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN}};
  if (body) opts.body = JSON.stringify(body);
  try {
    const res = await fetch(API + '?action=' + action, opts);
    const data = await res.json();
    if (!res.ok) {
      const msg = data.error || 'Erreur ' + res.status;
      if (res.status === 401) {
        alert('Session expirée. Veuillez vous reconnecter.');
        window.location.reload();
        return {data:[], error: msg};
      }
      if (res.status === 403) {
        alert('Token de sécurité expiré. La page va se recharger.');
        window.location.reload();
        return {data:[], error: msg};
      }
      alert('Erreur API : ' + msg);
      return {data:[], error: msg};
    }
    return data;
  } catch(e) {
    alert('Erreur réseau : impossible de contacter le serveur.');
    return {data:[], error: e.message};
  }
}

// ===== STATUS HELPERS =====
const statusBadge = (s) => {
  const map = {nouveau:'new',new:'new','en attente':'pending',planifié:'pending',brouillon:'draft',confirmée:'confirmed',actif:'confirmed',accepté:'confirmed',envoyé:'pending',payé:'done',annulée:'cancelled',inactif:'cancelled',refusé:'cancelled'};
  return '<span class="badge badge--' + (map[s]||'draft') + '">' + s + '</span>';
};

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR',{day:'2-digit',month:'short'}) : '—';
const fmtDateTime = (d) => d ? new Date(d).toLocaleDateString('fr-FR',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}) : '—';
const truncate = (s, n=50) => s && s.length > n ? s.substring(0, n) + '…' : (s || '—');

// ===== LOAD SECTIONS =====
async function loadSection(name) {
  switch(name) {
    case 'overview': return loadOverview();
    case 'messages': return loadMessages();
    case 'reservations': return loadReservations();
    case 'events': return loadEvents();
    case 'social': return loadSocial();
    case 'finances': return loadFinances();
    case 'carte': return loadCarte();
    case 'boutique': return loadBoutique();
    case 'reviews': return loadReviews();
    case 'observations': return loadObservations();
    case 'gallery': return loadGallery();
    case 'announcements': return loadAnnouncements();
    case 'newsletter': return loadNewsletter();
  }
}

async function loadOverview() {
  const d = await api('stats');
  document.getElementById('stats-cards').innerHTML = `
    <div class="card"><p class="card__label">Messages non lus</p><p class="card__value">${d.new_messages||0}</p><p class="card__sub">${d.week_messages||0} cette semaine</p></div>
    <div class="card card--blue"><p class="card__label">Réservations aujourd'hui</p><p class="card__value">${d.today_reservations||0}</p><p class="card__sub">${d.total_reservations||0} au total</p></div>
    <div class="card card--orange"><p class="card__label">Événements à venir</p><p class="card__value">${d.upcoming_events||0}</p></div>
    <div class="card card--green"><p class="card__label">CA du mois</p><p class="card__value">${(d.month_revenue||0).toLocaleString('fr-FR')} €</p></div>
  `;
  const rm = document.getElementById('recent-messages');
  rm.innerHTML = (d.recent_messages||[]).length ? d.recent_messages.map(m =>
    `<tr><td>${m.name}</td><td>${truncate(m.subject,30)}</td><td>${fmtDate(m.date)}</td></tr>`
  ).join('') : '<tr><td colspan="3" class="empty"><p class="empty__text">Aucun nouveau message</p></td></tr>';

  const tr = document.getElementById('today-resas');
  tr.innerHTML = (d.today_resas||[]).length ? d.today_resas.map(r =>
    `<tr><td>${r.name}</td><td>${r.time}</td><td>${r.guests}</td><td>${statusBadge(r.status)}</td></tr>`
  ).join('') : '<tr><td colspan="4" class="empty"><p class="empty__text">Aucune réservation aujourd\'hui</p></td></tr>';
}

async function loadMessages() {
  const d = await api('messages');
  document.getElementById('msg-count').textContent = d.count + ' messages';
  const el = document.getElementById('messages-list');
  el.innerHTML = (d.data||[]).map(m => `
    <tr>
      <td>${statusBadge(m.status)}</td>
      <td><strong>${m.name}</strong></td>
      <td><a href="mailto:${m.email}">${m.email}</a></td>
      <td>${truncate(m.subject,40)}</td>
      <td>${fmtDateTime(m.date)}</td>
      <td class="btn-group">
        ${m.status==='nouveau'?'<button class="btn btn--sm btn--ghost" onclick="updateMsg(\''+m.id+'\',\'lu\')">Marquer lu</button>':''}
        <button class="btn btn--sm btn--ghost" onclick="viewMessage('${m.id}')">Voir</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('messages','${m.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="6"><div class="empty"><p class="empty__icon">📬</p><p class="empty__text">Aucun message</p></div></td></tr>';
}

async function loadReservations() {
  const d = await api('reservations');
  document.getElementById('resa-count').textContent = d.count + ' réservations';
  const el = document.getElementById('reservations-list');
  el.innerHTML = (d.data||[]).map(r => `
    <tr>
      <td>${statusBadge(r.status)}</td>
      <td>${fmtDate(r.date_resa)}</td>
      <td>${r.time||'—'}</td>
      <td><strong>${r.name}</strong></td>
      <td>${r.guests}</td>
      <td>${r.phone||'—'}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="updateResa('${r.id}','confirmée')">✓</button>
        <button class="btn btn--sm btn--danger" onclick="updateResa('${r.id}','annulée')">✗</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="7"><div class="empty"><p class="empty__icon">📅</p><p class="empty__text">Aucune réservation</p></div></td></tr>';
}

async function loadEvents() {
  const d = await api('events');
  const el = document.getElementById('events-list');
  const types = {jazz:'🎵 Jazz',vin:'🍷 Vins',dj:'🎧 DJ',special:'✨ Spécial',prive:'🔒 Privé'};
  el.innerHTML = (d.data||[]).map(e => `
    <tr>
      <td>${statusBadge(e.status)}</td>
      <td>${fmtDate(e.date)}</td>
      <td>${types[e.type]||e.type}</td>
      <td><strong>${e.title}</strong><br><small style="color:var(--text-dim)">${truncate(e.description,60)}</small></td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editEvent('${e.id}')">Modifier</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('events','${e.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="5"><div class="empty"><p class="empty__icon">🎭</p><p class="empty__text">Aucun événement</p></div></td></tr>';
}

async function loadSocial() {
  const d = await api('social');
  const platforms = {instagram:'📸 Instagram',facebook:'📘 Facebook',both:'📸📘 Les deux'};
  const el = document.getElementById('social-list');
  el.innerHTML = (d.data||[]).map(s => `
    <tr>
      <td>${statusBadge(s.status)}</td>
      <td>${fmtDate(s.date)}</td>
      <td>${platforms[s.platform]||s.platform}</td>
      <td>${s.type}</td>
      <td>${truncate(s.caption,50)}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editSocial('${s.id}')">Modifier</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('social','${s.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="6"><div class="empty"><p class="empty__icon">📱</p><p class="empty__text">Aucun post planifié</p></div></td></tr>';
}

async function loadFinances() {
  const d = await api('finances');
  document.getElementById('finance-cards').innerHTML = `
    <div class="card card--green"><p class="card__label">Total encaissé</p><p class="card__value">${(d.total_paid||0).toLocaleString('fr-FR')} €</p></div>
    <div class="card card--orange"><p class="card__label">En attente</p><p class="card__value">${(d.total_pending||0).toLocaleString('fr-FR')} €</p></div>
    <div class="card"><p class="card__label">Devis total</p><p class="card__value">${d.count||0}</p></div>
  `;
  const el = document.getElementById('finances-list');
  el.innerHTML = (d.data||[]).map(f => `
    <tr>
      <td><strong>${f.ref}</strong></td>
      <td>${f.client}</td>
      <td>${fmtDate(f.date_event)}</td>
      <td>${parseFloat(f.amount).toLocaleString('fr-FR')} €</td>
      <td>${statusBadge(f.status)}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editFinance('${f.id}')">Modifier</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('finances','${f.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="6"><div class="empty"><p class="empty__icon">💰</p><p class="empty__text">Aucun devis</p></div></td></tr>';
}

// ===== ACTIONS =====
async function updateMsg(id, status) { await api('messages','PATCH',{id,status}); loadMessages(); loadOverview(); }
async function updateResa(id, status) { await api('reservations','PATCH',{id,status}); loadReservations(); loadOverview(); }
async function deleteItem(entity, id) {
  if (!confirm('Supprimer cet élément ?')) return;
  await api(entity, 'DELETE', {id});
  loadSection(document.querySelector('.sidebar__link.active')?.dataset.section || 'overview');
}

async function viewMessage(id) {
  const d = await api('messages');
  const m = (d.data||[]).find(x => x.id === id);
  if (!m) return;
  if (m.status === 'nouveau') await api('messages','PATCH',{id,status:'lu'});
  // Note: message data may come from external forms — keep esc() for safety
  document.getElementById('modal-content').innerHTML = `
    <p class="modal__title">${esc(m.subject||'Message')}</p>
    <p style="margin-bottom:.5rem"><strong>${esc(m.name)}</strong> · <a href="mailto:${esc(m.email)}">${esc(m.email)}</a>${m.phone?' · <a href="tel:'+esc(m.phone)+'">'+esc(m.phone)+'</a>':''}</p>
    <p style="font-size:.7rem;color:var(--text-dim);margin-bottom:1rem">${fmtDateTime(m.date)}</p>
    <div style="background:var(--surface2);padding:1rem;border-radius:var(--radius);white-space:pre-wrap;font-family:'Cormorant Garamond',serif;font-size:.95rem;line-height:1.6">${esc(m.message)}</div>
    <div class="modal__actions">
      <a href="mailto:${esc(m.email)}?subject=Re: ${encodeURIComponent(m.subject||'')}" class="btn btn--primary">Répondre par email</a>
      <button class="btn btn--ghost" onclick="closeModal()">Fermer</button>
    </div>
  `;
  document.getElementById('modal-overlay').classList.add('visible');
  loadMessages();
}

// ===== MODALS =====
function openModal(type) {
  const mc = document.getElementById('modal-content');
  switch(type) {
    case 'event':
      mc.innerHTML = `
        <p class="modal__title">Nouvel événement</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Titre</label><input class="form-input" id="evt-title" placeholder="Jazz au Terrier"></div>
          <div class="form-group"><label class="form-label">Type</label><select class="form-select" id="evt-type"><option value="jazz">🎵 Jazz</option><option value="vin">🍷 Vins</option><option value="dj">🎧 DJ</option><option value="special">✨ Spécial</option><option value="prive">🔒 Privé</option></select></div>
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" id="evt-date"></div>
          <div class="form-group"><label class="form-label">Heure</label><input type="time" class="form-input" id="evt-time" value="20:00"></div>
          <div class="form-group form-group--full"><label class="form-label">Description</label><textarea class="form-textarea" id="evt-desc" placeholder="Description de l'événement"></textarea></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveEvent()">Créer</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
    case 'social':
      mc.innerHTML = `
        <p class="modal__title">Nouveau post</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Date de publication</label><input type="date" class="form-input" id="soc-date"></div>
          <div class="form-group"><label class="form-label">Plateforme</label><select class="form-select" id="soc-platform"><option value="instagram">📸 Instagram</option><option value="facebook">📘 Facebook</option><option value="both">📸📘 Les deux</option></select></div>
          <div class="form-group"><label class="form-label">Type</label><select class="form-select" id="soc-type"><option value="photo">Photo</option><option value="reel">Reel/Vidéo</option><option value="story">Story</option><option value="carousel">Carrousel</option></select></div>
          <div class="form-group"><label class="form-label">Statut</label><select class="form-select" id="soc-status"><option value="brouillon">Brouillon</option><option value="planifié">Planifié</option><option value="publié">Publié</option></select></div>
          <div class="form-group form-group--full"><label class="form-label">Légende</label><textarea class="form-textarea" id="soc-caption" placeholder="Texte du post"></textarea></div>
          <div class="form-group form-group--full"><label class="form-label">Hashtags</label><input class="form-input" id="soc-hashtags" placeholder="#LeTerrierBar #Nîmes #Cocktails"></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveSocial()">Créer</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
    case 'generator':
      mc.innerHTML = `
        <p class="modal__title">Générateur de posts</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Type de post</label><select class="form-select" id="gen-type"><option value="cocktail">🍸 Cocktail</option><option value="tapas">🍽️ Tapas</option><option value="event">🎭 Événement</option><option value="ambiance">✨ Ambiance</option></select></div>
          <div class="form-group"><label class="form-label">Nom (cocktail/plat/event)</label><input class="form-input" id="gen-name" placeholder="Le Terrier"></div>
          <div class="form-group form-group--full"><label class="form-label">Description courte</label><input class="form-input" id="gen-desc" placeholder="Gin, miel de garrigue, thym..."></div>
        </div>
        <div class="gen-output" id="gen-output">Le post généré apparaîtra ici.</div>
        <div class="gen-actions">
          <button class="btn btn--primary" onclick="generatePost()">Générer</button>
          <button class="btn btn--ghost" onclick="copyGenerated(event)">Copier</button>
          <button class="btn btn--ghost" onclick="closeModal()">Fermer</button>
        </div>`;
      break;
    case 'boutique':
      mc.innerHTML = `
        <p class="modal__title">Nouveau produit</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Nom du produit</label><input class="form-input" id="bq-name" placeholder="T-shirt Le Terrier"></div>
          <div class="form-group"><label class="form-label">Catégorie</label><select class="form-select" id="bq-category"><option value="cocktail">Cocktail</option><option value="accessoire">Accessoire</option><option value="vetement">Vêtement</option><option value="epicerie">Épicerie</option><option value="cadeau">Coffret cadeau</option></select></div>
          <div class="form-group"><label class="form-label">Prix (€)</label><input type="number" step="0.01" class="form-input" id="bq-price" placeholder="25.00"></div>
          <div class="form-group"><label class="form-label">Stock</label><input type="number" class="form-input" id="bq-stock" placeholder="10"></div>
          <div class="form-group"><label class="form-label">Statut</label><select class="form-select" id="bq-status"><option value="actif">Actif</option><option value="inactif">Inactif</option><option value="rupture">En rupture</option></select></div>
          <div class="form-group"><label class="form-label">URL image</label><input class="form-input" id="bq-image" placeholder="images/produit.jpg"></div>
          <div class="form-group form-group--full"><label class="form-label">Description</label><textarea class="form-textarea" id="bq-desc" placeholder="Description du produit"></textarea></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveBoutique()">Créer</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
    case 'review':
      mc.innerHTML = `
        <p class="modal__title">Nouvel avis client</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Nom du client</label><input class="form-input" id="rv-client" placeholder="Jean D."></div>
          <div class="form-group"><label class="form-label">Note (1-5)</label><select class="form-select" id="rv-rating"><option value="5">★★★★★ (5)</option><option value="4">★★★★☆ (4)</option><option value="3">★★★☆☆ (3)</option><option value="2">★★☆☆☆ (2)</option><option value="1">★☆☆☆☆ (1)</option></select></div>
          <div class="form-group"><label class="form-label">Source</label><select class="form-select" id="rv-source"><option value="google">Google</option><option value="tripadvisor">TripAdvisor</option><option value="instagram">Instagram</option><option value="bouche-a-oreille">Bouche à oreille</option><option value="autre">Autre</option></select></div>
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" id="rv-date"></div>
          <div class="form-group form-group--full"><label class="form-label">Commentaire</label><textarea class="form-textarea" id="rv-comment" placeholder="Super ambiance, cocktails incroyables..."></textarea></div>
          <div class="form-group"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer"><input type="checkbox" id="rv-visible" checked> <span class="form-label" style="margin:0">Visible sur le site</span></label></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveReview()">Ajouter</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
    case 'observation':
      mc.innerHTML = `
        <p class="modal__title">Nouvelle observation</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Catégorie</label><select class="form-select" id="obs-category"><option value="general">Général</option><option value="bug">Bug / Problème</option><option value="amelioration">Amélioration</option><option value="contenu">Contenu à modifier</option><option value="securite">Sécurité</option><option value="design">Design</option></select></div>
          <div class="form-group"><label class="form-label">Priorité</label><select class="form-select" id="obs-priority"><option value="haute">Haute</option><option value="moyenne" selected>Moyenne</option><option value="basse">Basse</option></select></div>
          <div class="form-group form-group--full"><label class="form-label">Note / Observation</label><textarea class="form-textarea" id="obs-note" placeholder="Décrire l'observation, le problème ou l'amélioration souhaitée..."></textarea></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveObservation()">Ajouter</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
    case 'gallery':
      mc.innerHTML = `
        <p class="modal__title">Ajouter une photo</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Titre</label><input class="form-input" id="gal-title" placeholder="Cocktail Signature"></div>
          <div class="form-group"><label class="form-label">Catégorie</label><select class="form-select" id="gal-category"><option value="ambiance">Ambiance</option><option value="cocktails">Cocktails</option><option value="tapas">Tapas</option><option value="decor">Décor</option><option value="events">Événements</option><option value="equipe">Équipe</option></select></div>
          <div class="form-group form-group--full"><label class="form-label">Légende</label><input class="form-input" id="gal-caption" placeholder="Description courte de la photo"></div>
          <div class="form-group form-group--full">
            <label class="form-label">Photo</label>
            <div class="dropzone" id="gal-dropzone" onclick="document.getElementById('gal-file').click()">
              <p class="dropzone__text">Cliquez ou glissez une image ici (JPG, PNG, WebP · max 5 Mo)</p>
              <input type="file" id="gal-file" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewUpload(this)">
            </div>
            <div id="gal-preview" style="margin-top:.5rem"></div>
            <input type="hidden" id="gal-image-url">
          </div>
          <div class="form-group"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer"><input type="checkbox" id="gal-visible" checked> <span class="form-label" style="margin:0">Visible sur le site</span></label></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveGallery()">Ajouter</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      // Setup drag and drop
      setTimeout(() => setupDropzone('gal-dropzone', 'gal-file'), 50);
      break;
    case 'announcement':
      mc.innerHTML = `
        <p class="modal__title">Nouvelle annonce</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Titre</label><input class="form-input" id="ann-title" placeholder="Soirée Jazz ce vendredi"></div>
          <div class="form-group"><label class="form-label">Type</label><select class="form-select" id="ann-type"><option value="info">ℹ️ Info générale</option><option value="event">🎭 Événement</option><option value="promo">🎁 Promotion</option><option value="urgent">🔴 Urgent</option><option value="horaires">🕐 Horaires</option></select></div>
          <div class="form-group form-group--full"><label class="form-label">Contenu</label><textarea class="form-textarea" id="ann-content" placeholder="Le texte de votre annonce..."></textarea></div>
          <div class="form-group"><label class="form-label">Lien (optionnel)</label><input class="form-input" id="ann-link" placeholder="evenements.html"></div>
          <div class="form-group"><label class="form-label">Texte du lien</label><input class="form-input" id="ann-link-text" placeholder="En savoir plus"></div>
          <div class="form-group"><label class="form-label">Date d'expiration</label><input type="date" class="form-input" id="ann-expires"><small style="color:var(--text-dim);font-size:.65rem">Laisser vide = permanent</small></div>
          <div class="form-group"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer"><input type="checkbox" id="ann-active" checked> <span class="form-label" style="margin:0">Active immédiatement</span></label></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveAnnouncement()">Créer</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
    case 'finance':
      mc.innerHTML = `
        <p class="modal__title">Nouveau devis</p>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Client</label><input class="form-input" id="fin-client" placeholder="Nom du client"></div>
          <div class="form-group"><label class="form-label">Date événement</label><input type="date" class="form-input" id="fin-date-event"></div>
          <div class="form-group"><label class="form-label">Montant TTC (€)</label><input type="number" class="form-input" id="fin-amount" placeholder="2500"></div>
          <div class="form-group"><label class="form-label">Nombre d'invités</label><input type="number" class="form-input" id="fin-guests" placeholder="50"></div>
          <div class="form-group form-group--full"><label class="form-label">Description</label><textarea class="form-textarea" id="fin-desc" placeholder="Cocktail dînatoire 50 personnes..."></textarea></div>
          <div class="form-group form-group--full"><label class="form-label">Notes internes</label><input class="form-input" id="fin-notes" placeholder="Notes privées"></div>
        </div>
        <div class="modal__actions">
          <button class="btn btn--primary" onclick="saveFinance()">Créer</button>
          <button class="btn btn--ghost" onclick="closeModal()">Annuler</button>
        </div>`;
      break;
  }
  document.getElementById('modal-overlay').classList.add('visible');
}

function closeModal() { document.getElementById('modal-overlay').classList.remove('visible'); }

// ===== SAVE FUNCTIONS =====
async function saveEvent() {
  await api('events','POST',{
    title: document.getElementById('evt-title').value,
    date: document.getElementById('evt-date').value,
    time: document.getElementById('evt-time').value,
    type: document.getElementById('evt-type').value,
    description: document.getElementById('evt-desc').value,
  });
  closeModal(); loadEvents();
}

async function saveSocial() {
  await api('social','POST',{
    date: document.getElementById('soc-date').value,
    platform: document.getElementById('soc-platform').value,
    type: document.getElementById('soc-type').value,
    status: document.getElementById('soc-status').value,
    caption: document.getElementById('soc-caption').value,
    hashtags: document.getElementById('soc-hashtags').value,
  });
  closeModal(); loadSocial();
}

async function saveFinance() {
  await api('finances','POST',{
    client: document.getElementById('fin-client').value,
    date_event: document.getElementById('fin-date-event').value,
    amount: document.getElementById('fin-amount').value,
    guests: document.getElementById('fin-guests').value,
    description: document.getElementById('fin-desc').value,
    notes: document.getElementById('fin-notes').value,
  });
  closeModal(); loadFinances();
}

async function generatePost() {
  const d = await api('generate','POST',{
    type: document.getElementById('gen-type').value,
    name: document.getElementById('gen-name').value,
    description: document.getElementById('gen-desc').value,
  });
  document.getElementById('gen-output').textContent = d.caption || 'Erreur de génération';
}

function copyGenerated(e) {
  const text = document.getElementById('gen-output').textContent;
  const btn = e ? e.target : document.querySelector('.gen-actions .btn--ghost');
  navigator.clipboard.writeText(text).then(() => {
    if (btn) { btn.textContent = 'Copié !'; setTimeout(() => btn.textContent = 'Copier', 1500); }
  });
}

// ===== EDIT FUNCTIONS (simplified — reload with modal) =====
async function editEvent(id) {
  const d = await api('events');
  const e = (d.data||[]).find(x => x.id === id);
  if (!e) return;
  openModal('event');
  setTimeout(() => {
    document.getElementById('evt-title').value = e.title||'';
    document.getElementById('evt-date').value = e.date||'';
    document.getElementById('evt-time').value = e.time||'';
    document.getElementById('evt-type').value = e.type||'special';
    document.getElementById('evt-desc').value = e.description||'';
    // Override save to PATCH
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('events','PATCH',{id, title:document.getElementById('evt-title').value, date:document.getElementById('evt-date').value, time:document.getElementById('evt-time').value, type:document.getElementById('evt-type').value, description:document.getElementById('evt-desc').value});
      closeModal(); loadEvents();
    };
  }, 50);
}

async function editSocial(id) {
  const d = await api('social');
  const s = (d.data||[]).find(x => x.id === id);
  if (!s) return;
  openModal('social');
  setTimeout(() => {
    document.getElementById('soc-date').value = s.date||'';
    document.getElementById('soc-platform').value = s.platform||'instagram';
    document.getElementById('soc-type').value = s.type||'photo';
    document.getElementById('soc-status').value = s.status||'brouillon';
    document.getElementById('soc-caption').value = s.caption||'';
    document.getElementById('soc-hashtags').value = s.hashtags||'';
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('social','PATCH',{id, date:document.getElementById('soc-date').value, platform:document.getElementById('soc-platform').value, type:document.getElementById('soc-type').value, status:document.getElementById('soc-status').value, caption:document.getElementById('soc-caption').value, hashtags:document.getElementById('soc-hashtags').value});
      closeModal(); loadSocial();
    };
  }, 50);
}

async function editFinance(id) {
  const d = await api('finances');
  const f = (d.data||[]).find(x => x.id === id);
  if (!f) return;
  openModal('finance');
  setTimeout(() => {
    document.getElementById('fin-client').value = f.client||'';
    document.getElementById('fin-date-event').value = f.date_event||'';
    document.getElementById('fin-amount').value = f.amount||'';
    document.getElementById('fin-guests').value = f.guests||'';
    document.getElementById('fin-desc').value = f.description||'';
    document.getElementById('fin-notes').value = f.notes||'';
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('finances','PATCH',{id, client:document.getElementById('fin-client').value, date_event:document.getElementById('fin-date-event').value, amount:document.getElementById('fin-amount').value, guests:document.getElementById('fin-guests').value, description:document.getElementById('fin-desc').value, notes:document.getElementById('fin-notes').value, status:f.status});
      closeModal(); loadFinances();
    };
  }, 50);
}

// ===== BOUTIQUE =====
async function loadBoutique() {
  const d = await api('boutique');
  const cats = {cocktail:'Cocktail',accessoire:'Accessoire',vetement:'Vêtement',epicerie:'Épicerie',cadeau:'Coffret cadeau'};
  const el = document.getElementById('boutique-list');
  el.innerHTML = (d.data||[]).map(p => `
    <tr>
      <td>${statusBadge(p.status)}</td>
      <td>${p.image ? '<img src="'+p.image+'" style="width:40px;height:40px;object-fit:cover;border-radius:4px">' : '—'}</td>
      <td><strong>${p.name}</strong><br><small style="color:var(--text-dim)">${truncate(p.description,40)}</small></td>
      <td>${cats[p.category]||p.category}</td>
      <td>${parseFloat(p.price||0).toLocaleString('fr-FR')} €</td>
      <td>${p.stock ?? '—'}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editBoutique('${p.id}')">Modifier</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('boutique','${p.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="7"><div class="empty"><p class="empty__icon">🛍️</p><p class="empty__text">Aucun produit</p></div></td></tr>';
}

async function saveBoutique() {
  await api('boutique','POST',{
    name: document.getElementById('bq-name').value,
    category: document.getElementById('bq-category').value,
    price: document.getElementById('bq-price').value,
    stock: document.getElementById('bq-stock').value,
    description: document.getElementById('bq-desc').value,
    image: document.getElementById('bq-image').value,
    status: document.getElementById('bq-status').value,
  });
  closeModal(); loadBoutique();
}

async function editBoutique(id) {
  const d = await api('boutique');
  const p = (d.data||[]).find(x => x.id === id);
  if (!p) return;
  openModal('boutique');
  setTimeout(() => {
    document.getElementById('bq-name').value = p.name||'';
    document.getElementById('bq-category').value = p.category||'cocktail';
    document.getElementById('bq-price').value = p.price||'';
    document.getElementById('bq-stock').value = p.stock||'';
    document.getElementById('bq-desc').value = p.description||'';
    document.getElementById('bq-image').value = p.image||'';
    document.getElementById('bq-status').value = p.status||'actif';
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('boutique','PATCH',{id, name:document.getElementById('bq-name').value, category:document.getElementById('bq-category').value, price:document.getElementById('bq-price').value, stock:document.getElementById('bq-stock').value, description:document.getElementById('bq-desc').value, image:document.getElementById('bq-image').value, status:document.getElementById('bq-status').value});
      closeModal(); loadBoutique();
    };
  }, 50);
}

// ===== AVIS CLIENTS =====
async function loadReviews() {
  const d = await api('reviews');
  const stars = n => '★'.repeat(n) + '☆'.repeat(5-n);
  const el = document.getElementById('reviews-list');
  const reviews = d.data || [];
  const pending = reviews.filter(r => r.submitted_by === 'visiteur' && !r.visible);

  // Notification avis en attente de modération
  let pendingHtml = '';
  if (pending.length > 0) {
    pendingHtml = `<tr><td colspan="7" style="background:rgba(200,164,92,.1);padding:.6rem 1rem;font-size:.85rem;color:var(--or)">
      ${pending.length} avis de visiteur(s) en attente de validation — cliquez "Afficher" pour les publier
    </td></tr>`;
  }

  el.innerHTML = pendingHtml + reviews.map(r => {
    const isPending = r.submitted_by === 'visiteur' && !r.visible;
    return `
    <tr${isPending ? ' style="background:rgba(200,164,92,.05)"' : ''}>
      <td style="color:var(--or);font-size:1rem">${stars(r.rating||5)}</td>
      <td><strong>${r.client}</strong>${r.submitted_by === 'visiteur' ? ' <span style="font-size:.65rem;color:var(--or);border:1px solid var(--or);padding:.1rem .3rem;border-radius:3px">visiteur</span>' : ''}</td>
      <td>${truncate(r.comment,50)}</td>
      <td>${r.source||'—'}</td>
      <td>${fmtDate(r.date)}</td>
      <td>${r.visible ? '<span style="color:var(--green)">Oui</span>' : '<span style="color:var(--text-dim)">Non</span>'}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editReview('${r.id}')">Modifier</button>
        <button class="btn btn--sm btn--ghost" onclick="toggleReviewVisibility('${r.id}',${!r.visible})">${r.visible?'Masquer':'Afficher'}</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('reviews','${r.id}')">×</button>
      </td>
    </tr>`;
  }).join('') || '<tr><td colspan="7"><div class="empty"><p class="empty__icon">⭐</p><p class="empty__text">Aucun avis</p></div></td></tr>';
}

async function saveReview() {
  const result = await api('reviews','POST',{
    client: document.getElementById('rv-client').value,
    rating: document.getElementById('rv-rating').value,
    comment: document.getElementById('rv-comment').value,
    source: document.getElementById('rv-source').value,
    date: document.getElementById('rv-date').value,
    visible: document.getElementById('rv-visible').checked,
  });
  if (result.error) return;
  closeModal(); loadReviews();
}

async function editReview(id) {
  const d = await api('reviews');
  const r = (d.data||[]).find(x => x.id === id);
  if (!r) return;
  openModal('review');
  setTimeout(() => {
    document.getElementById('rv-client').value = r.client||'';
    document.getElementById('rv-rating').value = r.rating||5;
    document.getElementById('rv-comment').value = r.comment||'';
    document.getElementById('rv-source').value = r.source||'google';
    document.getElementById('rv-date').value = r.date||'';
    document.getElementById('rv-visible').checked = r.visible !== false;
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('reviews','PATCH',{id, client:document.getElementById('rv-client').value, rating:document.getElementById('rv-rating').value, comment:document.getElementById('rv-comment').value, source:document.getElementById('rv-source').value, date:document.getElementById('rv-date').value, visible:document.getElementById('rv-visible').checked});
      closeModal(); loadReviews();
    };
  }, 50);
}

async function toggleReviewVisibility(id, visible) {
  await api('reviews','PATCH',{id, visible});
  loadReviews();
}

// ===== OBSERVATIONS =====
async function loadObservations() {
  const d = await api('observations');
  const priorities = {haute:'<span style="color:var(--red)">● Haute</span>',moyenne:'<span style="color:var(--orange)">● Moyenne</span>',basse:'<span style="color:var(--green)">● Basse</span>'};
  const el = document.getElementById('observations-list');
  el.innerHTML = (d.data||[]).map(o => `
    <tr>
      <td>${priorities[o.priority]||o.priority}</td>
      <td>${o.category||'—'}</td>
      <td><strong>${truncate(o.note,60)}</strong></td>
      <td>${fmtDate(o.created)}</td>
      <td>${statusBadge(o.status)}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editObservation('${o.id}')">Modifier</button>
        ${o.status!=='fait'?'<button class="btn btn--sm btn--ghost" onclick="completeObservation(\''+o.id+'\')">✓ Fait</button>':''}
        <button class="btn btn--sm btn--danger" onclick="deleteItem('observations','${o.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="6"><div class="empty"><p class="empty__icon">📋</p><p class="empty__text">Aucune observation</p></div></td></tr>';
}

async function saveObservation() {
  await api('observations','POST',{
    note: document.getElementById('obs-note').value,
    category: document.getElementById('obs-category').value,
    priority: document.getElementById('obs-priority').value,
  });
  closeModal(); loadObservations();
}

async function editObservation(id) {
  const d = await api('observations');
  const o = (d.data||[]).find(x => x.id === id);
  if (!o) return;
  openModal('observation');
  setTimeout(() => {
    document.getElementById('obs-note').value = o.note||'';
    document.getElementById('obs-category').value = o.category||'general';
    document.getElementById('obs-priority').value = o.priority||'moyenne';
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('observations','PATCH',{id, note:document.getElementById('obs-note').value, category:document.getElementById('obs-category').value, priority:document.getElementById('obs-priority').value});
      closeModal(); loadObservations();
    };
  }, 50);
}

async function completeObservation(id) {
  await api('observations','PATCH',{id, status:'fait'});
  loadObservations();
}

// ===== GALERIE =====
async function loadGallery() {
  const d = await api('gallery');
  const el = document.getElementById('gallery-grid');
  const cats = {ambiance:'Ambiance',cocktails:'Cocktails',tapas:'Tapas',decor:'Décor',events:'Événements',equipe:'Équipe'};
  // Note: data is already htmlspecialchars'd server-side via sanitize()
  el.innerHTML = (d.data||[]).map(p => `
    <div class="gallery-card">
      ${p.image ? '<img src="/'+p.image+'" alt="'+p.title+'">' : '<div class="gallery-card__placeholder">Pas d\'image</div>'}
      <span class="gallery-card__badge ${p.visible?'gallery-card__badge--visible':'gallery-card__badge--hidden'}">${p.visible?'Visible':'Masqué'}</span>
      <div class="gallery-card__actions">
        <button class="btn btn--sm btn--ghost" onclick="editGallery('${p.id}')" style="background:rgba(0,0,0,.6)">Modifier</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('gallery','${p.id}')" style="background:rgba(0,0,0,.6)">×</button>
      </div>
      <div class="gallery-card__overlay">
        <p class="gallery-card__title">${p.title}</p>
        <p class="gallery-card__caption">${cats[p.category]||p.category} ${p.caption ? '· '+p.caption : ''}</p>
      </div>
    </div>
  `).join('') || '<div style="grid-column:1/-1;text-align:center;padding:3rem"><p style="font-size:1.5rem;margin-bottom:.5rem">🖼️</p><p style="color:var(--text-dim)">Aucune photo dans la galerie</p></div>';
}

async function saveGallery() {
  // Upload image first if present
  const fileInput = document.getElementById('gal-file');
  let imageUrl = document.getElementById('gal-image-url').value;

  if (fileInput && fileInput.files.length > 0) {
    const formData = new FormData();
    formData.append('image', fileInput.files[0]);
    formData.append('csrf_token', CSRF_TOKEN);
    const uploadRes = await fetch(API + '?action=upload', {method:'POST', body: formData});
    const uploadData = await uploadRes.json();
    if (uploadData.success) {
      imageUrl = uploadData.url;
    } else {
      alert('Erreur upload: ' + (uploadData.error || 'Erreur inconnue'));
      return;
    }
  }

  await api('gallery','POST',{
    title: document.getElementById('gal-title').value,
    caption: document.getElementById('gal-caption').value,
    category: document.getElementById('gal-category').value,
    image: imageUrl,
    visible: document.getElementById('gal-visible').checked,
  });
  closeModal(); loadGallery();
}

async function editGallery(id) {
  const d = await api('gallery');
  const p = (d.data||[]).find(x => x.id === id);
  if (!p) return;
  openModal('gallery');
  setTimeout(() => {
    document.getElementById('gal-title').value = p.title||'';
    document.getElementById('gal-caption').value = p.caption||'';
    document.getElementById('gal-category').value = p.category||'ambiance';
    document.getElementById('gal-image-url').value = p.image||'';
    document.getElementById('gal-visible').checked = p.visible !== false;
    if (p.image) {
      document.getElementById('gal-preview').innerHTML = '<img src="/'+p.image+'" style="max-height:120px;border-radius:4px">';
    }
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      const fileInput = document.getElementById('gal-file');
      let imageUrl = document.getElementById('gal-image-url').value;
      if (fileInput && fileInput.files.length > 0) {
        const formData = new FormData();
        formData.append('image', fileInput.files[0]);
        formData.append('csrf_token', CSRF_TOKEN);
        const uploadRes = await fetch(API + '?action=upload', {method:'POST', body: formData});
        const uploadData = await uploadRes.json();
        if (uploadData.success) imageUrl = uploadData.url;
      }
      await api('gallery','PATCH',{id, title:document.getElementById('gal-title').value, caption:document.getElementById('gal-caption').value, category:document.getElementById('gal-category').value, image:imageUrl, visible:document.getElementById('gal-visible').checked});
      closeModal(); loadGallery();
    };
  }, 50);
}

// ===== ANNONCES =====
async function loadAnnouncements() {
  const d = await api('announcements');
  const types = {info:'ℹ️ Info',event:'🎭 Événement',promo:'🎁 Promo',urgent:'🔴 Urgent',horaires:'🕐 Horaires'};
  const el = document.getElementById('announcements-list');
  // Note: data is already htmlspecialchars'd server-side via sanitize()
  el.innerHTML = (d.data||[]).map(a => `
    <tr>
      <td>${a.active ? '<span class="badge badge--confirmed">Active</span>' : '<span class="badge badge--draft">Inactive</span>'}</td>
      <td>${types[a.type]||a.type}</td>
      <td><strong>${a.title}</strong></td>
      <td>${truncate(a.content,50)}</td>
      <td>${a.expires ? fmtDate(a.expires) : '<span style="color:var(--text-dim)">Permanent</span>'}</td>
      <td class="btn-group">
        <button class="btn btn--sm btn--ghost" onclick="editAnnouncement('${a.id}')">Modifier</button>
        <button class="btn btn--sm btn--ghost" onclick="toggleAnnouncement('${a.id}',${!a.active})">${a.active?'Désactiver':'Activer'}</button>
        <button class="btn btn--sm btn--danger" onclick="deleteItem('announcements','${a.id}')">×</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="6"><div class="empty"><p class="empty__icon">📢</p><p class="empty__text">Aucune annonce</p></div></td></tr>';
}

async function saveAnnouncement() {
  await api('announcements','POST',{
    title: document.getElementById('ann-title').value,
    content: document.getElementById('ann-content').value,
    type: document.getElementById('ann-type').value,
    link: document.getElementById('ann-link').value,
    link_text: document.getElementById('ann-link-text').value,
    expires: document.getElementById('ann-expires').value,
    active: document.getElementById('ann-active').checked,
  });
  closeModal(); loadAnnouncements();
}

async function editAnnouncement(id) {
  const d = await api('announcements');
  const a = (d.data||[]).find(x => x.id === id);
  if (!a) return;
  openModal('announcement');
  setTimeout(() => {
    document.getElementById('ann-title').value = a.title||'';
    document.getElementById('ann-content').value = a.content||'';
    document.getElementById('ann-type').value = a.type||'info';
    document.getElementById('ann-link').value = a.link||'';
    document.getElementById('ann-link-text').value = a.link_text||'';
    document.getElementById('ann-expires').value = a.expires||'';
    document.getElementById('ann-active').checked = a.active !== false;
    document.querySelector('#modal-content .btn--primary').onclick = async () => {
      await api('announcements','PATCH',{id, title:document.getElementById('ann-title').value, content:document.getElementById('ann-content').value, type:document.getElementById('ann-type').value, link:document.getElementById('ann-link').value, link_text:document.getElementById('ann-link-text').value, expires:document.getElementById('ann-expires').value, active:document.getElementById('ann-active').checked});
      closeModal(); loadAnnouncements();
    };
  }, 50);
}

async function toggleAnnouncement(id, active) {
  await api('announcements','PATCH',{id, active});
  loadAnnouncements();
}

// ===== CARTE / MENU =====
let carteData = [];

async function loadCarte() {
  const d = await api('carte');
  carteData = d.data || [];
  renderCarte();
}

function renderCarte() {
  const editor = document.getElementById('carte-editor');
  if (!carteData.length) {
    editor.innerHTML = '<div class="empty"><p class="empty__text">Aucune catégorie dans la carte</p></div>';
    return;
  }
  editor.innerHTML = carteData.map(cat => {
    const items = cat.items || [];
    const subcats = cat.subcategories || [];
    let itemsHtml = '';

    if (subcats.length) {
      itemsHtml = subcats.map(sc => `
        <p style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin:1rem 0 .5rem;padding-top:.5rem;border-top:1px solid rgba(200,164,92,.1)">${esc(sc.name)}</p>
        ${(sc.items||[]).map(item => renderCarteItem(cat.id, item)).join('')}
      `).join('');
    } else {
      itemsHtml = items.map(item => renderCarteItem(cat.id, item)).join('');
    }

    return `
    <div style="background:rgba(0,0,0,.2);border:1px solid rgba(200,164,92,.12);padding:1rem;margin-bottom:1rem;border-radius:6px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
        <div>
          <h3 style="font-family:var(--font-display);color:var(--gold);font-size:1.1rem;margin:0">${esc(cat.category)}</h3>
          <span style="font-size:.7rem;color:var(--text-dim)">${esc(cat.subtitle || '')} ${cat.price_label ? '· ' + esc(cat.price_label) : ''}</span>
        </div>
        <button class="btn-action" onclick="openCarteItemModal('${cat.id}')">+ Plat</button>
      </div>
      ${itemsHtml || '<p style="font-size:.8rem;color:var(--text-dim);font-style:italic">Aucun plat dans cette catégorie</p>'}
    </div>`;
  }).join('');
}

function renderCarteItem(catId, item) {
  return `<div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.04);gap:.5rem">
    <div style="flex:1;min-width:0">
      <span style="font-size:.9rem;color:var(--creme)">${esc(item.name)}</span>
      <span style="font-size:.75rem;color:var(--text-dim);margin-left:.5rem">${esc(item.description || '')}</span>
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0">
      <strong style="color:var(--gold);font-size:.9rem">${esc(item.price)} €</strong>
      <button class="btn-action" onclick="editCarteItem('${catId}','${item.id}')" style="font-size:.6rem">Modifier</button>
      <button class="btn-action btn-action--danger" onclick="deleteCarteItem('${catId}','${item.id}')" style="font-size:.6rem">Suppr</button>
    </div>
  </div>`;
}

function openCarteItemModal(catId) {
  const catOptions = carteData.map(c => `<option value="${c.id}" ${c.id === catId ? 'selected' : ''}>${esc(c.category)}</option>`).join('');
  document.getElementById('modal-content').innerHTML = `
    <h2 class="modal__title">Ajouter un plat</h2>
    <div class="form-group"><label class="form-label">Catégorie</label><select class="form-input" id="ci-cat">${catOptions}</select></div>
    <div class="form-group"><label class="form-label">Nom</label><input class="form-input" id="ci-name" placeholder="Nom du plat"></div>
    <div class="form-group"><label class="form-label">Prix (€)</label><input class="form-input" id="ci-price" type="number" step="0.5" placeholder="10"></div>
    <div class="form-group"><label class="form-label">Description / Ingrédients</label><input class="form-input" id="ci-desc" placeholder="Gin, citron, miel..."></div>
    <div class="form-group"><label class="form-label">Accroche (optionnel)</label><input class="form-input" id="ci-accent" placeholder="Le cocktail du Sud"></div>
    <div class="modal__actions"><button class="btn btn--primary" onclick="saveNewCarteItem()">Ajouter</button><button class="btn" onclick="closeModal()">Annuler</button></div>
  `;
  openModal();
}

async function saveNewCarteItem() {
  const catId = document.getElementById('ci-cat').value;
  const item = {
    name: document.getElementById('ci-name').value,
    price: document.getElementById('ci-price').value,
    description: document.getElementById('ci-desc').value,
    accent: document.getElementById('ci-accent').value || undefined,
  };
  if (!item.name || !item.price) { alert('Nom et prix requis'); return; }
  await api('carte', 'PATCH', { category_id: catId, type: 'add', item });
  closeModal();
  loadCarte();
}

function editCarteItem(catId, itemId) {
  let item = null;
  for (const cat of carteData) {
    if (cat.id === catId) {
      item = (cat.items || []).find(i => i.id === itemId);
      if (!item && cat.subcategories) {
        for (const sc of cat.subcategories) {
          item = (sc.items || []).find(i => i.id === itemId);
          if (item) break;
        }
      }
      break;
    }
  }
  if (!item) return;

  document.getElementById('modal-content').innerHTML = `
    <h2 class="modal__title">Modifier — ${esc(item.name)}</h2>
    <div class="form-group"><label class="form-label">Nom</label><input class="form-input" id="ci-name" value="${esc(item.name)}"></div>
    <div class="form-group"><label class="form-label">Prix (€)</label><input class="form-input" id="ci-price" type="number" step="0.5" value="${esc(item.price)}"></div>
    <div class="form-group"><label class="form-label">Description</label><input class="form-input" id="ci-desc" value="${esc(item.description || '')}"></div>
    <div class="form-group"><label class="form-label">Accroche</label><input class="form-input" id="ci-accent" value="${esc(item.accent || '')}"></div>
    <div class="modal__actions"><button class="btn btn--primary" onclick="updateCarteItem('${catId}','${itemId}')">Sauvegarder</button><button class="btn" onclick="closeModal()">Annuler</button></div>
  `;
  openModal();
}

async function updateCarteItem(catId, itemId) {
  const item = {
    name: document.getElementById('ci-name').value,
    price: document.getElementById('ci-price').value,
    description: document.getElementById('ci-desc').value,
    accent: document.getElementById('ci-accent').value || undefined,
  };
  await api('carte', 'PATCH', { category_id: catId, type: 'update', item_id: itemId, item });
  closeModal();
  loadCarte();
}

async function deleteCarteItem(catId, itemId) {
  if (!confirm('Supprimer ce plat ?')) return;
  await api('carte', 'PATCH', { category_id: catId, type: 'delete', item_id: itemId });
  loadCarte();
}

// ===== NEWSLETTER =====
async function loadNewsletter() {
  const d = await api('newsletter');
  const list = d.data || d || [];
  document.getElementById('nl-count').textContent = list.length + ' abonné' + (list.length > 1 ? 's' : '');
  const tbody = document.getElementById('newsletter-list');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="empty"><p class="empty__text">Aucun abonné pour le moment</p></td></tr>';
    return;
  }
  tbody.innerHTML = list.map(s => `<tr>
    <td>${esc(s.email)}</td>
    <td>${fmtDateTime(s.subscribed)}</td>
    <td>${s.active ? '<span class="badge badge--confirmed">Actif</span>' : '<span class="badge badge--cancelled">Désabonné</span>'}</td>
    <td><button class="btn-action btn-action--danger" onclick="removeSubscriber('${s.id}')">Supprimer</button></td>
  </tr>`).join('');
}

async function removeSubscriber(id) {
  if (!confirm('Supprimer cet abonné ?')) return;
  await api('newsletter','DELETE',{id});
  loadNewsletter();
}

function exportNewsletter() {
  const rows = document.querySelectorAll('#newsletter-list tr');
  if (!rows.length) { alert('Aucun abonné à exporter.'); return; }
  let csv = 'Email,Date inscription,Statut\n';
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    if (cells.length >= 3) {
      csv += '"' + cells[0].textContent + '","' + cells[1].textContent + '","' + cells[2].textContent.trim() + '"\n';
    }
  });
  const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = 'newsletter-le-terrier.csv'; a.click();
  URL.revokeObjectURL(url);
}

// ===== UPLOAD HELPERS =====
function setupDropzone(dropzoneId, fileInputId) {
  const dz = document.getElementById(dropzoneId);
  if (!dz) return;
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
  dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('dragover');
    const fi = document.getElementById(fileInputId);
    if (e.dataTransfer.files.length && fi) {
      fi.files = e.dataTransfer.files;
      previewUpload(fi);
    }
  });
}

function previewUpload(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  if (file.size > 5 * 1024 * 1024) { alert('Fichier trop volumineux (max 5 Mo)'); input.value = ''; return; }
  const reader = new FileReader();
  reader.onload = (e) => {
    const preview = document.getElementById('gal-preview');
    if (preview) preview.innerHTML = '<img src="'+e.target.result+'" style="max-height:120px;border-radius:4px;margin-top:.5rem"><p style="font-size:.65rem;color:var(--text-dim);margin-top:.3rem">'+esc(file.name)+' ('+Math.round(file.size/1024)+' Ko)</p>';
    const dz = document.getElementById('gal-dropzone');
    if (dz) dz.querySelector('.dropzone__text').textContent = 'Image sélectionnée : ' + file.name;
  };
  reader.readAsDataURL(file);
}

// ===== KEYBOARD SHORTCUT =====
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ===== INIT =====
loadOverview();
</script>
</body>
</html>
