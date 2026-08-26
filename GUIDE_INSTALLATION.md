# Guide d’installation PGFE (Back + Front)

Ce guide permet d’installer **PGFE** sur une autre machine (local) et de le publier **en ligne**.

## Dépôt (monorepo unique)

| | |
|--|--|
| **GitHub (public)** | https://github.com/rooneyi/PGFE |
| **Branche** | `main` |

Un seul clone contient le backend **et** le frontend.

| Dossier | Rôle | Stack |
|---------|------|--------|
| `PGFEv2-ENABEL` | Backend API | Laravel 12, PHP **8.4**, MySQL, Sanctum |
| `PGFEv2-ENABEL-FRONT` | Frontend | Vue 3, Vite, Node.js **20+** |

URL locale typique :
- API : `http://localhost:8000`
- Front : `http://localhost:5173` (ou `5174`)

URL en ligne (exemple) :
- Même domaine : `https://apischool.capslockdev.com` (front + API)

---

## 1. Logiciels à installer

### Obligatoire

| Outil | Version | Notes |
|-------|---------|--------|
| **Git** | récent | Cloner le monorepo |
| **PHP** | **8.4** | Obligatoire (`composer.json` → `"php": "^8.4"`) |
| **Composer** | 2.x | Dépendances PHP |
| **MySQL** ou **MariaDB** | 8.x / 10.x | Base de données |
| **Node.js** | **20 LTS** ou + | Front Vue / Vite |
| **npm** | fourni avec Node | |

Sous Windows, options simples :
- [Laragon](https://laragon.org/) (PHP + MySQL + Composer) + installer PHP 8.4
- ou [XAMPP](https://www.apachefriends.org/) / WAMP + PHP 8.4 séparément
- Node : [https://nodejs.org](https://nodejs.org)

Sous Linux (Debian/Ubuntu) :

```bash
sudo apt update
sudo apt install -y git curl unzip mysql-server
# PHP 8.4 + extensions (voir section suivante)
# Node 20 via nodesource ou nvm
```

### Extensions PHP à activer (ne pas les oublier)

| Extension | Pourquoi |
|-----------|----------|
| `pdo` / `pdo_mysql` | Connexion MySQL |
| `mbstring` | Chaînes / Laravel |
| `openssl` | HTTPS, chiffrement |
| `tokenizer` | Laravel |
| `xml` / `dom` | XML, DomPDF |
| `ctype` | Laravel |
| `json` | API |
| `fileinfo` | Uploads / médias |
| `bcmath` | Calculs |
| `curl` | HTTP sortant |
| `gd` **ou** `imagick` | Images (Media Library, Excel/PDF) |
| `zip` | Composer, Excel, archives |
| `intl` | Localisation / téléphone |
| `exif` | Images (recommandé) |

Vérifier :

```bash
php -v          # doit afficher 8.4.x
php -m          # liste des extensions chargées
composer -V
node -v
npm -v
```

---

## 2. Récupérer le code

```bash
git clone https://github.com/rooneyi/PGFE.git
cd PGFE
```

Structure :

```text
PGFE/
├── PGFEv2-ENABEL/           ← backend Laravel
├── PGFEv2-ENABEL-FRONT/     ← frontend Vue
├── GUIDE_INSTALLATION.md
└── README.md
```

> Il n’y a plus deux dépôts séparés : tout est dans **rooneyi/PGFE**.

---

## 3. Installation backend (local)

```bash
cd PGFEv2-ENABEL

composer install

copy .env.example .env          # Windows
# cp .env.example .env          # Linux / macOS

php artisan key:generate
```

### Configurer `.env` (extrait)

```env
APP_NAME="PGFE ENABEL"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgfev2
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Créer la base MySQL :

```sql
CREATE DATABASE pgfev2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Migrations + seeders

```bash
php artisan migrate --force
php artisan db:seed --force
```

### Lancer l’API

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

API : `http://localhost:8000/api/v1/`

### (Optionnel) Storage public

```bash
php artisan storage:link
```

---

## 4. Installation frontend (local)

Ouvrir un **second terminal** à la racine du monorepo :

```bash
cd PGFEv2-ENABEL-FRONT

npm install

copy .env.example .env.local          # Windows
# cp .env.example .env.local          # Linux / macOS
```

### Configurer `.env.local`

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1/
VITE_SANCTUM_BASE_URL=http://localhost:8000/
```

### Lancer le front

```bash
npm run dev
```

Ouvrir l’URL Vite (souvent `http://localhost:5173`).

> Si le port du front change, mets à jour `FRONTEND_URL` dans le `.env` du backend.

---

## 5. Accès après seed (local / démo)

| Rôle | Email | Mot de passe |
|------|--------|--------------|
| Super Admin | `superadmin@pgfe.com` | `SuperAdmin@2025` |
| Admin | `elvis1@gmail.com` | `codecode` |
| Admin école | `admin-ecole@gmail.com` | `codecode` |
| Enseignant | `enseignant1@gmail.com` | `codecode` |

**Change ces mots de passe** hors environnement de démo.

---

## 6. Utilisation en ligne (production)

### Option A — Même domaine (recommandé)

Le front est **buildé dans** `PGFEv2-ENABEL/public` → un seul domaine (ex. `https://apischool.capslockdev.com`).

#### Build

```bash
cd PGFEv2-ENABEL-FRONT

# .env.production contient déjà (dans le repo) :
# VITE_API_BASE_URL=/api/v1/
# VITE_SANCTUM_BASE_URL=/

npm run build:backend
```

Cela écrit `index.html`, `assets/`, `fonts/` dans `PGFEv2-ENABEL/public/`.

#### Serveur

Prérequis :
- PHP **8.4** + **mêmes extensions** (section 1)
- Composer, MySQL
- Apache / Nginx → **docroot** = `PGFEv2-ENABEL/public`
- SSL (Let’s Encrypt)

```bash
cd /chemin/vers/PGFE/PGFEv2-ENABEL

composer install --no-dev --optimize-autoloader

# .env production :
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://ton-domaine.com
# FRONTEND_URL=https://ton-domaine.com
# DB_* = identifiants MySQL prod

php artisan key:generate          # une seule fois
php artisan migrate --force
php artisan db:seed --force       # optionnel (démo)
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

Permissions (Linux) :

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

Le `public/.htaccess` sert le SPA pour les routes non-API. Le panel Blade legacy reste sous `/legacy/...`.

### Option B — Deux domaines (API + Front séparés)

1. Backend sur `https://api.exemple.com` (docroot `public/`).
2. Build front :

```bash
cd PGFEv2-ENABEL-FRONT
# VITE_API_BASE_URL=https://api.exemple.com/api/v1/
# VITE_SANCTUM_BASE_URL=https://api.exemple.com/
npm run build-only
```

3. Uploader `dist/` sur `https://app.exemple.com`.
4. Backend `.env` :

```env
APP_URL=https://api.exemple.com
FRONTEND_URL=https://app.exemple.com
```

5. CORS (`config/cors.php`) : autoriser l’URL du front.

### DNS

- enregistrement **A** → IP du VPS
- puis SSL (Let’s Encrypt / Hestia / Certbot)

---

## 7. Checklist rapide

### Local

- [ ] PHP 8.4 + extensions listées
- [ ] Composer, MySQL, Node 20+
- [ ] `git clone https://github.com/rooneyi/PGFE.git`
- [ ] Back : `composer install` + `.env` + `migrate` + `db:seed` + `serve`
- [ ] Front : `npm install` + `.env.local` → `:8000` + `npm run dev`
- [ ] Login OK

### En ligne

- [ ] PHP 8.4 + **mêmes extensions**
- [ ] Docroot = `PGFEv2-ENABEL/public/`
- [ ] `.env` production (`APP_DEBUG=false`)
- [ ] Migrate (+ seed si besoin)
- [ ] `npm run build:backend` (ou `dist/` en option B)
- [ ] DNS A + SSL
- [ ] `FRONTEND_URL` / CORS corrects
- [ ] Mots de passe seed changés

---

## 8. Dépannage courant

| Problème | Piste |
|----------|--------|
| `composer` refuse / php version | Installer PHP **8.4** |
| `ext-xxx missing` | Activer l’extension PHP manquante |
| CORS / login front | `FRONTEND_URL` + CORS = URL exacte du front |
| Page blanche après refresh (prod) | `.htaccess` / rewrite SPA + `index.html` présent |
| 500 Apache sur Laravel | Droits `storage/`, `bootstrap/cache` ; pas de `.htaccess` invalide hors `public/` |
| Build front échoue sur `vue-tsc` | `npm run build-only` ou `npm run build:backend` |

---

## 9. Commandes utiles (résumé)

```bash
# Clone
git clone https://github.com/rooneyi/PGFE.git
cd PGFE

# Backend
cd PGFEv2-ENABEL
composer install
php artisan migrate --force
php artisan db:seed --force
php artisan serve --host=127.0.0.1 --port=8000

# Frontend (dev) — autre terminal
cd PGFEv2-ENABEL-FRONT
npm install
npm run dev

# Frontend → intégré dans le backend (prod same-origin)
cd PGFEv2-ENABEL-FRONT
npm run build:backend
```

---

*PGFE / Enabel — monorepo public : [github.com/rooneyi/PGFE](https://github.com/rooneyi/PGFE).*
