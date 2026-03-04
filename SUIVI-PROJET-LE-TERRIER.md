# Le Terrier — Suivi du Projet Web
**Dernière mise à jour : 28 février 2026**

---

## 1. Inventaire des fichiers du site

| Fichier | Rôle | Modifié le |
|---------|------|------------|
| `index.html` | Accueil — hero, teaser, avis, FAQ Schema (15 questions) | 28/02/2026 |
| `carte.html` | Carte complète — formules, cocktails, tapas, vins, spiritueux | 28/02/2026 |
| `concept.html` | Histoire, espaces L'Antre & Le Perchoir | 28/02/2026 |
| `contact.html` | Formulaire résa, coordonnées, Google Maps | 28/02/2026 |
| `evenements.html` | Jazz, Vendredis Vins, DJ, privatisations + Schema Event | 28/02/2026 |
| `galerie.html` | Photos d'ambiance (actuellement placeholders) | 28/02/2026 |
| `boutique.html` | Réservations événements, produits terroir (en construction) | 28/02/2026 |
| `mentions-legales.html` | Infos légales obligatoires | 28/02/2026 |
| `politique-confidentialite.html` | RGPD, cookies, données | 28/02/2026 |
| `styles.css` | Feuille de style principale (source, éditable) | 28/02/2026 |
| `styles.min.css` | Version minifiée (optionnelle, pour production) | 28/02/2026 |
| `shared.js` | JavaScript principal (source, éditable) | 28/02/2026 |
| `shared.min.js` | Version minifiée (optionnelle, pour production) | 28/02/2026 |
| `favicon.svg` | Icône onglet navigateur (monogramme LT bordeaux/or) | 28/02/2026 |
| `robots.txt` | Instructions crawlers (Google, Bing, IA) | 28/02/2026 |
| `sitemap.xml` | Plan du site pour Google Search Console | 28/02/2026 |
| `llms.txt` | Source de vérité pour les IA (Perplexity, ChatGPT, etc.) | 28/02/2026 |
| `llms-full.txt` | Version enrichie pour les IA (storytelling + FAQ) | 28/02/2026 |

**Note :** Les HTML pointent vers `styles.css` et `shared.js` (les fichiers sources lisibles). Les versions `.min.css` / `.min.js` sont fournies en option — pour les utiliser, remplacer les références dans les HTML.

---

## 2. Informations de référence

### Coordonnées (intégrées partout)
- **Adresse** : 26 place de la République, 30920 Codognan
- **Téléphone** : 07 63 51 93 63
- **Email** : barleterrier@gmail.com
- **Domaine** : barleterrier.fr

### Horaires
- Mercredi → Dimanche : 17h – 00h
- Fermé lundi et mardi

### Réseaux sociaux
- Instagram : `#` ← **À REMPLACER** par l'URL réelle
- Facebook : `#` ← **À REMPLACER** par l'URL réelle

---

## 3. Carte actuelle (synchronisée avec le site)

### Formules — "Les Rituels"
| Formule | Prix | Contenu |
|---------|------|---------|
| L'Entrée du Terrier | **23 €** | 3 tapas + 1 cocktail |
| La Tanière ★ | **40 €** | 5 tapas + 2 cocktails |
| Le Festin du Renard | **55 €** | 7 tapas + 2 cocktails + 1 planche (pour deux) |

*Tapas au choix parmi Petites Tapas et Tapas uniquement (pas les Signatures).*

### Cocktails Signatures — 10 €
| Nom | Composition |
|-----|-------------|
| Le Terrier | Gin, miel de garrigue, thym frais, citron, tonic artisanal |
| L'Embuscade | Mezcal, pamplemousse rose, sirop de romarin, piment d'Espelette |
| Nuit Fauve | Rhum ambré, passion, lait de coco, vanille, zeste de combava |
| Le Refuge | Bourbon, sirop d'érable, bitter orange, cerise amarena, zeste fumé |
| Brume d'Été | Vodka, concombre, basilic frais, citron vert, fleur de sureau |
| Terre Rouge | Tequila reposado, betterave, gingembre, agave, citron |

### Classiques Revisités — 9 €
| Nom | Composition |
|-----|-------------|
| Spritz des Garrigues | Aperol, prosecco, lavande, olive verte |
| Negroni du Sud | Gin, Campari, vermouth rouge infusé au romarin |
| Mojito Sauvage | Rhum blanc, menthe sauvage, citron vert, sirop thym-citron |
| Paloma Rosée | Tequila, pamplemousse, hibiscus, sel de Camargue |

### Sans Alcool — 7 €
| Nom | Composition |
|-----|-------------|
| Le Doux Terrier | Tonic artisanal, miel, thym, citron, ginger beer |
| Jardin Secret | Concombre, basilic, pomme verte, citron vert, eau pétillante |
| Flamme Douce | Passion, mangue, gingembre, piment doux, lait de coco |

### Shooters — 4 €
| Nom | Composition |
|-----|-------------|
| L'Orgasme | Crème de menthe, vanille, tequila, Baileys |
| Kamikaze | Vodka, citron vert, Cointreau |
| Madeleine | Amaretto, jus d'ananas, triple sec |
| B-52 | Kahlúa, Baileys, Grand Marnier — en couches |
| Tequila Paf | Tequila, tonic — claqué au comptoir |

### Petites Tapas — 5-6 €
| Plat | Prix |
|------|------|
| Olives Cassées & Amandes Grillées | 5 € |
| Verrines Gaspacho & Chèvre | 5 € |
| Pan con Tomate & Anchois de Collioure | 6 € |
| Croquetas au Jambon Ibérique (x3) | 6 € |
| Piquillos Farcis à la Brandade | 6 € |
| Œuf Parfait, Parmesan & Truffe | 6 € |

### Tapas — 8-10 €
| Plat | Prix |
|------|------|
| Calamars Frits, Aïoli Safrané | 8 € |
| Burrata, Tomates Confites & Pistache | 9 € |
| Champignons à la Plancha | 9 € |
| Tataki de Thon Rouge (~120 g) | 10 € |
| Gambas à la Plancha (4 pièces) | 10 € |
| Tataki de Bœuf de l'Aubrac (~120 g) | 10 € |

### Tapas Signatures — 12-16 €
| Plat | Prix |
|------|------|
| Planche de Fromages Affinés (~200 g, 5 fromages) | 12 € |
| Tartare de Bœuf Aubrac au Couteau (~150 g) | 14 € |
| Poulpe Grillé (basse température, ~160 g) | 15 € |
| Planche Ibérique "Grand Cru" (~180 g) | 16 € |

### La Grande Tablée — 22 €
Planche signature à partager : croquetas, charcuterie ibérique, burrata, piquillos, olives, pan con tomate & gambas. Incluse dans "Le Festin du Renard".

### Spiritueux — 4 cl
**Whiskies** : Johnnie Walker Black Label (8 €), Glenlivet 12 (9 €), Ardbeg 10 (10 €), Balvenie DoubleWood 12 (10 €), Laphroaig 10 (10 €), Lagavulin 16 (12 €)

**Rhums** : Doorly's 12 (9 €), Clairin Sajous (9 €), Hampden Estate 8 (10 €), El Dorado 12 (10 €), Appleton Estate 12 (10 €), J.M XO (12 €)

### Vins au verre
| Vin | Couleur | Prix | Contenance |
|-----|---------|------|------------|
| Picpoul de Pinet | Blanc | 5 € | 12 cl |
| Costières de Nîmes | Blanc | 6 € | 12 cl |
| Costières de Nîmes | Rosé | 5 € | 12 cl |
| Tavel | Rosé | 6 € | 12 cl |
| Costières de Nîmes | Rouge | 6 € | 12 cl |
| Pic Saint-Loup | Rouge | 7 € | 12 cl |
| Terrasses du Larzac | Rouge | 8 € | 10 cl |
| Châteauneuf-du-Pape | Rouge | 9 € | 10 cl |

---

## 4. Ce qui reste à brancher

### Priorité haute (avant ouverture)
| Action | Fichiers concernés | Comment faire |
|--------|--------------------|---------------|
| **Brevo newsletter** | Tous les HTML (9 fichiers) | Créer un compte Brevo → créer un formulaire → copier l'URL → Ctrl+H remplacer `#BREVO_FORM_URL` dans tous les HTML |
| **Google Analytics** | `shared.js` | Créer un compte GA4 → copier l'ID (G-XXXXXXXXXX) → remplacer `GA_MEASUREMENT_ID` dans shared.js |
| **Google Search Console** | — | Ajouter barleterrier.fr → vérifier via DNS ou fichier HTML → soumettre sitemap.xml |
| **Google Business Profile** | — | Créer la fiche → ajouter photos, horaires, menu → lier au site |
| **Instagram/Facebook** | Tous les HTML (liens `#` dans le footer social) | Remplacer les `href="#"` par les vraies URLs dans la section `footer__social` |
| **Formulaire de réservation** | `contact.html` | Connecter le form à Formspree, Netlify Forms, ou un backend |
| **Google Maps** | `contact.html` | Vérifier l'embed — aller sur Google Maps → rechercher l'adresse → Partager → Intégrer → copier l'iframe |
| **og:image** | Tous les HTML | Créer une image 1200x630px → upload → décommenter les lignes og:image et twitter:image |

### Priorité moyenne (premières semaines)
| Action | Comment |
|--------|---------|
| Photos réelles | Remplacer les placeholders dans galerie.html + ajouter aux pages |
| favicon.ico | Convertir le favicon.svg en .ico 32x32 pour les vieux navigateurs |
| Certificat SSL | Configurer HTTPS sur l'hébergement (obligatoire pour le SEO) |
| Headers HTTP | Configurer CSP, HSTS, X-Content-Type-Options côté serveur |

---

## 5. Journal des modifications

### 1er mars 2026 — V3 : Dashboard + Sécurité OVH

**Dashboard admin (admin/) :**
- Interface complète accessible à barleterrier.fr/admin/
- Authentification par mot de passe (PHP sessions, 8h)
- 7 sections : Vue d'ensemble, Messages, Réservations, Événements, Réseaux sociaux, Finances, Espace libre
- API REST JSON (admin/api.php) — CRUD complet sur toutes les entités
- Stockage en fichiers JSON (pas de base de données nécessaire)
- Générateur de posts Instagram/Facebook avec templates métier
- Suivi financier avec références automatiques (DEV-YYYMM-XXX)
- Design cohérent avec la charte Le Terrier (bordeaux/or/crème)

**Formulaire de contact connecté :**
- Soumission AJAX vers api/contact.php
- Email HTML formaté envoyé à barleterrier@gmail.com
- Message stocké dans le dashboard (admin/data/messages.json)
- Honeypot anti-spam + rate limiting (1 msg / 60 sec par IP)
- Champ heure ajouté pour les réservations
- Affichage erreurs/succès côté client

**API réservation :**
- Endpoint api/reservation.php prêt pour un futur formulaire dédié
- Email de notification + stockage dashboard
- Rate limiting 2 minutes par IP

**Sécurité OVH (.htaccess) :**
- HTTPS forcé (redirection 301)
- Content-Security-Policy (CSP) — whitelist Google Fonts, Maps, GA4, Brevo
- X-Frame-Options, X-XSS-Protection, X-Content-Type-Options
- Referrer-Policy strict
- Permissions-Policy (caméra/micro/GPS désactivés)
- HSTS prêt (à activer après vérification HTTPS)
- Compression Gzip
- Cache navigateur (HTML 1h, CSS/JS 1 mois, images 1 an)
- URLs propres (/carte au lieu de /carte.html)
- Anti-hotlinking images
- Blocage bots SEO malveillants
- Protection fichiers sensibles (.htaccess, .md)

**Page 404 personnalisée :**
- Design speakeasy cohérent ("Ce passage est fermé")
- Liens vers carte, contact, événements

**Identifiants par défaut du dashboard :**
- Login : admin
- Mot de passe : LeTerrierAdmin2026!
- ⚠️ À CHANGER IMMÉDIATEMENT après déploiement (dans admin/config.php)

### 28 février 2026 — V2 complète

**Carte mise à jour :**
- Ajout : 6 Cocktails Signatures à 10 € (Le Terrier, L'Embuscade, Nuit Fauve, Le Refuge, Brume d'Été, Terre Rouge)
- Ajout : 4 Classiques Revisités à 9 € (Spritz des Garrigues, Negroni du Sud, Mojito Sauvage, Paloma Rosée)
- Ajout : 3 Sans Alcool à 7 € (Le Doux Terrier, Jardin Secret, Flamme Douce)
- Ajout : 4 Tapas Signatures 12-16 € (nouveau tier)
- Ajout : La Grande Tablée à 22 €
- Correction : Formule L'Entrée du Terrier 25 € → 23 €
- Conservation : Shooters (5 items à 4 €), Spiritueux, Vins
- Mise à jour : Petites Tapas et Tapas alignées sur le brief
- Navigation sticky carte mise à jour

**Technique :**
- Domaine : leterrier.bar → barleterrier.fr (tous fichiers)
- Encodage : correction caractères cassés (flèche, étoiles, tirets) sur 5 pages
- Cookie banner : harmonisé sur toutes les pages (mêmes classes CSS)
- Balise `<main>` + skip-to-content : ajouté sur les 9 pages
- Preloader : 800ms → 400ms
- Script defer : ajouté sur carte.html
- Newsletter Brevo : formulaire + honeypot + mention RGPD sur les 9 pages
- Favicon SVG : créé (monogramme LT bordeaux/or)
- Google Maps : intégré sur contact.html
- GA4 : prêt, chargement conditionnel au consentement cookies

**SEO :**
- FAQ Schema : 5 → 15 questions (index.html)
- Schema Menu : synchronisé avec nouvelle carte (carte.html + index.html)
- Schema EventSeries : ajouté (evenements.html)
- Schema BreadcrumbList : ajouté sur 8 sous-pages
- Twitter Cards : ajouté sur 7 pages
- og:url : ajouté partout
- Adresse exacte + téléphone + email : intégrés dans Schema, footer, contact

**IA / Crawlers :**
- llms.txt : réécrit avec carte complète + coordonnées
- llms-full.txt : créé (version enrichie avec storytelling)
- robots.txt : réécrit, crawlers IA autorisés (GPTBot, PerplexityBot, ClaudeBot, etc.)
- sitemap.xml : URLs et dates mises à jour

**Performance :**
- CSS : styles.min.css disponible (41 KB → 34 KB)
- JS : shared.min.js disponible (17 KB → 11 KB)

---

## 6. Règles pour éviter les erreurs

1. **Toujours modifier le fichier de suivi** quand un prix, un plat ou un cocktail change
2. **Synchroniser 4 fichiers** à chaque changement de carte : `carte.html`, `index.html` (Schema + FAQ), `llms.txt`, `llms-full.txt`
3. **Ne jamais supprimer** un élément du site sans validation explicite
4. **Tester sur mobile** après chaque modification (Chrome DevTools → mode responsive)
5. **Vérifier l'encodage** : les caractères spéciaux (€, é, è, ★, —) doivent être en UTF-8
6. **Garder les fichiers sources** (styles.css, shared.js) comme référence — les fichiers .min sont des copies de production
7. **Avant de publier** : ouvrir chaque page dans le navigateur et vérifier visuellement

---

## 7. Charte graphique — Rappel rapide

| Élément | Valeur |
|---------|--------|
| Bordeaux | `#5C0A1E` / `#3A0612` (deep) |
| Or | `#C8A45C` |
| Crème | `#F5F0E8` |
| Noir | `#1A1A1A` |
| Titres | Playfair Display |
| Sous-titres / descriptions | Cormorant Garamond |
| UI / Navigation / Boutons | Josefin Sans |
