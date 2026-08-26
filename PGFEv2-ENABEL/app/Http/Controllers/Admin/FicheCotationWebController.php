<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\FicheCotationExport;
use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\FicheCotation;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FicheCotationWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $rows = collect();

        if ($filters['classroomId'] > 0 && $filters['courseId'] > 0 && $filters['schoolYearId']) {
            $query = FicheCotation::with([
                'student:id,firstname,lastname,name,matricule',
                'course:id,label',
                'classroom:id,name',
            ])
                ->where('school_year_id', $filters['schoolYearId'])
                ->where('classroom_id', $filters['classroomId'])
                ->where('course_id', $filters['courseId']);

            if ($filters['schoolId']) {
                $query->whereHas('student', fn ($q) => $q->where('school_id', $filters['schoolId']));
            }

            if ($request->filled('search')) {
                $search = mb_strtolower(mb_trim((string) $request->input('search')));
                $query->where(function ($q) use ($search) {
                    $q->whereHas('student', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
                    });
                });
            }

            $rows = $query->orderBy('id')->get();
        }

        return view('backend.pages.fiche-cotations.index', array_merge($filters, [
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
            ->get();

        $created = 0;
        $emptyNote = json_encode(['P1' => 0, 'P2' => 0, 'P3' => 0, 'P4' => 0, 'E1' => 0, 'E2' => 0]);

        foreach ($registrations as $registration) {
            $fiche = FicheCotation::firstOrCreate(
                [
                    'school_year_id' => $data['school_year_id'],
                    'classroom_id' => $data['classroom_id'],
                    'course_id' => $data['course_id'],
                    'student_id' => $registration->student_id,
                ],
                ['note' => $emptyNote]
            );
            if ($fiche->wasRecentlyCreated) {
                $created++;
            }
        }

        return redirect()
            ->route('admin.fiche-cotations.index', [
                'classroom_id' => $data['classroom_id'],
                'course_id' => $data['course_id'],
                'school_year_id' => $data['school_year_id'],
            ])
            ->with('success', $created > 0
                ? "Fiches initialisées : {$created} élève(s)."
                : 'Les fiches existent déjà pour cette classe et ce cours.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->classCourseFilters($request);

        abort_if(! $filters['schoolYearId'], 422, 'Année scolaire requise.');

        return Excel::download(
            new FicheCotationExport(
                schoolYearId: $filters['schoolYearId'],
                classroomId: $filters['classroomId'] > 0 ? $filters['classroomId'] : null,
                courseId: $filters['courseId'] > 0 ? $filters['courseId'] : null,
            ),
            'fiche_cotations_'.now()->format('Ymd_His').'.xlsx',
        );
    }
}
