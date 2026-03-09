# Le Terrier — Carte de synchronisation Dashboard ↔ Site

> Ce fichier documente la synchronisation entre le dashboard admin (index.php) et les pages publiques du site. Mis a jour le 2026-03-09.

## Architecture

```
Dashboard (index.php) → API (api.php) → JSON (data/*.json) ← Pages publiques (*.html via shared.js)
```

## Sections synchronisees

| Section | Fichier JSON | API publique | API admin | Page(s) publique(s) | Chargement | Statut |
|---------|-------------|-------------|-----------|-------------------|------------|--------|
| Evenements | `data/events.json` | `public-events` | `events` | `index.html`, `evenements.html` | `shared.js` → `loadPublicEvents()` | OK |
| Avis clients | `data/reviews.json` | `public-reviews` | `reviews` | `index.html` | `shared.js` → reviews section | OK |
| Annonces (Ardoise) | `data/announcements.json` | `public-announcements` | `announcements` | `index.html` | `shared.js` → ardoise section | OK |
| Galerie | `data/gallery.json` | `public-gallery` | `gallery` | `galerie.html` | `galerie.html` inline JS | OK |
| Newsletter | `data/newsletter.json` | `subscribe-newsletter` | `newsletter` | Toutes pages | `shared.js` → newsletter form | OK |
| Carte / Menu | `data/carte.json` | `public-carte` | `carte` | `carte.html` | `carte.html` inline JS | OK |
| Journal du Terrier | `data/journal.json` | `public-journal` | `journal` | `index.html` | `shared.js` → journal section | OK |
| Bandeau annonce | `data/banner.json` | `public-banner` | `banner` | Toutes pages (`.announce`) | `shared.js` → announce banner | OK |

## Sections admin uniquement (pas de page publique)

| Section | Fichier JSON | API admin | Notes |
|---------|-------------|-----------|-------|
| Messages | `data/messages.json` | `messages` | Contact form → messages (write-only pour le public) |
| Reservations | `data/reservations.json` | `reservations` | Bookings par tel/Instagram/email |
| Reseaux sociaux | `data/social.json` | `social` | Calendrier editorial interne |
| Finances | `data/finances.json` | `finances` | Devis et facturation interne |
| Observations | `data/observations.json` | `observations` | Notes internes |
| Boutique | `data/boutique.json` | `boutique` | Pas encore lance (placeholder) |

## Flux de donnees detaille

### Comment ca marche
1. L'admin modifie les donnees dans le dashboard (`index.php`)
2. Le dashboard appelle `api.php?action=<section>` (POST/PATCH/DELETE)
3. L'API sauvegarde dans `data/<section>.json`
4. Les pages publiques chargent via `api.php?action=public-<section>` (GET, sans auth)
5. L'API filtre (actif, dates, visible) et trie avant de repondre

### Points d'entree publics (sans auth)
- `GET /api.php?action=public-carte` → menu complet
- `GET /api.php?action=public-events` → evenements actifs futurs
- `GET /api.php?action=public-gallery` → photos visibles
- `GET /api.php?action=public-announcements` → annonces actives non expirees
- `GET /api.php?action=public-reviews` → avis visibles + moyenne
- `GET /api.php?action=public-journal` → 3 derniers articles actifs
- `GET /api.php?action=public-banner` → texte et statut du bandeau
- `POST /api.php?action=submit-review` → soumission avis visiteur
- `POST /api.php?action=subscribe-newsletter` → inscription newsletter

## Fichiers cles

| Fichier | Role |
|---------|------|
| `index.php` | Dashboard admin (HTML + JS) |
| `api.php` | API REST (endpoints publics + admin) |
| `shared.js` | JS partage pour toutes les pages publiques |
| `data/*.json` | Stockage des donnees |
| `config.php` | Configuration (auth, chemins) |

## Fonctionnalites transversales

### Dictee vocale (Web Speech API)
- Disponible sur tous les champs texte du dashboard via bouton "Dicter"
- Utilise `window.SpeechRecognition` (Chrome, Edge, Safari)
- Gratuit, pas d'API externe, fonctionne en francais (`fr-FR`)

### Securite
- Endpoints admin : session PHP + token CSRF
- Endpoints publics : lecture seule (GET) sauf submit-review et subscribe-newsletter
- XSS : `sanitize()` cote serveur + `esc()` cote client
- Rate limiting : 3 avis par IP par heure
- Honeypot anti-spam sur formulaires publics
