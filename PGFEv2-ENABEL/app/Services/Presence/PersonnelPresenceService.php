<?php

declare(strict_types=1);

namespace App\Services\Presence;

use App\Models\AcademicPersonal;
use App\Models\PersonPresence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class PersonnelPresenceService
{
    public const STATUSES = ['present', 'absent', 'absent_justified', 'sick'];

    /**
     * @return Collection<int, array{personnel: AcademicPersonal, presence: PersonPresence|null, status: string}>
     */
    public function buildAttendanceSheet(
        int $schoolId,
        string $date,
        ?string $search = null,
        ?string $statusFilter = null,
    ): Collection {
        $personnels = AcademicPersonal::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->orderBy('pre_name')
            ->get();

        $presenceByPersonnel = PersonPresence::query()
            ->where('school_id', $schoolId)
            ->whereDate('created_at', $date)
            ->get()
            ->keyBy('personnel_id');

        $rows = $personnels->map(function (AcademicPersonal $personnel) use ($presenceByPersonnel) {
            $presence = $presenceByPersonnel->get($personnel->id);

            return [
                'personnel' => $personnel,
                'presence' => $presence,
                'status' => $this->resolveStatus($presence),
            ];
        });

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(function (array $row) use ($needle) {
                $p = $row['personnel'];

                return str_contains(mb_strtolower($p->matricule ?? ''), $needle)
                    || str_contains(mb_strtolower($p->name ?? ''), $needle)
                    || str_contains(mb_strtolower($p->pre_name ?? ''), $needle)
                    || str_contains(mb_strtolower($p->post_name ?? ''), $needle)
                    || str_contains(mb_strtolower($p->email ?? ''), $needle)
                    || str_contains(mb_strtolower($p->phone ?? ''), $needle);
            });
        }

        if ($statusFilter !== null && $statusFilter !== '' && in_array($statusFilter, self::STATUSES, true)) {
            $rows = $rows->filter(fn (array $row) => $row['status'] === $statusFilter);
        }

        return $rows->values();
    }

    /**
     * Query "index" API: liste des présences du personnel existantes pour une école et une date.
     * (Ne crée pas de lignes manquantes: la présence doit être initialisée via l'endpoint de création.)
     *
     * @return Collection<int, PersonPresence>
     *
     * @throws ValidationException si le statut est invalide.
     */
    public function listPresencesIndex(
        ?int $schoolId,
        string $date,
        ?string $status = null,
        ?string $search = null,
    ): Collection {
        $query = PersonPresence::with(['personnel', 'school']);

        // Scoping école: param ou école de l'utilisateur
        if ($schoolId !== null && $schoolId !== 0) {
            $query->where('person_presences.school_id', (int) $schoolId);
        }

        $query->whereDate('person_presences.created_at', (string) $date);

        // Filtre par statut
        if ($status !== null && $status !== '') {
            $statusLower = mb_strtolower((string) $status);
            switch ($statusLower) {
                case 'present':
                    $query->where('person_presences.presence', true);
                    break;
                case 'absent':
                    $query->where('person_presences.presence', false)
                        ->where('person_presences.absent_justified', false)
                        ->where('person_presences.sick', false);
                    break;
                case 'absent_justified':
                    $query->where('person_presences.presence', false)
                        ->where('person_presences.absent_justified', true);
                    break;
                case 'sick':
                    $query->where('person_presences.presence', false)
                        ->where('person_presences.sick', true);
                    break;
                default:
                    throw ValidationException::withMessages([
                        'status' => 'Statut de présence invalide. Valeurs acceptées : present, absent, absent_justified, sick.',
                    ]);
            }
        }

        // Jointure pour tri et recherche
        $query->leftJoin('academic_personals', 'academic_personals.id', '=', 'person_presences.personnel_id')
            ->select('person_presences.*');

        if ($search !== null && $search !== '') {
            $needle = trim((string) $search);
            $query->where(function ($q) use ($needle) {
                $q->where('academic_personals.name', 'like', "%{$needle}%")
                    ->orWhere('academic_personals.pre_name', 'like', "%{$needle}%")
                    ->orWhere('academic_personals.post_name', 'like', "%{$needle}%")
                    ->orWhere('academic_personals.matricule', 'like', "%{$needle}%")
                    ->orWhere('academic_personals.phone', 'like', "%{$needle}%")
                    ->orWhere('academic_personals.email', 'like', "%{$needle}%");
            });
        }

        $query->orderBy('academic_personals.name');

        return $query->get();
    }

    public function initializeSheet(int $schoolId, string $date, int $authorId): int
    {
        $this->assertDateNotInFuture($date);

        $personnels = AcademicPersonal::query()
            ->where('school_id', $schoolId)
            ->get();

        if ($personnels->isEmpty()) {
            throw ValidationException::withMessages([
                'school_id' => 'Aucun personnel académique pour cette école.',
            ]);
        }

        $created = 0;
        foreach ($personnels as $personnel) {
            $exists = PersonPresence::query()
                ->where('personnel_id', $personnel->id)
                ->where('school_id', $schoolId)
                ->whereDate('created_at', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            $record = PersonPresence::create([
                'personnel_id' => $personnel->id,
                'presence' => true,
                'absent_justified' => false,
                'sick' => false,
                'school_id' => $schoolId,
                'author_id' => $authorId,
            ]);

            if ($date !== now()->format('Y-m-d')) {
                $record->created_at = $date.' 00:00:00';
                $record->save();
            }

            $created++;
        }

        return $created;
    }

    /**
     * @param  array<int, array{personnel_id: int|string, status: string}>  $items
     */
    public function syncBulk(int $schoolId, string $date, array $items, int $authorId): int
    {
        $this->assertDateNotInFuture($date);

        $saved = 0;
        foreach ($items as $row) {
            if (empty($row['personnel_id'])) {
                continue;
            }

            $personnel = AcademicPersonal::query()
                ->where('school_id', $schoolId)
                ->find((int) $row['personnel_id']);

            if (! $personnel) {
                continue;
            }

            $status = (string) ($row['status'] ?? 'present');
            if (! in_array($status, self::STATUSES, true)) {
                $status = 'present';
            }

            [$present, $absentJustified, $sick] = $this->statusToFlags($status);

            $presence = PersonPresence::query()
                ->where('personnel_id', $personnel->id)
                ->where('school_id', $schoolId)
                ->whereDate('created_at', $date)
                ->first();

            if ($presence) {
                $presence->update([
                    'presence' => $present,
                    'absent_justified' => $absentJustified,
                    'sick' => $sick,
                    'author_id' => $authorId,
                ]);
            } else {
                $record = PersonPresence::create([
                    'personnel_id' => $personnel->id,
                    'presence' => $present,
                    'absent_justified' => $absentJustified,
                    'sick' => $sick,
                    'school_id' => $schoolId,
                    'author_id' => $authorId,
                ]);

                if ($date !== now()->format('Y-m-d')) {
                    $record->created_at = $date.' 00:00:00';
                    $record->save();
                }
            }

            $saved++;
        }

        return $saved;
    }

    public function resolveStatus(?PersonPresence $presence): string
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
     * @return array{0: bool, 1: bool, 2: bool}
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
