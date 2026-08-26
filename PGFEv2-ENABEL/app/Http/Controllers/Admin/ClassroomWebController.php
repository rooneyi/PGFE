<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Classroom;
use App\Models\Filiaire;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ClassroomWebController extends Controller
{
    public function index(Request $request)
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        $query = Classroom::query()
            ->with(['school:id,name', 'filiaire:id,name'])
            ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId));

        if ($request->filled('school_id')) {
            // Forcer la cohérence: ignorer school_id s’il ne correspond pas à l’école active côté super admin
            $schoolId = (int) $request->integer('school_id');
            if (! $selectedSchoolId || $selectedSchoolId === $schoolId) {
                $query->where('school_id', $schoolId);
            }
        }
        if ($search = mb_trim((string) $request->get('q', ''))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $classrooms = $query->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $schools = \App\Models\School::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.classrooms.index', compact('classrooms', 'schools'));
    }

    public function create(Request $request)
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        $filiaires = $this->filiairesForSchool($selectedSchoolId);
        $academicLevels = $this->academicLevelsForSchool($selectedSchoolId);
        $activeSchoolName = $selectedSchoolId
            ? School::query()->whereKey($selectedSchoolId)->value('name')
            : null;

        return view('backend.pages.classrooms.create', compact('filiaires', 'academicLevels', 'activeSchoolName'));
    }

    public function store(Request $request)
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        if (! $selectedSchoolId) {
            return back()->with('error', "Impossible de déterminer l'école active.")->withInput();
        }

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('classrooms', 'name')->where(fn ($q) => $q
                    ->where('school_id', $selectedSchoolId)
                    ->where('filiaire_id', $request->integer('filiaire_id'))),
            ],
            'filiaire_id' => ['required', 'exists:filiaires,id'],
            'indicator' => ['nullable', 'string', 'max:255'],
            'academic_level_id' => ['nullable', 'exists:academic_levels,id'],
        ]);

        if (! $this->filiaireBelongsToSchool((int) $data['filiaire_id'], $selectedSchoolId)) {
            return back()->with('error', 'La filiere selectionnee n\'appartient pas a l\'ecole active.')->withInput();
        }

        $payload = [
            'name' => $data['name'],
            'school_id' => $selectedSchoolId,
            'filiaire_id' => $data['filiaire_id'],
            'indicator' => $data['indicator'] ?? null,
            'academic_level_id' => $data['academic_level_id'] ?? null,
        ];

        Classroom::create($payload);

        return redirect()->route('admin.classrooms.index')->with('success', 'Classe créée avec succès.');
    }

    public function edit(Request $request, Classroom $classroom)
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        if ($selectedSchoolId && (int) $classroom->school_id !== (int) $selectedSchoolId) {
            return redirect()->route('admin.classrooms.index')->with('error', 'Cette classe n’appartient pas à l’école sélectionnée.');
        }

        $classroom->load(['school:id,name', 'filiaire:id,name']);

        $schoolId = (int) ($selectedSchoolId ?? $classroom->school_id);
        $filiaires = $this->filiairesForSchool($schoolId);
        $academicLevels = $this->academicLevelsForSchool($schoolId);

        return view('backend.pages.classrooms.edit', compact('classroom', 'filiaires', 'academicLevels'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        if ($selectedSchoolId && (int) $classroom->school_id !== (int) $selectedSchoolId) {
            return redirect()->route('admin.classrooms.index')->with('error', 'Cette classe n’appartient pas à l’école sélectionnée.');
        }

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('classrooms', 'name')->where(fn ($q) => $q
                    ->where('school_id', $classroom->school_id)
                    ->where('filiaire_id', $request->integer('filiaire_id')))->ignore($classroom->id),
            ],
            'filiaire_id' => ['required', 'exists:filiaires,id'],
            'indicator' => ['nullable', 'string', 'max:255'],
            'academic_level_id' => ['nullable', 'exists:academic_levels,id'],
        ]);

        if (! $this->filiaireBelongsToSchool((int) $data['filiaire_id'], (int) $classroom->school_id)) {
            return back()->with('error', 'La filiere selectionnee n\'appartient pas a cette ecole.')->withInput();
        }

        $classroom->update([
            'name' => $data['name'],
            'filiaire_id' => $data['filiaire_id'],
            'indicator' => $data['indicator'] ?? null,
            'academic_level_id' => $data['academic_level_id'] ?? null,
        ]);

        return redirect()->route('admin.classrooms.index')->with('success', 'Classe mise à jour.');
    }

    public function destroy(Request $request, Classroom $classroom)
    {
        $selectedSchoolId = $this->activeSchoolId($request);
        if ($selectedSchoolId && (int) $classroom->school_id !== (int) $selectedSchoolId) {
            return redirect()->route('admin.classrooms.index')->with('error', 'Cette classe n’appartient pas à l’école sélectionnée.');
        }

        $classroom->delete();

        return redirect()->route('admin.classrooms.index')->with('success', 'Classe supprimée.');
    }

    private function activeSchoolId(Request $request): ?int
    {
        $user = $request->user();
        if ($user && $user->hasRole('super-admin')) {
            return session('selected_school_id');
        }

        return $user?->school_id;
    }

    private function filiairesForSchool(?int $schoolId)
    {
        return Filiaire::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function academicLevelsForSchool(?int $schoolId)
    {
        return AcademicLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function filiaireBelongsToSchool(int $filiaireId, int $schoolId): bool
    {
        return Filiaire::query()
            ->whereKey($filiaireId)
            ->where('school_id', $schoolId)
            ->exists();
    }
}
