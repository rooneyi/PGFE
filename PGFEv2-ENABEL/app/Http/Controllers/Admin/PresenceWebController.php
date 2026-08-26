<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PresencesExport;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Presence;
use App\Services\Presence\StudentPresenceService;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class PresenceWebController extends Controller
{
    public function __construct(
        private readonly StudentPresenceService $presenceService,
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId($request);
        $classroomId = (int) $request->input('classroom_id', $request->input('idClasse', 0));
        $date = (string) $request->input('date', now()->format('Y-m-d'));
        $search = $request->filled('search') ? mb_trim((string) $request->input('search')) : null;
        $statusFilter = $request->filled('status') ? (string) $request->input('status') : null;

        $classroomsQuery = Classroom::query()->orderBy('name');
        if ($schoolId) {
            $classroomsQuery->where('school_id', $schoolId);
        }
        $classrooms = $classroomsQuery->get(['id', 'name']);

        $rows = collect();
        $classroom = null;
        $initializedCount = 0;

        if ($classroomId > 0) {
            $classroom = $classrooms->firstWhere('id', $classroomId) ?? Classroom::query()->find($classroomId);
            $rows = $this->presenceService->buildAttendanceSheet($classroomId, $date, $search, $statusFilter);
            $initializedCount = Presence::query()
                ->where('classroom_id', $classroomId)
                ->whereDate('created_at', $date)
                ->count();
        }

        return view('backend.pages.presences.index', [
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'classroomId' => $classroomId,
            'date' => $date,
            'search' => $request->input('search'),
            'statusFilter' => $statusFilter,
            'rows' => $rows,
            'initializedCount' => $initializedCount,
            'schoolId' => $schoolId,
            'statuses' => StudentPresenceService::STATUSES,
        ]);
    }

    public function initialize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'date' => ['required', 'date'],
        ]);

        $count = $this->presenceService->initializeSheet((int) $data['classroom_id'], $data['date']);

        return redirect()
            ->route('admin.presences.index', [
                'classroom_id' => $data['classroom_id'],
                'date' => $data['date'],
            ])
            ->with('success', "Feuille initialisée : {$count} présence(s) créée(s) (élèves déjà pointés ignorés).");
    }

    public function bulkUpdate(Request $request, int $classroom): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'presences' => ['required', 'array', 'min:1'],
            'presences.*.student_id' => ['required', 'exists:students,id'],
            'presences.*.status' => ['required', 'in:present,absent,absent_justified,sick'],
        ]);

        $saved = $this->presenceService->syncBulk($classroom, $data['date'], $data['presences']);

        return redirect()
            ->route('admin.presences.index', [
                'classroom_id' => $classroom,
                'date' => $data['date'],
            ])
            ->with('success', "Présences enregistrées pour {$saved} élève(s).");
    }

    public function update(Request $request, Presence $presence): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:present,absent,absent_justified,sick'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'date' => ['required', 'date'],
        ]);

        $this->presenceService->updateRecord($presence, $data['status']);

        return redirect()
            ->route('admin.presences.index', [
                'classroom_id' => $data['classroom_id'],
                'date' => $data['date'],
            ])
            ->with('success', 'Présence mise à jour.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $request->validate([
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'date' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $schoolId = $this->activeSchoolId($request);
        $classroomId = isset($data['classroom_id']) ? (int) $data['classroom_id'] : null;
        $date = $data['date'] ?? null;

        return Excel::download(
            new PresencesExport(
                dateDebut: $data['date_debut'] ?? $date,
                dateFin: $data['date_fin'] ?? $date,
                schoolId: $schoolId,
                classroomId: $classroomId,
            ),
            'presences_'.now()->format('Ymd_His').'.xlsx',
        );
    }

    public function exportPdf(Request $request): Response
    {
        $data = $request->validate([
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'date' => ['nullable', 'date'],
        ]);

        $schoolId = $this->activeSchoolId($request);
        $classroomId = isset($data['classroom_id']) ? (int) $data['classroom_id'] : null;
        $date = $data['date'] ?? now()->format('Y-m-d');

        $query = Presence::with(['student', 'school', 'classroom'])
            ->leftJoin('students', 'students.id', '=', 'presences.student_id')
            ->select('presences.*');

        if ($schoolId) {
            $query->where('presences.school_id', $schoolId);
        }
        if ($classroomId) {
            $query->where('presences.classroom_id', $classroomId);
        }
        $query->whereDate('presences.created_at', $date);
        $query->orderBy('students.lastname')->orderBy('students.firstname');

        $presences = $query->get();

        $html = view('exports.presences', [
            'presences' => $presences,
            'date_debut' => $date,
            'date_fin' => $date,
        ])->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'presences_'.$date.'.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    private function activeSchoolId(Request $request): ?int
    {
        $user = $request->user();
        if ($user && $user->hasRole('super-admin')) {
            $id = session('selected_school_id');

            return $id ? (int) $id : null;
        }

        return $user?->school_id ? (int) $user->school_id : null;
    }
}
