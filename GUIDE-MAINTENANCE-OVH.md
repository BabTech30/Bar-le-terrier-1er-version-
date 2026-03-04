# Le Terrier — Guide Maintenance & Sécurité OVH
**Version : 1.0 — 1er mars 2026**

---

## 1. Déploiement initial sur OVH

### Étape par étape

1. **Activer le SSL (HTTPS)**
   - Aller dans l'espace client OVH → Hébergements → ton hébergement
   - Section "Informations générales" → SSL → Commander un certificat gratuit (Let's Encrypt)
   - Attendre ~30 min que le certificat soit généré
   - Vérifier que https://barleterrier.fr fonctionne

2. **Uploader les fichiers**
   - Se connecter en FTP (FileZilla recommandé)
   - Hôte : `ftp.barleterrier.fr` (ou celui fourni par OVH)
   - Identifiants : ceux de ton espace FTP OVH
   - Aller dans le dossier `www/`
   - Uploader TOUS les fichiers du dossier `leterrier-site/`
   - **Le `.htaccess` doit être à la racine de `www/`**

3. **Vérifier le .htaccess**
   - FileZilla masque les fichiers cachés par défaut
   - Menu → Serveur → Forcer l'affichage des fichiers cachés
   - Vérifier que `.htaccess` est bien présent dans `www/`

4. **Tester**
   - http://barleterrier.fr → doit rediriger vers https://
   - https://barleterrier.fr/carte → doit fonctionner (URLs propres)
   - https://barleterrier.fr/nimportequoi → doit afficher la page 404
   - Ouvrir la console navigateur (F12) → Onglet Console → vérifier qu'il n'y a pas d'erreurs rouges

5. **Activer HSTS (après 48h de HTTPS fonctionnel)**
   - Dans le fichier `.htaccess`, décommenter la ligne HSTS :
   - Retirer le `#` devant `Header always set Strict-Transport-Security`
   - Re-uploader le `.htaccess`

### Structure du dossier `www/` sur OVH

```
www/
├── .htaccess              ← Configuration Apache (sécurité, cache, redirections)
├── index.html             ← Accueil
├── carte.html             ← Notre carte
├── concept.html           ← Le concept
├── contact.html           ← Contact & réservation
├── evenements.html        ← Événements
├── galerie.html           ← Galerie
├── boutique.html          ← Boutique
├── mentions-legales.html  ← Mentions légales
├── politique-confidentialite.html ← RGPD
├── 404.html               ← Page d'erreur
├── styles.css             ← Feuille de style
├── shared.js              ← JavaScript
├── favicon.svg            ← Icône navigateur
├── robots.txt             ← Instructions crawlers
├── sitemap.xml            ← Plan du site
├── llms.txt               ← Source IA (résumé)
└── llms-full.txt          ← Source IA (détaillé)
```

**NE PAS uploader** : `SUIVI-PROJET-LE-TERRIER.md`, `GUIDE-MAINTENANCE-OVH.md`, `styles.min.css`, `shared.min.js` (sauf si tu veux optimiser plus tard).

---

## 2. Maintenance courante

### Modifier un prix ou un plat

**Fichiers à modifier (TOUJOURS les 4) :**

| Fichier | Quoi modifier | Où |
|---------|---------------|-----|
| `carte.html` | Le HTML visible du menu | Chercher le nom du plat, modifier le prix/texte |
| `index.html` | Le Schema.org (invisible mais lu par Google) | Chercher dans le bloc `<script type="application/ld+json">` |
| `llms.txt` | Source de vérité pour les IA | Chercher le plat/prix dans le texte |
| `llms-full.txt` | Version détaillée pour les IA | Idem |

**Méthode simple :**
1. Ouvrir le fichier dans un éditeur de texte (VS Code, Notepad++, ou même Bloc-notes)
2. Faire Ctrl+H (Rechercher/Remplacer)
3. Chercher l'ancien prix → remplacer par le nouveau
4. Sauvegarder
5. Uploader le fichier modifié via FileZilla

**Ou bien :** m'envoyer le changement sur Claude et je te donne les 4 fichiers corrigés.

### Ajouter un cocktail ou un plat

C'est plus délicat car il faut respecter la structure HTML. Deux options :
- **Option simple** : me demander, je te donne les fichiers
- **Option autonome** : copier-coller un bloc `menu-item` existant dans `carte.html`, modifier le nom/description/prix

Exemple de bloc à copier :
```html
<div class="menu-item">
  <div class="menu-item__info">
    <h3 class="menu-item__name">NOM DU PLAT</h3>
    <p class="menu-item__comp">Description, ingrédients</p>
  </div>
  <span class="menu-item__price">XX €</span>
</div>
```

### Changer les horaires

Fichiers concernés : `contact.html`, `index.html` (Schema), `llms.txt`, `llms-full.txt`

---

## 3. Sécurité — Ce qui est protégé

### Protections actives (via .htaccess)

| Protection | Contre quoi | Status |
|-----------|-------------|--------|
| HTTPS forcé | Interception de données | ✅ Actif |
| Content-Security-Policy | Injection de scripts malveillants | ✅ Actif |
| X-Frame-Options | Clickjacking (iframe pirate) | ✅ Actif |
| X-Content-Type-Options | MIME sniffing | ✅ Actif |
| X-XSS-Protection | Cross-site scripting basique | ✅ Actif |
| Referrer-Policy | Fuite d'URLs | ✅ Actif |
| Permissions-Policy | Accès caméra/micro/GPS | ✅ Actif |
| Anti-hotlinking | Vol de bande passante images | ✅ Actif |
| Blocage bots malveillants | Scraping abusif | ✅ Actif |
| Protection .htaccess | Lecture de la config | ✅ Actif |
| Protection fichiers internes | Accès aux .md de suivi | ✅ Actif |
| Honeypot formulaires | Spam basique | ✅ Actif |
| Sanitization inputs JS | Injection XSS via formulaires | ✅ Actif |
| Compression Gzip | — (performance) | ✅ Actif |
| Cache navigateur | — (performance) | ✅ Actif |
| HSTS | Downgrade HTTPS → HTTP | ⏳ À activer après 48h |

### Ce qu'il faut encore faire manuellement

| Action | Pourquoi | Comment |
|--------|----------|---------|
| **2FA sur OVH** | Empêcher le piratage du compte hébergement | Espace client OVH → Sécurité → Activer la double authentification |
| **Mot de passe FTP fort** | Empêcher l'accès aux fichiers | Espace client OVH → Hébergements → FTP → Modifier le mot de passe (12+ caractères, chiffres, symboles) |
| **Sauvegardes régulières** | Pouvoir restaurer en cas de problème | OVH fait des snapshots automatiques (14 jours), mais garde aussi une copie locale sur ton ordi |
| **Captcha sur le formulaire** | Anti-spam avancé | Voir section 4 ci-dessous |

---

## 4. Ajouter un Captcha (recommandé)

Le honeypot bloque les bots basiques, mais pour une vraie protection du formulaire de contact, je recommande **Cloudflare Turnstile** (gratuit, invisible, pas de puzzle agaçant).

### Étapes :
1. Créer un compte Cloudflare (gratuit) → https://dash.cloudflare.com
2. Aller dans Turnstile → Add Widget
3. Ajouter le domaine `barleterrier.fr`
4. Choisir "Managed" (le captcha apparaît seulement si nécessaire)
5. Copier la **Site Key** et la **Secret Key**

### Intégration dans `contact.html` :
Ajouter avant le bouton "Envoyer" du formulaire :
```html
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<div class="cf-turnstile" data-sitekey="VOTRE_SITE_KEY" data-theme="dark"></div>
```

### Mise à jour CSP dans `.htaccess` :
Ajouter `https://challenges.cloudflare.com` dans les directives `script-src` et `frame-src`.

---

## 5. Checklist de vérification (après chaque modification)

À faire après CHAQUE upload de fichiers :

- [ ] Le site s'affiche correctement sur mobile (Chrome → F12 → icône mobile)
- [ ] Le site s'affiche correctement sur desktop
- [ ] Les prix affichés correspondent au fichier SUIVI
- [ ] Le menu sticky de la carte fonctionne (cliquer sur chaque catégorie)
- [ ] Le formulaire de contact fonctionne
- [ ] La newsletter fonctionne (si Brevo branché)
- [ ] La page 404 s'affiche quand on tape une URL inexistante
- [ ] Pas d'erreurs dans la console navigateur (F12 → Console)
- [ ] Le cookie banner apparaît à la première visite

---

## 6. Contacts utiles

| Service | URL | Usage |
|---------|-----|-------|
| OVH Espace client | https://www.ovh.com/manager/ | Gestion hébergement, SSL, FTP |
| Google Search Console | https://search.google.com/search-console/ | SEO, indexation, erreurs |
| Google Business Profile | https://business.google.com/ | Fiche Google Maps |
| Brevo | https://app.brevo.com/ | Newsletter |
| Cloudflare Turnstile | https://dash.cloudflare.com/ | Captcha formulaire |
| FileZilla | https://filezilla-project.org/ | Client FTP gratuit |

---

## 7. En cas de problème

### Le site affiche une erreur 500
→ Problème dans le `.htaccess`. Renommer le fichier en `.htaccess.bak` via FTP et le site remarche immédiatement. Corriger le `.htaccess` puis le remettre.

### Le CSS ne se charge pas
→ Vérifier que `styles.css` est bien uploadé à la racine de `www/` (même dossier que `index.html`). Vider le cache navigateur (Ctrl+Shift+R).

### Les URLs propres ne fonctionnent pas (/carte au lieu de /carte.html)
→ Le module `mod_rewrite` doit être activé sur OVH (il l'est par défaut). Si ça ne marche pas, contacter le support OVH.

### Google n'indexe pas le site
→ Aller dans Google Search Console → soumettre `https://barleterrier.fr/sitemap.xml`. L'indexation peut prendre 1 à 4 semaines.
