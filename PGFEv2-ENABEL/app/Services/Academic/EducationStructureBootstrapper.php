<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicLevel;
use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Filiaire;
use App\Models\School;
use App\Support\Academic\EducationTracks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class EducationStructureBootstrapper
{
    /**
     * @param  list<string>  $trackKeys
     * @return array{created: list<string>, already: list<string>, trimmed: list<string>, skipped: list<string>}
     *
     * @throws Throwable
     */
    public function install(School $school, array $trackKeys, bool $trimSecondary = true): array
    {
        $created = [];
        $already = [];
        $trimmed = [];
        $skipped = [];

        $allowed = EducationTracks::keys();
        $trackKeys = array_values(array_intersect($trackKeys, $allowed));

        DB::transaction(function () use ($school, $trackKeys, $trimSecondary, &$created, &$already, &$trimmed, &$skipped): void {
            foreach ($trackKeys as $key) {
                $track = EducationTracks::basicTracks()[$key];
                $result = $this->ensureTrack($school, $track);
                if ($result === 'created') {
                    $created[] = $track['section']['name'];
                } else {
                    $already[] = $track['section']['name'];
                }
            }

            if ($trimSecondary) {
                $trim = $this->trimObsoleteSecondaryLevels($school);
                $trimmed = $trim['removed'];
                $skipped = $trim['skipped'];
            }
        });

        return [
            'created' => $created,
            'already' => $already,
            'trimmed' => $trimmed,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return list<array{key: string, label: string, code: string, cycle: string, levels: list<string>, installed: bool}>
     */
    public function status(School $school): array
    {
        $rows = [];

        foreach (EducationTracks::basicTracks() as $key => $track) {
            $rows[] = [
                'key' => $key,
                'label' => $track['section']['name'],
                'code' => $track['section']['code'],
                'cycle' => $track['cycle'],
                'levels' => $track['levels'],
                'installed' => $this->findSection($school, $track) !== null,
            ];
        }

        return $rows;
    }

    public function attachDefaultSecondaryCycles(Filiaire $filiaire): void
    {
        $schoolId = (int) $filiaire->school_id;

        foreach (EducationTracks::secondaryCycles() as $cycleName => $levels) {
            $cycle = $filiaire->cycles()->firstOrCreate(
                ['name' => $cycleName],
                ['school_id' => $schoolId],
            );

            if (! $cycle->school_id) {
                $cycle->update(['school_id' => $schoolId]);
            }

            $this->ensureSecondaryLevels($cycle, $schoolId, $levels);
        }
    }

    /**
     * @param  array{section: array{name: string, code: string}, cycle: string, levels: list<string>}  $track
     */
    private function ensureTrack(School $school, array $track): string
    {
        $existing = $this->findSection($school, $track);

        $filiaire = $existing ?? Filiaire::query()->create([
            'school_id' => $school->id,
            'name' => $track['section']['name'],
            'code' => $track['section']['code'],
            'uuid' => (string) Str::uuid(),
        ]);

        $cycle = $filiaire->cycles()->firstOrCreate(
            ['name' => $track['cycle']],
            ['school_id' => $school->id],
        );

        if (! $cycle->school_id) {
            $cycle->update(['school_id' => $school->id]);
        }

        foreach ($track['levels'] as $levelName) {
            $cycle->academicLevels()->firstOrCreate(
                [
                    'name' => $levelName,
                    'school_id' => $school->id,
                ],
            );
        }

        return $existing ? 'already' : 'created';
    }

    /**
     * @param  array{section: array{name: string, code: string}, cycle: string, levels: list<string>}  $track
     */
    private function findSection(School $school, array $track): ?Filiaire
    {
        return Filiaire::query()
            ->where('school_id', $school->id)
            ->where(function ($query) use ($track) {
                $query->where('name', $track['section']['name'])
                    ->orWhere('code', $track['section']['code']);
            })
            ->first();
    }

    /**
     * @param  list<string>  $levels
     */
    private function ensureSecondaryLevels(Cycle $cycle, int $schoolId, array $levels): void
    {
        $existingNames = $cycle->academicLevels()
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        foreach ($levels as $canonical) {
            $aliases = EducationTracks::secondaryLevelAliases()[$canonical] ?? [$canonical];
            $hasAlias = false;
            foreach ($aliases as $alias) {
                if (in_array(mb_strtolower($alias), $existingNames, true)) {
                    $hasAlias = true;
                    break;
                }
            }
            if ($hasAlias) {
                continue;
            }

            $cycle->academicLevels()->create([
                'school_id' => $schoolId,
                'name' => $canonical,
            ]);
            $existingNames[] = mb_strtolower($canonical);
        }
    }

    /**
     * @return array{removed: list<string>, skipped: list<string>}
     */
    private function trimObsoleteSecondaryLevels(School $school): array
    {
        $removed = [];
        $skipped = [];

        $levels = AcademicLevel::query()
            ->where('school_id', $school->id)
            ->whereIn('name', EducationTracks::obsoleteSecondaryLevelNames())
            ->get();

        foreach ($levels as $level) {
            $label = $level->name.' (id '.$level->id.')';

            if ($this->levelIsUsed($level)) {
                $skipped[] = $label;

                continue;
            }

            $this->deleteEmptyClassrooms($level);
            $level->delete();
            $removed[] = $label;
        }

        return ['removed' => $removed, 'skipped' => $skipped];
    }

    private function deleteEmptyClassrooms(AcademicLevel $level): void
    {
        $level->classrooms()->each(function (Classroom $classroom): void {
            if ($this->classroomIsUsed($classroom)) {
                return;
            }
            $classroom->delete();
        });
    }

    private function levelIsUsed(AcademicLevel $level): bool
    {
        if ($level->classrooms()->get()->contains(fn (Classroom $classroom) => $this->classroomIsUsed($classroom))) {
            return true;
        }

        if (Schema::hasColumn('students', 'academic_level_id') && $level->students()->exists()) {
            return true;
        }

        if (Schema::hasColumn('registrations', 'academic_level_id')) {
            $exists = DB::table('registrations')->where('academic_level_id', $level->id)->exists();
            if ($exists) {
                return true;
            }
        }

        if (Schema::hasColumn('courses', 'academic_level_id')) {
            $exists = DB::table('courses')->where('academic_level_id', $level->id)->exists();
            if ($exists) {
                return true;
            }
        }

        if (Schema::hasColumn('courses', 'level_id')) {
            $exists = DB::table('courses')->where('level_id', $level->id)->exists();
            if ($exists) {
                return true;
            }
        }

        return false;
    }

    private function classroomIsUsed(Classroom $classroom): bool
    {
        if (method_exists($classroom, 'students') && $classroom->students()->exists()) {
            return true;
        }

        if (Schema::hasColumn('registrations', 'classroom_id')) {
            return DB::table('registrations')->where('classroom_id', $classroom->id)->exists();
        }

        return false;
    }
}
