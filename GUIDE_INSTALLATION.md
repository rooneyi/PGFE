# Guide d’installation PGFE (Back + Front)

Ce guide permet d’installer **PGFE** sur une autre machine (local) et de le publier **en ligne**.

| Dossier | Rôle | Stack |
|---------|------|--------|
| `PGFEv2-ENABEL` | Backend API | Laravel 12, PHP **8.4**, MySQL, Sanctum |
| `PGFEv2-ENABEL-FRONT` | Frontend | Vue 3, Vite, Node.js **20+** |

URL locale typique :
- API : `http://localhost:8000`
- Front : `http://localhost:5173` (ou `5174`)

URL en ligne (exemple actuel) :
- Tout sur le même domaine : `https://apischool.capslockdev.com` (front + API)

---

## 1. Logiciels à installer

### Obligatoire

| Outil | Version | Notes |
|-------|---------|--------|
| **Git** | récent | Cloner les dépôts |
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
# Exemple (adapter selon la distro)
sudo apt update
sudo apt install -y git curl unzip mysql-server
# PHP 8.4 + extensions (voir section suivante)
# Node 20 via nodesource ou nvm
```

### Extensions PHP à activer (ne pas les oublier)

Active **toutes** ces extensions dans `php.ini` (ou via le gestionnaire de paquets) :

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

Si une extension manque, Laravel / Composer échoueront au `composer install` ou au runtime.

---

## 2. Récupérer le code

```bash
# Exemple : placer les deux projets côte à côte
git clone <url-repo-backend>  PGFEv2-ENABEL
git clone <url-repo-frontend> PGFEv2-ENABEL-FRONT
```

Structure attendue :

```text
PGFE/
├── PGFEv2-ENABEL/          ← backend
└── PGFEv2-ENABEL-FRONT/    ← frontend
```

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

Créer la base MySQL `pgfev2` (via phpMyAdmin, HeidiSQL, ou) :

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

L’API répond sur : `http://localhost:8000/api/v1/`

### (Optionnel) Storage public

```bash
php artisan storage:link
```

---

## 4. Installation frontend (local)

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

Ouvrir l’URL affichée par Vite (souvent `http://localhost:5173`).

> Si le port du front change, mets à jour `FRONTEND_URL` dans le `.env` du backend.

---

## 5. Accès après seed (local / démo)

| Rôle | Email | Mot de passe |
|------|--------|--------------|
| Super Admin | `superadmin@pgfe.com` | `SuperAdmin@2025` |
| Admin | `elvis1@gmail.com` | `codecode` |
| Admin école | `admin-ecole@gmail.com` | `codecode` |
| Enseignant | `enseignant1@gmail.com` | `codecode` |

**Change ces mots de passe** dès que tu es hors démo.

---

## 6. Utilisation en ligne (production)

Deux approches possibles.

### Option A — Même domaine (recommandé)

Front **buildé dans** `PGFEv2-ENABEL/public` → un seul domaine (ex. `https://apischool.capslockdev.com`).

#### Sur la machine de build

```bash
cd PGFEv2-ENABEL-FRONT

# .env.production (ou .env.production.local)
# VITE_API_BASE_URL=/api/v1/
# VITE_SANCTUM_BASE_URL=/

npm run build:backend
```

Cela écrit `index.html`, `assets/`, `fonts/` dans `PGFEv2-ENABEL/public/`.

#### Sur le serveur

Prérequis serveur :
- PHP **8.4** + **mêmes extensions** que la section 1
- Composer, MySQL
- Apache ou Nginx (docroot = `.../public`)
- SSL (Let’s Encrypt)

```bash
cd /chemin/vers/PGFEv2-ENABEL

composer install --no-dev --optimize-autoloader

# Configurer .env production
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

Le fichier `public/.htaccess` doit servir le SPA pour les routes non-API (déjà prévu dans le projet). Le panel Blade legacy reste sous `/legacy/...`.

### Option B — Deux domaines (API + Front séparés)

1. Déployer le backend sur `https://api.exemple.com` (docroot `public/`).
2. Build front :

```bash
cd PGFEv2-ENABEL-FRONT
# VITE_API_BASE_URL=https://api.exemple.com/api/v1/
# VITE_SANCTUM_BASE_URL=https://api.exemple.com/
npm run build-only
```

3. Uploader le contenu de `dist/` sur `https://app.exemple.com`.
4. Dans le `.env` backend :

```env
APP_URL=https://api.exemple.com
FRONTEND_URL=https://app.exemple.com
```

5. Vérifier CORS dans `config/cors.php` (origins autorisées = URL du front).

### DNS

Pour chaque domaine / sous-domaine :
- enregistrement **A** → IP du VPS
- puis SSL (Let’s Encrypt / Hestia / Certbot)

Sans DNS correct, le navigateur et Let’s Encrypt échouent.

---

## 7. Checklist rapide

### Local

- [ ] PHP 8.4 + extensions listées
- [ ] Composer, MySQL, Node 20+
- [ ] `composer install` + `.env` + `migrate` + `db:seed`
- [ ] `npm install` + `.env.local` pointant vers `:8000`
- [ ] `php artisan serve` + `npm run dev`
- [ ] Login OK

### En ligne

- [ ] PHP 8.4 + **mêmes extensions**
- [ ] Docroot = `public/`
- [ ] `.env` production (`APP_DEBUG=false`)
- [ ] Migrate (+ seed si besoin)
- [ ] Build front (`build:backend` ou `dist/`)
- [ ] DNS A + SSL
- [ ] `FRONTEND_URL` / CORS corrects
- [ ] Mots de passe seed changés

---

## 8. Dépannage courant

| Problème | Piste |
|----------|--------|
| `composer` refuse / php version | Installer PHP **8.4**, pas 8.2/8.3 seul |
| `ext-xxx missing` | Activer l’extension PHP manquante |
| CORS / login front | `FRONTEND_URL` + CORS = URL exacte du front |
| Page blanche après refresh (prod) | `.htaccess` / rewrite SPA + `index.html` présent |
| 500 Apache sur Laravel | Vérifier droits `storage/`, `bootstrap/cache` ; éviter un `.htaccess` invalide à la racine hors `public/` |
| Build front échoue sur `vue-tsc` | Utiliser `npm run build-only` ou `npm run build:backend` |

---

## 9. Commandes utiles (résumé)

```bash
# Backend
cd PGFEv2-ENABEL
composer install
php artisan migrate --force
php artisan db:seed --force
php artisan serve --host=127.0.0.1 --port=8000

# Frontend (dev)
cd PGFEv2-ENABEL-FRONT
npm install
npm run dev

# Frontend → intégré dans le backend (prod same-origin)
cd PGFEv2-ENABEL-FRONT
npm run build:backend
```

---

*Document généré pour le projet PGFE / Enabel — à adapter selon l’hébergeur (Laragon, VPS Hestia, etc.).*
