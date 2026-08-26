<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\BulletinRessource\StudentBulletinResource;
use App\Models\Student;
use App\Services\StudentBulletinService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class BulletinWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function __construct(
        private readonly StudentBulletinService $bulletinService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->classCourseFilters($request);
        $schoolId = $filters['schoolId'];

        $studentsQuery = Student::query()->orderBy('lastname')->orderBy('name');
        if ($schoolId) {
            $studentsQuery->where('school_id', $schoolId);
        }
        if ($request->filled('search')) {
            $search = mb_strtolower(mb_trim((string) $request->input('search')));
            $studentsQuery->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
            });
        }

        $students = $studentsQuery->limit(100)->get(['id', 'firstname', 'lastname', 'name', 'matricule']);

        return view('backend.pages.bulletins.index', array_merge($filters, [
            'students' => $students,
            'search' => $request->input('search'),
            'selectedStudentId' => (int) $request->input('student_id', 0),
        ]));
    }

    public function print(Request $request): Response
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
        ]);

        $schoolYearId = isset($data['school_year_id']) ? (int) $data['school_year_id'] : null;
        $studentModel = $this->bulletinService->loadStudent((int) $data['student_id'], $schoolYearId);
        $resource = (new StudentBulletinResource($studentModel))->toArray($request);

        $html = view('bulletins.bulletin_dompdf', [
            'student' => $resource['student'] ?? [],
            'school' => $resource['registration']['school'] ?? [],
            'classe' => $resource['registration']['classroom'] ?? [],
            'grades' => $resource['grades'] ?? [],
            'summary' => $resource['summary'] ?? [],
            'schoolYear' => $resource['registration']['school_year']['name'] ?? '',
            'generatedAt' => $resource['generated_at'] ?? now()->format('Y-m-d H:i:s'),
        ])->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'bulletin_'.($data['student_id']).'.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
