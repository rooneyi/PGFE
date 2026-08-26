<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\PersonAffectation;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PersonAffectationWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request): View
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        $schoolYearId = $this->resolveSchoolYearId($request);

        $query = PersonAffectation::query()
            ->with(['academicPersonal:id,name,pre_name,post_name,matricule', 'schoolYear:id,name', 'school:id,name'])
            ->latest('id');

        if ($selectedSchoolId) {
            $query->where('school_id', $selectedSchoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', (int) $request->integer('school_id'));
        }

        if ($schoolYearId) {
            $query->where('school_year_id', $schoolYearId);
        }

        if ($request->filled('search')) {
            $search = mb_trim((string) $request->input('search'));
            $query->whereHas('academicPersonal', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('pre_name', 'like', "%{$search}%")
                    ->orWhere('post_name', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $affectations = $query->paginate(25)->appends($request->query());
        $schools = School::orderBy('name')->get(['id', 'name']);
        $schoolYears = $this->schoolYearsForContext($selectedSchoolId);

        return view('backend.pages.person-affectations.index', compact(
            'affectations',
            'selectedSchoolId',
            'schools',
            'schoolYears',
            'schoolYearId',
        ));
    }
}
