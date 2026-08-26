<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Deliberation;

use App\Exports\DeliberationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deliberation\DeliberationRequest;
use App\Http\Resources\Deliberation\DeliberationResource;
use App\Models\Deliberation;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class DeliberationController extends Controller
{
    public function index(Request $request)
    {
        $deliberations = $this->filteredQuery($request)
            ->with([
                'student',
                'cotations',
                'classroom',
                'filiaire',
                'academicLevel',
                'cycle',
                'schoolYear',
                'school',
                'course',
                'registration',
                'conduiteGrade',
            ])
            ->get();

        return DeliberationResource::collection($deliberations);
    }

    /**
     * Export Excel des délibérations (mêmes filtres que index).
     * Query: classroom_id, course_id, school_year_id, search, initialized
     */
    public function export(Request $request): BinaryFileResponse|HttpResponse
    {
        $classroomId = $request->input('classroom_id', $request->input('classe', $request->input('idClasse')));
        $courseId = $request->input('course_id', $request->input('cours'));

        if (empty($classroomId) || empty($courseId)) {
            return response()->json([
                'success' => false,
                'message' => 'Les paramètres classroom_id (classe) et course_id (cours) sont requis pour l\'export.',
            ], 422);
        }

        $deliberations = $this->filteredQuery($request)
            ->with(['student', 'classroom', 'course', 'schoolYear'])
            ->orderBy('id')
            ->get();

        $schoolYearId = $request->input('school_year_id', $request->input('annee_scolaire'));
        $suffix = $schoolYearId ? '_annee_'.$schoolYearId : '';

        return Excel::download(
            new DeliberationsExport($deliberations),
            'deliberations_classe_'.$classroomId.'_cours_'.$courseId.$suffix.'_'.now()->format('Ymd_His').'.xlsx',
        );
    }

    public function store(DeliberationRequest $request)
    {
        $data = $request->validated();

        // Détermination automatique de l'année scolaire si non fournie
        $schoolYearId = $data['school_year_id']
            ?? ($request->user()->school_year_id ?? SchoolYear::where('is_active', true)->value('id'));
        $classroomId = $data['classroom_id'] ?? null;
        $courseId = $data['course_id'] ?? null;

        if (! $classroomId || ! $courseId || ! $schoolYearId) {
            return response()->json([
                'status' => false,
                'message' => 'classroom_id, course_id et school_year_id sont obligatoires.',
            ], 422);
        }

        // Récupérer tous les élèves inscrits dans cette classe et année
        $registrations = \App\Models\Registration::with([
            'classroom.academicLevel.cycle.filiaire',
            'classroom.filiaire',
            'student',
        ])
            ->where('classroom_id', $classroomId)
            ->where('school_year_id', $schoolYearId)
            ->get();

        if ($registrations->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun élève trouvé pour cette classe et cette année scolaire.',
            ], 404);
        }

        $created = [];
        $skipped = 0;
        foreach ($registrations as $r) {
            $context = $this->resolveDeliberationContext($r);
            if ($context === null) {
                $skipped++;
                continue;
            }

            $exists = Deliberation::where('classroom_id', $classroomId)
                ->where('course_id', $courseId)
                ->where('school_year_id', $schoolYearId)
                ->where('student_id', $r->student_id)
                ->exists();
            if (! $exists) {
                $deliberation = Deliberation::create([
                    'student_id' => $r->student_id,
                    'classroom_id' => $classroomId,
                    'course_id' => $courseId,
                    'school_year_id' => $schoolYearId,
                    'filiaire_id' => $context['filiaire_id'],
                    'academic_level_id' => $context['academic_level_id'],
                    'cycle_id' => $context['cycle_id'],
                    'school_id' => $context['school_id'],
                    'is_validated' => false,
                ]);
                $created[] = $deliberation;
            }
        }

        if (empty($created)) {
            return response()->json([
                'status' => true,
                'message' => $skipped > 0
                    ? 'Aucune délibération créée : données de classe incomplètes (cycle/filière/niveau) pour certains élèves.'
                    : 'Toutes les délibérations existent déjà pour cette classe et ce cours.',
                'created_count' => 0,
                'skipped_count' => $skipped,
                'data' => [],
            ], $skipped > 0 ? 422 : 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Délibérations créées avec succès.',
            'created_count' => count($created),
            'skipped_count' => $skipped,
            'data' => DeliberationResource::collection(collect($created)),
        ], 201);
    }

    public function show($id)
    {
        $deliberation = Deliberation::with([
            'student',
            'cotations',
            'classroom',
            'filiaire',
            'academicLevel',
            'cycle',
            'schoolYear',
            'school',
            'course',
            'registration',
            'conduiteGrade', // Ajout ici aussi
        ])->findOrFail($id);

        return new DeliberationResource($deliberation);
    }

    public function update(DeliberationRequest $request, $id)
    {
        $deliberation = Deliberation::findOrFail($id);
        // On ne met à jour que le champ is_validated
        $deliberation->is_validated = $request->validated()['is_validated'] ?? $deliberation->is_validated;
        $deliberation->save();

        return new DeliberationResource($deliberation);
    }

    public function destroy($id)
    {
        $deliberation = Deliberation::findOrFail($id);
        $deliberation->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function initialize(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'course_id' => 'required|exists:courses,id',
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        // Récupération des élèves inscrits dans cette classe et année
        $registrations = \App\Models\Registration::with([
            'classroom.academicLevel.cycle.filiaire',
            'classroom.filiaire',
            'student',
        ])
            ->where('classroom_id', $request->classroom_id)
            ->where('school_year_id', $request->school_year_id)
            ->get();

        if ($registrations->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun élève trouvé pour cette classe et cette année scolaire.',
                'created_count' => 0,
            ], 404);
        }

        $created = [];
        $skipped = 0;
        foreach ($registrations as $r) {
            $context = $this->resolveDeliberationContext($r);
            if ($context === null) {
                $skipped++;
                continue;
            }

            $exists = Deliberation::where('classroom_id', $request->classroom_id)
                ->where('course_id', $request->course_id)
                ->where('school_year_id', $request->school_year_id)
                ->where('student_id', $r->student_id)
                ->exists();

            if (! $exists) {
                $deliberation = Deliberation::create([
                    'student_id' => $r->student_id,
                    'classroom_id' => $r->classroom_id,
                    'course_id' => $request->course_id,
                    'school_year_id' => $request->school_year_id,
                    'filiaire_id' => $context['filiaire_id'],
                    'academic_level_id' => $context['academic_level_id'],
                    'cycle_id' => $context['cycle_id'],
                    'school_id' => $context['school_id'],
                    'is_validated' => false,
                ]);
                $created[] = $deliberation;
            }
        }

        if (empty($created)) {
            return response()->json([
                'status' => true,
                'message' => $skipped > 0
                    ? 'Aucune délibération créée : données de classe incomplètes (cycle/filière/niveau).'
                    : 'Toutes les délibérations existent déjà pour cette classe et ce cours.',
                'created_count' => 0,
                'skipped_count' => $skipped,
                'data' => [],
            ], $skipped > 0 ? 422 : 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Délibérations initialisées avec succès.',
            'created_count' => count($created),
            'skipped_count' => $skipped,
            'data' => DeliberationResource::collection(collect($created)),
        ], 201);
    }

    /**
     * Résout filiaire/cycle/niveau/école depuis l'inscription ou la hiérarchie de la classe.
     *
     * @return array{filiaire_id:int, academic_level_id:int, cycle_id:int, school_id:int}|null
     */
    private function resolveDeliberationContext(\App\Models\Registration $registration): ?array
    {
        $classroom = $registration->classroom;
        $level = $classroom?->academicLevel;
        $cycle = $level?->cycle;
        $filiaireFromCycle = $cycle?->filiaire;
        $filiaireFromClass = $classroom?->filiaire;

        $academicLevelId = $registration->academic_level_id
            ?: $classroom?->academic_level_id
            ?: $level?->id;
        $cycleId = $registration->cycle_id
            ?: $level?->cycle_id
            ?: $cycle?->id;
        $filiaireId = $registration->filiaire_id
            ?: $classroom?->filiaire_id
            ?: $cycle?->filiaire_id
            ?: $filiaireFromClass?->id
            ?: $filiaireFromCycle?->id;
        $schoolId = $registration->school_id
            ?: $classroom?->school_id
            ?: $registration->student?->school_id
            ?: $filiaireFromClass?->school_id
            ?: $filiaireFromCycle?->school_id;

        if (! $academicLevelId || ! $cycleId || ! $filiaireId || ! $schoolId) {
            return null;
        }

        return [
            'filiaire_id' => (int) $filiaireId,
            'academic_level_id' => (int) $academicLevelId,
            'cycle_id' => (int) $cycleId,
            'school_id' => (int) $schoolId,
        ];
    }

    private function filteredQuery(Request $request)
    {
        $user = $request->user();

        $schoolYearId = $request->input('school_year_id', $request->input('annee_scolaire'))
            ?? ($user->school_year_id ?? SchoolYear::query()->where('is_active', true)->value('id'));

        $query = Deliberation::query();

        if ($user && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        if ($schoolYearId) {
            $query->where('school_year_id', $schoolYearId);
        }

        $classroomId = $request->input('classroom_id', $request->input('classe', $request->input('idClasse')));
        if (! empty($classroomId)) {
            $query->where('classroom_id', $classroomId);
        }

        $courseId = $request->input('course_id', $request->input('cours'));
        if (! empty($courseId)) {
            $query->where('course_id', $courseId);
        }

        if ($request->has('initialized')) {
            $query->where('is_validated', $request->boolean('initialized'));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower(mb_trim($request->input('search')));
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($q2) use ($search) {
                    $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
                })
                    ->orWhereHas('course', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(label) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        return $query;
    }
}
