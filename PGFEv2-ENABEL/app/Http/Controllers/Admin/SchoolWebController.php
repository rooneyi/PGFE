<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolRequest;
use App\Models\School;
use App\Services\Organization\SchoolScopeResolver;
use Illuminate\Http\Request;

final class SchoolWebController extends Controller
{
    public function index(Request $request, SchoolScopeResolver $resolver)
    {
        $q = $request->input('q');
        $allowedIds = $resolver->allowedSchoolIds($request->user());

        $schools = School::query()
            ->with(['province:id,name', 'country:id,name', 'type:id,title', 'sousDivision:id,name,code'])
            ->when($allowedIds !== null, fn ($query) => $query->whereIn('id', $allowedIds))
            ->when($q, function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $statsQuery = School::query();
        if ($allowedIds !== null) {
            $statsQuery->whereIn('id', $allowedIds);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'provinces' => \App\Models\Province::has('schools')->count(),
        ];

        return view('backend.pages.schools.index', compact('schools', 'stats'));
    }

    public function create(Request $request)
    {
        $countries = \App\Models\Country::query()->orderBy('name')->get(['id', 'name']);
        $types = \App\Models\Type::query()->orderBy('title')->get(['id', 'title']);
        $school = new School;

        return view('backend.pages.schools.create', compact('countries', 'types', 'school'));
    }

    public function store(Request $request, SchoolScopeResolver $resolver)
    {
        $user = $request->user();

        if ($request->filled('phone_number')) {
            $request->merge([
                'phone_number' => SchoolRequest::normalizeDrcPhone((string) $request->input('phone_number')),
            ]);
        } else {
            $request->merge(['phone_number' => null]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:schools,name'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'type_id' => ['required', 'exists:types,id'],
            'phone_number' => ['nullable', 'string', 'regex:/^\+243[0-9]{9}$/', 'unique:schools,phone_number'],
            'email' => ['nullable', 'email', 'max:255', 'unique:schools,email'],
            'latitude' => ['nullable', 'string', 'max:100'],
            'longitude' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'file', 'image', 'max:1024'],
            'sous_division_id' => ['nullable', 'integer', 'exists:sous_divisions,id'],
        ];

        $data = $request->validate($rules, [
            'phone_number.regex' => 'Numéro invalide. Utilisez +243 suivi de 9 chiffres (ex. +243812345678) ou le format local 0XXXXXXXXX.',
            'phone_number.unique' => 'Ce numéro de téléphone existe déjà',
        ]);
        $data = $resolver->applySousDivisionToSchoolData($data, $user);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('schools/logos', 'public');
        }

        School::create($data);

        return redirect()->route('admin.schools.index')
            ->with('success', 'École créée avec succès.');
    }

    public function edit(Request $request, School $school, SchoolScopeResolver $resolver)
    {
        abort_unless($resolver->canAccessSchool($school->id, $request->user()), 403);

        $countries = \App\Models\Country::query()->orderBy('name')->get(['id', 'name']);
        $types = \App\Models\Type::query()->orderBy('title')->get(['id', 'title']);

        return view('backend.pages.schools.edit', compact('school', 'countries', 'types'));
    }

    public function update(Request $request, School $school, SchoolScopeResolver $resolver)
    {
        abort_unless($resolver->canAccessSchool($school->id, $request->user()), 403);

        $user = $request->user();

        if ($request->filled('phone_number')) {
            $request->merge([
                'phone_number' => SchoolRequest::normalizeDrcPhone((string) $request->input('phone_number')),
            ]);
        } else {
            $request->merge(['phone_number' => null]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:schools,name,'.$school->id],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'type_id' => ['required', 'exists:types,id'],
            'phone_number' => ['nullable', 'string', 'regex:/^\+243[0-9]{9}$/', 'unique:schools,phone_number,'.$school->id],
            'email' => ['nullable', 'email', 'max:255', 'unique:schools,email,'.$school->id],
            'latitude' => ['nullable', 'string', 'max:100'],
            'longitude' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'file', 'image', 'max:1024'],
            'sous_division_id' => ['nullable', 'integer', 'exists:sous_divisions,id'],
        ];

        $data = $request->validate($rules, [
            'phone_number.regex' => 'Numéro invalide. Utilisez +243 suivi de 9 chiffres (ex. +243812345678) ou le format local 0XXXXXXXXX.',
            'phone_number.unique' => 'Ce numéro de téléphone existe déjà',
        ]);
        $data = $resolver->applySousDivisionToSchoolData($data, $user);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('schools/logos', 'public');
        }
        $school->update($data);

        return redirect()->route('admin.schools.index')->with('success', 'École mise à jour.');
    }

    public function destroy(School $school)
    {
        $school->delete();

        return redirect()->route('admin.schools.index')->with('success', 'École supprimée.');
    }
        /**
     * Change le contexte d'école (global ou spécifique)
     */
    public function switchSchool($id, SchoolScopeResolver $resolver)
    {
        if ($id === 'all') {
            session()->forget(['selected_school_id', 'selected_school_name']);
        } else {
            $school = School::query()->findOrFail((int) $id);
            abort_unless($resolver->canAccessSchool($school->id), 403);
            session(['selected_school_id' => $school->id, 'selected_school_name' => $school->name]);
        }

        return back();
    }
}
