# Réparation depuis la base actuelle

**Script :** `database/generate_repair_from_current_sql.py`  
**Différence avec** `generate_migration_sql.py` :

| Script | Source | Effet |
|--------|--------|--------|
| `generate_migration_sql.py` | Ancien backup (`ap_*`, `hr_*`) | **TRUNCATE** + réimport complet |
| `generate_repair_from_current_sql.py` | Export de la **base Laravel actuelle** | **Complète** ce qui manque, garde users/registrations |

---

## Quand l’utiliser

- La base contient déjà des **`users`** et/ou **`registrations`** (et parfois **`students`**).
- Beaucoup de tables de référence sont **vides** (école, années, filières, finance, cours, etc.).
- Tu ne veux **pas** repasser par l’ancien système `ap_etudie` / `ap_apprenant`.

---

## Algorithme (résumé)

```text
1. Lire le dump actuel (50+ tables listées dans TABLE_GROUPS)
2. Rapport par groupe (A→O) : lignes présentes / VIDE
3. SQL généré (sans TRUNCATE), dans l’ordre :
   A  Géographie (countries → communes)
   B  types, fonctions, mécanisations, semesters
   C  proveds, sous_divisions
   D  école + rattachement sous_division_id
   E  année scolaire (+ années orphelines) + une seule is_active
   F→I  filière → cycle → niveau → classe
   J  élèves manquants (student_id dans registrations, absent de students)
   K  personnel académique minimal (+ orphelins)
   L  devises, taux, types de frais, modes de paiement, frais
   M  cours général + lien academic_personal_course
   N  parent générique + FK students.parents_id orphelines
   O  conduite minimale
   P  school_id sur users / students / registrations
   Q  FK registrations (filiaire, cycle, année, niveau, classe, personnel, type)
```

---

## Tables analysées dans le dump

Le script lit les `INSERT INTO` des tables ci-dessous (rapport `--report-only`).  
Les groupes **entièrement vides** reçoivent des lignes de bootstrap dans le SQL généré (sauf modules avancés compta/stock : rapport seulement).

| Groupe | Tables |
|--------|--------|
| A | countries, provinces, territories, communes |
| B | types, fonctions, mecanisations, semesters |
| C | proveds, sous_divisions |
| D | schools, school_years |
| E | filiaires, cycles, academic_levels, classrooms |
| F | users, students, parents, academic_personals |
| G | registrations, courses, academic_personal_course, presences, fiche_cotations |
| H | currencies, exchange_rates, fee_types, fees, payment_methods, payment_motifs, payments, accounts, account_numbers, periods |
| I | conduites, conduite_semesters, conduite_grades, fiche_cotations, indiscipline_cases, abandon_cases, disciplinary_actions, person_presences |
| J | personals, person_conges, person_evaluations |
| K | parents, registration_parents |
| L | infra_*, stock_* |
| M | class_comptability, account_plan, journals, … |
| N | documents, student_transfers, plannings, sync_logs, … |
| O | roles, permissions, model_has_roles, model_has_permissions |

Pour la **comptabilité complète**, les **stocks** ou l’**infra**, prévoir en plus `php artisan db:seed` ou un export legacy via `generate_migration_sql.py`.

---

## Prérequis

1. Base Laravel migrée (`php artisan migrate`).
2. Export SQL de la base **actuelle** avec les données métier (voir ci-dessous).

---

## Étape 1 — Exporter la base actuelle

Export **recommandé** (dump complet — le script ignore les tables absentes) :

```bash
mysqldump -u VOTRE_USER -p \
  --no-create-info \
  --complete-insert \
  NOM_DE_LA_BASE > export_pgfe_actuel_complet.sql
```

Export **minimal** (si le fichier est trop gros) :

```bash
mysqldump -u VOTRE_USER -p \
  --no-create-info \
  --complete-insert \
  --tables \
  countries provinces territories communes \
  types fonctions mecanisations semesters \
  proveds sous_divisions \
  schools school_years \
  filiaires cycles academic_levels classrooms \
  users students parents academic_personals \
  registrations courses academic_personal_course \
  currencies exchange_rates fee_types fees payment_methods payments \
  conduites \
  NOM_DE_LA_BASE > export_pgfe_actuel.sql
```

---

## Étape 2 — Analyser sans générer (optionnel)

```bash
cd /home/rooney/Data/INAFRICA/PGFEv2-ENABEL

python3 database/generate_repair_from_current_sql.py \
  --dump /chemin/export_pgfe_actuel.sql \
  --output /tmp/repair.sql \
  --report-only
```

Affiche le nombre de lignes par table et les groupes vides.

---

## Étape 3 — Générer le SQL de réparation

```bash
python3 database/generate_repair_from_current_sql.py \
  --dump /chemin/export_pgfe_actuel.sql \
  --output /chemin/repair_from_current.sql
```

Options utiles :

```bash
python3 database/generate_repair_from_current_sql.py \
  --dump export.sql \
  --output repair.sql \
  --school-id 1 \
  --school-name "Mon école" \
  --school-city "Goma"
```

---

## Étape 4 — Appliquer sur la base

```bash
mysql -u VOTRE_USER -p NOM_DE_LA_BASE < /chemin/repair_from_current.sql
```

Puis vérifier :

```sql
SELECT id, name, sous_division_id FROM schools;
SELECT id, name, is_active FROM school_years WHERE school_id = (SELECT id FROM schools LIMIT 1);
SELECT COUNT(*) FROM registrations;
SELECT COUNT(*) FROM students;
SELECT COUNT(*) FROM courses WHERE school_id = (SELECT id FROM schools LIMIT 1);
SELECT COUNT(*) FROM currencies;
```

Ensuite, si besoin des rôles Spatie et données démo :

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=OrganizationStructureSeeder
```

---

## Comparaison des deux scripts

| | Legacy `generate_migration_sql.py` | Réparation `generate_repair_from_current_sql.py` |
|---|-----------------------------------|--------------------------------------------------|
| Fichier source | `backup_now.sql` (ap_*) | Export base Laravel actuelle |
| users | Recréés depuis hr_employee | **Conservés** |
| registrations | Recréées depuis ap_etudie | **Conservées**, FK réparées |
| students | Recréés depuis ap_apprenant | Conservés + **créés** si id dans registrations |
| TRUNCATE | Oui | **Non** |

---

## Limites

- Les **paiements** existants avec FK cassées ne sont pas tous recalculés automatiquement (trop de dépendances : account_type_id, payment_motif_id, etc.). Utiliser le seed finance ou corriger manuellement.
- **Compta / stock / infra** : rapport dans le dump, bootstrap minimal ou absent — compléter via seeders ou migration legacy.
- Le dump doit contenir des `INSERT INTO` (pas export structure seule).
- Les rôles Spatie ne sont pas créés par le SQL de réparation.

---

## Import Yanonge et Yangambi (2 bases ou 2 écoles)

Chaque école a son **Proved**, sa **sous-division** et son école avec `sous_division_id` :

| École | Proved (code) | Sous-division (code) | Fichiers |
|-------|---------------|----------------------|----------|
| Yanonge | PROVED-YANONGE | SD-YANONGE | `yanonge_good.sql` + `migration_yanonge.sql` |
| Yangambi | PROVED-YANGAMBI | SD-YANGAMBI | `migration_yangambi.sql` seul (import destructif) |

### Base dédiée Yanonge (`pgfe_yanonge` ou base vide migrée)

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder

mysql -u USER -p pgfe_yanonge < yanonge_good.sql
mysql -u USER -p pgfe_yanonge < migration_yanonge.sql

php artisan pgfe:assign-missing-role admin-ecole
```

### Base dédiée Yangambi

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder

mysql -u USER -p pgfe_yangambi < migration_yangambi.sql

php artisan pgfe:assign-missing-role admin-ecole
```

Ne pas importer les deux `migration_*.sql` sur la **même** base : Yangambi fait des `TRUNCATE` sur les tables métier.

Régénérer avec organisation :

```bash
python3 generate_repair_from_current_sql.py --dump yanonge_good.sql --output migration_yanonge.sql \
  --proved-code PROVED-YANONGE --sous-division-code SD-YANONGE --school-name "YANONGE LOCALITE"

python3 generate_migration_sql.py --patch-organization --output migration_yangambi.sql \
  --legacy-school-id 23 --proved-code PROVED-YANGAMBI --sous-division-code SD-YANGAMBI
```

---

## Fichiers

- `database/generate_repair_from_current_sql.py` — générateur
- `database/migration_yanonge.sql`, `database/migration_yangambi.sql` — prêts à l’import
- `docs/algorithme-migration-sql.md` — migration depuis l’ancien système
- `docs/frontend-guide-organisation-proved-sous-division.md` — API école / organisation
