<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPersonal;
use App\Models\Classroom;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Type;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class RegistrationWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = Registration::query()
            ->with([
                'student:id,firstname,lastname,name,matricule,gender,birth_date',
                'classroom:id,name',
                'school:id,name',
                'schoolYear:id,name',
            ])
            ->latest('id');

        if ($request->filled('search')) {
            $search = mb_strtolower(mb_trim((string) $request->input('search')));
            $query->whereHas('student', function ($q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$search}%"]);
            });
        }

        $registrations = $query->paginate(20)->withQueryString();

        return view('backend.pages.registrations.index', compact('registrations'));
    }

    public function create(): View
    {
        return view('backend.pages.registrations.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'academic_personal_id' => ['required', 'exists:academic_personals,id'],
            'registration_date' => ['required', 'date'],
            'registration_status' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);

        $context = $this->resolveFromClassroom((int) $data['classroom_id']);
        $schoolId = (int) $context['school_id'];

        $schoolYearId = $data['school_year_id'] ?? SchoolYear::active($schoolId)?->id;
        if (! $schoolYearId) {
            throw ValidationException::withMessages([
                'school_year_id' => 'Aucune année scolaire active pour cette école. Sélectionnez-en une.',
            ]);
        }

        $duplicate = Registration::query()
            ->where('student_id', $data['student_id'])
            ->where('school_year_id', $schoolYearId)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'student_id' => 'Cet élève est déjà inscrit pour cette année scolaire.',
            ]);
        }

        Registration::create([
            ...$context,
            'student_id' => $data['student_id'],
            'classroom_id' => $data['classroom_id'],
            'school_year_id' => $schoolYearId,
            'academic_personal_id' => $data['academic_personal_id'],
            'registration_date' => $data['registration_date'],
            'registration_status' => (bool) ($data['registration_status'] ?? true),
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('admin.registrations.index')
            ->with('success', 'Inscription enregistrée.');
    }

    public function edit(Registration $registration): View
    {
        $registration->load(['student', 'classroom', 'schoolYear']);

        return view('backend.pages.registrations.edit', [
            'registration' => $registration,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Registration $registration): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'academic_personal_id' => ['required', 'exists:academic_personals,id'],
            'registration_date' => ['required', 'date'],
            'registration_status' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);

        $context = $this->resolveFromClassroom((int) $data['classroom_id']);

        $duplicate = Registration::query()
            ->where('student_id', $data['student_id'])
            ->where('school_year_id', $data['school_year_id'])
            ->where('id', '!=', $registration->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'student_id' => 'Cet élève est déjà inscrit pour cette année scolaire.',
            ]);
        }

        $registration->update([
            ...$context,
            'student_id' => $data['student_id'],
            'classroom_id' => $data['classroom_id'],
            'school_year_id' => $data['school_year_id'],
            'academic_personal_id' => $data['academic_personal_id'],
            'registration_date' => $data['registration_date'],
            'registration_status' => (bool) ($data['registration_status'] ?? false),
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('admin.registrations.index')
            ->with('success', 'Inscription mise à jour.');
    }

    public function destroy(Registration $registration): RedirectResponse
    {
        $registration->delete();

        return redirect()
            ->route('admin.registrations.index')
            ->with('success', 'Inscription supprimée.');
    }

    /**
     * @return array{students: \Illuminate\Support\Collection, classrooms: \Illuminate\Support\Collection, schoolYears: \Illuminate\Support\Collection, personnels: \Illuminate\Support\Collection, types: \Illuminate\Support\Collection}
     */
    private function formData(): array
    {
        return [
            'students' => Student::query()->orderBy('lastname')->orderBy('name')->limit(500)->get(['id', 'firstname', 'lastname', 'name', 'matricule']),
            'classrooms' => Classroom::query()->with('academicLevel.cycle.filiaire')->orderBy('name')->get(['id', 'name', 'school_id', 'filiaire_id', 'academic_level_id']),
            'schoolYears' => SchoolYear::query()->orderByDesc('id')->get(['id', 'name', 'school_id']),
            'personnels' => AcademicPersonal::query()
                ->orderBy('post_name')
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'pre_name', 'post_name', 'matricule']),
            'types' => Type::query()->orderBy('title')->get(['id', 'title']),
        ];
    }

    /**
     * Déduit école, filière, cycle et niveau à partir de la classe.
     *
     * @return array{school_id: int, filiaire_id: int, cycle_id: int, academic_level_id: int}
     */
    private function resolveFromClassroom(int $classroomId): array
    {
        $classroom = Classroom::query()
            ->with(['academicLevel.cycle.filiaire', 'filiaire'])
            ->find($classroomId);

        if (! $classroom) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Classe introuvable.',
            ]);
        }

        $schoolId = $classroom->school_id ?? $classroom->filiaire?->school_id;
        $filiaireId = $classroom->filiaire_id ?? $classroom->academicLevel?->cycle?->filiaire_id;
        $cycleId = $classroom->academicLevel?->cycle_id;
        $academicLevelId = $classroom->academic_level_id;

        if (! $schoolId || ! $filiaireId || ! $cycleId || ! $academicLevelId) {
            throw ValidationException::withMessages([
                'classroom_id' => 'La classe sélectionnée est incomplète (école, filière, cycle ou niveau manquant). Complétez la fiche classe.',
            ]);
        }

        return [
            'school_id' => (int) $schoolId,
            'filiaire_id' => (int) $filiaireId,
            'cycle_id' => (int) $cycleId,
            'academic_level_id' => (int) $academicLevelId,
        ];
    }
}
