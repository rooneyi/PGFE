<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\DeliberationsExport;
use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\Deliberation;
use App\Models\Registration;
use App\Services\Deliberation\DeliberationGradesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DeliberationWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function __construct(
        private readonly DeliberationGradesService $gradesService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $rows = collect();

        if ($filters['classroomId'] > 0 && $filters['courseId'] > 0) {
            $query = $this->filteredDeliberationsQuery($request);
            if ($filters['schoolYearId']) {
                $query->where('school_year_id', $filters['schoolYearId']);
            }

            $deliberations = $query
                ->with(['student', 'course', 'classroom', 'schoolYear'])
                ->orderBy('id')
                ->get();

            $rows = $deliberations->map(function (Deliberation $d) {
                $grades = $this->gradesService->computeForDeliberation($d);

                return [
                    'deliberation' => $d,
                    'grades' => $grades,
                ];
            });
        }

        return view('backend.pages.deliberations.index', array_merge($filters, [
            'rows' => $rows,
            'search' => $request->input('search'),
        ]));
    }

    public function initialize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
        ]);

        $registrations = Registration::query()
            ->where('classroom_id', $data['classroom_id'])
            ->where('school_year_id', $data['school_year_id'])
            ->where('registration_status', true)
            ->whereHas('student', fn ($q) => $q->whereNull('deleted_at'))
            ->get();

        $created = 0;
        foreach ($registrations as $registration) {
            $exists = Deliberation::query()
                ->where('classroom_id', $data['classroom_id'])
                ->where('course_id', $data['course_id'])
                ->where('school_year_id', $data['school_year_id'])
                ->where('student_id', $registration->student_id)
                ->exists();

            if ($exists) {
                continue;
            }

            Deliberation::create([
                'student_id' => $registration->student_id,
                'classroom_id' => $data['classroom_id'],
                'course_id' => $data['course_id'],
                'school_year_id' => $data['school_year_id'],
                'filiaire_id' => $registration->filiaire_id,
                'academic_level_id' => $registration->academic_level_id,
                'cycle_id' => $registration->cycle_id,
                'school_id' => $registration->school_id,
                'is_validated' => false,
            ]);
            $created++;
        }

        return redirect()
            ->route('admin.deliberations.index', [
                'classroom_id' => $data['classroom_id'],
                'course_id' => $data['course_id'],
                'school_year_id' => $data['school_year_id'],
            ])
            ->with('success', $created > 0
                ? "Délibérations initialisées : {$created} élève(s)."
                : 'Les délibérations existent déjà pour cette classe et ce cours.');
    }

    public function updateValidation(Request $request, Deliberation $deliberation): RedirectResponse
    {
        $data = $request->validate([
            'is_validated' => ['required', 'boolean'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
        ]);

        $deliberation->update(['is_validated' => (bool) $data['is_validated']]);

        return redirect()
            ->route('admin.deliberations.index', [
                'classroom_id' => $data['classroom_id'],
                'course_id' => $data['course_id'],
                'school_year_id' => $data['school_year_id'],
            ])
            ->with('success', 'Validation mise à jour.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $classroomId = (int) $request->input('classroom_id', $request->input('classe', 0));
        $courseId = (int) $request->input('course_id', $request->input('cours', 0));

        abort_if($classroomId <= 0 || $courseId <= 0, 422);

        $deliberations = $this->filteredDeliberationsQuery($request)
            ->with(['student', 'classroom', 'course', 'schoolYear'])
            ->get();

        return Excel::download(
            new DeliberationsExport($deliberations),
            'deliberations_'.now()->format('Ymd_His').'.xlsx',
        );
    }

    private function filteredDeliberationsQuery(Request $request)
    {
        $filters = $this->classCourseFilters($request);
        $schoolId = $filters['schoolId'];

        $query = Deliberation::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($filters['schoolYearId']) {
            $query->where('school_year_id', $filters['schoolYearId']);
        }
        if ($filters['classroomId'] > 0) {
            $query->where('classroom_id', $filters['classroomId']);
        }
        if ($filters['courseId'] > 0) {
            $query->where('course_id', $filters['courseId']);
        }

        if ($request->filled('search')) {
            $search = mb_strtolower(mb_trim((string) $request->input('search')));
            $query->whereHas('student', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query;
    }
}
