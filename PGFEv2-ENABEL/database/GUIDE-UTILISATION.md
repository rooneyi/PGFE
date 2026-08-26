# Guide d’utilisation — Scripts de migration PGFE v2

Ce dossier contient **3 fichiers à envoyer ensemble** :

| Fichier | Rôle |
|---------|------|
| `generate_migration_sql.py` | Ancien système → Laravel (backup `ap_*`, `hr_*`) |
| `generate_repair_from_current_sql.py` | Base Laravel déjà partielle → compléter les tables vides |
| `GUIDE-UTILISATION.md` | Ce guide (du début à la fin) |

**À fournir en plus** (selon le cas) : un fichier `.sql` exporté, **avec le nom que vous voulez** (ex. `backup_maendeleo_now.sql`, `export_ecole_x_2026.sql`, `dump_prod.sql`). Seule la **structure des tables** compte, pas le nom du fichier.

---

## Noms de fichiers : libres

| Paramètre | Obligatoire ? | Exemples valides |
|-----------|---------------|------------------|
| Fichier source `--backup` ou `--dump` | Oui (un chemin vers un `.sql`) | `backup_maendeleo_now.sql`, `ecole_a.sql`, `/data/dump_2026-05-20.sql` |
| Fichier sortie `--output` | Oui (nom choisi par vous) | `migration.sql`, `import_ecole_a.sql`, `repair_goma.sql` |

Les noms **`backup_maendeleo_now.sql`** et **`migration_maendeleo.sql`** dans ce guide sont des **exemples** pour Maendeleo. Pour une autre école ou une autre date, gardez la même commande en changeant uniquement les chemins :

```bash
python3 generate_migration_sql.py \
  --backup /chemin/votre_export.sql \
  --output /chemin/votre_import.sql
```

**Ce qui ne change pas** (peu importe le nom du fichier) :

- **Parcours A** : le dump doit contenir des tables legacy `ap_*`, `hr_*`, etc.
- **Parcours B** : le dump doit contenir des tables Laravel `users`, `registrations`, `schools`, etc.
- La base MySQL cible (`pgfe_db` ou autre) est définie dans le `.env` Laravel, pas dans le nom du fichier SQL.

---

## Sommaire

1. [Choisir le bon script](#1-choisir-le-bon-script)
2. [Prérequis sur la machine](#2-prérequis-sur-la-machine)
3. [Préparer la base Laravel cible](#3-préparer-la-base-laravel-cible)
4. [Parcours A — Backup ancien système (Maendeleo / ap_*)](#4-parcours-a--backup-ancien-système-maendeleo--ap_)
5. [Parcours B — Export Laravel actuel (réparation)](#5-parcours-b--export-laravel-actuel-réparation)
6. [Après l’import : organisation & vérifications](#6-après-limport--organisation--vérifications)
7. [Connexion à l’application](#7-connexion-à-lapplication)
8. [Dépannage](#8-dépannage)
9. [Contenu du zip à envoyer](#9-contenu-du-zip-à-envoyer)

---

## 1. Choisir le bon script

Ouvrez votre fichier `.sql` dans un éditeur et cherchez `INSERT INTO` :

| Vous voyez dans le fichier… | Script à utiliser |
|-----------------------------|-------------------|
| `ap_etudie`, `ap_apprenant`, `ap_annee_scolaire`, `hr_employee`, `acc_*` | **`generate_migration_sql.py`** |
| `users`, `registrations`, `schools`, `students`, `school_years` | **`generate_repair_from_current_sql.py`** |

**Exemple :** un fichier nommé `export_ecole_2026.sql` qui contient `ap_etudie` → **Parcours A**, même si le nom n’est pas `backup_maendeleo_now.sql`.

**Erreur fréquente :** utiliser le script de réparation sur un backup `ap_*` → rapport tout à **VIDE** (normal).

---

## 2. Prérequis sur la machine

### Logiciels

- **Python 3** (3.10 ou plus)
- **MySQL** ou **MariaDB**
- Projet **Laravel PGFE v2** déjà cloné, avec `.env` configuré (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)

### Dépendance Python (Parcours A uniquement)

```bash
pip install bcrypt
```

### Où lancer les commandes

Placez-vous **dans le dossier qui contient les 3 fichiers** (ce zip décompressé) :

```bash
cd /chemin/vers/le/dossier/scripts
ls
# generate_migration_sql.py
# generate_repair_from_current_sql.py
# GUIDE-UTILISATION.md
```

**Important :** les commandes utilisent :

```bash
python3 generate_migration_sql.py ...
```

et **pas** `python3 database/generate_migration_sql.py` (sauf si vous copiez les scripts dans `PGFEv2-ENABEL/database/` du projet Laravel).

---

## 3. Préparer la base Laravel cible

Ces étapes sont **obligatoires** avant d’importer le SQL généré.

### 3.1 Créer la base MySQL

Adaptez le nom (`pgfe_db` = exemple ; utilisez celui de votre `.env`) :

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pgfe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3.2 Fichier `.env` Laravel

Dans le projet `PGFEv2-ENABEL` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgfe_db
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

### 3.3 Migrations Laravel

```bash
cd /chemin/vers/PGFEv2-ENABEL
php artisan migrate
```

### 3.4 Seeders minimaux (référentiels + rôles)

Sans au moins **pays** et **types**, le SQL de migration ne peut pas créer l’école.

**Important :** le rôle `admin-ecole` doit exister **avant** l’import du SQL généré (étape 11 du fichier SQL).

```bash
php artisan db:seed --class=CountrySeeder
php artisan db:seed --class=ProvinceSeeder
php artisan db:seed --class=TerritorySeeder
php artisan db:seed --class=CommuneSeeder
php artisan db:seed --class=TypeSeeder
php artisan db:seed --class=FonctionSeeder

# Obligatoire AVANT l'import migration.sql (crée admin-ecole, permissions, etc.)
php artisan db:seed --class=RolesAndPermissionsSeeder
```

*(Organisation Proved / SD : après l’import — section 6.)*

---

## 4. Parcours A — Backup ancien système (Maendeleo / ap_*)

### 4.1 Fichier source

Vous devez avoir un export SQL legacy (ancien système). **Le nom du fichier est libre** ; placez-le où vous voulez et indiquez le chemin complet si besoin.

Ce fichier est un **fichier texte sur le disque**. On **ne** lance **pas** `mysqldump` sur son nom : ce n’est pas une base MySQL.

### 4.2 Générer le SQL d’import Laravel

```bash
cd /chemin/vers/le/dossier/scripts

# Remplacez SOURCE.sql et SORTIE.sql par vos noms
python3 generate_migration_sql.py \
  --backup SOURCE.sql \
  --output SORTIE.sql
```

Exemple Maendeleo :

```bash
python3 generate_migration_sql.py \
  --backup backup_maendeleo_now.sql \
  --output migration_maendeleo.sql
```

Durée : quelques dizaines de secondes à quelques minutes selon la taille du backup.

**Options utiles :**

```bash
# Forcer une école déjà existante (id=1)
python3 generate_migration_sql.py \
  --backup backup_maendeleo_now.sql \
  --output migration_maendeleo.sql \
  --school-id 1

# Personnaliser le nom de l’école créée (si table schools vide)
python3 generate_migration_sql.py \
  --backup backup_maendeleo_now.sql \
  --output migration_maendeleo.sql \
  --school-name "École Maendeleo" \
  --school-city "Kinshasa" \
  --school-address "Adresse complète"

# Mot de passe par défaut si absent du legacy
python3 generate_migration_sql.py \
  --backup backup_maendeleo_now.sql \
  --output migration_maendeleo.sql \
  --default-password "VotreMotDePasse123!"
```

### 4.3 Ce que fait le SQL généré (à connaître)

- **TRUNCATE** (vide) : `registrations`, `students`, `users`, `courses`, `classrooms`, `academic_personals`, `filiaires`, `academic_levels`, `school_years`, etc.
- **Ne vide pas** : `schools` (sauf création si vide), `countries`, `types`, `proveds`, …
- Recrée : années, niveaux, filières, classes, utilisateurs, personnel, cours, élèves, inscriptions.
- Active **une seule** année scolaire (`is_active = 1`).
- Crée une **école** avec `uuid` si aucune n’existe.

**Conséquence :** les `users` / `students` / `registrations` déjà présents dans la base cible seront **remplacés** par les données du backup legacy.

### 4.4 Importer dans MySQL

Remplacez `pgfe_db` par votre `DB_DATABASE` :

```bash
mysql -u root -p pgfe_db < SORTIE.sql
```

(Remplacez `SORTIE.sql` par le fichier passé à `--output`, et `pgfe_db` par votre `DB_DATABASE`.)

Ou **phpMyAdmin** → base cible → **Importer** → choisir votre fichier `--output`.

En cas d’erreur, l’import s’arrête ; relisez le message (section 8) avant de relancer.

### 4.5 Données migrées depuis le legacy

| Legacy | Laravel |
|--------|---------|
| `ap_annee_scolaire` | `school_years` |
| `ap_niveau` | `academic_levels` |
| `ap_section` | `filiaires` |
| `ap_class` / `ap_cour` | `classrooms`, `courses` |
| `ap_apprenant` | `students` |
| `ap_etudie` | `registrations` |
| `hr_employee` + `system_access` | `users`, `academic_personals` |

Une ligne `ap_etudie` n’est migrée en inscription **que si** matricule, classe/section et année scolaire sont exploitables ; sinon elle est ignorée.

---

## 5. Parcours B — Export Laravel actuel (réparation)

À utiliser quand la base contient déjà des `users` / `registrations` Laravel et que beaucoup de tables de référence sont **vides**.

### 5.1 Obtenir un export (si la base tourne déjà)

```bash
mysqldump -u root -p \
  --no-create-info \
  --complete-insert \
  pgfe_db > export_pgfe_actuel.sql
```

Si vous avez **déjà** un fichier `.sql` reçu (export phpMyAdmin), passez directement à 5.2.

### 5.2 Analyser le dump (sans modifier la base)

```bash
cd /chemin/vers/le/dossier/scripts

python3 generate_repair_from_current_sql.py \
  --dump export_pgfe_actuel.sql \
  --output repair.sql \
  --report-only
```

Le rapport liste chaque table : **ok** ou **VIDE**.

### 5.3 Générer le SQL de réparation

```bash
python3 generate_repair_from_current_sql.py \
  --dump export_pgfe_actuel.sql \
  --output repair.sql
```

Options :

```bash
  --school-id 1
  --school-name "Mon école"
  --school-city "Kinshasa"
```

### 5.4 Appliquer

```bash
mysql -u root -p pgfe_db < repair.sql
```

**Différence avec le Parcours A :** pas de TRUNCATE global ; complète école, années, filières, classes, finance minimale, cours, parents, FK orphelines.

---

## 6. Après l’import : rôles, organisation & vérifications

### 6.1 Rôle `admin-ecole` sur les utilisateurs migrés

Le SQL généré (étape **11**) attribue automatiquement le rôle **`admin-ecole`** à **chaque** ligne de `users` qui n’a pas encore de rôle, à condition que `RolesAndPermissionsSeeder` ait été exécuté **avant** l’import (section 3.4).

Vérification :

```sql
SELECT u.id, u.email, r.name AS role
FROM users u
LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON r.id = mhr.role_id
ORDER BY u.id;
```

Si des comptes n’ont toujours pas de rôle (ancien fichier SQL sans étape 11, ou seeder oublié) :

```bash
cd /chemin/vers/PGFEv2-ENABEL
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan pgfe:assign-missing-role admin-ecole
```

Options utiles :

```bash
# Voir qui serait traité, sans modifier
php artisan pgfe:assign-missing-role admin-ecole --dry-run

# Un seul compte
php artisan pgfe:assign-missing-role admin-ecole --email=aimeebasomboli@pgfe.local
```

**Note :** tous les comptes migrés reçoivent `admin-ecole` (gestion locale de l’école). Pour un autre rôle (`enseignant`, `tiers`, …), modifiez ensuite dans l’admin ou via l’API utilisateurs. Ne pas lancer `pgfe:assign-missing-role` sur des comptes déjà configurés si vous avez des rôles différents par utilisateur.

### 6.2 Lier l’école à une sous-division (Proved)

```bash
cd /chemin/vers/PGFEv2-ENABEL
php artisan db:seed --class=OrganizationStructureSeeder
```

### 6.3 Super-admin global (optionnel)

```bash
php artisan db:seed --class=SuperAdminSeeder
```

Compte type `superadmin@pgfe.com` — distinct des `admin-ecole` migrés.

### 6.4 Vérifications SQL

```bash
mysql -u root -p pgfe_db
```

```sql
SELECT id, name, sous_division_id FROM schools;
SELECT id, name, is_active FROM school_years ORDER BY is_active DESC;
SELECT COUNT(*) AS users FROM users;
SELECT COUNT(*) AS students FROM students;
SELECT COUNT(*) AS registrations FROM registrations;
SELECT COUNT(*) AS courses FROM courses;
```

Résultat attendu après **Parcours A** réussi : au moins 1 école, 1 année active, des élèves et inscriptions > 0.

### 6.5 Renommer l’école

Dans l’admin Laravel ou via API `PUT /api/v1/school/schools/{id}` si le nom « École PGFE (migration) » ne convient pas.

---

## 7. Connexion à l’application

### Comptes migrés (Parcours A)

- **Email :** `{username}@pgfe.local` (ex. `aimeebasomboli@pgfe.local`)
- **Mot de passe :** celui du legacy si présent dans `system_access`, sinon la valeur de `--default-password` (défaut script : `ChangeMe123!`)

### Comptes démo (si seeders passés)

Voir la doc projet / seeders : ex. `superadmin@pgfe.com`, `admin-proved@demo.local`, etc.

### Lancer l’app

```bash
cd /chemin/vers/PGFEv2-ENABEL
php artisan serve
```

Ouvrir l’URL affichée (souvent `http://127.0.0.1:8000`).

---

## 8. Dépannage

| Problème | Cause | Solution |
|----------|--------|----------|
| `can't open file '.../database/generate_...'` | Mauvais dossier courant | `cd` vers le dossier du zip ; utiliser `python3 generate_migration_sql.py` |
| `Unknown database 'backup_xxx.sql'` | Confusion fichier / base | Le `.sql` est lu par le script Python, pas par `mysqldump` |
| Rapport réparation tout **VIDE** | Backup `ap_*` avec mauvais script | Utiliser **Parcours A** |
| `Field 'uuid' doesn't have a default value` sur `schools` | Ancienne version du script | Utiliser la version fournie dans ce zip (inclut `UUID()` sur `schools`) |
| `No module named 'bcrypt'` | Dépendance manquante | `pip install bcrypt` |
| Erreur FK `countries` / `types` | Seeders non passés | Section 3.4 |
| Peu d’élèves vs ancien système | Lignes `ap_etudie` filtrées | Vérifier matricule, classe, section, année dans le backup |
| Import interrompu après TRUNCATE | Échec milieu d’import | Corriger l’erreur, **regénérer** le `.sql`, **réimporter** |

---

## 9. Contenu du zip à envoyer

### Package technique (3 fichiers)

```
scripts-pgfe-migration/
├── generate_migration_sql.py
├── generate_repair_from_current_sql.py
└── GUIDE-UTILISATION.md
```

### Fichier données (séparé ou dans un 2ᵉ zip)

- Un ou plusieurs exports `.sql` (**noms libres** : `backup_ecole1.sql`, `dump_2026.sql`, …)

**Ne pas inclure** dans le zip technique : les fichiers générés (`--output`) — chaque destinataire les recrée avec son propre nom.

### Commandes récap — Parcours A (Maendeleo)

```bash
# 1. Décompresser le zip des scripts
cd scripts-pgfe-migration

# 2. Laravel : migrate + seeders référentiels + RolesAndPermissionsSeeder (section 3)

# 3. Générer (noms de fichiers = ce que vous voulez)
python3 generate_migration_sql.py \
  --backup /chemin/VOTRE_SOURCE.sql \
  --output /chemin/VOTRE_SORTIE.sql

# 4. Importer
mysql -u root -p pgfe_db < /chemin/VOTRE_SORTIE.sql

# 5. Organisation
cd ../PGFEv2-ENABEL
php artisan db:seed --class=OrganizationStructureSeeder
```

### Commandes récap — Parcours B (réparation)

```bash
cd scripts-pgfe-migration
python3 generate_repair_from_current_sql.py --dump export.sql --output repair.sql --report-only
python3 generate_repair_from_current_sql.py --dump export.sql --output repair.sql
mysql -u root -p pgfe_db < repair.sql
```

---

## Support

- Projet Laravel : `PGFEv2-ENABEL`
- Organisation Proved / SD : `docs/frontend-guide-organisation-proved-sous-division.md` (dans le dépôt applicatif)
- Algorithme détaillé migration : `docs/algorithme-migration-sql.md` (dépôt applicatif)

---

*Guide version 1.0 — PGFE v2 ENABEL — scripts `generate_migration_sql.py` + `generate_repair_from_current_sql.py`*
