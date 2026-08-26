# Guide front — Organisation Proved & Sous-division

**Public :** équipe front (Vue / React / mobile)  
**Date :** 2026-05-19  
**Backend de référence :**

- [organisation-proved-sous-division.md](./organisation-proved-sous-division.md) — règles métier
- [SPEC-02-organisation-routes.md](./SPEC-02-organisation-routes.md) — routes web + API
- [ui-style-guide.md](./ui-style-guide.md) — charte UI du backend Blade (à réutiliser si vous alignez le design)

---

## 1. Ce que le front doit comprendre

### 1.1 Hiérarchie

```
Proved (1)
  └── Sous-division (N)
        └── École (N)
              └── Données métier (élèves, compta, présences, …) — school_id
```

Chaque **école** est rattachée à une **sous-division** (`schools.sous_division_id`).  
Les utilisateurs organisationnels portent des FK sur leur compte :

| Champ utilisateur | Rôle typique |
|-------------------|--------------|
| `proved_id` | `admin-proved` |
| `sous_division_id` | `admin-sous-division` |
| `school_id` | `admin-ecole`, enseignant, comptable, … |

### 1.2 Deux « contextes » à gérer côté UI

Comme pour le super-admin qui choisit une école dans le backend Blade, les rôles organisationnels travaillent souvent en **deux temps** :

1. **Périmètre organisationnel** — tout ce qui est visible (liste SD, liste écoles du proved, etc.).
2. **Focus école** — pour ouvrir les modules métier (élèves, classes, cotations…) **comme si** on était dans une école précise.

Sur le **backend web**, ce focus est stocké en **session** :

| Clé session | Rôle | Effet |
|-------------|------|--------|
| `selected_sous_division_id` | `admin-proved` | Restreint les écoles visibles à une SD |
| `selected_school_id` | `super-admin`, `admin-proved`, `admin-sous-division` | Les modules « par école » ne ciblent qu’une école |

**Important pour une SPA pure (Bearer Sanctum, sans cookies de session) :** ces routes de switch sont **web uniquement** (`GET /admin/sous-division/switch/{id}`, `GET /admin/school/switch/{id}`).  
Le front doit **reproduire la même logique en local** (store Pinia/Vuex, Context React, etc.) et, là où l’API ne filtre pas encore automatiquement, **envoyer le contexte** (query `school_id`, filtre client, ou coordination backend — voir § 8).

---

## 2. Authentification

### 2.1 Connexion

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin-proved@demo.local",
  "password": "password"
}
```

Réponse utile (extrait) :

```json
{
  "message": "Authentication successful.",
  "token": "1|…. ",
  "user": {
    "id": 1,
    "name": "...",
    "email": "...",
    "school_id": null,
    "proved_id": 1,
    "sous_division_id": null,
    "roles": [{ "name": "admin-proved", ... }]
  },
  "roles": ["admin-proved"],
  "permissions": ["schools.view", "..."],
  "redirect_url": null,
  "is_super_admin": false
}
```

- **`redirect_url`** : renseigné seulement pour `super-admin` (dashboard Laravel `/admin/...`). Pour les autres rôles, **`null`** → c’est au front de router.
- Conserver **`token`** + sérialiser **`user`**, **`roles`**, **`permissions`**.

### 2.2 En-têtes sur toutes les requêtes API

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### 2.3 Profil courant

```http
GET /api/v1/auth/user-info
Authorization: Bearer …
```

Utile au refresh de page ; recharger `proved_id`, `sous_division_id`, `school_id` et les rôles.

### 2.4 Comptes démo (après seed)

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| `admin-proved@demo.local` | `password` | `admin-proved` |
| `admin-sd@demo.local` | `password` | `admin-sous-division` |

---

## 3. API Organisation (module à implémenter en priorité)

**Préfixe :** `/api/v1/organization`  
**Middleware :** `auth:sanctum` + rôle `super-admin|admin-proved|admin-sous-division`

Convention réponse :

```json
{
  "data": { },
  "message": "…"
}
```

Erreurs : `401` (non connecté), `403` (policy / rôle), `422` (validation).

### 3.1 Proved

| Action | Méthode | URI | Qui |
|--------|---------|-----|-----|
| Liste | `GET` | `/api/v1/organization/proveds` | `super-admin`, `admin-proved` (voit le sien) |
| Détail + SD | `GET` | `/api/v1/organization/proveds/{id}` | idem |
| Créer | `POST` | `/api/v1/organization/proveds` | `super-admin` seul |
| Modifier | `PUT` | `/api/v1/organization/proveds/{id}` | `super-admin` ; `admin-proved` sur **son** proved |
| Supprimer | `DELETE` | `/api/v1/organization/proveds/{id}` | `super-admin` |
| SD du proved | `GET` | `/api/v1/organization/proveds/{id}/sous-divisions` | `super-admin`, `admin-proved` |

**Corps POST/PUT Proved :**

```json
{
  "name": "PROVED Kinshasa",
  "code": "PKIN",
  "province_id": 1,
  "address": "…",
  "phone": "…",
  "email": "proved@example.com"
}
```

`code` : unique global. `province_id` : nullable.

### 3.2 Sous-division

| Action | Méthode | URI | Qui |
|--------|---------|-----|-----|
| Liste | `GET` | `/api/v1/organization/sous-divisions` | tous les 3 rôles (filtré serveur) |
| Détail + écoles | `GET` | `/api/v1/organization/sous-divisions/{id}` | idem |
| Créer | `POST` | `/api/v1/organization/sous-divisions` | `super-admin`, `admin-proved` |
| Modifier | `PUT` | `/api/v1/organization/sous-divisions/{id}` | `super-admin`, `admin-proved` |
| Supprimer | `DELETE` | `/api/v1/organization/sous-divisions/{id}` | idem |
| Écoles rattachées | `GET` | `/api/v1/organization/sous-divisions/{id}/schools` | idem |

**Query liste :**

```http
GET /api/v1/organization/sous-divisions?proved_id=2
```

**Corps POST/PUT Sous-division :**

```json
{
  "proved_id": 1,
  "name": "SD Centre",
  "code": "SD-C"
}
```

`code` : unique **par proved**.  
Si l’utilisateur est `admin-proved`, le backend **force** `proved_id` à `user.proved_id` (inutile de l’envoyer, mais pas bloquant).

**Réponse liste (champs typiques) :** chaque SD inclut souvent `proved`, `schools_count`.

**Réponse détail :** `data` contient `proved` et `schools` (relation chargée).

**Réponse écoles :**

```json
{
  "data": [
    { "id": 12, "name": "École A", "city": "…", "sous_division_id": 3 }
  ]
}
```

### 3.3 Filtrage serveur (ne pas re-filtrer à l’aveugle)

| Rôle | `GET …/proveds` | `GET …/sous-divisions` |
|------|-----------------|-------------------------|
| `super-admin` | Tous | Tous (+ `?proved_id=`) |
| `admin-proved` | Son proved uniquement | SD de son `proved_id` |
| `admin-sous-division` | Pas d’accès routes proved | Sa SD uniquement |

Le front peut afficher directement la liste renvoyée ; un second filtre client n’est utile que pour la recherche UX.

---

## 4. Matrice écrans & actions (UX)

### 4.1 `super-admin`

- Menu **Organisation** : CRUD Proved, CRUD Sous-divisions, gestion écoles (`sous_division_id` obligatoire à la création).
- Switchers (équivalent sidebar Blade) : toutes SD / toutes écoles / une école.
- Accès modules métier **après** sélection d’une école (comme aujourd’hui).

### 4.2 `admin-proved`

| Écran | Actions |
|-------|---------|
| Mon Proved | Lecture + édition **de son** proved (`user.proved_id`) |
| Sous-divisions | Liste, créer, modifier, supprimer **dans son proved** |
| Switcher SD | « Toutes » ou une SD → réduit la liste d’écoles du switcher école |
| Écoles | Liste agrégée ; création avec `sous_division_id` dans **son** proved |
| Modules métier | Après **sélection d’une école** dans le périmètre |

**Ne pas afficher :** création de Proved, liste de tous les proved.

### 4.3 `admin-sous-division`

| Écran | Actions |
|-------|---------|
| Ma sous-division | Lecture seule (policy : pas de `update` pour ce rôle) |
| Écoles | Liste via `GET …/sous-divisions/{id}/schools` ou détail SD |
| Création école | Oui, si permission `schools.create` (dans sa SD) |
| Switcher école | Choisir une école parmi celles de sa SD |
| Modules métier | Contexte école obligatoire |

**Ne pas afficher :** CRUD Proved, création/suppression SD, switcher SD.

### 4.4 `admin-ecole`

- Inchangé : une seule `school_id` sur le user, pas de module Organisation.
- Pas de switcher ; les données sont déjà scopées par `user.school_id` + `ScopeBySchool`.

---

## 5. Implémentation front recommandée

### 5.1 Store de contexte (obligatoire)

Exemple de state global :

```ts
type OrgContext = {
  selectedSousDivisionId: number | null; // null = « toutes » (admin-proved)
  selectedSchoolId: number | null;       // null = vue agrégée multi-écoles
  selectedSchoolName: string | null;
  selectedSousDivisionName: string | null;
};
```

**Persistance :** `localStorage` ou sessionStorage par utilisateur (`user.id`).

**Règles :**

- `admin-sous-division` : `selectedSousDivisionId` = `user.sous_division_id` (fixe).
- `admin-proved` : peut changer `selectedSousDivisionId` ; recalculer la liste d’écoles du switcher.
- Tous (proved, SD, super-admin) : avant route `/students`, `/accounting`, etc. → si le métier est **par école**, exiger `selectedSchoolId` ou afficher un écran « Choisissez une école ».

### 5.2 Chargement des listes pour les switchers

```text
admin-proved:
  1. GET /organization/proveds/{user.proved_id}/sous-divisions  → switcher SD
  2. Si SD sélectionnée → GET …/sous-divisions/{sdId}/schools
     Sinon → agréger schools de toutes les SD du proved (boucle ou endpoint dédié)
  3. Stocker selectedSchoolId

admin-sous-division:
  1. GET /organization/sous-divisions/{user.sous_division_id}  (ou /schools)
  2. Switcher école uniquement

super-admin:
  1. GET /organization/sous-divisions?proved_id=… (optionnel)
  2. GET /api/v1/school/schools — attention : liste non filtrée côté API aujourd’hui ;
     préférer filtrage par SD ou coordination backend (§ 8)
```

### 5.3 Garde de routes (router)

Pseudo-code :

```ts
function canAccessOrgAdmin(user) {
  return user.roles.some(r =>
    ['super-admin', 'admin-proved', 'admin-sous-division'].includes(r)
  );
}

function requiresSchoolContext(to) {
  return to.meta.requiresSchool === true;
}

// beforeEach:
if (requiresSchoolContext(to) && !store.selectedSchoolId) {
  return { name: 'select-school', query: { redirect: to.fullPath } };
}
```

### 5.4 Affichage conditionnel (permissions Spatie)

Utiliser le tableau `permissions` du login, pas seulement le rôle :

| Permission | UI |
|------------|-----|
| `schools.view` | Voir listes écoles |
| `schools.create` | Bouton créer école |
| `schools.update` | Édition école |

Les rôles `admin-proved` / `admin-sous-division` n’ont en général **pas** toutes les permissions métier d’un `admin-ecole` : l’accès aux modules passe par **sélection d’école** + policies backend sur chaque endpoint.

### 5.5 Formulaires

**Création sous-division (`admin-proved`) :**

- Masquer le select `proved_id` (valeur = `user.proved_id`).
- Champs : `name`, `code`.

**Création école :**

- Champ obligatoire `sous_division_id` (select alimenté par les SD accessibles).
- Pour `admin-sous-division` : une seule option (sa SD).

**Édition Proved (`admin-proved`) :**

- `PUT /organization/proveds/{user.proved_id}` uniquement.

---

## 6. Parcours utilisateur (wireframes textuels)

### 6.1 Premier login `admin-proved`

```text
Login → Dashboard organisation
  ├─ Carte « Mon PROVED » (nom, code, province)
  ├─ Liste sous-divisions (+ bouton Créer)
  └─ Bandeau contexte :
        [SD: Toutes | SD-C | SD-N]  [École: Vue globale ▼]
```

Clic module « Élèves » sans école → modal « Sélectionnez une école ».

### 6.2 Premier login `admin-sous-division`

```text
Login → Dashboard SD
  ├─ Fiche sous-division (lecture seule)
  ├─ Liste des écoles (table)
  └─ Switcher école uniquement
```

---

## 7. Intégration avec les autres APIs

### 7.1 Écoles (hors module organisation)

```http
GET  /api/v1/school/schools
POST /api/v1/school/schools
```

Le corps création doit inclure **`sous_division_id`** pour les rôles organisationnels (aligné sur le backend web).

### 7.2 Données métier (`ScopeBySchool`)

De nombreux modèles Eloquent appliquent un filtre automatique via `SchoolScopeResolver` **si** la requête API passe par ces modèles **et** qu’il y a un utilisateur authentifié.

Le resolver tient compte de :

- `user.proved_id` / `user.sous_division_id`
- **Session** `selected_sous_division_id` / `selected_school_id` (côté serveur)

→ En SPA, tant que la session n’est pas partagée avec le navigateur, **ne pas compter** sur la session serveur : voir § 8.

### 7.3 Exemple : liste élèves

L’endpoint élèves utilise encore en partie `user.school_id` ou `session('selected_school_id')` selon le rôle.  
Pour `admin-proved` / `admin-sous-division`, **`user.school_id` est souvent `null`** : prévoir soit :

- d’appeler les endpoints métier **après** choix d’école + passage explicite de `school_id` en query (si l’API l’accepte),  
- soit une évolution backend (recommandé) pour lire le contexte depuis un header ou query standard.

---

## 8. Points de coordination backend (à trancher en équipe)

| Sujet | État actuel | Impact front |
|-------|-------------|--------------|
| Switch contexte | Routes **web** + session PHP | Le front gère le contexte en **local** |
| `GET /api/v1/school/schools` | Retourne toutes les écoles | Ne pas utiliser seul pour proved/SD ; préférer `/organization/.../schools` |
| `SchoolScopeResolver` + session | Actif sur requêtes **avec session** | Sanctum Bearer seul : filtrage global scope sur `proved_id` / `sous_division_id` OK, focus école/session **non** |
| Endpoints métier (élèves, etc.) | Parfois `school_id` user ou session | Prévoir query `school_id` ou header `X-School-Id` — **à valider / implémenter côté API** |

**Recommandation produit :** définir un contrat unique, par exemple :

```http
X-Context-School-Id: 42
X-Context-Sous-Division-Id: 3   # optionnel, admin-proved
```

et faire évoluer `SchoolScopeResolver` pour lire ces en-têtes en plus de la session.

En attendant, le front peut :

1. Filtrer côté client les listes déjà restreintes par l’API organisation ;
2. Envoyer `school_id` dans les POST (création élève, etc.) en se basant sur `selectedSchoolId` ;
3. Tester chaque module avec les comptes démo.

---

## 9. Gestion des erreurs

| Code | Cause | Action UI |
|------|-------|-----------|
| `403` | Policy (accès hors périmètre) | Toast « Accès refusé » + retour liste |
| `422` | Validation | Afficher `errors` Laravel par champ |
| `401` | Token expiré | Redirect login |

Exemple validation :

```json
{
  "message": "The code has already been taken.",
  "errors": {
    "code": ["The code has already been taken."]
  }
}
```

---

## 10. Structure de fichiers front (suggestion)

```text
src/
  api/
    organization/
      proved.ts          # CRUD + sousDivisions(provedId)
      sousDivision.ts    # CRUD + schools(sdId)
    http.ts              # intercepteur Bearer
  stores/
    auth.ts
    orgContext.ts        # selectedSchoolId, selectedSousDivisionId
  views/
    organization/
      ProvedList.vue
      ProvedForm.vue
      SousDivisionList.vue
      SousDivisionForm.vue
      SchoolListOrg.vue
    context/
      SelectSchool.vue
  components/
    ContextSwitcher.vue  # bandeau SD + école
  router/
    guards.ts
```

---

## 11. Checklist d’implémentation

- [ ] Après login, lire `proved_id`, `sous_division_id`, `school_id`, `roles`, `permissions`
- [ ] Store contexte + persistance locale
- [ ] Composant switcher SD (`admin-proved` uniquement)
- [ ] Composant switcher école (proved, SD, super-admin)
- [ ] Garde router `requiresSchool` sur modules métier
- [ ] Pages CRUD branchées sur `/api/v1/organization/*`
- [ ] Masquer actions selon rôle (matrice § 4)
- [ ] Création école : select `sous_division_id`
- [ ] Tests manuels comptes démo § 2.4
- [ ] Aligner avec l’équipe backend sur § 8 (header ou query `school_id`)

---

## 12. Référence rapide des URLs

| Ressource | URL |
|-----------|-----|
| Login | `POST /api/v1/auth/login` |
| User info | `GET /api/v1/auth/user-info` |
| Proveds | `/api/v1/organization/proveds` |
| Sous-divisions | `/api/v1/organization/sous-divisions` |
| Écoles d’une SD | `/api/v1/organization/sous-divisions/{id}/schools` |
| Écoles (CRUD général) | `/api/v1/school/schools` |
| Doc OpenAPI (si activée) | `/docs/api` |

---

*Document maintenu par l’équipe PGFE — mettre à jour ce guide si de nouveaux endpoints de contexte API sont ajoutés.*
