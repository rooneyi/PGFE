<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminSchoolContext;
use App\Http\Controllers\Controller;
use App\Models\AcademicPersonal;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

final class PersonnelWebController extends Controller
{
    use ResolvesAdminSchoolContext;

    public function index(Request $request)
    {
        $selectedSchoolId = $this->activeSchoolId($request);

        $query = AcademicPersonal::query()
            ->with(['school:id,name', 'fonction:id,title'])
            ->latest('id');

        if ($selectedSchoolId) {
            $query->where('school_id', $selectedSchoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', (int) $request->integer('school_id'));
        }

        if ($request->filled('search')) {
            $search = mb_trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('pre_name', 'like', "%{$search}%")
                    ->orWhere('post_name', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $personnels = $query->paginate(20)->appends($request->query());
        $schools = School::orderBy('name')->get(['id', 'name']);

        $statsQuery = AcademicPersonal::query();
        if ($selectedSchoolId) {
            $statsQuery->where('school_id', $selectedSchoolId);
        } elseif ($request->filled('school_id')) {
            $statsQuery->where('school_id', (int) $request->integer('school_id'));
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'with_account' => (clone $statsQuery)->whereNotNull('user_id')->count(),
        ];

        return view('backend.pages.personnels.index', compact('personnels', 'schools', 'stats', 'selectedSchoolId'));
    }

    public function edit(AcademicPersonal $personnel)
    {
        $schools = School::orderBy('name')->get(['id', 'name']);

        return view('backend.pages.personnels.edit', [
            'personnel' => $personnel->load('user:id,email'),
            'schools' => $schools,
        ]);
    }

    public function update(Request $request, AcademicPersonal $personnel)
    {
        $linkedUser = $personnel->user;

        $data = $request->validate([
            'pre_name' => ['nullable', 'string', 'max:255'],
            'post_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($linkedUser?->id),
            ],
            'school_id' => ['nullable', 'exists:schools,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $personnel->update(array_filter([
            'pre_name' => $data['pre_name'] ?? null,
            'post_name' => $data['post_name'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'school_id' => $data['school_id'] ?? null,
        ], fn ($v) => $v !== null));

        if (! empty($data['password'])) {
            if (! $linkedUser) {
                return back()
                    ->withErrors(['password' => "Ce personnel n'a pas de compte utilisateur lié."])
                    ->withInput();
            }

            $linkedUser->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        if (! empty($data['email']) && $linkedUser) {
            $linkedUser->update([
                'email' => $data['email'],
            ]);
        }

        return redirect()->route('admin.personnels.index')->with('success', 'Personnel mis à jour.');
    }

    public function destroy(AcademicPersonal $personnel)
    {
        $personnel->delete();

        return redirect()->route('admin.personnels.index')->with('success', 'Personnel supprimé.');
    }
}
