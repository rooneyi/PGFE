<?php

declare(strict_types=1);

namespace App\Services\Presence;

use App\Models\Classroom;
use App\Models\Presence;
use App\Models\Registration;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class StudentPresenceService
{
    public const STATUSES = ['present', 'absent', 'absent_justified', 'sick'];

    /**
     * @return Collection<int, array{student: Student, presence: Presence|null, status: string}>
     */
    public function buildAttendanceSheet(
        int $classroomId,
        string $date,
        ?string $search = null,
        ?string $statusFilter = null,
    ): Collection {
        $classroom = Classroom::query()->find($classroomId);
        if (! $classroom) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Classe introuvable.',
            ]);
        }

        $registrations = Registration::query()
            ->with(['student'])
            ->where('classroom_id', $classroomId)
            ->where('registration_status', true)
            ->whereHas('student', fn ($q) => $q->whereNull('deleted_at'))
            ->get();

        $presenceByStudent = Presence::query()
            ->where('classroom_id', $classroomId)
            ->whereDate('created_at', $date)
            ->get()
            ->keyBy('student_id');

        $rows = $registrations->map(function (Registration $registration) use ($presenceByStudent) {
            $student = $registration->student;
            $presence = $presenceByStudent->get($registration->student_id);

            return [
                'student' => $student,
                'presence' => $presence,
                'status' => $this->resolveStatus($presence),
            ];
        })->filter(fn (array $row) => $row['student'] !== null);

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(function (array $row) use ($needle) {
                $s = $row['student'];

                return str_contains(mb_strtolower($s->matricule ?? ''), $needle)
                    || str_contains(mb_strtolower($s->name ?? ''), $needle)
                    || str_contains(mb_strtolower($s->lastname ?? ''), $needle)
                    || str_contains(mb_strtolower($s->firstname ?? ''), $needle);
            });
        }

        if ($statusFilter !== null && $statusFilter !== '' && in_array($statusFilter, self::STATUSES, true)) {
            $rows = $rows->filter(fn (array $row) => $row['status'] === $statusFilter);
        }

        return $rows->sortBy(fn (array $row) => mb_strtolower(
            mb_trim(($row['student']->lastname ?? '').' '.($row['student']->name ?? '').' '.($row['student']->firstname ?? ''))
        ))->values();
    }

    /**
     * Query "index" API: liste des présences existantes pour une classe et une date.
     * (Ne crée pas de lignes manquantes: la présence doit être initialisée via le endpoint de création.)
     *
     * @return Collection<int, Presence>
     *
     * @throws ValidationException si le statut est invalide.
     */
    public function listPresencesIndex(
        int $classroomId,
        string $effectiveDate,
        ?int $schoolId = null,
        ?int $academicLevelId = null,
        ?string $status = null,
        ?string $search = null,
    ): Collection {
        $query = Presence::with(['student', 'school', 'classroom'])
            ->leftJoin('students', 'students.id', '=', 'presences.student_id')
            ->select('presences.*');

        // Scoping école: school_id param ou école de l'utilisateur
        if ($schoolId !== null && $schoolId !== 0) {
            $query->where('presences.school_id', (int) $schoolId);
        }

        $query->where('presences.classroom_id', (int) $classroomId);

        if ($academicLevelId !== null) {
            $query->where('presences.academic_level_id', (int) $academicLevelId);
        }

        $query->whereDate('presences.created_at', $effectiveDate);

        // Statut optionnel
        if ($status !== null && $status !== '') {
            $statusLower = mb_strtolower((string) $status);
            switch ($statusLower) {
                case 'present':
                    $query->where('presences.presence', true);
                    break;
                case 'absent':
                    $query->where('presences.presence', false);
                    break;
                case 'absent_justified':
                    $query->where('presences.presence', false)
                        ->where('presences.absent_justified', true);
                    break;
                case 'sick':
                    $query->where('presences.presence', false)
                        ->where('presences.sick', true);
                    break;
                default:
                    throw ValidationException::withMessages([
                        'status' => 'Status invalide. Valeurs acceptées: present, absent, absent_justified, sick.',
                    ]);
            }
        }

        // Recherche optionnelle sur l'élève
        if ($search !== null && $search !== '') {
            $needle = mb_strtolower(mb_trim((string) $search));
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(students.name) LIKE ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(students.lastname) LIKE ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(students.firstname) LIKE ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(students.matricule) LIKE ?', ["%{$needle}%"]);
            });
        }

        // Tri alphabétique par élève
        $query->orderBy('students.lastname')
            ->orderBy('students.firstname')
            ->orderBy('students.name');

        return $query->get();
    }

    /**
     * Équivalent API POST /presence/presences (initialiser la feuille).
     */
    public function initializeSheet(int $classroomId, string $date): int
    {
        $this->assertDateNotInFuture($date);

        $classroom = Classroom::query()->find($classroomId);
        if (! $classroom) {
            throw ValidationException::withMessages(['classroom_id' => 'Classe introuvable.']);
        }

        $registrations = Registration::query()
            ->with(['student', 'classroom'])
            ->where('classroom_id', $classroomId)
            ->where('registration_status', true)
            ->whereHas('student', fn ($q) => $q->whereNull('deleted_at'))
            ->get();

        if ($registrations->isEmpty()) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Aucun élève inscrit activement dans cette classe.',
            ]);
        }

        $created = 0;
        foreach ($registrations as $registration) {
            $student = $registration->student;
            if (! $student) {
                continue;
            }

            $exists = Presence::query()
                ->where('student_id', $student->id)
                ->where('classroom_id', $classroomId)
                ->whereDate('created_at', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            Presence::create([
                'student_id' => $student->id,
                'school_id' => $registration->school_id,
                'classroom_id' => $classroomId,
                'academic_level_id' => $registration->classroom?->academic_level_id,
                'presence' => true,
                'absent_justified' => false,
                'sick' => false,
                'created_at' => $date,
                'updated_at' => now(),
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Équivalent API POST .../classrooms/{id}/bulk avec statuts détaillés.
     *
     * @param  array<int, array{student_id: int|string, status: string}>  $items
     */
    public function syncBulk(int $classroomId, string $date, array $items): int
    {
        $this->assertDateNotInFuture($date);

        $classroom = Classroom::query()->find($classroomId);
        if (! $classroom) {
            throw ValidationException::withMessages(['classroom_id' => 'Classe introuvable.']);
        }

        $academicLevelId = $classroom->academic_level_id;
        $saved = 0;

        foreach ($items as $row) {
            if (empty($row['student_id'])) {
                continue;
            }

            $student = Student::query()->find((int) $row['student_id']);
            if (! $student) {
                continue;
            }

            $status = (string) ($row['status'] ?? 'present');
            if (! in_array($status, self::STATUSES, true)) {
                $status = 'present';
            }

            [$present, $absentJustified, $sick] = $this->statusToFlags($status);

            $presence = Presence::query()
                ->where('student_id', $student->id)
                ->where('classroom_id', $classroomId)
                ->whereDate('created_at', $date)
                ->first();

            if ($presence) {
                $presence->update([
                    'presence' => $present,
                    'absent_justified' => $absentJustified,
                    'sick' => $sick,
                    'school_id' => $presence->school_id ?? $student->school_id,
                    'academic_level_id' => $presence->academic_level_id ?? $academicLevelId,
                ]);
            } else {
                Presence::create([
                    'student_id' => $student->id,
                    'school_id' => $student->school_id,
                    'classroom_id' => $classroomId,
                    'academic_level_id' => $academicLevelId,
                    'presence' => $present,
                    'absent_justified' => $absentJustified,
                    'sick' => $sick,
                    'created_at' => $date,
                    'updated_at' => now(),
                ]);
            }

            $saved++;
        }

        return $saved;
    }

    /**
     * Équivalent API PATCH /presences/{id}.
     */
    public function updateRecord(Presence $presence, string $status): Presence
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Statut invalide.']);
        }

        [$present, $absentJustified, $sick] = $this->statusToFlags($status);
        $presence->update([
            'presence' => $present,
            'absent_justified' => $absentJustified,
            'sick' => $sick,
        ]);

        return $presence->fresh(['student', 'classroom']);
    }

    public function resolveStatus(?Presence $presence): string
    {
        if (! $presence) {
            return 'absent';
        }

        if ($presence->absent_justified) {
            return 'absent_justified';
        }

        if ($presence->sick) {
            return 'sick';
        }

        return $presence->presence ? 'present' : 'absent';
    }

    /**
     * @return array{0: bool, 1: bool, 2: bool} present, absent_justified, sick
     */
    public function statusToFlags(string $status): array
    {
        return match ($status) {
            'present' => [true, false, false],
            'absent_justified' => [false, true, false],
            'sick' => [false, false, true],
            default => [false, false, false],
        };
    }

    public function assertDateNotInFuture(string $date): void
    {
        if (Carbon::parse($date)->startOfDay()->gt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'date' => 'La date de présence ne peut pas être dans le futur.',
            ]);
        }
    }
}
