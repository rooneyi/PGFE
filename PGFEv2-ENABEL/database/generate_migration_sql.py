#!/usr/bin/env python3
"""
Generate an importable SQL file for migrating selected data from backup_now.sql
to the Laravel target schema.

Algorithme (ordre d'exécution du SQL généré) :
  0. proveds / sous_divisions / schools — chaîne organisationnelle + école cible
  1. school_years     <- ap_annee_scolaire ; puis une seule année is_active = 1
  2. academic_levels  <- ap_niveau (+ inféré classes/cours/inscriptions)
  3. filiaires        <- ap_section (+ inféré)
  4. users            <- hr_employee + system_access (email @pgfe.local, bcrypt)
  5. academic_personals <- hr_employee
  6. classrooms       <- ap_class + ap_cour (clé « niveau - section »)
  7. courses          <- ap_cour
  8. academic_personal_course
  9. students         <- ap_apprenant
 10. registrations    <- ap_etudie (nécessite élève + classe + année + @mig_school_id)

Scope migrated:
- school_years            <- ap_annee_scolaire
- academic_levels         <- ap_niveau (+ inferred from classes/courses)
- filiaires               <- ap_section (+ inferred from classes/courses)
- classrooms              <- ap_class + ap_cour (normalized key level+section)
- users                   <- hr_employee with system_access (username@pgfe.local + bcrypt password)
- academic_personals      <- hr_employee
- courses                 <- ap_cour
- academic_personal_course <- ap_cour.staff -> hr_employee.id -> academic_personals
- students                <- ap_apprenant
- registrations           <- ap_etudie (status/type_enscription/classes/section/annee_scolaire)

Voir : GUIDE-UTILISATION.md (ce dossier)

Usage :
  python3 generate_migration_sql.py --backup backup.sql --output migration.sql

The generated SQL truncates the migrated business tables before inserting fresh data.
It also keeps NOT EXISTS checks around reference-like rows for safer reruns.

Voir aussi : docs/algorithme-migration-sql.md
"""

from __future__ import annotations

import argparse
import datetime as dt
import re
import sys
import unicodedata
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, Iterable, List, Optional, Sequence, Tuple

try:
    import bcrypt
except Exception as exc:  # pragma: no cover - explicit runtime guidance
    raise SystemExit(
        "Missing dependency 'bcrypt'. Install with: pip install bcrypt\n"
        f"Original error: {exc}"
    )


TABLES_NEEDED = [
    "ap_annee_scolaire",
    "ap_niveau",
    "ap_section",
    "ap_class",
    "ap_cour",
    "ap_apprenant",
    "ap_etudie",
    "hr_employee",
    "hr_etatc",
    "system_access",
]

DEFAULT_ACADEMIC_LEVEL_NAME = "1ère"

# Legacy Maendeleo : ynSexe 1 = Féminin, 2 = Masculin (voir hr_employee / ap_apprenant)
# refEtatC : table hr_etatc (1=Célibataire … 5=Veuf(ve))


def map_legacy_gender(yn_sexe: int) -> str:
    if yn_sexe == 1:
        return "Féminin"
    if yn_sexe == 2:
        return "Masculin"
    return "Non spécifié"


def map_etat_civil_label_to_enum(label: str, ref_id: int = 0) -> str:
    n = label.lower()
    if "célib" in n or "celib" in n:
        return "Célibataire"
    if "mari" in n:
        return "Marié(e)"
    if "divorc" in n:
        return "Divorcé(e)"
    if "veuf" in n or "veuve" in n:
        return "Veuf/Veuve"
    if "sépar" in n or "separ" in n:
        return "Divorcé(e)"
    return {
        1: "Célibataire",
        2: "Marié(e)",
        3: "Divorcé(e)",
        4: "Divorcé(e)",
        5: "Veuf/Veuve",
    }.get(ref_id, "Célibataire")


def build_etat_civil_map(rows: List[SourceRow]) -> Dict[int, str]:
    out: Dict[int, str] = {}
    for r in rows:
        ref_id = parse_int(r.values.get("refEtatC"))
        label = norm_text(r.values.get("txtEtatC"))
        if ref_id > 0:
            out[ref_id] = map_etat_civil_label_to_enum(label, ref_id) if label else map_etat_civil_label_to_enum("", ref_id)
    return out


def map_legacy_civil_status(ref_etat_c: int, etatc_map: Dict[int, str]) -> str:
    if ref_etat_c in etatc_map:
        return etatc_map[ref_etat_c]
    return map_etat_civil_label_to_enum("", ref_etat_c)


@dataclass
class SourceRow:
    table: str
    values: Dict[str, object]


def read_text_with_fallback(path: Path) -> str:
    for enc in ("utf-8", "latin-1", "cp1252"):
        try:
            return path.read_text(encoding=enc)
        except UnicodeDecodeError:
            continue
    return path.read_text(encoding="utf-8", errors="replace")


def extract_columns(sql_text: str, table: str) -> List[str]:
    # Backups may use either:
    #   CREATE TABLE `t` (
    # or:
    #   CREATE TABLE IF NOT EXISTS `t` (
    # Be permissive on whitespace/newlines but still anchor on ENGINE= to stop
    # at the end of the CREATE TABLE statement.
    pattern = (
        rf"CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`{re.escape(table)}`\s*\((.*?)\)\s*ENGINE="
    )
    match = re.search(pattern, sql_text, flags=re.S | re.I)
    if not match:
        raise ValueError(f"CREATE TABLE block not found for '{table}'")

    block = match.group(1)
    cols = []
    for line in block.splitlines():
        m = re.match(r"\s*`([^`]+)`\s+", line)
        if m:
            cols.append(m.group(1))
    if not cols:
        raise ValueError(f"No columns parsed for '{table}'")
    return cols


def split_tuples(values_blob: str) -> List[str]:
    items: List[str] = []
    in_quote = False
    esc = False
    depth = 0
    start = -1

    for i, ch in enumerate(values_blob):
        if esc:
            esc = False
            continue
        if ch == "\\" and in_quote:
            esc = True
            continue
        if ch == "'" and not esc:
            in_quote = not in_quote
            continue
        if in_quote:
            continue
        if ch == "(":
            if depth == 0:
                start = i
            depth += 1
        elif ch == ")":
            depth -= 1
            if depth == 0 and start >= 0:
                items.append(values_blob[start : i + 1])
                start = -1
    return items


def split_fields(tuple_sql: str) -> List[str]:
    assert tuple_sql.startswith("(") and tuple_sql.endswith(")")
    inner = tuple_sql[1:-1]

    out: List[str] = []
    cur: List[str] = []
    in_quote = False
    esc = False

    for ch in inner:
        if esc:
            cur.append(ch)
            esc = False
            continue
        if ch == "\\" and in_quote:
            cur.append(ch)
            esc = True
            continue
        if ch == "'":
            cur.append(ch)
            in_quote = not in_quote
            continue
        if ch == "," and not in_quote:
            out.append("".join(cur).strip())
            cur = []
            continue
        cur.append(ch)
    out.append("".join(cur).strip())
    return out


def decode_sql_literal(token: str) -> object:
    t = token.strip()
    if t.upper() == "NULL":
        return None
    if t.startswith("'") and t.endswith("'"):
        body = t[1:-1]
        body = (
            body.replace("\\\\", "\\")
            .replace("\\'", "'")
            .replace("\\n", "\n")
            .replace("\\r", "\r")
            .replace("\\t", "\t")
            .replace("\\0", "\0")
        )
        return body
    if re.fullmatch(r"-?\d+", t):
        try:
            return int(t)
        except ValueError:
            return t
    if re.fullmatch(r"-?\d+\.\d+", t):
        try:
            return float(t)
        except ValueError:
            return t
    return t


def _parse_insert_columns(col_group: str) -> List[str]:
    """
    Parse an INSERT column list like: (`id`, `name`, `active`) -> ["id","name","active"]
    Keeps names as-is (no lowercasing) since downstream expects legacy column names.
    """
    if not col_group:
        return []
    cols = re.findall(r"`([^`]+)`", col_group)
    if cols:
        return [c.strip() for c in cols if c.strip()]
    # Fallback: tolerate non-backticked identifiers.
    out: List[str] = []
    for part in col_group.split(","):
        p = part.strip().strip("()").strip()
        p = p.strip("`").strip()
        if p:
            out.append(p)
    return out


def extract_rows(sql_text: str, table: str, columns: Sequence[str]) -> List[SourceRow]:
    """
    Extract row tuples from a SQL dump for a given legacy table.

    Supports both formats commonly produced by mysqldump/phpMyAdmin:
      1) INSERT INTO `table` VALUES (...),(...);
      2) INSERT INTO `table` (`c1`,`c2`,...) VALUES (...),(...);

    If a column list is present in the INSERT statement, it is used for mapping.
    Otherwise, the provided `columns` sequence is used.
    """
    # NOTE: the dump we ingest may have newlines between tokens; keep it DOTALL.
    pattern = (
        rf"INSERT\s+INTO\s+`?{re.escape(table)}`?\s*"
        rf"(?:\((?P<cols>.*?)\)\s*)?"
        rf"VALUES\s*(?P<vals>.*?);"
    )
    matches = re.finditer(pattern, sql_text, flags=re.S | re.I)
    rows: List[SourceRow] = []

    for m in matches:
        insert_cols = _parse_insert_columns(m.group("cols") or "")
        blob = m.group("vals")
        tuples = split_tuples(blob)
        for tpl in tuples:
            fields = split_fields(tpl)
            if insert_cols:
                if len(fields) != len(insert_cols):
                    continue
                decoded = [decode_sql_literal(x) for x in fields]
                row = dict(zip(insert_cols, decoded))
                rows.append(SourceRow(table=table, values=row))
                continue

            if len(fields) != len(columns):
                continue
            decoded = [decode_sql_literal(x) for x in fields]
            row = dict(zip(columns, decoded))
            rows.append(SourceRow(table=table, values=row))

    return rows


def norm_text(v: object) -> str:
    if v is None:
        return ""
    s = str(v).strip()
    s = re.sub(r"\s+", " ", s)
    return s


def organization_sql_lines(
    proved_name: str,
    proved_code: str,
    sous_division_name: str,
    sous_division_code: str,
    school_name: str,
    school_city: str,
    school_address: str,
    school_id: Optional[int] = None,
) -> List[str]:
    """Proved → sous-division → école (structure PGFE v2)."""
    lines: List[str] = []
    lines.append("-- 0) Organisation : Proved → Sous-division → École")
    lines.append(
        "INSERT INTO proveds (name, code, created_at, updated_at)\n"
        f"SELECT {sql_quote(proved_name)}, {sql_quote(proved_code)}, @mig_now, @mig_now FROM DUAL\n"
        f"WHERE NOT EXISTS (SELECT 1 FROM proveds WHERE code = {sql_quote(proved_code)});"
    )
    lines.append(
        f"SET @mig_proved_id := (SELECT id FROM proveds WHERE code = {sql_quote(proved_code)} LIMIT 1);"
    )
    lines.append(
        "INSERT INTO sous_divisions (proved_id, name, code, created_at, updated_at)\n"
        f"SELECT @mig_proved_id, {sql_quote(sous_division_name)}, {sql_quote(sous_division_code)}, @mig_now, @mig_now FROM DUAL\n"
        f"WHERE @mig_proved_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sous_divisions WHERE proved_id = @mig_proved_id AND code = {sql_quote(sous_division_code)});"
    )
    lines.append(
        f"SET @mig_sous_division_id := (SELECT id FROM sous_divisions WHERE proved_id = @mig_proved_id AND code = {sql_quote(sous_division_code)} LIMIT 1);"
    )
    lines.append(
        "INSERT INTO schools (uuid, name, city, address, country_id, type_id, sous_division_id, created_at, updated_at)\n"
        f"SELECT UUID(), {sql_quote(school_name)}, {sql_quote(school_city)}, {sql_quote(school_address)}, "
        "@mig_default_country_id, @mig_default_type_id, @mig_sous_division_id, @mig_now, @mig_now\n"
        "FROM DUAL\n"
        f"WHERE @mig_sous_division_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE name = {sql_quote(school_name)});"
    )
    if school_id is not None:
        lines.append(f"SET @mig_requested_school_id := {int(school_id)};")
        lines.append(
            "SET @mig_school_id := COALESCE("
            "(SELECT id FROM schools WHERE id = @mig_requested_school_id LIMIT 1), "
            f"(SELECT id FROM schools WHERE name = {sql_quote(school_name)} LIMIT 1), "
            "(SELECT id FROM schools ORDER BY id LIMIT 1)"
            ");"
        )
    else:
        lines.append(
            f"SET @mig_school_id := (SELECT id FROM schools WHERE name = {sql_quote(school_name)} LIMIT 1);"
        )
        lines.append(
            "SET @mig_school_id := COALESCE(@mig_school_id, (SELECT id FROM schools ORDER BY id LIMIT 1));"
        )
    lines.append(
        "UPDATE schools SET sous_division_id = @mig_sous_division_id, country_id = COALESCE(country_id, @mig_default_country_id), "
        f"type_id = COALESCE(type_id, @mig_default_type_id), name = {sql_quote(school_name)}, "
        f"city = {sql_quote(school_city)}, address = COALESCE(NULLIF(address, ''), {sql_quote(school_address)})\n"
        "WHERE id = @mig_school_id AND @mig_sous_division_id IS NOT NULL;"
    )
    lines.append("")
    return lines


def patch_migration_sql_organization(
    content: str,
    proved_name: str,
    proved_code: str,
    sous_division_name: str,
    sous_division_code: str,
    school_name: str,
    school_city: str,
    school_address: str,
    legacy_school_id: Optional[int] = None,
) -> str:
    """Injecte la chaîne organisationnelle dans un migration.sql déjà généré."""
    block = organization_sql_lines(
        proved_name,
        proved_code,
        sous_division_name,
        sous_division_code,
        school_name,
        school_city,
        school_address,
        school_id=legacy_school_id,
    )
    org_text = "\n".join(block)
    if "SET @mig_sous_division_id :=" in content:
        return content
    content = re.sub(
        r"SET @mig_school_id\s*:=\s*\d+\s*;\s*\n",
        "",
        content,
        count=1,
    )
    marker = "SET @mig_default_type_id := (SELECT id FROM types ORDER BY id LIMIT 1);"
    if marker in content:
        return content.replace(
            marker,
            marker + "\n\n" + org_text,
            1,
        )
    marker2 = "SET @mig_now := NOW();"
    if marker2 in content:
        return content.replace(marker2, marker2 + "\n\n" + org_text, 1)
    return content + "\n\n" + org_text


def sql_quote(v: object) -> str:
    if v is None:
        return "NULL"
    s = str(v)
    s = s.replace("\\", "\\\\").replace("'", "''")
    s = s.replace("\r", " ").replace("\n", " ")
    return f"'{s}'"


def parse_int(v: object, default: int = 0) -> int:
    s = norm_text(v)
    if not s:
        return default
    try:
        return int(float(s))
    except ValueError:
        return default


def parse_float(v: object, default: float = 0.0) -> float:
    s = norm_text(v).replace(",", ".")
    if not s:
        return default
    try:
        return float(s)
    except ValueError:
        return default


def parse_date(v: object) -> str:
    s = norm_text(v)
    if not s:
        return "1990-01-01"
    for fmt in ("%d/%m/%Y", "%d-%m-%Y", "%Y-%m-%d", "%Y-%m-%d %H:%M:%S"):
        try:
            return dt.datetime.strptime(s, fmt).date().isoformat()
        except ValueError:
            continue
    return "1990-01-01"


def sanitize_student_phone(raw: object, used: set[str]) -> Optional[str]:
    s = norm_text(raw)
    if not s:
        return None
    digits = re.sub(r"[^0-9+]", "", s)
    if not digits:
        return None
    if digits in used:
        return None
    used.add(digits)
    return digits


def sanitize_student_email(raw: object, used: set[str]) -> Optional[str]:
    s = norm_text(raw).lower()
    if not s:
        return None
    if not re.match(r"^[^@\s]+@[^@\s]+\.[^@\s]+$", s):
        return None
    if s in used:
        return None
    used.add(s)
    return s


def unique_or_fallback(raw: object, fallback: str, used: set[str]) -> str:
    s = norm_text(raw) or fallback
    if s not in used:
        used.add(s)
        return s

    base = s
    n = 2
    while True:
        candidate = f"{base}-{n}"
        if candidate not in used:
            used.add(candidate)
            return candidate
        n += 1


def split_student_names(parts: Sequence[str]) -> Tuple[str, str, Optional[str]]:
    combined = " ".join([norm_text(p) for p in parts if norm_text(p)]).strip()
    tokens = [t for t in re.split(r"\s+", combined) if t]
    if not tokens:
        return ("N/A", "N/A", None)
    if len(tokens) == 1:
        return (tokens[0], tokens[0], None)
    firstname = tokens[0]
    name = tokens[1]
    lastname = " ".join(tokens[2:]) if len(tokens) > 2 else None
    return (firstname, name, lastname)


def normalize_email_local_part(username: object, fallback_id: int) -> str:
    s = norm_text(username).lower()
    if "@" in s:
        s = s.split("@", 1)[0]
    s = unicodedata.normalize("NFKD", s).encode("ascii", "ignore").decode("ascii")
    s = re.sub(r"[^a-z0-9._-]+", ".", s)
    s = re.sub(r"\.+", ".", s).strip(".-_")
    return s or f"staff{fallback_id}"


def email_from_username(username: object, fallback_id: int, used: set[str]) -> str:
    local_part = normalize_email_local_part(username, fallback_id)
    email = f"{local_part}@pgfe.local"

    if email not in used:
        used.add(email)
        return email

    n = 2
    while True:
        candidate = f"{local_part}{n}@pgfe.local"
        if candidate not in used:
            used.add(candidate)
            return candidate
        n += 1


def bcrypt_hash(password: str, rounds: int = 12) -> str:
    hashed = bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt(rounds=rounds))
    # PHP/Laravel's bcrypt hashes are commonly labeled "$2y$". The Python bcrypt
    # library typically emits "$2b$". Both are bcrypt, but we normalize to "$2y$"
    # to match Laravel conventions and avoid surprises during audits.
    s = hashed.decode("utf-8")
    if s.startswith("$2b$"):
        s = "$2y$" + s[len("$2b$") :]
    return s


def pick_system_access_credentials(rows: Iterable[SourceRow]) -> Dict[int, Dict[str, str]]:
    grouped: Dict[int, List[Dict[str, object]]] = {}
    for r in rows:
        staff_id = parse_int(r.values.get("staffID"))
        if staff_id <= 0:
            continue
        grouped.setdefault(staff_id, []).append(r.values)

    chosen: Dict[int, Dict[str, str]] = {}
    for staff_id, items in grouped.items():
        items.sort(
            key=lambda x: (
                parse_int(x.get("enabled"), 0),
                parse_int(x.get("id"), 0),
            ),
            reverse=True,
        )
        username = norm_text(items[0].get("username"))
        if not username:
            continue
        chosen[staff_id] = {
            "username": username,
            "password": norm_text(items[0].get("password")),
        }
    return chosen


def build_sql(
    all_rows: Dict[str, List[SourceRow]],
    school_id: Optional[int],
    default_plain_password: str,
    bcrypt_rounds: int,
    filiaire_ref: str = "id",
    school_name: str = "École PGFE (migration)",
    school_city: str = "Kinshasa",
    school_address: str = "Adresse — import migration",
    proved_name: str = "PROVED Migration",
    proved_code: str = "PROV-MIG",
    sous_division_name: str = "Sous-division migration",
    sous_division_code: str = "SD-MIG",
) -> str:
    years = all_rows.get("ap_annee_scolaire", [])
    niveaux = all_rows.get("ap_niveau", [])
    sections = all_rows.get("ap_section", [])
    classes = all_rows.get("ap_class", [])
    courses = all_rows.get("ap_cour", [])
    apprenants = all_rows.get("ap_apprenant", [])
    etudies = all_rows.get("ap_etudie", [])
    employees = all_rows.get("hr_employee", [])
    etatc_rows = all_rows.get("hr_etatc", [])
    accesses = all_rows.get("system_access", [])
    etatc_map = build_etat_civil_map(etatc_rows)

    level_by_id: Dict[int, str] = {
        parse_int(r.values.get("id")): norm_text(r.values.get("niveautxt"))
        for r in niveaux
        if parse_int(r.values.get("id")) > 0 and norm_text(r.values.get("niveautxt"))
    }

    section_names = {norm_text(r.values.get("sectiontxt")) for r in sections}
    section_names = {x for x in section_names if x}

    filtered_classes = classes
    filtered_courses = courses
    filtered_employees = employees

    # Build levels and filieres from declared + inferred values
    level_names = {name for name in level_by_id.values() if name}
    for r in filtered_classes:
        lvl = norm_text(r.values.get("designation")) or level_by_id.get(parse_int(r.values.get("niveau")), "")
        if lvl:
            level_names.add(lvl)
        sec = norm_text(r.values.get("section"))
        if sec:
            section_names.add(sec)
    for r in filtered_courses:
        lvl = level_by_id.get(parse_int(r.values.get("niveau")), "") or norm_text(r.values.get("niveau"))
        if lvl:
            level_names.add(lvl)
        sec = norm_text(r.values.get("section"))
        if sec:
            section_names.add(sec)
    # Also infer from student registration source.
    for r in etudies:
        raw_lvl = norm_text(r.values.get("classes"))
        lvl = raw_lvl if raw_lvl and raw_lvl not in {"-1", "0", "N/A"} else level_by_id.get(parse_int(r.values.get("niveau")), "")
        if not lvl:
            lvl = DEFAULT_ACADEMIC_LEVEL_NAME
        level_names.add(lvl)
        sec = norm_text(r.values.get("section"))
        if sec:
            section_names.add(sec)

    # Normalize classroom keys by (level_name, section_name)
    classroom_keys: set[Tuple[str, str]] = set()
    for r in filtered_classes:
        level_name = norm_text(r.values.get("designation")) or level_by_id.get(parse_int(r.values.get("niveau")), "")
        section_name = norm_text(r.values.get("section"))
        if level_name and section_name:
            classroom_keys.add((level_name, section_name))
    for r in filtered_courses:
        level_name = level_by_id.get(parse_int(r.values.get("niveau")), "") or norm_text(r.values.get("niveau"))
        section_name = norm_text(r.values.get("section"))
        if level_name and section_name:
            classroom_keys.add((level_name, section_name))
    for r in etudies:
        raw_lvl = norm_text(r.values.get("classes"))
        level_name = raw_lvl if raw_lvl and raw_lvl not in {"-1", "0", "N/A"} else level_by_id.get(parse_int(r.values.get("niveau")), "")
        if not level_name:
            level_name = DEFAULT_ACADEMIC_LEVEL_NAME
        section_name = norm_text(r.values.get("section"))
        if level_name and section_name:
            classroom_keys.add((level_name, section_name))

    # User + academic_personal payload
    access_by_staff = pick_system_access_credentials(accesses)
    used_emails: set[str] = set()
    used_employee_phones: set[str] = set()
    used_employee_identities: set[str] = set()

    employee_payloads = []
    emp_by_id: Dict[int, Dict[str, object]] = {}
    for r in filtered_employees:
        v = r.values
        staff_id = parse_int(v.get("id"))
        if staff_id <= 0:
            continue
        access = access_by_staff.get(staff_id)
        if not access:
            continue

        matricule = norm_text(v.get("no_matricule")) or f"EMP-{staff_id}"
        name = norm_text(v.get("nom")) or "N/A"
        post_name = norm_text(v.get("postnom")) or "N/A"
        pre_name = norm_text(v.get("prenom")) or "N/A"
        full_name = " ".join([x for x in [pre_name, name] if x and x != "N/A"]).strip() or f"Employe {staff_id}"

        source_username = access["username"]
        email = email_from_username(source_username, staff_id, used_emails)
        plain_pwd = access["password"] or default_plain_password
        pwd_hash = bcrypt_hash(plain_pwd, rounds=bcrypt_rounds)

        gender = map_legacy_gender(parse_int(v.get("ynSexe"), 0))
        civil = map_legacy_civil_status(parse_int(v.get("refEtatC"), 0), etatc_map)
        phone = unique_or_fallback(v.get("numTel"), f"+000{staff_id}", used_employee_phones)
        identity = unique_or_fallback(v.get("national_id"), f"NID-{staff_id}", used_employee_identities)
        address = norm_text(v.get("txtAd")) or "Adresse non renseignee"
        birth_date = parse_date(v.get("dteDDN"))
        birth_place = norm_text(v.get("lieu_ne")) or "N/A"

        payload = {
            "staff_id": staff_id,
            "matricule": matricule,
            "user_name": full_name,
            "email": email,
            "password_hash": pwd_hash,
            "name": name,
            "post_name": post_name,
            "pre_name": pre_name,
            "username": source_username,
            "phone": phone,
            "identity": identity,
            "gender": gender,
            "civil_status": civil,
            "address": address,
            "birth_date": birth_date,
            "birth_place": birth_place,
            "father_id": norm_text(v.get("txt_papa")) or None,
            "mother_id": norm_text(v.get("txt_mama")) or None,
            "mechanisation_id": parse_int(v.get("meca_id"), 0),
            "province_guess": parse_int(v.get("P_CODE_PRO"), 0),
            "territory_guess": parse_int(v.get("P_CODE_VILLE"), 0),
            "commune_guess": parse_int(v.get("P_CODE_COM"), 0),
            "type_guess": parse_int(v.get("refNatAct"), 0),
            "fonction_guess": parse_int(v.get("fonctionID"), 0),
        }
        employee_payloads.append(payload)
        emp_by_id[staff_id] = payload

    # Course payloads
    course_payloads = []
    link_payloads = []
    for r in filtered_courses:
        v = r.values
        label = norm_text(v.get("courtxt"))
        if not label:
            continue
        level_name = level_by_id.get(parse_int(v.get("niveau")), "") or norm_text(v.get("niveau"))
        section_name = norm_text(v.get("section"))
        if not level_name or not section_name:
            continue

        classroom_name = f"{level_name} - {section_name}"
        teacher_staff_id = parse_int(v.get("staff"), 0)
        teacher = emp_by_id.get(teacher_staff_id)

        payload = {
            "label": label,
            "level_name": level_name,
            "filiere_name": section_name,
            "classroom_name": classroom_name,
            "hourly_volume": parse_int(v.get("volume_h"), 0),
            "max_p1": parse_float(v.get("p1"), 0.0),
            "max_p2": parse_float(v.get("p2"), 0.0),
            "max_e1": parse_float(v.get("e1"), 0.0),
            "max_p3": parse_float(v.get("p3"), 0.0),
            "max_p4": parse_float(v.get("p4"), 0.0),
            "max_e2": parse_float(v.get("e2"), 0.0),
        }
        course_payloads.append(payload)

        if teacher:
            link_payloads.append(
                {
                    "course": payload,
                    "teacher_matricule": teacher["matricule"],
                }
            )

    # Students + registrations payload
    student_phone_used: set[str] = set()
    student_email_used: set[str] = set()
    student_payloads = []
    for r in apprenants:
        v = r.values
        matricule = norm_text(v.get("no_matricule"))
        if not matricule:
            continue
        firstname, name, lastname = split_student_names(
            [
                norm_text(v.get("txtPrenom")),
                norm_text(v.get("txtNom")),
                norm_text(v.get("txtPostNom")),
            ]
        )
        gender = map_legacy_gender(parse_int(v.get("ynSexe"), 0))
        civil = map_legacy_civil_status(parse_int(v.get("refEtatC"), 0), etatc_map)
        if name == "N/A":
            name = f"Apprenant-{matricule}"
            firstname = name
            lastname = None
        student_payloads.append(
            {
                "matricule": matricule,
                "name": name,
                "firstname": firstname,
                "lastname": lastname,
                "gender": gender,
                "civil_status": civil,
                "address": norm_text(v.get("txtAd")) or "Adresse non renseignee",
                "birth_date": parse_date(v.get("dteDDN")),
                "birth_place": norm_text(v.get("lieu_ne")) or "N/A",
                "phone_number": sanitize_student_phone(v.get("numTel"), student_phone_used),
                "email": sanitize_student_email(v.get("txtAdMail"), student_email_used),
                "province_guess": parse_int(v.get("P_CODE_PRO"), 0),
                "territory_guess": parse_int(v.get("P_CODE_VILLE"), 0),
                "commune_guess": parse_int(v.get("P_CODE_COM"), 0),
            }
        )

    registration_payloads = []
    for r in etudies:
        v = r.values
        matricule = norm_text(v.get("no_matricule"))
        raw_level_name = norm_text(v.get("classes"))
        level_name = raw_level_name if raw_level_name and raw_level_name not in {"-1", "0", "N/A"} else level_by_id.get(parse_int(v.get("niveau")), "")
        if not level_name:
            level_name = DEFAULT_ACADEMIC_LEVEL_NAME
        filiere_name = norm_text(v.get("section"))
        year_name = norm_text(v.get("annee_scolaire"))
        if not matricule or not level_name or not filiere_name or not year_name:
            continue
        registration_payloads.append(
            {
                "matricule": matricule,
                "level_name": level_name,
                "filiere_name": filiere_name,
                "classroom_name": f"{level_name} - {filiere_name}",
                "school_year_name": year_name,
                "type_id_guess": parse_int(v.get("type_enscription"), 0),
                "registration_status": 1 if parse_int(v.get("status"), 0) > 0 else 0,
                "registration_date": parse_date(v.get("datestamp")),
                "note": None,
            }
        )

    # Deduplicate payloads in Python before SQL generation
    def uniq_by(items: Iterable[dict], key_fn):
        seen = set()
        out = []
        for it in items:
            k = key_fn(it)
            if k in seen:
                continue
            seen.add(k)
            out.append(it)
        return out

    year_payloads = uniq_by(
        (
            {
                "name": norm_text(r.values.get("annee_txt")),
                "active": 1 if parse_int(r.values.get("active"), 0) else 0,
            }
            for r in years
            if norm_text(r.values.get("annee_txt"))
        ),
        key_fn=lambda x: x["name"],
    )
    if not year_payloads:
        current_year = dt.date.today().year
        year_payloads = [{"name": f"{current_year}-{current_year + 1}", "active": 1}]
    active_year_names = [y["name"] for y in year_payloads if y["active"]]
    preferred_active_year = (
        active_year_names[0]
        if active_year_names
        else year_payloads[-1]["name"]
    )
    level_payloads = [{"name": n} for n in sorted(level_names)]
    filiere_payloads = [{"name": n} for n in sorted(section_names)]
    classroom_payloads = uniq_by(
        (
            {
                "level_name": l,
                "section_name": s,
                "name": f"{l} - {s}",
            }
            for (l, s) in sorted(classroom_keys)
        ),
        key_fn=lambda x: (x["level_name"], x["section_name"]),
    )
    employee_payloads = uniq_by(employee_payloads, key_fn=lambda x: x["matricule"])
    course_payloads = uniq_by(
        course_payloads,
        key_fn=lambda x: (
            x["label"],
            x["level_name"],
            x["filiere_name"],
            x["classroom_name"],
        ),
    )
    link_payloads = uniq_by(
        link_payloads,
        key_fn=lambda x: (
            x["course"]["label"],
            x["course"]["level_name"],
            x["course"]["filiere_name"],
            x["course"]["classroom_name"],
            x["teacher_matricule"],
        ),
    )
    student_payloads = uniq_by(student_payloads, key_fn=lambda x: x["matricule"])
    registration_payloads = uniq_by(
        registration_payloads,
        key_fn=lambda x: (
            x["matricule"],
            x["school_year_name"],
            x["classroom_name"],
        ),
    )

    lines: List[str] = []
    lines.append("-- Auto-generated migration SQL from backup_now.sql")
    lines.append("-- Destructive import: target business tables are truncated before insert")
    lines.append("SET NAMES utf8mb4;")
    lines.append("SET FOREIGN_KEY_CHECKS = 0;")
    lines.append("START TRANSACTION;")
    lines.append("")
    lines.append("-- Reset business tables (TRUNCATE with identity reset)")
    lines.append("TRUNCATE TABLE registrations;")
    lines.append("TRUNCATE TABLE students;")
    lines.append("TRUNCATE TABLE academic_personal_course;")
    lines.append("TRUNCATE TABLE courses;")
    lines.append("TRUNCATE TABLE classrooms;")
    lines.append("TRUNCATE TABLE academic_personals;")
    lines.append("TRUNCATE TABLE users;")
    lines.append("TRUNCATE TABLE filiaires;")
    lines.append("TRUNCATE TABLE academic_levels;")
    lines.append("TRUNCATE TABLE school_years;")
    lines.append("")
    lines.append("SET @mig_now := NOW();")
    lines.append("SET @mig_default_country_id := (SELECT id FROM countries ORDER BY id LIMIT 1);")
    lines.append("SET @mig_default_type_id := (SELECT id FROM types ORDER BY id LIMIT 1);")
    lines.append("")
    lines.extend(
        organization_sql_lines(
            proved_name,
            proved_code,
            sous_division_name,
            sous_division_code,
            school_name,
            school_city,
            school_address,
            school_id=school_id,
        )
    )
    lines.append("SET @mig_default_province_id := (SELECT id FROM provinces ORDER BY id LIMIT 1);")
    lines.append("SET @mig_default_territory_id := (SELECT id FROM territories ORDER BY id LIMIT 1);")
    lines.append("SET @mig_default_commune_id := (SELECT id FROM communes ORDER BY id LIMIT 1);")
    lines.append("SET @mig_default_fonction_id := (SELECT id FROM fonctions ORDER BY id LIMIT 1);")
    lines.append("SET @mig_default_academic_level_id := (SELECT id FROM academic_levels ORDER BY id LIMIT 1);")
    lines.append("SET @mig_default_academic_personal_id := (SELECT id FROM academic_personals ORDER BY id LIMIT 1);")
    lines.append("")
    if filiaire_ref not in {"id", "uuid"}:
        raise ValueError("filiaire_ref must be 'id' or 'uuid'")
    filiaire_select_col = "id" if filiaire_ref == "id" else "uuid"
    lines.append("-- 1) School years")
    for y in year_payloads:
        lines.append(
            "INSERT INTO school_years (school_id, name, is_active, description, created_at, updated_at, deleted_at)\n"
            f"SELECT @mig_school_id, {sql_quote(y['name'])}, {y['active']}, NULL, @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (\n"
            f"  SELECT 1 FROM school_years sy WHERE sy.school_id = @mig_school_id AND sy.name = {sql_quote(y['name'])}\n"
            ");"
        )
    lines.append("")
    lines.append("-- 1b) Une seule année scolaire active pour cette école")
    lines.append("UPDATE school_years SET is_active = 0 WHERE school_id = @mig_school_id;")
    lines.append(
        "UPDATE school_years SET is_active = 1 "
        f"WHERE school_id = @mig_school_id AND name = {sql_quote(preferred_active_year)};"
    )
    lines.append("")
    lines.append("-- 2) Academic levels")
    for lvl in level_payloads:
        lines.append(
            "INSERT INTO academic_levels (uuid, cycle_id, name, created_at, updated_at, deleted_at)\n"
            f"SELECT UUID(), NULL, {sql_quote(lvl['name'])}, @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (SELECT 1 FROM academic_levels al WHERE al.name = {sql_quote(lvl['name'])});"
        )
    lines.append("")
    lines.append("SET @mig_default_academic_level_id := (SELECT id FROM academic_levels ORDER BY id LIMIT 1);")
    lines.append("")
    lines.append("-- 3) Filiaires (sections)")
    for fil in filiere_payloads:
        lines.append(
            "INSERT INTO filiaires (uuid, school_id, name, code, created_at, updated_at, deleted_at)\n"
            f"SELECT UUID(), @mig_school_id, {sql_quote(fil['name'])}, NULL, @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (\n"
            f"  SELECT 1 FROM filiaires f WHERE f.school_id = @mig_school_id AND f.name = {sql_quote(fil['name'])}\n"
            ");"
        )
    # filiaire_id is NOT NULL in some target tables (notably courses).
    # If a filiere name lookup misses (spacing/encoding mismatches, etc.), we still need a valid fallback.
    lines.append("")
    lines.append(
        f"SET @mig_default_filiaire_id := (SELECT f.{filiaire_select_col} FROM filiaires f WHERE f.school_id = @mig_school_id ORDER BY f.id LIMIT 1);"
    )
    lines.append("")
    lines.append("-- 4) Users from hr_employee + system_access (bcrypt)")
    for e in employee_payloads:
        lines.append(
            "INSERT INTO users (name, email, password, created_at, updated_at, school_id)\n"
            f"SELECT {sql_quote(e['user_name'])}, {sql_quote(e['email'])}, {sql_quote(e['password_hash'])}, @mig_now, @mig_now, @mig_school_id\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.email = {sql_quote(e['email'])});"
        )
    lines.append("")
    lines.append("-- 5) Academic personals from hr_employee")
    for e in employee_payloads:
        meca = int(e["mechanisation_id"])
        province_guess = int(e["province_guess"])
        territory_guess = int(e["territory_guess"])
        commune_guess = int(e["commune_guess"])
        type_guess = int(e["type_guess"])
        fonction_guess = int(e["fonction_guess"])
        lines.append(
            "INSERT INTO academic_personals (\n"
            "  uuid,\n"
            "  user_id, mechanisation_id, country_id, province_id, territory_id, commune_id, school_id, type_id,\n"
            "  father_id, mother_id, academic_level_id, fonction_id, matricule, name, post_name, pre_name,\n"
            "  username, phone, email, identity_card_number, gender, civil_status, physical_address,\n"
            "  birth_date, birth_place, created_at, updated_at, deleted_at, image\n"
            ")\n"
            "SELECT\n"
            "  UUID(),\n"
            f"  (SELECT u.id FROM users u WHERE u.email = {sql_quote(e['email'])} LIMIT 1),\n"
            f"  CASE WHEN {meca} > 0 AND EXISTS (SELECT 1 FROM mecanisations m WHERE m.id = {meca}) THEN {meca} ELSE NULL END,\n"
            "  @mig_default_country_id,\n"
            f"  COALESCE((SELECT p.id FROM provinces p WHERE p.id = {province_guess} LIMIT 1), @mig_default_province_id),\n"
            f"  COALESCE((SELECT t.id FROM territories t WHERE t.id = {territory_guess} LIMIT 1), @mig_default_territory_id),\n"
            f"  COALESCE((SELECT c.id FROM communes c WHERE c.id = {commune_guess} LIMIT 1), @mig_default_commune_id),\n"
            "  @mig_school_id,\n"
            f"  COALESCE((SELECT ty.id FROM types ty WHERE ty.id = {type_guess} LIMIT 1), @mig_default_type_id),\n"
            f"  {sql_quote(e['father_id'])},\n"
            f"  {sql_quote(e['mother_id'])},\n"
            "  COALESCE(@mig_default_academic_level_id, (SELECT id FROM academic_levels ORDER BY id LIMIT 1)),\n"
            f"  COALESCE((SELECT f.id FROM fonctions f WHERE f.id = {fonction_guess} LIMIT 1), @mig_default_fonction_id),\n"
            f"  {sql_quote(e['matricule'])}, {sql_quote(e['name'])}, {sql_quote(e['post_name'])}, {sql_quote(e['pre_name'])},\n"
            f"  {sql_quote(e['username'])}, {sql_quote(e['phone'])}, {sql_quote(e['email'])}, {sql_quote(e['identity'])},\n"
            f"  {sql_quote(e['gender'])}, {sql_quote(e['civil_status'])}, {sql_quote(e['address'])},\n"
            f"  {sql_quote(e['birth_date'])}, {sql_quote(e['birth_place'])}, @mig_now, @mig_now, NULL, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (\n"
            f"  SELECT 1 FROM academic_personals ap WHERE ap.matricule = {sql_quote(e['matricule'])} OR ap.email = {sql_quote(e['email'])}\n"
            ");"
        )
    lines.append("")
    lines.append("-- 6) Classrooms (normalized as '<niveau> - <section>')")
    for c in classroom_payloads:
        filiere_sub = (
            f"(SELECT f.{filiaire_select_col} FROM filiaires f WHERE f.school_id = @mig_school_id AND f.name = {sql_quote(c['section_name'])} LIMIT 1)"
        )
        filiere_or_default = f"COALESCE({filiere_sub}, @mig_default_filiaire_id)"
        lines.append(
            "INSERT INTO classrooms (uuid, school_id, filiaire_id, academic_level_id, name, indicator, created_at, updated_at, titulaire_id, deleted_at)\n"
            f"SELECT UUID(), @mig_school_id, {filiere_or_default}, (SELECT al.id FROM academic_levels al WHERE al.name = {sql_quote(c['level_name'])} LIMIT 1), "
            f"{sql_quote(c['name'])}, NULL, @mig_now, @mig_now, NULL, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (\n"
            f"  SELECT 1 FROM classrooms cl\n"
            f"  WHERE cl.name = {sql_quote(c['name'])}\n"
            f"    AND cl.academic_level_id = (SELECT al.id FROM academic_levels al WHERE al.name = {sql_quote(c['level_name'])} LIMIT 1)\n"
            ");"
        )
    lines.append("")
    lines.append("-- 7) Courses with level + filiere + classroom")
    for c in course_payloads:
        filiere_sub = (
            f"(SELECT f.{filiaire_select_col} FROM filiaires f WHERE f.school_id = @mig_school_id AND f.name = {sql_quote(c['filiere_name'])} LIMIT 1)"
        )
        filiere_or_default = f"COALESCE({filiere_sub}, @mig_default_filiaire_id)"
        lines.append(
            "INSERT INTO courses (\n"
            "  uuid,\n"
            "  label, academic_level_id, filiaire_id, school_id, cycle_id, classroom_id,\n"
            "  hourly_volume, max_period_1, max_period_2, max_period_3, max_period_4, max_exam_1, max_exam_2,\n"
            "  created_at, updated_at, deleted_at\n"
            ")\n"
            "SELECT\n"
            "  UUID(),\n"
            f"  {sql_quote(c['label'])},\n"
            f"  (SELECT al.id FROM academic_levels al WHERE al.name = {sql_quote(c['level_name'])} LIMIT 1),\n"
            f"  {filiere_or_default},\n"
            "  @mig_school_id,\n"
            "  NULL,\n"
            f"  (SELECT cl.id FROM classrooms cl WHERE cl.name = {sql_quote(c['classroom_name'])} AND cl.academic_level_id = (SELECT al2.id FROM academic_levels al2 WHERE al2.name = {sql_quote(c['level_name'])} LIMIT 1) LIMIT 1),\n"
            f"  {int(c['hourly_volume'])}, {float(c['max_p1'])}, {float(c['max_p2'])}, {float(c['max_p3'])}, {float(c['max_p4'])}, {float(c['max_e1'])}, {float(c['max_e2'])},\n"
            "  @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE NOT EXISTS (\n"
            "  SELECT 1 FROM courses c0\n"
            f"  WHERE c0.school_id = @mig_school_id\n"
            f"    AND c0.label = {sql_quote(c['label'])}\n"
            f"    AND c0.academic_level_id = (SELECT al.id FROM academic_levels al WHERE al.name = {sql_quote(c['level_name'])} LIMIT 1)\n"
            f"    AND c0.filiaire_id = {filiere_or_default}\n"
            f"    AND c0.classroom_id = (SELECT cl.id FROM classrooms cl WHERE cl.name = {sql_quote(c['classroom_name'])} AND cl.academic_level_id = (SELECT al2.id FROM academic_levels al2 WHERE al2.name = {sql_quote(c['level_name'])} LIMIT 1) LIMIT 1)\n"
            ");"
        )
    lines.append("")
    lines.append("-- 8) Link courses <-> teachers")
    for link in link_payloads:
        c = link["course"]
        matricule = link["teacher_matricule"]
        filiere_sub = (
            f"(SELECT f.{filiaire_select_col} FROM filiaires f WHERE f.school_id = @mig_school_id AND f.name = {sql_quote(c['filiere_name'])} LIMIT 1)"
        )
        filiere_or_default = f"COALESCE({filiere_sub}, @mig_default_filiaire_id)"
        course_sub = (
            "(SELECT c.id FROM courses c "
            "WHERE c.school_id = @mig_school_id "
            f"AND c.label = {sql_quote(c['label'])} "
            f"AND c.academic_level_id = (SELECT al.id FROM academic_levels al WHERE al.name = {sql_quote(c['level_name'])} LIMIT 1) "
            f"AND c.filiaire_id = {filiere_or_default} "
            f"AND c.classroom_id = (SELECT cl.id FROM classrooms cl WHERE cl.name = {sql_quote(c['classroom_name'])} AND cl.academic_level_id = (SELECT al2.id FROM academic_levels al2 WHERE al2.name = {sql_quote(c['level_name'])} LIMIT 1) LIMIT 1) "
            "LIMIT 1)"
        )
        person_sub = (
            f"(SELECT ap.id FROM academic_personals ap WHERE ap.matricule = {sql_quote(matricule)} LIMIT 1)"
        )
        lines.append(
            "INSERT INTO academic_personal_course (course_id, academic_personal_id, created_at, updated_at)\n"
            f"SELECT {course_sub}, {person_sub}, @mig_now, @mig_now\n"
            "FROM DUAL\n"
            f"WHERE {course_sub} IS NOT NULL\n"
            f"  AND {person_sub} IS NOT NULL\n"
            f"  AND NOT EXISTS (\n"
            f"    SELECT 1 FROM academic_personal_course apc\n"
            f"    WHERE apc.course_id = {course_sub} AND apc.academic_personal_id = {person_sub}\n"
            "  );"
        )
    lines.append("")
    lines.append("-- 9) Students from ap_apprenant")
    for s in student_payloads:
        province_guess = int(s["province_guess"])
        territory_guess = int(s["territory_guess"])
        commune_guess = int(s["commune_guess"])
        lines.append(
            "INSERT INTO students (\n"
            "  uuid,\n"
            "  province_id, territory_id, commune_id, parents_id, parent2_id, parent3_id,\n"
            "  matricule, name, firstname, lastname, gender, civil_status, address, birth_date, birth_place,\n"
            "  phone_number, email, image, deleted_at, created_at, updated_at, school_id, country_id\n"
            ")\n"
            "SELECT\n"
            "  UUID(),\n"
            f"  COALESCE((SELECT p.id FROM provinces p WHERE p.id = {province_guess} LIMIT 1), @mig_default_province_id),\n"
            f"  COALESCE((SELECT t.id FROM territories t WHERE t.id = {territory_guess} LIMIT 1), @mig_default_territory_id),\n"
            f"  COALESCE((SELECT c.id FROM communes c WHERE c.id = {commune_guess} LIMIT 1), @mig_default_commune_id),\n"
            "  NULL, NULL, NULL,\n"
            f"  {sql_quote(s['matricule'])}, {sql_quote(s['name'])}, {sql_quote(s['firstname'])}, {sql_quote(s['lastname'])},\n"
            f"  {sql_quote(s['gender'])}, {sql_quote(s['civil_status'])}, {sql_quote(s['address'])}, {sql_quote(s['birth_date'])}, {sql_quote(s['birth_place'])},\n"
            f"  {sql_quote(s['phone_number'])}, {sql_quote(s['email'])}, NULL, NULL, @mig_now, @mig_now, @mig_school_id, @mig_default_country_id\n"
            "FROM DUAL\n"
            "ON DUPLICATE KEY UPDATE\n"
            "  province_id = VALUES(province_id),\n"
            "  territory_id = VALUES(territory_id),\n"
            "  commune_id = VALUES(commune_id),\n"
            "  parents_id = VALUES(parents_id),\n"
            "  parent2_id = VALUES(parent2_id),\n"
            "  parent3_id = VALUES(parent3_id),\n"
            "  name = VALUES(name),\n"
            "  firstname = VALUES(firstname),\n"
            "  lastname = VALUES(lastname),\n"
            "  gender = VALUES(gender),\n"
            "  civil_status = VALUES(civil_status),\n"
            "  address = VALUES(address),\n"
            "  birth_date = VALUES(birth_date),\n"
            "  birth_place = VALUES(birth_place),\n"
            "  phone_number = VALUES(phone_number),\n"
            "  email = VALUES(email),\n"
            "  image = VALUES(image),\n"
            "  deleted_at = VALUES(deleted_at),\n"
            "  updated_at = VALUES(updated_at),\n"
            "  school_id = VALUES(school_id),\n"
            "  country_id = VALUES(country_id),\n"
            "  uuid = COALESCE(students.uuid, VALUES(uuid));"
        )

    lines.append("")
    lines.append("-- 10) Registrations from ap_etudie")
    lines.append("SET @mig_default_academic_personal_id := (SELECT id FROM academic_personals ORDER BY id LIMIT 1);")
    for r in registration_payloads:
        student_sub = (
            f"(SELECT st.id FROM students st WHERE st.matricule = {sql_quote(r['matricule'])} LIMIT 1)"
        )
        school_year_sub = (
            f"(SELECT sy.id FROM school_years sy WHERE sy.school_id = @mig_school_id AND sy.name = {sql_quote(r['school_year_name'])} LIMIT 1)"
        )
        academic_level_sub = (
            f"(SELECT al.id FROM academic_levels al WHERE al.name = {sql_quote(r['level_name'])} LIMIT 1)"
        )
        classroom_sub = (
            f"(SELECT cl.id FROM classrooms cl WHERE cl.name = {sql_quote(r['classroom_name'])} "
            f"AND cl.academic_level_id = {academic_level_sub} LIMIT 1)"
        )
        default_teacher_sub = "(SELECT ap.id FROM academic_personals ap ORDER BY ap.id LIMIT 1)"
        type_sub = (
            f"COALESCE((SELECT ty.id FROM types ty WHERE ty.id = {int(r['type_id_guess'])} LIMIT 1), @mig_default_type_id)"
        )
        lines.append(
            f"DELETE FROM registrations\n"
            f"WHERE student_id = {student_sub}\n"
            f"  AND school_year_id = {school_year_sub}\n"
            f"  AND classroom_id = {classroom_sub};"
        )
        lines.append(
            "INSERT INTO registrations (\n"
            "  uuid,\n"
            "  school_id, classroom_id, student_id, school_year_id, academic_personal_id,\n"
            "  academic_level_id, type_id, registration_date, registration_status, note,\n"
            "  created_at, updated_at, deleted_at\n"
            ")\n"
            "SELECT\n"
            f"  UUID(), @mig_school_id, {classroom_sub}, {student_sub}, {school_year_sub}, COALESCE(@mig_default_academic_personal_id, {default_teacher_sub}),\n"
            f"  {academic_level_sub}, {type_sub}, {sql_quote(r['registration_date'])}, {int(r['registration_status'])}, {sql_quote(r['note'])},\n"
            f"  @mig_now, @mig_now, NULL\n"
            "FROM DUAL\n"
            f"WHERE {student_sub} IS NOT NULL\n"
            f"  AND {school_year_sub} IS NOT NULL\n"
            f"  AND {academic_level_sub} IS NOT NULL\n"
            f"  AND {classroom_sub} IS NOT NULL;"
        )

    lines.append("")
    lines.append("-- 11) Rôles Spatie : admin-ecole pour tout user sans rôle")
    lines.append(
        "-- Prérequis : php artisan db:seed --class=RolesAndPermissionsSeeder AVANT cet import "
        "(crée le rôle admin-ecole, guard web)"
    )
    lines.append("DELETE FROM model_has_roles WHERE model_type = 'App\\\\Models\\\\User';")
    lines.append(
        "INSERT INTO model_has_roles (role_id, model_type, model_id)\n"
        "SELECT\n"
        "  (SELECT id FROM roles WHERE name = 'admin-ecole' AND guard_name = 'web' LIMIT 1),\n"
        "  'App\\\\Models\\\\User',\n"
        "  u.id\n"
        "FROM users u\n"
        "WHERE (SELECT id FROM roles WHERE name = 'admin-ecole' AND guard_name = 'web' LIMIT 1) IS NOT NULL\n"
        "  AND NOT EXISTS (\n"
        "    SELECT 1 FROM model_has_roles mhr\n"
        "    WHERE mhr.model_type = 'App\\\\Models\\\\User' AND mhr.model_id = u.id\n"
        "  );"
    )
    lines.append("")
    lines.append("COMMIT;")
    lines.append("SET FOREIGN_KEY_CHECKS = 1;")
    lines.append("")
    lines.append("-- Summary")
    lines.append(f"-- school_years: {len(year_payloads)}")
    lines.append(f"-- academic_levels: {len(level_payloads)}")
    lines.append(f"-- filiaires: {len(filiere_payloads)}")
    lines.append(f"-- users: {len(employee_payloads)}")
    lines.append(f"-- academic_personals: {len(employee_payloads)}")
    lines.append(f"-- classrooms: {len(classroom_payloads)}")
    lines.append(f"-- courses: {len(course_payloads)}")
    lines.append(f"-- academic_personal_course links: {len(link_payloads)}")
    lines.append(f"-- students: {len(student_payloads)}")
    lines.append(f"-- registrations: {len(registration_payloads)}")

    return "\n".join(lines) + "\n"


def parse_backup(backup_path: Path) -> Dict[str, List[SourceRow]]:
    text = read_text_with_fallback(backup_path)
    parsed: Dict[str, List[SourceRow]] = {}
    for table in TABLES_NEEDED:
        cols = extract_columns(text, table)
        parsed[table] = extract_rows(text, table, cols)
    return parsed


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Generate SQL migration file from backup_now.sql for Laravel DB."
    )
    parser.add_argument("--backup", help="Path to backup_now.sql (legacy ap_*)")
    parser.add_argument("--output", required=True, help="Path to generated SQL file")
    parser.add_argument(
        "--school-id",
        type=int,
        default=None,
        help="school_id existant dans Laravel. Si omis, le SQL crée une école quand schools est vide.",
    )
    parser.add_argument(
        "--school-name",
        default="École PGFE (migration)",
        help="Nom de l'école créée si schools est vide (ou si --school-id absent en base)",
    )
    parser.add_argument(
        "--school-city",
        default="Kinshasa",
        help="Ville de l'école par défaut",
    )
    parser.add_argument(
        "--school-address",
        default="Adresse — import migration",
        help="Adresse de l'école par défaut",
    )
    parser.add_argument(
        "--default-password",
        default="ChangeMe123!",
        help="Fallback plaintext password if no system_access password is found",
    )
    parser.add_argument(
        "--bcrypt-rounds",
        type=int,
        default=12,
        help="bcrypt rounds (default: 12)",
    )
    parser.add_argument(
        "--filiaire-ref",
        choices=["id", "uuid"],
        default="id",
        help="How to reference filiaires from other tables (id or uuid). Default: id",
    )
    parser.add_argument("--proved-name", default="PROVED Migration")
    parser.add_argument("--proved-code", default="PROV-MIG")
    parser.add_argument("--sous-division-name", default="Sous-division migration")
    parser.add_argument("--sous-division-code", default="SD-MIG")
    parser.add_argument(
        "--patch-organization",
        action="store_true",
        help="Met à jour un fichier migration.sql existant (--output) avec Proved/SD/école",
    )
    parser.add_argument(
        "--legacy-school-id",
        type=int,
        default=None,
        help="Avec --patch-organization : ancien SET @mig_school_id à remplacer",
    )

    args = parser.parse_args()
    output_path = Path(args.output)

    org_kwargs = {
        "proved_name": args.proved_name,
        "proved_code": args.proved_code,
        "sous_division_name": args.sous_division_name,
        "sous_division_code": args.sous_division_code,
        "school_name": args.school_name,
        "school_city": args.school_city,
        "school_address": args.school_address,
    }

    if args.patch_organization:
        if not output_path.exists():
            print(f"[ERROR] Fichier à patcher introuvable : {output_path}", file=sys.stderr)
            return 1
        content = output_path.read_text(encoding="utf-8")
        sql = patch_migration_sql_organization(
            content,
            legacy_school_id=args.legacy_school_id or args.school_id,
            **org_kwargs,
        )
        output_path.write_text(sql, encoding="utf-8")
        print("Organisation Proved/SD injectée dans le fichier migration.")
        print(f"Output: {output_path}")
        return 0

    if not args.backup:
        print("[ERROR] --backup est requis sauf avec --patch-organization", file=sys.stderr)
        return 1

    backup_path = Path(args.backup)
    if not backup_path.exists():
        print(f"[ERROR] Backup file not found: {backup_path}", file=sys.stderr)
        return 1

    parsed = parse_backup(backup_path)
    sql = build_sql(
        parsed,
        school_id=args.school_id,
        default_plain_password=args.default_password,
        bcrypt_rounds=args.bcrypt_rounds,
        filiaire_ref=args.filiaire_ref,
        **org_kwargs,
    )
    output_path.write_text(sql, encoding="utf-8")

    print("SQL migration file generated successfully.")
    print(f"Output: {output_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
