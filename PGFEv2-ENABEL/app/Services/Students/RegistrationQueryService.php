<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Registration;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class RegistrationQueryService
{
    /**
     * @param  array<int, string>  $relations
     */
    public function baseQuery(array $relations = []): Builder
    {
        return Registration::query()->with($relations);
    }

    /**
     * @param  array<int, string>  $relations
     */
    public function exportQuery(Request $request, array $relations): Builder
    {
        $query = $this->baseQuery($relations);
        $this->applyRegistrationYearFilters($request, $query);

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->input('classroom_id'));
        }

        return $query;
    }

    /**
     * @return Collection<int, Registration>
     */
    public function listForExport(Request $request): Collection
    {
        return $this->exportQuery($request, [
            'student',
            'academicLevel',
            'classroom',
            'filiaire',
            'cycle',
            'schoolYear',
            'registrationParents.parent1',
            'registrationParents.parent2',
            'registrationParents.parent3',
        ])->get();
    }

    /**
     * @return Collection<int, Registration>
     */
    public function listSimple(Request $request): Collection
    {
        $query = $this->baseQuery(['student', 'academicLevel', 'classroom', 'filiaire', 'cycle']);
        $this->applyRegistrationYearFilters($request, $query);

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->input('classroom_id'));
        }

        if ($request->filled('search')) {
            $this->applyStudentSearch($request, $query);
        }

        return $query->latest()->get();
    }

    /**
     * @return Collection<int, Registration>
     */
    public function listIndex(Request $request): Collection
    {
        $query = $this->baseQuery([
            'student',
            'academicLevel',
            'classroom',
            'filiaire',
            'cycle',
            'registrationParents.parent1',
            'registrationParents.parent2',
            'registrationParents.parent3',
        ])->latest();

        $this->applyRegistrationYearFilters($request, $query);
        $this->applyIndexFilters($request, $query);

        return $query->get();
    }

    public function applyRegistrationYearFilters(Request $request, Builder $query): void
    {
        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', (int) $request->input('school_year_id'));

            return;
        }

        if ($request->boolean('active_school_year_only')) {
            $uid = Auth::user()?->school_id;
            if ($uid) {
                $active = SchoolYear::active((int) $uid);
                if ($active) {
                    $query->where('school_year_id', $active->id);
                }
            }
        }
    }

    public function applyStudentSearch(Request $request, Builder $query): void
    {
        $search = mb_strtolower(mb_trim((string) $request->input('search')));
        $query->whereHas('student', function ($q) use ($search) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
        });
    }

    private function applyIndexFilters(Request $request, Builder $query): void
    {
        if ($request->filled('filiaire_id')) {
            $query->where('filiaire_id', (int) $request->input('filiaire_id'));
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->input('classroom_id'));
        }

        if ($request->filled('cycle_id')) {
            $query->where('cycle_id', (int) $request->input('cycle_id'));
        }

        if ($request->filled('gender')) {
            $gender = mb_strtoupper(mb_trim((string) $request->input('gender')));
            $query->whereHas('student', fn ($q) => $q->whereRaw('UPPER(gender) = ?', [$gender]));
        }

        if ($request->filled('search')) {
            $this->applyStudentSearch($request, $query);
        }

        if ($request->boolean('distinct_student')) {
            $latestIds = Registration::selectRaw('MAX(id) as id')->groupBy('student_id');
            $query->whereIn('id', $latestIds->pluck('id'));
        }
    }
}
