# Hiérarchie organisationnelle — Proved & Sous Division

Document de référence pour l’implémentation **simultanée** des deux niveaux intermédiaires au-dessus de l’école, dans PGFE v2 (ENABEL).

**Date :** 2026-05-19  
**Statut :** En cours d’implémentation  
**Routes (SPEC-02) :** [SPEC-02-organisation-routes.md](./SPEC-02-organisation-routes.md)
**Périmètre :** Proved + Sous Division + rattachement des écoles existantes (pas de livraison partielle d’un seul niveau).

---

## 1. Contexte et objectif

### 1.1 Schéma cible

```
Proved (1)
  └── Sous Division (N)
        └── École (N)
              └── Données métier (élèves, compta, stock, …) — déjà scopées par school_id
```

### 1.2 État actuel du code

| Niveau        | Dans PGFE aujourd’hui                                      |
|---------------|------------------------------------------------------------|
| **École**     | `School`, rôle `admin-ecole`, `users.school_id`, `ScopeBySchool` |
| **Sous Division** | Non implémenté                                         |
| **Proved**    | Non implémenté                                             |

Le **super-admin** peut déjà voir toutes les écoles et utiliser `session('selected_school_id')` (`SchoolWebController::switchSchool`). Ce comportement sert de **modèle** pour les switchers Proved / Sous Division, sans être équivalent à ces rôles.

### 1.3 Principes

1. **Les deux niveaux sont livrés ensemble** (tables, rôles, scoping, UI minimale).
2. Une **Sous Division** fait ce qu’on attend d’un regroupement d’écoles (vue multi-écoles, création d’écoles dans son périmètre).
3. Un **Proved** fait la même chose **au niveau agrégé**, connaît les **IDs des sous-divisions** qui lui sont rattachées, et peut **créer / gérer des sous-divisions**.
4. L’**admin-ecole** reste inchangé en périmètre : une seule école.

---

## 2. Modèle de données

### 2.1 Table `proveds`

| Colonne        | Type              | Notes                                      |
|----------------|-------------------|--------------------------------------------|
| `id`           | bigint PK         |                                            |
| `name`         | string            | Ex. « PROVED Kinshasa »                    |
| `code`         | string, unique    | Code officiel / sigle                      |
| `province_id`  | FK nullable       | Lien géographique (`provinces`)            |
| `address`      | string nullable   |                                            |
| `phone`        | string nullable   |                                            |
| `email`        | string nullable   |                                            |
| `timestamps`   |                   |                                            |

**Modèle :** `App\Models\Proved`

**Relations :**

- `hasMany(SousDivision::class)`
- `hasManyThrough(School::class, SousDivision::class)` (écoles du proved)

### 2.2 Table `sous_divisions`

| Colonne      | Type              | Notes                                      |
|--------------|-------------------|--------------------------------------------|
| `id`         | bigint PK         |                                            |
| `proved_id`  | FK → `proveds`    | Obligatoire                                |
| `name`       | string            |                                            |
| `code`       | string, unique    | Unique **par proved** (index composite)    |
| `timestamps` |                   |                                            |

**Modèle :** `App\Models\SousDivision`

**Relations :**

- `belongsTo(Proved::class)`
- `hasMany(School::class)`

### 2.3 Modification `schools`

| Colonne               | Type                    | Notes                    |
|-----------------------|-------------------------|--------------------------|
| `sous_division_id`    | FK → `sous_divisions`   | Nullable en migration, obligatoire après backfill |

**Modèle `School` :**

- `belongsTo(SousDivision::class)`
- Accesseur ou relation indirecte `proved()` via sous-division

### 2.4 Modification `users`

| Colonne               | Type                    | Rôle concerné              |
|-----------------------|-------------------------|----------------------------|
| `proved_id`           | FK nullable → `proveds` | `admin-proved`             |
| `sous_division_id`    | FK nullable → `sous_divisions` | `admin-sous-division` |
| `school_id`           | inchangé                | `admin-ecole`, staff école |

**Règles d’intégrité (application) :**

- `admin-proved` : `proved_id` renseigné ; `sous_division_id` et `school_id` null (sauf cas exceptionnels documentés).
- `admin-sous-division` : `sous_division_id` renseigné ; `proved_id` dérivable ; `school_id` null.
- `admin-ecole` : `school_id` renseigné ; l’école doit avoir un `sous_division_id`.

### 2.5 Migrations (ordre)

1. `create_proveds_table`
2. `create_sous_divisions_table`
3. `add_sous_division_id_to_schools_table`
4. `add_proved_id_and_sous_division_id_to_users_table`
5. Seeder / commande de **backfill** (voir § 7)

---

## 3. Rôles et permissions (Spatie)

### 3.1 Nouveaux rôles

| Rôle                    | Guard | Description                                      |
|-------------------------|-------|--------------------------------------------------|
| `admin-proved`          | `web` | Direction provinciale — gère SD + vue agrégée    |
| `admin-sous-division`   | `web` | Sous-division scolaire — gère les écoles de la SD |

Les rôles existants (`admin-ecole`, `super-admin`, métiers école) restent.

### 3.2 Nouvelles permissions

```
proveds.view
proveds.update                    # le proved modifie ses propres infos (optionnel)

sous-divisions.view
sous-divisions.create
sous-divisions.update
sous-divisions.delete

schools.view                    # déjà partiellement — clarifier par niveau
schools.create                  # SD crée école ; proved peut aussi (dans ses SD)
schools.update
```

### 3.3 Matrice rôle → permissions

| Permission              | super-admin | admin-proved | admin-sous-division | admin-ecole |
|-------------------------|:-----------:|:------------:|:-------------------:|:-----------:|
| `proveds.view`          | ✓           | ✓ (le sien)  | —                   | —           |
| `proveds.update`        | ✓           | ✓ (le sien)  | —                   | —           |
| `sous-divisions.*`      | ✓           | ✓ (son proved)| —                  | —           |
| `sous-divisions.view`   | ✓           | ✓            | ✓ (la sienne)       | —           |
| `schools.view`          | ✓           | ✓ (agrégé)   | ✓ (ses écoles)      | ✓ (la sienne)|
| `schools.create`        | ✓           | ✓            | ✓                   | —           |
| `schools.update`        | ✓           | ✓            | ✓ (ses écoles)      | limité      |
| Modules métier (élèves, compta, …) | ✓ | via contexte école | via contexte école | ✓ |

Les permissions métier déjà assignées à `admin-ecole` ne sont **pas** dupliquées sur proved/SD : l’accès aux modules « école » passe par **sélection d’une école** dans le périmètre (voir § 5).

### 3.4 Fichiers à mettre à jour

- `database/seeders/RolesAndPermissionsSeeder.php`
- `roles_et_acces.txt` (documentation utilisateur)

---

## 4. Scoping des données (backend)

### 4.1 Résolution des `school_id` autorisés

Helper central (ex. `App\Services\Organization\SchoolScopeResolver`) :

```php
// Pseudo-code
function allowedSchoolIds(User $user): ?array
{
    if ($user->hasRole('super-admin')) return null; // pas de filtre

    if ($user->hasRole('admin-proved')) {
        return School::whereHas('sousDivision', fn ($q) =>
            $q->where('proved_id', $user->proved_id)
        )->pluck('id')->all();
    }

    if ($user->hasRole('admin-sous-division')) {
        return School::where('sous_division_id', $user->sous_division_id)
            ->pluck('id')->all();
    }

    if ($user->school_id) return [$user->school_id];

    return []; // aucun accès
}
```

### 4.2 Évolution de `ScopeBySchool`

Renommer ou étendre le trait `App\Models\Concerns\ScopeBySchool` :

| Rôle                  | Filtre sur `school_id`                    |
|-----------------------|-------------------------------------------|
| `super-admin`         | Aucun                                     |
| `admin-proved`        | `IN (allowedSchoolIds)`                   |
| `admin-sous-division` | `IN (allowedSchoolIds)`                   |
| `admin-ecole`         | `= user.school_id` (actuel)               |
| Autres rôles école    | `= user.school_id`                        |

**Session de focus (optionnelle) :**

- Si `session('selected_school_id')` est défini **et** appartient au périmètre → filtrer sur cette école uniquement (comportement « travail dans une école »).
- Si `session('selected_sous_division_id')` (proved) → restreindre `allowedSchoolIds` à cette SD.

### 4.3 Contrôleurs à auditer

Tout ce qui filtre déjà par `school_id` ou `Auth::user()->school_id` :

- `AdminUserController` (création users : proved crée admin-sous-division / admin-ecole dans son périmètre)
- `ScopeBySchool` sur les modèles métier
- Exports, API resources, sync
- Middleware `RestrictReadOnlyRole`, routes `role:…`

---

## 5. Contexte UI et sessions

### 5.1 Variables de session

| Clé                           | Utilisée par           | Effet                                      |
|-------------------------------|------------------------|--------------------------------------------|
| `selected_sous_division_id`   | `admin-proved`         | Focus une SD (sinon vue toutes les SD)     |
| `selected_school_id`          | proved, SD, super-admin| Focus une école pour modules métier        |
| `selected_school_name`        | affichage              | Inchangé                                   |

### 5.2 Switchers (sur le modèle `SchoolWebController::switchSchool`)

| Route (proposition)                         | Action                          |
|---------------------------------------------|---------------------------------|
| `GET admin/sous-division/switch/{id\|all}`  | Proved change de SD active      |
| `GET admin/school/switch/{id\|all}`         | Existant — étendre aux nouveaux rôles |

### 5.3 Middlewares

| Middleware                      | Rôles concernés        | Règle                                      |
|---------------------------------|------------------------|--------------------------------------------|
| `RequireSelectedSchool`         | proved, SD, super-admin| Modules « par école » sans `selected_school_id` → redirect dashboard |
| `RequireSelectedSousDivision`   | optionnel              | Uniquement si écran exige une SD précise   |

### 5.4 Menus (`AdminMenuService`)

**admin-proved :**

- Dashboard (agrégé)
- Sous-divisions (liste, créer, modifier)
- Écoles (toutes du proved, filtre par SD)
- Switcher SD + switcher école
- Pas d’accès Pays / config globale (sauf si super-admin)

**admin-sous-division :**

- Dashboard (agrégé SD)
- Écoles (CRUD dans la SD)
- Utilisateurs (`admin-ecole`, tiers)
- Switcher école
- Mêmes modules métier que super-admin **une fois une école sélectionnée**

**admin-ecole :** inchangé.

---

## 6. Fonctionnalités par niveau

### 6.1 Admin Sous Division (`admin-sous-division`)

- Voir toutes les **écoles** de sa `sous_division_id`.
- **Créer / modifier** des écoles (`schools.sous_division_id` forcé à la sienne).
- **Créer** des utilisateurs `admin-ecole` et `tiers` rattachés à une école de la SD.
- Sélectionner une école → accès aux modules déjà existants (élèves, compta, stock, …) avec scoping.
- **Ne peut pas** créer de sous-division ni changer de proved.

### 6.2 Admin Proved (`admin-proved`)

- Tout ce que fait la sous-division, **en mode agrégé** sur toutes ses SD.
- **Liste des sous-divisions** avec leurs `id` (besoin métier explicite).
- **Créer / modifier / désactiver** des sous-divisions (`proved_id` = le sien).
- **Créer des écoles** dans n’importe quelle SD du proved (choix de `sous_division_id` dans un select filtré).
- **Créer** des utilisateurs `admin-sous-division` (rattachés à une SD).
- Option : créer directement `admin-ecole` (école + SD du proved).
- Dashboard : indicateurs consolidés par SD et global proved.

### 6.3 Super-admin

- CRUD **proved** (création des entités proved).
- Voit tout ; peut impersonner / switcher sans restriction.
- Migration des écoles existantes vers une SD par défaut.

---

## 7. Migration des données existantes

### 7.1 Script / seeder de backfill

1. Créer un **Proved** par défaut (ex. « PROVED Démo ») lié à une province existante.
2. Créer une **Sous Division** par défaut (ex. « SD Démo ») rattachée à ce proved.
3. `UPDATE schools SET sous_division_id = <sd_demo> WHERE sous_division_id IS NULL`.
4. Vérifier les utilisateurs `admin-ecole` : chaque `school_id` doit appartenir à une école déjà rattachée.

### 7.2 Compte démo (seeder)

| Email (exemple)              | Rôle                  | Rattachement        |
|------------------------------|-----------------------|---------------------|
| `admin-proved@demo.local`    | `admin-proved`        | `proved_id` = démo  |
| `admin-sd@demo.local`        | `admin-sous-division` | `sous_division_id`  |
| `admin-ecole@gmail.com`      | `admin-ecole`         | `school_id` (existant) |

---

## 8. API REST (optionnel phase 1, recommandé)

Préfixe : `routes/api/organization.php` → `/v1/organization/…`

| Méthode | URI                              | Rôle minimum        |
|---------|----------------------------------|---------------------|
| GET     | `/proveds`                       | super-admin         |
| POST    | `/proveds`                       | super-admin         |
| GET     | `/proveds/{proved}`              | super-admin, admin-proved (le sien) |
| PUT     | `/proveds/{proved}`              | idem                |
| GET     | `/proveds/{proved}/sous-divisions` | admin-proved      |
| POST    | `/sous-divisions`                | admin-proved        |
| GET     | `/sous-divisions/{sd}`           | admin-proved, admin-sous-division (la sienne) |
| PUT     | `/sous-divisions/{sd}`           | admin-proved        |
| GET     | `/sous-divisions/{sd}/schools`   | admin-proved, admin-sous-division |

Policies Laravel : `ProvedPolicy`, `SousDivisionPolicy` (vérifier `proved_id` / `sous_division_id`).

---

## 9. Interface web (Blade) — livrables minimaux

### 9.1 Proved

- `resources/views/backend/pages/proveds/` — index, create, edit (super-admin)
- `resources/views/backend/pages/sous-divisions/` — index, create, edit (admin-proved + super-admin)

### 9.2 Contrôleurs web

- `App\Http\Controllers\Admin\ProvedWebController`
- `App\Http\Controllers\Admin\SousDivisionWebController`
- Adapter `SchoolWebController` : filtre par périmètre ; à la création, imposer `sous_division_id`.

### 9.3 Routes (`routes/web/admin.php`)

```php
Route::resource('proveds', ProvedWebController::class)->middleware('role:super-admin');
Route::resource('sous-divisions', SousDivisionWebController::class)
    ->middleware('role:super-admin|admin-proved');
Route::get('sous-division/switch/{id}', ...)->name('sous-division.switch');
```

---

## 10. Ordre d’implémentation (checklist)

Les phases sont **couplées** ; ne pas merger sans les deux niveaux + backfill.

- [ ] **A. Données** — Migrations `proveds`, `sous_divisions`, FK `schools`, FK `users`
- [ ] **B. Modèles** — `Proved`, `SousDivision`, relations `School` / `User`
- [ ] **C. Rôles** — `admin-proved`, `admin-sous-division`, permissions, seeder
- [ ] **D. Scoping** — `SchoolScopeResolver` + mise à jour `ScopeBySchool`
- [ ] **E. Policies** — Accès CRUD par niveau
- [ ] **F. Web CRUD** — Proveds (super-admin), Sous-divisions (proved), Écoles adaptées
- [ ] **G. Sessions** — Switchers SD + école pour proved/SD
- [ ] **H. Menus** — `AdminMenuService` + partials sidebar
- [ ] **I. Users** — `AdminUserController` : qui peut créer quel rôle
- [ ] **J. Backfill** — Commande ou seeder + doc `.env` démo
- [ ] **K. API** — Routes organization (si requis mobile / sync)
- [ ] **L. Doc** — Mettre à jour `roles_et_acces.txt`
- [ ] **M. Tests manuels** — Matrice ci-dessous

---

## 11. Tests manuels (matrice)

| Action                              | super-admin | admin-proved | admin-sous-division | admin-ecole |
|-------------------------------------|:-----------:|:------------:|:-------------------:|:-----------:|
| Créer un proved                     | ✓           | ✗            | ✗                   | ✗           |
| Créer une sous-division             | ✓           | ✓ (son proved)| ✗                  | ✗           |
| Créer une école                     | ✓           | ✓ (ses SD)   | ✓ (sa SD)           | ✗           |
| Voir écoles d’une autre SD          | ✓           | ✗            | ✗                   | ✗           |
| Modules métier sans école sélectionnée | N/A      | ✗ (redirect) | ✗ (redirect)        | N/A (toujours son école) |
| Modules métier école A              | ✓           | ✓ si A dans périmètre | ✓ si A dans SD | ✓ si A = son école |

---

## 12. Conventions de nommage

| Concept        | Table / modèle      | Rôle Spatie           | Clé user / school      |
|----------------|---------------------|-----------------------|------------------------|
| Proved         | `proveds` / `Proved`| `admin-proved`        | `users.proved_id`      |
| Sous Division  | `sous_divisions` / `SousDivision` | `admin-sous-division` | `users.sous_division_id` |
| École          | `schools` / `School`| `admin-ecole`         | `users.school_id`, `schools.sous_division_id` |

Routes URL : `sous-divisions` (kebab-case), noms de routes `admin.sous-divisions.*`.

---

## 13. Hors périmètre (v1)

- Statistiques avancées / tableaux de bord BI proved
- Sync offline des entités proved/SD (sauf si exigé par équipe sync — traiter en phase 2)
- Multi-proved pour un même utilisateur
- Délégation temporaire entre proved

---

## 14. Références code existant à réutiliser

| Besoin              | Fichier existant                                      |
|---------------------|-------------------------------------------------------|
| Switcher école      | `app/Http/Controllers/Admin/SchoolWebController.php` (`switchSchool`) |
| Scope multi-école   | `app/Models/Concerns/ScopeBySchool.php`               |
| Menus par rôle      | `app/Services/MenuService/AdminMenuService.php`       |
| Création utilisateurs | `app/Http/Controllers/Api/Admin/AdminUserController.php` |
| Rôles               | `database/seeders/RolesAndPermissionsSeeder.php`      |
| Doc rôles           | `roles_et_acces.txt`                                  |

---

*Fin du document — implémentation Proved + Sous Division en une seule livraison.*
