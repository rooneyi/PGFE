-- ============================================================================
-- Cohérence classrooms / registrations / students (MySQL / MariaDB)
-- À exécuter APRÈS sauvegarde de la base. Tester sur une copie en premier.
--
-- Chaînage (projet PGFE) :
--   classrooms : school_id, filiaire_id, academic_level_id
--   students   : school_id (+ soft deletes)
--   registrations : school_id, classroom_id, student_id, academic_level_id… (+ soft deletes)
--
-- Règle :
--   A) Si classroom_id → registration.school_id = classrooms.school_id
--   B) Inscriptions encore sans school_id → reprendre students.school_id
--   C) registration.academic_level_id ← classrooms.academic_level_id si besoin
--   D) Élèves sans school_id → dernière inscription (MAX(id)) ayant un school_id
-- ============================================================================
-- Pas de START TRANSACTION : chaque UPDATE est appliqué tout de suite (pratique
-- pour mysql ... < script.sql). Sinon fermer la session sans COMMIT annule tout.

-- --- Diagnostics -------------------------------------------------------------

SELECT COUNT(*) AS registrations_ecole_differe_de_la_classe
FROM registrations r
JOIN classrooms c ON c.id = r.classroom_id
WHERE r.classroom_id IS NOT NULL
  AND r.deleted_at IS NULL
  AND r.school_id IS NOT NULL
  AND c.school_id IS NOT NULL
  AND r.school_id <> c.school_id;

SELECT COUNT(*) AS registrations_ecole_differe_de_leleve
FROM registrations r
JOIN students s ON s.id = r.student_id
WHERE r.student_id IS NOT NULL
  AND r.deleted_at IS NULL
  AND r.school_id IS NOT NULL
  AND s.school_id IS NOT NULL
  AND r.school_id <> s.school_id;

SELECT COUNT(*) AS students_sans_school_id
FROM students
WHERE school_id IS NULL AND deleted_at IS NULL;

SELECT COUNT(*) AS classrooms_sans_school_id
FROM classrooms
WHERE school_id IS NULL;


-- --- Corrections -------------------------------------------------------------

-- A) Priorité à la classe : même école que la classe
UPDATE registrations r
JOIN classrooms c ON c.id = r.classroom_id
SET r.school_id = c.school_id
WHERE r.classroom_id IS NOT NULL
  AND r.deleted_at IS NULL
  AND c.school_id IS NOT NULL
  AND (r.school_id IS NULL OR r.school_id <> c.school_id);

-- B) Toujours sans école : copier depuis l’élève
UPDATE registrations r
JOIN students s ON s.id = r.student_id AND s.deleted_at IS NULL
SET r.school_id = s.school_id
WHERE r.deleted_at IS NULL
  AND r.student_id IS NOT NULL
  AND s.school_id IS NOT NULL
  AND r.school_id IS NULL;

-- C) Niveau académique aligné sur la classe
UPDATE registrations r
JOIN classrooms c ON c.id = r.classroom_id
SET r.academic_level_id = c.academic_level_id
WHERE r.classroom_id IS NOT NULL
  AND r.deleted_at IS NULL
  AND c.academic_level_id IS NOT NULL
  AND (r.academic_level_id IS NULL OR r.academic_level_id <> c.academic_level_id);

-- D) Élève sans school_id : école de la dernière inscription non supprimée avec school_id
UPDATE students s
JOIN (
    SELECT r.student_id, MAX(r.id) AS max_reg_id
    FROM registrations r
    WHERE r.deleted_at IS NULL
      AND r.school_id IS NOT NULL
    GROUP BY r.student_id
) t ON t.student_id = s.id
JOIN registrations r ON r.id = t.max_reg_id AND r.deleted_at IS NULL
SET s.school_id = r.school_id
WHERE s.deleted_at IS NULL
  AND s.school_id IS NULL;

-- Les lignes ci-dessus sont persistées immédiatement (autocommit).
