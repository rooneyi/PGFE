#!/usr/bin/env python3
"""
Génère un SQL de RÉPARATION à partir d'un export de la base Laravel ACTUELLE
(mysqldump / phpMyAdmin), sans tables legacy ap_*.

Cas typique : la base ne contient que des données dans users, registrations,
éventuellement students — mais schools, school_years, classrooms, etc. sont vides.

Le SQL produit :
  - n'efface PAS les tables (pas de TRUNCATE) ;
  - insère les lignes de référence manquantes (école, année, niveau, filière, classe, personnel) ;
  - met à jour school_id / FKs orphelines sur users, students, registrations ;
  - active une seule année scolaire pour l'école cible.

Voir : GUIDE-UTILISATION.md (ce dossier) ou docs/algorithme-reparation-base-actuelle.md

Usage :
  python3 generate_repair_from_current_sql.py \\
    --dump export_pgfe_actuel.sql \\
    --output repair.sql
"""

from __future__ import annotations

import argparse
import datetime as dt
import sys
from pathlib import Path
from typing import Dict, List, Optional, Set

# Réutilise le parseur du script legacy (même dossier)
sys.path.insert(0, str(Path(__file__).resolve().parent))
from generate_migration_sql import (  # noqa: E402
    extract_columns,
    extract_rows,
    map_legacy_civil_status,
    map_legacy_gender,
    organization_sql_lines,
    parse_int,
    read_text_with_fallback,
    sql_quote,
)

# Tables analysées dans le dump (rapport + détection d'orphelins)
TABLE_GROUPS: Dict[str, List[str]] = {
    "A — Référence géographique": [
        "countries",
        "provinces",
        "territories",
        "communes",
    ],
    "B — Référence métier": [
        "types",
        "fonctions",
        "mecanisations",
        "semesters",
    ],
    "C — Organisation": [
        "proveds",
        "sous_divisions",
    ],
    "D — École & années": [
        "schools",
        "school_years",
    ],
    "E — Chaîne scolaire": [
        "filiaires",
        "cycles",
        "academic_levels",
        "classrooms",
    ],
    "F — Personnes": [
        "users",
        "students",
        "parents",
        "academic_personals",
    ],
    "G — Inscriptions & pédagogie": [
        "registrations",
        "courses",
        "academic_personal_course",
        "presences",
        "fiche_cotations",
    ],
    "H — Finance (si présentes)": [
        "currencies",
        "exchange_rates",
        "fee_types",
        "fees",
        "payment_methods",
        "payment_motifs",
        "payments",
        "accounts",
        "account_numbers",
        "periods",
    ],
    "I — Conduite / discipline": [
        "conduites",
        "conduite_semesters",
        "conduite_grades",
        "fiche_cotations",
        "indiscipline_cases",
        "abandon_cases",
        "disciplinary_actions",
        "person_presences",
    ],
    "J — RH / personnel": [
        "personals",
        "person_conges",
        "person_evaluations",
    ],
    "K — Parents & liens": [
        "parents",
        "registration_parents",
    ],
    "L — Infrastructure / stock (si présentes)": [
        "infra_categories",
        "infra_infrastructures",
        "infra_inventaires",
        "stock_categories",
        "stock_entries",
        "stock_states",
    ],
    "M — Comptabilité avancée (si présentes)": [
        "class_comptability",
        "account_plan",
        "sub_account_plan",
        "journals",
        "annalytique_comptabilities",
        "ammortissement_comptabilities",
    ],
    "N — Autres modules (si présentes)": [
        "documents",
        "student_transfers",
        "student_exits",
        "student_activities",
        "schoolwork_plannings",
        "work_deposits",
        "planning_files",
        "teacher_unavailabilities",
        "sync_logs",
    ],
    "O — Permissions Spatie (si présentes)": [
        "roles",
        "permissions",
        "model_has_roles",
        "model_has_permissions",
    ],
}

CURRENT_TABLES: List[str] = []
for _tables in TABLE_GROUPS.values():
    for t in _tables:
        if t not in CURRENT_TABLES:
            CURRENT_TABLES.append(t)


def parse_current_dump(dump_path: Path) -> Dict[str, List[dict]]:
    text = read_text_with_fallback(dump_path)
    out: Dict[str, List[dict]] = {}
    for table in CURRENT_TABLES:
        cols: List[str] = []
        try:
            cols = extract_columns(text, table)
        except ValueError:
            pass
        rows = extract_rows(text, table, cols)
        out[table] = [r.values for r in rows]
    return out


def collect_ids(rows: List[dict], key: str) -> Set[int]:
    ids: Set[int] = set()
    for r in rows:
        v = parse_int(r.get(key), 0)
        if v > 0:
            ids.add(v)
    return ids


def first_positive(rows: List[dict], key: str) -> Optional[int]:
    for r in rows:
        v = parse_int(r.get(key), 0)
        if v > 0:
            return v
    return None


def build_repair_sql(
    data: Dict[str, List[dict]],
    school_name: str,
    school_city: str,
    school_address: str,
    forced_school_id: Optional[int],
    proved_name: str = "PROVED Migration",
    proved_code: str = "PROV-MIG",
    sous_division_name: str = "Sous-division migration",
    sous_division_code: str = "SD-MIG",
) -> str:
    schools = data.get("schools", [])
    years = data.get("school_years", [])
    users = data.get("users", [])
    students = data.get("students", [])
    registrations = data.get("registrations", [])
    levels = data.get("academic_levels", [])
    filiaires = data.get("filiaires", [])
    classrooms = data.get("classrooms", [])
    personals = data.get("academic_personals", [])

    school_ids = collect_ids(schools, "id")
    reg_school_ids = collect_ids(registrations, "school_id")
    user_school_ids = collect_ids(users, "school_id")
    student_school_ids = collect_ids(students, "school_id")

    inferred_school = (
        forced_school_id
        if forced_school_id and forced_school_id > 0
        else first_positive(registrations, "school_id")
        or first_positive(users, "school_id")
        or first_positive(students, "school_id")
        or (min(school_ids) if school_ids else None)
    )

    year_ids = collect_ids(years, "id")
    reg_year_ids = collect_ids(registrations, "school_year_id")
    missing_year_ids = sorted(reg_year_ids - year_ids)

    classroom_ids = collect_ids(classrooms, "id")
    reg_classroom_ids = collect_ids(registrations, "classroom_id")
    missing_classroom_ids = sorted(reg_classroom_ids - classroom_ids)

    level_ids = collect_ids(levels, "id")
    reg_level_ids = collect_ids(registrations, "academic_level_id")
    missing_level_ids = sorted(reg_level_ids - level_ids)

    personal_ids = collect_ids(personals, "id")
    reg_personal_ids = collect_ids(registrations, "academic_personal_id")
    missing_personal_ids = sorted(reg_personal_ids - personal_ids)

    reg_type_ids = collect_ids(registrations, "type_id")

    active_year_names = [
        str(r.get("name"))
        for r in years
        if parse_int(r.get("is_active"), 0) == 1 and r.get("name")
    ]
    current_year = dt.date.today().year
    default_year_name = active_year_names[0] if active_year_names else f"{current_year}-{current_year + 1}"

    lines: List[str] = []
    lines.append("-- Réparation PGFE : base Laravel actuelle (non destructif)")
    lines.append(f"-- Généré le {dt.datetime.now().isoformat(timespec='seconds')}")
    lines.append("--")
    lines.append(f"-- schools dans le dump        : {len(schools)}")
    lines.append(f"-- school_years                  : {len(years)}")
    lines.append(f"-- users                         : {len(users)}")
    lines.append(f"-- students                      : {len(students)}")
    lines.append(f"-- registrations                 : {len(registrations)}")
    lines.append(f"-- academic_levels               : {len(levels)}")
    lines.append(f"-- filiaires                     : {len(filiaires)}")
    lines.append(f"-- classrooms                    : {len(classrooms)}")
    lines.append(f"-- academic_personals            : {len(personals)}")
    if missing_year_ids:
        lines.append(f"-- school_year_id orphelins     : {missing_year_ids}")
    if missing_classroom_ids:
        lines.append(f"-- classroom_id orphelins       : {missing_classroom_ids}")
    lines.append("")
    lines.append("SET NAMES utf8mb4;")
    lines.append("SET FOREIGN_KEY_CHECKS = 0;")
    lines.append("START TRANSACTION;")
    lines.append("")
    lines.append("SET @mig_now := NOW();")
    lines.append("")

    # --- A) Référence géographique minimale ---
    lines.append("-- A) Géographie (si tables vides)")
    lines.append(
        "INSERT INTO countries (name, created_at, updated_at)\n"
        f"SELECT 'République Démocratique du Congo', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM countries LIMIT 1);"
    )
    lines.append("SET @mig_default_country_id := (SELECT id FROM countries ORDER BY id LIMIT 1);")
    lines.append(
        "INSERT INTO provinces (country_id, name, created_at, updated_at)\n"
        "SELECT @mig_default_country_id, 'Province migration', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM provinces LIMIT 1);"
    )
    lines.append("SET @mig_default_province_id := (SELECT id FROM provinces ORDER BY id LIMIT 1);")
    lines.append(
        "INSERT INTO territories (province_id, name, created_at, updated_at)\n"
        "SELECT @mig_default_province_id, 'Territoire migration', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM territories LIMIT 1);"
    )
    lines.append("SET @mig_default_territory_id := (SELECT id FROM territories ORDER BY id LIMIT 1);")
    lines.append(
        "INSERT INTO communes (province_id, name, created_at, updated_at)\n"
        "SELECT @mig_default_province_id, 'Commune migration', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM communes LIMIT 1);"
    )
    lines.append("SET @mig_default_commune_id := (SELECT id FROM communes ORDER BY id LIMIT 1);")
    lines.append("")

    # --- B) Référence métier ---
    lines.append("-- B) Types, fonctions, mécanisations, semestres")
    lines.append(
        "INSERT INTO types (title, created_at, updated_at)\n"
        "SELECT 'Formel', @mig_now, @mig_now FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM types LIMIT 1);"
    )
    lines.append(
        "INSERT INTO types (title, created_at, updated_at)\n"
        "SELECT 'Non formel', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM types WHERE title = 'Non formel');"
    )
    lines.append("SET @mig_default_type_id := (SELECT id FROM types ORDER BY id LIMIT 1);")
    lines.append(
        "INSERT INTO fonctions (name, created_at, updated_at)\n"
        "SELECT 'Fonction migration', @mig_now, @mig_now FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM fonctions LIMIT 1);"
    )
    lines.append("SET @mig_default_fonction_id := (SELECT id FROM fonctions ORDER BY id LIMIT 1);")
    lines.append(
        "INSERT INTO mecanisations (label, description, created_at, updated_at)\n"
        "SELECT 'Migration', 'Créé par script de réparation', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM mecanisations LIMIT 1);"
    )
    lines.append(
        "SET @mig_default_mecanisation_id := (SELECT id FROM mecanisations ORDER BY id LIMIT 1);"
    )
    lines.append(
        "INSERT INTO semesters (name, created_at, updated_at)\n"
        "SELECT '1er Semestre', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM semesters WHERE name = '1er Semestre');"
    )
    lines.append(
        "INSERT INTO semesters (name, created_at, updated_at)\n"
        "SELECT '2e Semestre', @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM semesters WHERE name = '2e Semestre');"
    )
    lines.append("")

    # --- C) Organisation Proved / SD / École ---
    lines.extend(
        organization_sql_lines(
            proved_name,
            proved_code,
            sous_division_name,
            sous_division_code,
            school_name,
            school_city,
            school_address,
            school_id=inferred_school,
        )
    )

    # --- Année scolaire par défaut ---
    lines.append("-- E) Année scolaire pour l'école")
    lines.append(
        "INSERT INTO school_years (school_id, name, is_active, description, created_at, updated_at, deleted_at)\n"
        f"SELECT @mig_school_id, {sql_quote(default_year_name)}, 0, "
        "'Créée par script de réparation', @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND NOT EXISTS (\n"
        "    SELECT 1 FROM school_years sy WHERE sy.school_id = @mig_school_id\n"
        "  );"
    )
    for orphan_year_id in missing_year_ids:
        label = f"Année récupérée #{orphan_year_id}"
        lines.append(
            "INSERT INTO school_years (school_id, name, is_active, description, created_at, updated_at, deleted_at)\n"
            f"SELECT @mig_school_id, {sql_quote(label)}, 0, 'FK orpheline registrations', @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE @mig_school_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM school_years WHERE id = {orphan_year_id});"
        )
        lines.append(
            f"UPDATE registrations SET school_year_id = (SELECT sy.id FROM school_years sy "
            f"WHERE sy.school_id = @mig_school_id AND sy.name = {sql_quote(label)} LIMIT 1)\n"
            f"WHERE school_year_id = {orphan_year_id};"
        )
    lines.append(
        "SET @mig_school_year_id := (SELECT sy.id FROM school_years sy "
        "WHERE sy.school_id = @mig_school_id ORDER BY sy.is_active DESC, sy.id DESC LIMIT 1);"
    )
    lines.append("UPDATE school_years SET is_active = 0 WHERE school_id = @mig_school_id;")
    lines.append(
        "UPDATE school_years SET is_active = 1 "
        "WHERE school_id = @mig_school_id AND id = @mig_school_year_id;"
    )
    lines.append("")

    # --- Filière → Cycle → Niveau (chaîne PGFE) ---
    lines.append("-- F) Filière minimale pour l'école")
    lines.append(
        "INSERT INTO filiaires (uuid, school_id, name, code, created_at, updated_at, deleted_at)\n"
        "SELECT UUID(), @mig_school_id, 'Filière générale', 'GEN', @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM filiaires f WHERE f.school_id = @mig_school_id);"
    )
    lines.append(
        "SET @mig_default_filiaire_id := (SELECT id FROM filiaires WHERE school_id = @mig_school_id ORDER BY id LIMIT 1);"
    )
    lines.append("")
    lines.append("-- G) Cycle scolaire")
    lines.append(
        "INSERT INTO cycles (school_id, filiaire_id, name, created_at, updated_at)\n"
        "SELECT @mig_school_id, @mig_default_filiaire_id, 'Cycle général', @mig_now, @mig_now\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM cycles c WHERE c.school_id = @mig_school_id);"
    )
    lines.append(
        "SET @mig_default_cycle_id := (SELECT id FROM cycles WHERE school_id = @mig_school_id ORDER BY id LIMIT 1);"
    )
    lines.append("")
    lines.append("-- H) Niveau académique (rattaché au cycle)")
    lines.append(
        "INSERT INTO academic_levels (uuid, cycle_id, name, created_at, updated_at, deleted_at)\n"
        "SELECT UUID(), @mig_default_cycle_id, 'Niveau général', @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM academic_levels LIMIT 1);"
    )
    lines.append(
        "SET @mig_default_level_id := (SELECT id FROM academic_levels ORDER BY id LIMIT 1);"
    )
    for orphan_level_id in missing_level_ids:
        label = f"Niveau récupéré #{orphan_level_id}"
        lines.append(
            "INSERT INTO academic_levels (uuid, cycle_id, name, created_at, updated_at, deleted_at)\n"
            f"SELECT UUID(), @mig_default_cycle_id, {sql_quote(label)}, @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (SELECT 1 FROM academic_levels WHERE id = {orphan_level_id});"
        )
        lines.append(
            f"UPDATE registrations SET academic_level_id = (SELECT id FROM academic_levels "
            f"WHERE name = {sql_quote(label)} LIMIT 1)\n"
            f"WHERE academic_level_id = {orphan_level_id};"
        )
    lines.append("")

    # --- Classe ---
    lines.append("-- I) Classe minimale")
    lines.append(
        "INSERT INTO classrooms (uuid, school_id, filiaire_id, academic_level_id, name, indicator, "
        "created_at, updated_at, titulaire_id, deleted_at)\n"
        "SELECT UUID(), @mig_school_id, @mig_default_filiaire_id, @mig_default_level_id, "
        "'Classe générale', NULL, @mig_now, @mig_now, NULL, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND @mig_default_filiaire_id IS NOT NULL\n"
        "  AND @mig_default_level_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM classrooms cl WHERE cl.school_id = @mig_school_id);"
    )
    lines.append(
        "SET @mig_default_classroom_id := (SELECT id FROM classrooms WHERE school_id = @mig_school_id ORDER BY id LIMIT 1);"
    )
    for orphan_class_id in missing_classroom_ids:
        label = f"Classe récupérée #{orphan_class_id}"
        lines.append(
            "INSERT INTO classrooms (uuid, school_id, filiaire_id, academic_level_id, name, indicator, "
            "created_at, updated_at, titulaire_id, deleted_at)\n"
            f"SELECT UUID(), @mig_school_id, @mig_default_filiaire_id, @mig_default_level_id, "
            f"{sql_quote(label)}, NULL, @mig_now, @mig_now, NULL, NULL\n"
            "FROM DUAL\n"
            f"WHERE @mig_school_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classrooms WHERE id = {orphan_class_id});"
        )
        lines.append(
            f"UPDATE registrations SET classroom_id = (SELECT id FROM classrooms "
            f"WHERE school_id = @mig_school_id AND name = {sql_quote(label)} LIMIT 1)\n"
            f"WHERE classroom_id = {orphan_class_id};"
        )
    lines.append("")

    # --- Élèves manquants (IDs présents dans registrations seulement) ---
    lines.append("-- J) Créer les élèves référencés par registrations mais absents de students")
    lines.append(
        "INSERT INTO students (\n"
        "  id, uuid, province_id, territory_id, commune_id, matricule, name, firstname,\n"
        "  gender, civil_status, address, birth_date, birth_place, school_id, country_id,\n"
        "  created_at, updated_at, deleted_at\n"
        ")\n"
        "SELECT\n"
        "  r.student_id,\n"
        "  UUID(),\n"
        "  @mig_default_province_id,\n"
        "  @mig_default_territory_id,\n"
        "  @mig_default_commune_id,\n"
        "  CONCAT('MIG-', r.student_id),\n"
        "  CONCAT('Élève ', r.student_id),\n"
        "  CONCAT('Élève ', r.student_id),\n"
        f"  {sql_quote(map_legacy_gender(0))},\n"
        f"  {sql_quote(map_legacy_civil_status(0, {}))},\n"
        "  'Adresse migration',\n"
        "  '1990-01-01',\n"
        "  'N/A',\n"
        "  @mig_school_id,\n"
        "  @mig_default_country_id,\n"
        "  @mig_now,\n"
        "  @mig_now,\n"
        "  NULL\n"
        "FROM registrations r\n"
        "WHERE r.student_id IS NOT NULL\n"
        "  AND @mig_school_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM students s WHERE s.id = r.student_id)\n"
        "GROUP BY r.student_id;"
    )
    lines.append("")

    # --- Personnel académique minimal ---
    lines.append("-- K) Personnel académique minimal (pour FK registrations)")
    lines.append(
        "INSERT INTO academic_personals (\n"
        "  uuid, user_id, mechanisation_id, country_id, province_id, territory_id, commune_id,\n"
        "  school_id, type_id, father_id, mother_id, academic_level_id, fonction_id,\n"
        "  matricule, name, post_name, pre_name, username, phone, email, identity_card_number,\n"
        "  gender, civil_status, physical_address, birth_date, birth_place,\n"
        "  created_at, updated_at, deleted_at, image\n"
        ")\n"
        "SELECT\n"
        "  UUID(),\n"
        "  (SELECT u.id FROM users u ORDER BY u.id LIMIT 1),\n"
        "  @mig_default_mecanisation_id, @mig_default_country_id,\n"
        "  (SELECT id FROM provinces ORDER BY id LIMIT 1),\n"
        "  (SELECT id FROM territories ORDER BY id LIMIT 1),\n"
        "  (SELECT id FROM communes ORDER BY id LIMIT 1),\n"
        "  @mig_school_id, @mig_default_type_id, NULL, NULL, @mig_default_level_id, @mig_default_fonction_id,\n"
        "  'MIG-PERSONNEL-1', 'Personnel migration', 'N/A', 'N/A', 'migration', '+243000000001',\n"
        f"  'migration@pgfe.local', 'MIG-ID-1', {sql_quote(map_legacy_gender(0))}, "
        f"{sql_quote(map_legacy_civil_status(0, {}))}, 'N/A', '1990-01-01', 'N/A',\n"
        "  @mig_now, @mig_now, NULL, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM academic_personals LIMIT 1);"
    )
    lines.append(
        "SET @mig_default_personal_id := (SELECT id FROM academic_personals ORDER BY id LIMIT 1);"
    )
    for orphan_personal_id in missing_personal_ids:
        matricule = f"MIG-PERS-{orphan_personal_id}"
        lines.append(
            "INSERT INTO academic_personals (\n"
            "  uuid, user_id, country_id, province_id, territory_id, commune_id, school_id, type_id,\n"
            "  academic_level_id, fonction_id, matricule, name, post_name, pre_name, username, phone, email,\n"
            "  identity_card_number, gender, civil_status, physical_address, birth_date, birth_place,\n"
            "  created_at, updated_at, deleted_at\n"
            ")\n"
            "SELECT UUID(), (SELECT u.id FROM users u ORDER BY u.id LIMIT 1),\n"
            "  @mig_default_country_id,\n"
            "  (SELECT id FROM provinces ORDER BY id LIMIT 1),\n"
            "  (SELECT id FROM territories ORDER BY id LIMIT 1),\n"
            "  (SELECT id FROM communes ORDER BY id LIMIT 1),\n"
            "  @mig_school_id, @mig_default_type_id, @mig_default_level_id, @mig_default_fonction_id,\n"
            f"  {sql_quote(matricule)}, 'Personnel récupéré', 'N/A', 'N/A', {sql_quote(matricule)}, '+243000000099',\n"
            f"  {sql_quote(matricule + '@pgfe.local')}, {sql_quote('MIG-' + str(orphan_personal_id))},\n"
            f"  {sql_quote(map_legacy_gender(0))}, {sql_quote(map_legacy_civil_status(0, {}))}, "
            "'N/A', '1990-01-01', 'N/A', @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (SELECT 1 FROM academic_personals WHERE id = {orphan_personal_id});"
        )
        lines.append(
            f"UPDATE registrations SET academic_personal_id = (SELECT id FROM academic_personals "
            f"WHERE matricule = {sql_quote(matricule)} LIMIT 1)\n"
            f"WHERE academic_personal_id = {orphan_personal_id};"
        )
    lines.append("")

    # --- Finance minimale ---
    lines.append("-- L) Référentiels finance (si tables vides)")
    lines.append(
        "INSERT INTO currencies (code, name, symbol, is_default, deleted_at, created_at, updated_at)\n"
        "SELECT 'CDF', 'Franc congolais', 'FC', 1, NULL, @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM currencies WHERE code = 'CDF');"
    )
    lines.append(
        "INSERT INTO currencies (code, name, symbol, is_default, deleted_at, created_at, updated_at)\n"
        "SELECT 'USD', 'Dollar américain', '$', 0, NULL, @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM currencies WHERE code = 'USD');"
    )
    lines.append("SET @mig_default_currency_id := (SELECT id FROM currencies WHERE code = 'CDF' LIMIT 1);")
    lines.append(
        "INSERT INTO exchange_rates (currency_id, school_id, rate, date_effective, is_active, created_at, updated_at, deleted_at)\n"
        "SELECT @mig_default_currency_id, @mig_school_id, 1.000000, CURDATE(), 1, @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL AND @mig_default_currency_id IS NOT NULL\n"
        "  AND NOT EXISTS (\n"
        "    SELECT 1 FROM exchange_rates er WHERE er.school_id = @mig_school_id AND er.is_active = 1\n"
        "  );"
    )
    lines.append(
        "INSERT INTO fee_types (name, code, description, created_at, updated_at, deleted_at)\n"
        "SELECT 'Frais scolaires', 'tuition', 'Créé par réparation', @mig_now, @mig_now, NULL FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM fee_types WHERE code = 'tuition');"
    )
    lines.append("SET @mig_default_fee_type_id := (SELECT id FROM fee_types WHERE code = 'tuition' LIMIT 1);")
    lines.append(
        "INSERT INTO payment_methods (name, code, deleted_at, created_at, updated_at)\n"
        "SELECT 'Espèces', 'cash', NULL, @mig_now, @mig_now FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM payment_methods WHERE code = 'cash');"
    )
    lines.append(
        "INSERT INTO fees (label, amount, currency_id, fee_type_id, school_id, effective_date, created_at, updated_at, deleted_at)\n"
        "SELECT 'Frais migration', 0, @mig_default_currency_id, @mig_default_fee_type_id, @mig_school_id, CURDATE(), @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL AND @mig_default_currency_id IS NOT NULL AND @mig_default_fee_type_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM fees WHERE school_id = @mig_school_id);"
    )
    lines.append("")

    # --- Cours minimal ---
    lines.append("-- M) Cours & lien enseignant (si aucun cours pour l'école)")
    lines.append(
        "INSERT INTO courses (\n"
        "  uuid, label, academic_level_id, filiaire_id, school_id, cycle_id, classroom_id,\n"
        "  hourly_volume, max_period_1, max_period_2, max_period_3, max_period_4, max_exam_1, max_exam_2,\n"
        "  created_at, updated_at, deleted_at\n"
        ")\n"
        "SELECT UUID(), 'Cours général', @mig_default_level_id, @mig_default_filiaire_id, @mig_school_id,\n"
        "  @mig_default_cycle_id, @mig_default_classroom_id, 1, 10, 10, 10, 10, 20, 20, @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND @mig_default_level_id IS NOT NULL\n"
        "  AND @mig_default_filiaire_id IS NOT NULL\n"
        "  AND @mig_default_classroom_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.school_id = @mig_school_id);"
    )
    lines.append("SET @mig_default_course_id := (SELECT id FROM courses WHERE school_id = @mig_school_id ORDER BY id LIMIT 1);")
    lines.append(
        "INSERT INTO academic_personal_course (course_id, academic_personal_id, created_at, updated_at)\n"
        "SELECT @mig_default_course_id, @mig_default_personal_id, @mig_now, @mig_now\n"
        "FROM DUAL\n"
        "WHERE @mig_default_course_id IS NOT NULL AND @mig_default_personal_id IS NOT NULL\n"
        "  AND NOT EXISTS (\n"
        "    SELECT 1 FROM academic_personal_course apc\n"
        "    WHERE apc.course_id = @mig_default_course_id AND apc.academic_personal_id = @mig_default_personal_id\n"
        "  );"
    )
    lines.append("")

    # --- Parents minimal (pour FK students.parents_id) ---
    lines.append("-- N) Parent générique (si table parents vide)")
    lines.append(
        "INSERT INTO parents (uuid, school_id, name, firstname, lastname, genre, phone_number, identity_card, created_at, updated_at, deleted_at)\n"
        "SELECT UUID(), @mig_school_id, 'Parent', 'Migration', 'Générique', 'male', '+243900000001', 'MIG-PARENT-1', @mig_now, @mig_now, NULL\n"
        "FROM DUAL\n"
        "WHERE NOT EXISTS (SELECT 1 FROM parents LIMIT 1);"
    )
    lines.append("SET @mig_default_parent_id := (SELECT id FROM parents ORDER BY id LIMIT 1);")
    lines.append(
        "UPDATE students SET parents_id = @mig_default_parent_id\n"
        "WHERE @mig_default_parent_id IS NOT NULL\n"
        "  AND parents_id IS NOT NULL\n"
        "  AND parents_id > 0\n"
        "  AND NOT EXISTS (SELECT 1 FROM parents p WHERE p.id = students.parents_id);"
    )
    lines.append(
        "UPDATE parents SET school_id = @mig_school_id\n"
        "WHERE @mig_school_id IS NOT NULL AND (school_id IS NULL OR school_id = 0);"
    )
    lines.append("")

    # --- Conduite minimale ---
    lines.append("-- O) Conduite scolaire (optionnel)")
    lines.append(
        "INSERT INTO conduites (school_id, label, created_at, updated_at)\n"
        "SELECT @mig_school_id, 'Conduite générale', @mig_now, @mig_now FROM DUAL\n"
        "WHERE @mig_school_id IS NOT NULL\n"
        "  AND NOT EXISTS (SELECT 1 FROM conduites WHERE school_id = @mig_school_id);"
    )
    lines.append("")

    # --- Rattachements school_id ---
    lines.append("-- P) Rattacher school_id sur users / students / registrations")
    lines.append(
        "UPDATE users SET school_id = @mig_school_id "
        "WHERE @mig_school_id IS NOT NULL AND (school_id IS NULL OR school_id = 0);"
    )
    lines.append(
        "UPDATE students SET school_id = @mig_school_id "
        "WHERE @mig_school_id IS NOT NULL AND (school_id IS NULL OR school_id = 0);"
    )
    lines.append(
        "UPDATE registrations SET school_id = @mig_school_id "
        "WHERE @mig_school_id IS NOT NULL AND (school_id IS NULL OR school_id = 0);"
    )
    lines.append("")

    # --- FK registrations vers défauts si encore invalides ---
    lines.append("-- Q) Compléter FK registrations manquantes")
    if registrations:
        lines.append(
            "UPDATE registrations SET filiaire_id = @mig_default_filiaire_id\n"
            "WHERE @mig_default_filiaire_id IS NOT NULL\n"
            "  AND (filiaire_id IS NULL OR filiaire_id = 0\n"
            "       OR NOT EXISTS (SELECT 1 FROM filiaires f WHERE f.id = registrations.filiaire_id));"
        )
        lines.append(
            "UPDATE registrations SET cycle_id = @mig_default_cycle_id\n"
            "WHERE @mig_default_cycle_id IS NOT NULL\n"
            "  AND (cycle_id IS NULL OR cycle_id = 0\n"
            "       OR NOT EXISTS (SELECT 1 FROM cycles c WHERE c.id = registrations.cycle_id));"
        )
    lines.append(
        "UPDATE registrations SET school_year_id = @mig_school_year_id\n"
        "WHERE @mig_school_year_id IS NOT NULL\n"
        "  AND (school_year_id IS NULL OR school_year_id = 0\n"
        "       OR NOT EXISTS (SELECT 1 FROM school_years sy WHERE sy.id = registrations.school_year_id));"
    )
    lines.append(
        "UPDATE registrations SET academic_level_id = @mig_default_level_id\n"
        "WHERE @mig_default_level_id IS NOT NULL\n"
        "  AND (academic_level_id IS NULL OR academic_level_id = 0\n"
        "       OR NOT EXISTS (SELECT 1 FROM academic_levels al WHERE al.id = registrations.academic_level_id));"
    )
    lines.append(
        "UPDATE registrations SET classroom_id = @mig_default_classroom_id\n"
        "WHERE @mig_default_classroom_id IS NOT NULL\n"
        "  AND (classroom_id IS NULL OR classroom_id = 0\n"
        "       OR NOT EXISTS (SELECT 1 FROM classrooms cl WHERE cl.id = registrations.classroom_id));"
    )
    lines.append(
        "UPDATE registrations SET academic_personal_id = @mig_default_personal_id\n"
        "WHERE @mig_default_personal_id IS NOT NULL\n"
        "  AND (academic_personal_id IS NULL OR academic_personal_id = 0\n"
        "       OR NOT EXISTS (SELECT 1 FROM academic_personals ap WHERE ap.id = registrations.academic_personal_id));"
    )
    lines.append(
        "UPDATE registrations SET type_id = @mig_default_type_id\n"
        "WHERE @mig_default_type_id IS NOT NULL\n"
        "  AND (type_id IS NULL OR type_id = 0\n"
        "       OR NOT EXISTS (SELECT 1 FROM types ty WHERE ty.id = registrations.type_id));"
    )

    if reg_type_ids:
        lines.append("")
        lines.append(f"-- type_id distincts vus dans registrations : {sorted(reg_type_ids)}")

    lines.append("")
    lines.append("COMMIT;")
    lines.append("SET FOREIGN_KEY_CHECKS = 1;")
    lines.append("")
    lines.append("-- Fin réparation (relancer l'app / vérifier registrations avec élèves existants)")

    return "\n".join(lines) + "\n"


def print_report(data: Dict[str, List[dict]]) -> None:
    print("=== Analyse du dump actuel (par groupe) ===")
    empty_groups: List[str] = []
    for group, tables in TABLE_GROUPS.items():
        print(f"\n{group}")
        group_empty = True
        for table in tables:
            n = len(data.get(table, []))
            flag = "ok" if n > 0 else "VIDE"
            print(f"  {table:28} : {n:5} ligne(s)  [{flag}]")
            if n > 0:
                group_empty = False
        if group_empty:
            empty_groups.append(group)
    regs = data.get("registrations", [])
    students = data.get("students", [])
    if regs:
        reg_student_ids = collect_ids(regs, "student_id")
        student_ids = collect_ids(students, "id")
        orphan_students = sorted(reg_student_ids - student_ids)
        print("\n--- Cohérence registrations ---")
        print(f"  registrations avec school_id      : {sum(1 for r in regs if parse_int(r.get('school_id'), 0) > 0)}")
        print(f"  registrations sans school_id      : {sum(1 for r in regs if parse_int(r.get('school_id'), 0) <= 0)}")
        if orphan_students:
            print(f"  student_id dans registrations mais ABSENTS de students (dump) : {orphan_students[:20]}{'…' if len(orphan_students) > 20 else ''}")
            print(f"    → total orphelins : {len(orphan_students)} (le SQL peut les créer)")
    if empty_groups:
        print("\n--- Groupes entièrement vides dans le dump (seront complétés par le SQL) ---")
        for g in empty_groups:
            print(f"  - {g}")
    print("")


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Génère un SQL de réparation depuis un export de la base Laravel actuelle."
    )
    parser.add_argument(
        "--dump",
        required=True,
        help="Export SQL de la base actuelle (mysqldump). Doit contenir INSERT INTO users/registrations/…",
    )
    parser.add_argument("--output", required=True, help="Fichier SQL de sortie")
    parser.add_argument(
        "--school-id",
        type=int,
        default=None,
        help="Forcer l'école cible si déjà connue",
    )
    parser.add_argument("--school-name", default="École PGFE (réparation)")
    parser.add_argument("--school-city", default="Kinshasa")
    parser.add_argument("--school-address", default="Adresse — réparation données existantes")
    parser.add_argument(
        "--report-only",
        action="store_true",
        help="Affiche l'analyse sans écrire le fichier SQL",
    )
    parser.add_argument("--proved-name", default="PROVED Migration")
    parser.add_argument("--proved-code", default="PROV-MIG")
    parser.add_argument("--sous-division-name", default="Sous-division migration")
    parser.add_argument("--sous-division-code", default="SD-MIG")

    args = parser.parse_args()
    dump_path = Path(args.dump)
    if not dump_path.exists():
        print(f"[ERROR] Fichier introuvable : {dump_path}", file=sys.stderr)
        return 1

    data = parse_current_dump(dump_path)
    print_report(data)

    if not data.get("registrations") and not data.get("users"):
        print(
            "[WARN] Aucune ligne users/registrations dans le dump. "
            "Vérifiez que l'export contient bien INSERT INTO `users` / `registrations`.",
            file=sys.stderr,
        )

    if args.report_only:
        return 0

    sql = build_repair_sql(
        data,
        school_name=args.school_name,
        school_city=args.school_city,
        school_address=args.school_address,
        forced_school_id=args.school_id,
        proved_name=args.proved_name,
        proved_code=args.proved_code,
        sous_division_name=args.sous_division_name,
        sous_division_code=args.sous_division_code,
    )
    out = Path(args.output)
    out.write_text(sql, encoding="utf-8")
    print(f"SQL de réparation écrit : {out}")
    print("")
    print("Exécution :")
    print(f"  mysql -u USER -p NOM_BASE < {out}")
    print("  # ou via phpMyAdmin → Importer")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
