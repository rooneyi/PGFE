# SPEC-02 — Routes Organisation (Proved & Sous-division)

**Référence fonctionnelle :** [organisation-proved-sous-division.md](./organisation-proved-sous-division.md)  
**Version :** 1.0  
**Date :** 2026-05-19  
**Préfixe application :** `{APP_URL}`

---

## 1. Vue d’ensemble

> **Web + API** : le module Organisation est exposé sur **les deux canaux**. Le Proved n’est pas limité au backend Blade ; les clients mobiles / sync utilisent `/api/v1/organization/proveds` avec `Authorization: Bearer {token}` (Sanctum).

### Accès API Proved (résumé)

| Action | Méthode | URI |
|--------|---------|-----|
| Liste | `GET` | `/api/v1/organization/proveds` |
| Créer | `POST` | `/api/v1/organization/proveds` (super-admin) |
| Détail | `GET` | `/api/v1/organization/proveds/{id}` |
| Modifier | `PUT` | `/api/v1/organization/proveds/{id}` |
| Supprimer | `DELETE` | `/api/v1/organization/proveds/{id}` (super-admin) |
| Sous-divisions du proved | `GET` | `/api/v1/organization/proveds/{id}/sous-divisions` |

| Couche | Fichier source | Préfixe URL | Nom de route |
|--------|----------------|-------------|--------------|
| Web admin | `routes/web/organization.php` (inclus dans `routes/web/admin.php`) | `/admin` | `admin.*` |
| API JSON | `routes/api/organization.php` (inclus dans `routes/api.php`) | `/api/v1` | `api.v1.organization.*` |

### Hiérarchie métier

```
Proved → Sous-division → École
```

### Rôles concernés

| Rôle | Slug Spatie |
|------|-------------|
| Super administrateur | `super-admin` |
| Admin Proved | `admin-proved` |
| Admin Sous-division | `admin-sous-division` |
| Admin École | `admin-ecole` |

---

## 2. Routes Web (`/admin`)

Middleware groupe parent (`routes/web/admin.php`) :

- `web`, `auth`
- `role:super-admin|admin-proved|admin-sous-division|admin-ecole|…`

### 2.1 Proved — CRUD

Middleware additionnel : `role:super-admin`

| Méthode | URI | Nom de route | Contrôleur | Action |
|---------|-----|--------------|------------|--------|
| GET | `/admin/proveds` | `admin.proveds.index` | `ProvedWebController` | `index` |
| GET | `/admin/proveds/create` | `admin.proveds.create` | `ProvedWebController` | `create` |
| POST | `/admin/proveds` | `admin.proveds.store` | `ProvedWebController` | `store` |
| GET | `/admin/proveds/{proved}/edit` | `admin.proveds.edit` | `ProvedWebController` | `edit` |
| PUT/PATCH | `/admin/proveds/{proved}` | `admin.proveds.update` | `ProvedWebController` | `update` |
| DELETE | `/admin/proveds/{proved}` | `admin.proveds.destroy` | `ProvedWebController` | `destroy` |

**Remarque :** pas de route `show`. L’`admin-proved` consulte son entité via la liste filtrée (policy) si exposée ultérieurement au super-admin uniquement sur ce groupe.

### 2.2 Sous-divisions — CRUD

Middleware additionnel : `role:super-admin|admin-proved|admin-sous-division`

| Méthode | URI | Nom de route | Contrôleur | Action |
|---------|-----|--------------|------------|--------|
| GET | `/admin/sous-divisions` | `admin.sous-divisions.index` | `SousDivisionWebController` | `index` |
| GET | `/admin/sous-divisions/create` | `admin.sous-divisions.create` | `SousDivisionWebController` | `create` |
| POST | `/admin/sous-divisions` | `admin.sous-divisions.store` | `SousDivisionWebController` | `store` |
| GET | `/admin/sous-divisions/{sous_division}/edit` | `admin.sous-divisions.edit` | `SousDivisionWebController` | `edit` |
| PUT/PATCH | `/admin/sous-divisions/{sous_division}` | `admin.sous-divisions.update` | `SousDivisionWebController` | `update` |
| DELETE | `/admin/sous-divisions/{sous_division}` | `admin.sous-divisions.destroy` | `SousDivisionWebController` | `destroy` |

**Paramètre route :** `{sous_division}` → modèle `App\Models\SousDivision` (table `sous_divisions`).

**Filtre liste (query) :**

| Paramètre | Type | Description |
|-----------|------|-------------|
| `proved_id` | int | Filtre par proved (super-admin) |

### 2.3 Switcher de contexte — Sous-division

| Méthode | URI | Nom de route | Rôle |
|---------|-----|--------------|------|
| GET | `/admin/sous-division/switch/{id}` | `admin.sous-division.switch` | `admin-proved` |

| Valeur `{id}` | Effet session |
|---------------|---------------|
| `all` | Supprime `selected_sous_division_id`, `selected_sous_division_name` |
| `{id}` numérique | Définit le focus sur la sous-division (vérif. `proved_id`) |

### 2.4 Switcher de contexte — École (existant, étendu)

| Méthode | URI | Nom de route | Fichier |
|---------|-----|--------------|---------|
| GET | `/admin/school/switch/{id}` | `admin.school.switch` | `routes/web/admin.php` |

| Valeur `{id}` | Effet |
|---------------|--------|
| `all` | Efface `selected_school_id`, `selected_school_name` |
| `{id}` | Sélectionne l’école si autorisée (`SchoolScopeResolver::canAccessSchool`) |

**Rôles utilisant le switcher école :** `super-admin`, `admin-proved`, `admin-sous-division`.

### 2.5 Écoles — champ sous-division

Routes existantes `admin.schools.*` (`SchoolWebController`) :

- Création / liste filtrées par périmètre organisationnel.
- Champ formulaire `sous_division_id` (obligatoire pour `super-admin`, `admin-proved`, `admin-sous-division`).

---

## 3. Routes API (`/api/v1`)

Middleware groupe :

- `auth:sanctum`
- `role:super-admin|admin-proved|admin-sous-division`

Préfixe : `/api/v1/organization`  
Nom : `api.v1.organization.*`

### 3.1 Proved

| Méthode | URI | Nom de route | Middleware rôle | Description |
|---------|-----|--------------|-----------------|-------------|
| GET | `/api/v1/organization/proveds` | `api.v1.organization.proveds.index` | super-admin, admin-proved | Liste |
| POST | `/api/v1/organization/proveds` | `api.v1.organization.proveds.store` | super-admin | Création |
| GET | `/api/v1/organization/proveds/{proved}` | `api.v1.organization.proveds.show` | super-admin, admin-proved | Détail + sous-divisions |
| PUT | `/api/v1/organization/proveds/{proved}` | `api.v1.organization.proveds.update` | super-admin, admin-proved (le sien) | Mise à jour |
| DELETE | `/api/v1/organization/proveds/{proved}` | `api.v1.organization.proveds.destroy` | super-admin | Suppression |
| GET | `/api/v1/organization/proveds/{proved}/sous-divisions` | `api.v1.organization.proveds.sous-divisions` | super-admin, admin-proved | Liste SD du proved |

### 3.2 Sous-divisions

| Méthode | URI | Nom de route | Middleware rôle | Description |
|---------|-----|--------------|-----------------|-------------|
| GET | `/api/v1/organization/sous-divisions` | `api.v1.organization.sous-divisions.index` | tous du groupe | Liste |
| POST | `/api/v1/organization/sous-divisions` | `api.v1.organization.sous-divisions.store` | super-admin, admin-proved | Création |
| GET | `/api/v1/organization/sous-divisions/{sous_division}` | `api.v1.organization.sous-divisions.show` | tous du groupe | Détail + écoles |
| PUT | `/api/v1/organization/sous-divisions/{sous_division}` | `api.v1.organization.sous-divisions.update` | super-admin, admin-proved | Mise à jour |
| DELETE | `/api/v1/organization/sous-divisions/{sous_division}` | `api.v1.organization.sous-divisions.destroy` | super-admin, admin-proved | Suppression |
| GET | `/api/v1/organization/sous-divisions/{sous_division}/schools` | `api.v1.organization.sous-divisions.schools` | tous du groupe | Écoles rattachées |

**Query `GET …/sous-divisions` :**

| Paramètre | Type | Description |
|-----------|------|-------------|
| `proved_id` | int | Filtre par proved |

### 3.3 Corps de requête (validation)

**Proved** (`ProvedRequest`) :

```json
{
  "name": "string, required",
  "code": "string, required, unique:proveds",
  "province_id": "integer, nullable, exists:provinces",
  "address": "string, nullable",
  "phone": "string, nullable",
  "email": "email, nullable"
}
```

**Sous-division** (`SousDivisionRequest`) :

```json
{
  "proved_id": "integer, required, exists:proveds",
  "name": "string, required",
  "code": "string, required, unique per proved_id"
}
```

### 3.4 Réponses JSON (convention)

Succès :

```json
{
  "data": { },
  "message": "string"
}
```

Création : HTTP `201`.

Erreur auth : HTTP `403`.  
Validation : HTTP `422`.

---

## 4. Sessions de contexte

| Clé session | Définie par | Utilisée par |
|-------------|-------------|--------------|
| `selected_sous_division_id` | `admin.sous-division.switch` | `SchoolScopeResolver` (admin-proved) |
| `selected_sous_division_name` | idem | Affichage UI |
| `selected_school_id` | `admin.school.switch` | Modules métier, `ScopeBySchool` |
| `selected_school_name` | idem | Affichage UI |

Middleware `require.school` : exige `selected_school_id` pour `super-admin`, `admin-proved`, `admin-sous-division`.

---

## 5. Matrice d’accès par route (résumé)

| Route / action | super-admin | admin-proved | admin-sous-division | admin-ecole |
|----------------|:-----------:|:------------:|:-------------------:|:-----------:|
| CRUD proved (web) | ✓ | — | — | — |
| CRUD sous-div (web) | ✓ | ✓ (son proved) | lecture seule (la sienne) | — |
| Switch sous-div | — | ✓ | — | — |
| Switch école | ✓ | ✓ (périmètre) | ✓ (périmètre) | — (école fixe) |
| API POST proved | ✓ | — | — | — |
| API POST sous-div | ✓ | ✓ | — | — |

Policies : `App\Policies\ProvedPolicy`, `App\Policies\SousDivisionPolicy`.

---

## 6. Fichiers implémentés (référence code)

| Type | Chemin |
|------|--------|
| Routes web | `routes/web/organization.php` |
| Routes API | `routes/api/organization.php` |
| Contrôleurs web | `app/Http/Controllers/Admin/ProvedWebController.php`, `SousDivisionWebController.php` |
| Contrôleurs API | `app/Http/Controllers/Api/Organization/ProvedController.php`, `SousDivisionController.php` |
| Scoping | `app/Services/Organization/SchoolScopeResolver.php` |
| Modèles | `app/Models/Proved.php`, `SousDivision.php` |
| Migrations | `database/migrations/2026_05_19_100000_*` … `100002_*` |
| Seeder | `database/seeders/OrganizationStructureSeeder.php` |

---

## 7. Comptes démo (après seed)

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| `admin-proved@demo.local` | `password` | `admin-proved` |
| `admin-sd@demo.local` | `password` | `admin-sous-division` |

---

## 8. Commandes utiles

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=OrganizationStructureSeeder
php artisan route:list --path=proved
php artisan route:list --path=sous-division
php artisan route:list --path=organization
```

---

*SPEC-02 — documenter toute nouvelle route organisationnelle dans ce fichier avant merge.*
