# Guide d’installation PGFE — Windows (client)

Installer et lancer **PGFE** sur un PC Windows **sans taper de commandes au quotidien**.

**Code :** https://github.com/rooneyi/PGFE

---

## 1. À installer une seule fois

| Outil | Lien |
|-------|------|
| **Laragon Full** | https://laragon.org/download/ |
| **Node.js 20 LTS** | https://nodejs.org/en/download |
| **Git for Windows** | https://git-scm.com/download/win |

Après l’installation de Laragon :
1. Ouvrez Laragon et cliquez **Start All**
2. Menu → **PHP** → choisissez **PHP 8.4** (ou la version 8.x la plus récente)

---

## 2. Télécharger le projet

```bat
git clone https://github.com/rooneyi/PGFE.git
cd PGFE
```

---

## 3. Installation initiale (une seule fois)

Double-cliquez sur :

```
scripts\setup-pgfe.bat
```

Ce script fait automatiquement :
- `composer install`, `.env`, clé Laravel, migrations, seeders
- création de la base `pgfev2` si MySQL Laragon est dispo
- `npm install` et `.env.local`
- création du raccourci **PGFE.lnk** (projet + Bureau si vous acceptez)

Attendez la fin du message « Installation terminee ».

---

## 4. Usage quotidien

Double-cliquez sur **`PGFE.lnk`** (Bureau ou racine du dossier PGFE).

Cela démarre automatiquement :
1. Laragon (s’il n’est pas déjà ouvert)
2. le backend Laravel
3. le frontend Vue
4. le navigateur sur **http://localhost:5173**

Pour arrêter backend + frontend : double-cliquez sur `scripts\stop-pgfe.bat`  
(Laragon reste ouvert ; fermez-le manuellement si besoin.)

Si le raccourci manque :

```
scripts\create-shortcut.bat
```

---

## 5. Connexion

| Email | Mot de passe |
|--------|--------------|
| `superadmin@pgfe.com` | `SuperAdmin@2025` |

Changez le mot de passe après la 1ʳᵉ connexion.

---

## En cas de problème

| Problème | Solution |
|----------|----------|
| Rien ne démarre | Ouvrez Laragon → **Start All**, puis relancez `PGFE.lnk` |
| `php` / Composer introuvable | Installez **PHP 8.4+** (Laragon → PHP → 8.4) puis relancez |
| Erreur « PHP version >= 8.4 » | Laragon n’a que 8.3 : ajoutez PHP 8.4 dans Laragon, ou installez PHP 8.4 système |
| Erreur MySQL / migrate | Laragon **Start All** ; mot de passe MySQL souvent vide |
| Page blanche | Vérifiez que backend (8000) et front (5173) tournent |
| Port déjà utilisé | Normal si déjà lancé ; ou utilisez `stop-pgfe.bat` puis relancez |

---

*Installation client Windows — https://github.com/rooneyi/PGFE*
