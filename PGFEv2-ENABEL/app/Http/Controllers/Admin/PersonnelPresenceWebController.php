<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PersonPresencesExport;
use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\PersonPresence;
use App\Models\School;
use App\Services\Presence\PersonnelPresenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PersonnelPresenceWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function __construct(
        private readonly PersonnelPresenceService $presenceService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $schoolId = $this->activeSchoolId($request);
        if (! $schoolId && $request->filled('school_id')) {
            $schoolId = (int) $request->integer('school_id');
        }
        $date = (string) $request->input('date', now()->format('Y-m-d'));
        $search = $request->filled('search') ? mb_trim((string) $request->input('search')) : null;
        $statusFilter = $request->filled('status') ? (string) $request->input('status') : null;

        $schools = School::query()->orderBy('name')->get(['id', 'name']);

        $rows = collect();
        $initializedCount = 0;
        $school = null;

        if ($schoolId) {
            $school = $schools->firstWhere('id', $schoolId) ?? School::query()->find($schoolId);
            $rows = $this->presenceService->buildAttendanceSheet($schoolId, $date, $search, $statusFilter);
            $initializedCount = PersonPresence::query()
                ->where('school_id', $schoolId)
                ->whereDate('created_at', $date)
                ->count();
        }

        return view('backend.pages.personnel-presences.index', [
            'schools' => $schools,
            'school' => $school,
            'schoolId' => $schoolId,
            'date' => $date,
            'search' => $request->input('search'),
            'statusFilter' => $statusFilter,
            'rows' => $rows,
            'initializedCount' => $initializedCount,
            'statuses' => PersonnelPresenceService::STATUSES,
        ]);
    }

    public function initialize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'date' => ['required', 'date'],
        ]);

        $schoolId = (int) $data['school_id'];
        $this->assertSchoolAccessible($request, $schoolId);

        $authorId = $this->resolvePersonPresenceAuthorId($request, $schoolId);

        $count = $this->presenceService->initializeSheet(
            $schoolId,
            $data['date'],
            $authorId,
        );

        return redirect()
            ->route('admin.personnel-presences.index', [
                'school_id' => $schoolId,
                'date' => $data['date'],
            ])
            ->with('success', "Feuille initialisée : {$count} présence(s) créée(s) (agents déjà pointés ignorés).");
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'date' => ['required', 'date'],
            'presences' => ['required', 'array', 'min:1'],
            'presences.*.personnel_id' => ['required', 'exists:academic_personals,id'],
            'presences.*.status' => ['required', 'in:present,absent,absent_justified,sick'],
        ]);

        $schoolId = (int) $data['school_id'];
        $this->assertSchoolAccessible($request, $schoolId);

        $authorId = $this->resolvePersonPresenceAuthorId($request, $schoolId);

        $saved = $this->presenceService->syncBulk(
            $schoolId,
            $data['date'],
            $data['presences'],
            $authorId,
        );

        return redirect()
            ->route('admin.personnel-presences.index', [
                'school_id' => $schoolId,
                'date' => $data['date'],
            ])
            ->with('success', "Présences enregistrées pour {$saved} agent(s).");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $request->validate([
            'school_id' => ['nullable', 'exists:schools,id'],
            'date' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $schoolId = isset($data['school_id']) ? (int) $data['school_id'] : $this->activeSchoolId($request);
        $date = $data['date'] ?? null;

        return Excel::download(
            new PersonPresencesExport(
                dateDebut: $data['date_debut'] ?? $date,
                dateFin: $data['date_fin'] ?? $date,
                schoolId: $schoolId,
            ),
            'presences_personnel_'.now()->format('Ymd_His').'.xlsx',
        );
    }

    private function assertSchoolAccessible(Request $request, int $schoolId): void
    {
        $active = $this->activeSchoolId($request);
        if ($active !== null && $active !== $schoolId) {
            abort(403, 'École non autorisée pour votre session.');
        }
    }
}
