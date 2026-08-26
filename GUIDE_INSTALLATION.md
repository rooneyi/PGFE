# Guide d’installation PGFE — Windows

Installer **PGFE** en local sur une machine **Windows**.

## Dépôt

| | |
|--|--|
| **GitHub (public)** | https://github.com/rooneyi/PGFE |
| **Branche** | `main` |

| Dossier | Rôle |
|---------|------|
| `PGFEv2-ENABEL` | Backend — Laravel 12, PHP **8.4**, MySQL |
| `PGFEv2-ENABEL-FRONT` | Frontend — Vue 3, Vite, Node.js **20+** |

Après installation :
- API : `http://localhost:8000`
- Front : `http://localhost:5173`

---

## 1. Outils à installer (Windows)

### Recommandé : Laragon + Node.js

| Outil | Lien de téléchargement |
|-------|------------------------|
| **Laragon Full** | https://laragon.org/download/ |
| **Node.js 20 LTS** (Windows) | https://nodejs.org/en/download |

Laragon installe en général : **PHP**, **MySQL**, **Apache**, **Composer**.  
Node.js doit être installé **à part**.

Après Laragon :
1. Démarre Laragon (**Start All**)
2. Menu → **PHP** → choisis / ajoute **PHP 8.4**

### Outils obligatoires (liens Windows)

| Outil | Version | Lien |
|-------|---------|------|
| **Git for Windows** | récent | https://git-scm.com/download/win |
| **PHP 8.4** (si pas Laragon) | **8.4** | https://windows.php.net/download/ |
| **Composer-Setup.exe** | 2.x | https://getcomposer.org/download/ |
| **MySQL Installer** (si pas Laragon) | 8.x | https://dev.mysql.com/downloads/installer/ |
| **Node.js** | **20 LTS** ou + | https://nodejs.org/en/download |

> **npm** est inclus avec Node.js — pas besoin de l’installer séparément.

### Alternatives tout-en-un (Windows)

| Suite | Lien |
|-------|------|
| **Laragon** (recommandé) | https://laragon.org/download/ |
| **XAMPP** | https://www.apachefriends.org/download.html |
| **WampServer** | https://www.wampserver.com/ |
| **Herd** (optionnel) | https://herd.laravel.com/ |
| **Docker Desktop** (optionnel) | https://www.docker.com/products/docker-desktop/ |

### Clients utiles (optionnel)

| Outil | Lien | Usage |
|-------|------|--------|
| **VS Code** | https://code.visualstudio.com/ | Éditeur |
| **Cursor** | https://cursor.com/ | Éditeur |
| **HeidiSQL** | https://www.heidisql.com/download.php | MySQL |
| **DBeaver** | https://dbeaver.io/download/ | MySQL |
| **Postman** | https://www.postman.com/downloads/ | Tester l’API |
| **phpMyAdmin** | inclus dans Laragon | MySQL via navigateur |

### Extensions PHP à activer

Dans Laragon / `php.ini`, active :

| Extension | Pourquoi |
|-----------|----------|
| `pdo` / `pdo_mysql` | MySQL |
| `mbstring` | Laravel |
| `openssl` | HTTPS / chiffrement |
| `tokenizer` | Laravel |
| `xml` / `dom` | DomPDF |
| `ctype`, `json` | Laravel / API |
| `fileinfo` | Uploads |
| `bcmath` | Calculs |
| `curl` | HTTP |
| `gd` ou `imagick` | Images |
| `zip` | Composer / Excel |
| `intl` | Localisation |
| `exif` | Images (recommandé) |

### Vérifier (PowerShell ou CMD)

```bat
php -v
php -m
composer -V
node -v
npm -v
git --version
```

`php -v` doit afficher **8.4.x**.

---

## 2. Récupérer le code

Dans **PowerShell** ou **Git Bash** :

```bat
git clone https://github.com/rooneyi/PGFE.git
cd PGFE
```

```text
PGFE\
├── PGFEv2-ENABEL\           ← backend
├── PGFEv2-ENABEL-FRONT\     ← frontend
├── GUIDE_INSTALLATION.md
└── README.md
```

---

## 3. Backend (local)

```bat
cd PGFEv2-ENABEL
composer install
copy .env.example .env
php artisan key:generate
```

### `.env` (extrait)

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

> Sous Laragon, le mot de passe MySQL `root` est souvent **vide**.

Créer la base (HeidiSQL, phpMyAdmin, ou MySQL) :

```sql
CREATE DATABASE pgfev2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bat
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

API : `http://localhost:8000/api/v1/`

---

## 4. Frontend (local)

Ouvre un **deuxième** terminal :

```bat
cd PGFEv2-ENABEL-FRONT
npm install
copy .env.example .env.local
```

### `.env.local`

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1/
VITE_SANCTUM_BASE_URL=http://localhost:8000/
```

```bat
npm run dev
```

Ouvre `http://localhost:5173` (ou le port affiché par Vite).

---

## 5. Accès après seed

| Rôle | Email | Mot de passe |
|------|--------|--------------|
| Super Admin | `superadmin@pgfe.com` | `SuperAdmin@2025` |
| Admin | `elvis1@gmail.com` | `codecode` |
| Admin école | `admin-ecole@gmail.com` | `codecode` |
| Enseignant | `enseignant1@gmail.com` | `codecode` |

Change ces mots de passe hors démo.

---

## 6. Build pour production (depuis Windows)

Pour générer le front **dans** le backend (même domaine) :

```bat
cd PGFEv2-ENABEL-FRONT
npm run build:backend
```

Les fichiers vont dans `PGFEv2-ENABEL\public\` (`index.html`, `assets\`, …).  
Ensuite tu déploies le dossier `PGFEv2-ENABEL` sur ton serveur (docroot = `public`).

---

## 7. Checklist Windows

- [ ] Laragon (ou PHP 8.4 + MySQL + Composer) + **Node 20**
- [ ] Git for Windows
- [ ] Extensions PHP activées
- [ ] `git clone https://github.com/rooneyi/PGFE.git`
- [ ] Back : `composer install` → `.env` → migrate → seed → `serve`
- [ ] Front : `npm install` → `.env.local` → `npm run dev`
- [ ] Login OK sur `http://localhost:5173`

---

## 8. Dépannage Windows

| Problème | Piste |
|----------|--------|
| `php` inconnu | Ajoute PHP au **PATH** ou ouvre le terminal **Laragon** |
| Mauvaise version PHP | Laragon → Menu → PHP → **8.4** |
| `composer` / `ext-xxx` | Active l’extension dans `php.ini`, redémarre |
| MySQL refuse la connexion | Vérifie que Laragon a démarré MySQL ; `DB_PASSWORD=` souvent vide |
| Port 8000 / 5173 occupé | Change le port ou ferme l’autre app |
| Build `vue-tsc` échoue | Utilise `npm run build-only` ou `npm run build:backend` |

---

## 9. Commandes utiles

```bat
git clone https://github.com/rooneyi/PGFE.git
cd PGFE

cd PGFEv2-ENABEL
composer install
php artisan migrate --force
php artisan db:seed --force
php artisan serve --host=127.0.0.1 --port=8000

cd PGFEv2-ENABEL-FRONT
npm install
npm run dev
```

---

*Windows uniquement — monorepo : [github.com/rooneyi/PGFE](https://github.com/rooneyi/PGFE).*
