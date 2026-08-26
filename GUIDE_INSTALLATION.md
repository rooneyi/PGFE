# Guide d’installation PGFE — Windows (client)

Installer et lancer **PGFE** sur un PC Windows.

**Code :** https://github.com/rooneyi/PGFE

---

## 1. À installer (liens)

| Outil | Lien | Pourquoi |
|-------|------|----------|
| **Laragon Full** | https://laragon.org/download/ | PHP, MySQL, Composer |
| **Node.js 20 LTS** | https://nodejs.org/en/download | Frontend |
| **Git for Windows** | https://git-scm.com/download/win | Télécharger le projet |

Après Laragon :
1. Clique **Start All**
2. Menu → **PHP** → choisis **PHP 8.4**

Vérifie dans un terminal Laragon :

```bat
php -v
composer -V
node -v
git --version
```

`php -v` doit afficher **8.4**.

---

## 2. Télécharger le projet

```bat
git clone https://github.com/rooneyi/PGFE.git
cd PGFE
```

---

## 3. Backend

```bat
cd PGFEv2-ENABEL
composer install
copy .env.example .env
php artisan key:generate
```

Dans `.env`, laisse en général :

```env
DB_DATABASE=pgfev2
DB_USERNAME=root
DB_PASSWORD=
```

(Laragon : mot de passe MySQL souvent vide.)

Crée la base (phpMyAdmin Laragon, ou HeidiSQL) :

```sql
CREATE DATABASE pgfev2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis :

```bat
php artisan migrate --force
php artisan db:seed --force
php artisan serve --host=127.0.0.1 --port=8000
```

Laisse cette fenêtre ouverte.

---

## 4. Frontend

Ouvre un **2ᵉ terminal** :

```bat
cd PGFEv2-ENABEL-FRONT
npm install
copy .env.example .env.local
npm run dev
```

Ouvre le navigateur : **http://localhost:5173**

---

## 5. Connexion

| Email | Mot de passe |
|--------|--------------|
| `superadmin@pgfe.com` | `SuperAdmin@2025` |

Change le mot de passe après la 1ʳᵉ connexion.

---

## En cas de problème

| Problème | Solution |
|----------|----------|
| `php` introuvable | Ouvre le terminal **depuis Laragon** |
| Mauvaise version PHP | Laragon → PHP → **8.4** |
| Erreur MySQL | **Start All** dans Laragon ; `DB_PASSWORD=` vide |
| Page front blanche | Vérifie que le backend tourne sur le port **8000** |

---

*Installation client Windows — https://github.com/rooneyi/PGFE*
