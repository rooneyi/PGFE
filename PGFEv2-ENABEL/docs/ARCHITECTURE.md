# Architecture PGFE ENABEL

Guide de lecture du projet et des responsabilités par couche.

## Vue d'ensemble

```
Proved → Sous-division → École → (élèves, classes, compta…)
```

| Canal | Rôle | Routes |
|-------|------|--------|
| **Web admin** | Interface Blade, formulaires, exports | `routes/web/admin.php` + `routes/web/admin/*.php` |
| **API REST** | Clients mobiles / intégrations | `routes/api.php` + `routes/api/*.php` |
| **Organisation** | PROVED, SD, collecte rapide | `routes/web/organization.php` |

## Structure `app/`

| Dossier | Responsabilité |
|---------|----------------|
| `Http/Controllers/Admin/` | HTTP web : vues, redirects, autorisation |
| `Http/Controllers/Api/` | HTTP API : JSON uniquement |
| `Http/Requests/` | Validation des entrées |
| `Http/Resources/` | Transformation des sorties API |
| `Models/` | Eloquent, relations, scopes |
| `Policies/` | Autorisations fines (module par module) |
| `Services/` | **Logique métier** et requêtes réutilisables |
| `Support/` | Helpers transverses (`ApiResponse`) |
| `Exports/` | Exports Excel partagés web + API |

## Règles de responsabilité

1. **Contrôleur** : auth, policy, appel service, réponse HTTP. Pas de requêtes SQL complexes.
2. **Service** : règles métier, agrégations, scoping PROVED/école.
3. **Request** : validation + petits helpers (`provedId()`, etc.).
4. **Policy** : qui peut voir/créer/modifier selon le rôle et le périmètre.

## Module modèle : Collecte rapide

```
config/collecte_rapide.php
app/Models/CollecteRapide.php
app/Policies/CollecteRapidePolicy.php
app/Services/Collecte/
  ├── CollecteRapideSchema.php      # étapes, matrices, totaux
  ├── CollecteRapideExcelService.php
  ├── CollecteRapideExcelMapper.php
  └── CollecteRapideQueryService.php # listes, stats, exports
app/Http/Controllers/Admin/CollecteRapideWebController.php
```

Nouveau module métier → reproduire ce découpage.

## Réponses API

Utiliser `App\Support\ApiResponse` pour les nouveaux endpoints :

```php
return ApiResponse::success($data, 'Message optionnel');
return ApiResponse::created($data, 'Créé');
return ApiResponse::error('Erreur', 422, $errors);
```

Les endpoints existants gardent leur format actuel ; migration progressive domaine par domaine.

## Routes web admin (par domaine)

| Fichier | Contenu |
|---------|---------|
| `admin/dashboard.php` | Dashboard, recherche, sync |
| `organization.php` | PROVED, SD, collecte |
| `admin/schools.php` | Écoles |
| `admin/geo.php` | Pays, provinces, communes |
| `admin/academic.php` | Élèves, classes, présences, délibérations |
| `admin/hr.php` | Personnels |
| `admin/users.php` | Utilisateurs, rôles, inscriptions |
| `admin/accounting.php` | Comptabilité |
| `admin/calendar.php` | Années, semestres, périodes |
| `admin/infra.php` | Infrastructures |
| `admin/stock.php` | Stock |

## Zones sensibles (ne pas refactoriser sans tests)

- Sync : `app/Services/Sync/`
- Comptabilité : observers + journals
- Scoping école : `SchoolScopeResolver`, `ScopeBySchool`
- Ordre des routes inscriptions : `routes/api/students.php`

## Prochaines étapes recommandées

1. Déporter la logique des gros contrôleurs API (présences, inscriptions) vers des services existants.
2. Adopter `ApiResponse` module par module.
3. Migrer les contrôleurs Stock/Infra legacy (`app/Http/Controllers/Stock*.php`) vers `Api/Stock/`.
4. Étendre les Policies au-delà de Organisation / Collecte.
