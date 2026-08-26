<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AcademicPersonal;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait ResolvesAdminSchoolContext
{
    protected function activeSchoolId(Request $request): ?int
    {
        $user = $request->user();
        if ($user && $user->hasRole('super-admin')) {
            $id = session('selected_school_id');

            return $id ? (int) $id : null;
        }

        return $user?->school_id ? (int) $user->school_id : null;
    }

    /**
     * person_presences.author_id → academic_personals.id (pas users.id).
     */
    protected function resolvePersonPresenceAuthorId(Request $request, int $schoolId): int
    {
        $user = $request->user();
        if ($user) {
            $personalId = $user->academicPersonal?->id;
            if ($personalId) {
                return (int) $personalId;
            }
        }

        $fallback = AcademicPersonal::query()
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->value('id');

        if ($fallback) {
            return (int) $fallback;
        }

        throw ValidationException::withMessages([
            'school_id' => 'Aucun personnel dans cet établissement pour enregistrer les présences.',
        ]);
    }

    protected function resolveSchoolYearId(Request $request): ?int
    {
        $id = $request->input('school_year_id', $request->input('annee_scolaire'));
        if ($id) {
            return (int) $id;
        }

        $active = SchoolYear::active($this->activeSchoolId($request));

        return $active?->id;
    }

    /**
     * @return Collection<int, Classroom>
     */
    protected function classroomsForSchool(?int $schoolId): Collection
    {
        $query = Classroom::query()->orderBy('name');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->get(['id', 'name', 'school_id']);
    }

    /**
     * @return Collection<int, SchoolYear>
     */
    protected function schoolYearsForContext(?int $schoolId): Collection
    {
        $query = SchoolYear::query()->orderByDesc('id');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->get(['id', 'name', 'is_active', 'school_id']);
    }

    /**
     * @return Collection<int, Course>
     */
    protected function coursesForClassroom(?int $classroomId): Collection
    {
        if (! $classroomId) {
            return collect();
        }

        return Course::query()
            ->where('classroom_id', $classroomId)
            ->orderBy('label')
            ->get(['id', 'label', 'classroom_id']);
    }

    /**
     * @return array{
     *     schoolId: ?int,
     *     schoolYearId: ?int,
     *     classroomId: int,
     *     courseId: int,
     *     classrooms: Collection,
     *     schoolYears: Collection,
     *     courses: Collection,
     * }
     */
    protected function classCourseFilters(Request $request): array
    {
        $schoolId = $this->activeSchoolId($request);
        $classroomId = (int) $request->input('classroom_id', $request->input('classe', 0));
        $courseId = (int) $request->input('course_id', $request->input('cours', 0));
        $schoolYearId = $this->resolveSchoolYearId($request);

        if ($classroomId > 0 && $courseId > 0) {
            $courseValid = Course::query()
                ->where('classroom_id', $classroomId)
                ->where('id', $courseId)
                ->exists();
            if (! $courseValid) {
                $courseId = 0;
            }
        }

        return [
            'schoolId' => $schoolId,
            'schoolYearId' => $schoolYearId,
            'classroomId' => $classroomId,
            'courseId' => $courseId,
            'classrooms' => $this->classroomsForSchool($schoolId),
            'schoolYears' => $this->schoolYearsForContext($schoolId),
            'courses' => $this->coursesForClassroom($classroomId > 0 ? $classroomId : null),
        ];
    }
}
