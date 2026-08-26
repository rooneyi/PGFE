<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Filiaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CycleWebController extends Controller
{
    public function index(): View
    {
        $cycles = Cycle::query()
            ->with('filiaire:id,name')
            ->latest('id')
            ->paginate(20);

        return view('backend.pages.cycles.index', compact('cycles'));
    }

    public function create(): View
    {
        $filiaires = Filiaire::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.cycles.create', compact('filiaires'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'filiaire_id' => ['required', 'exists:filiaires,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $filiaire = Filiaire::query()->findOrFail($data['filiaire_id']);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cycles', 'name')
                    ->where(fn ($q) => $q->where('filiaire_id', $filiaire->id)->where('school_id', $filiaire->school_id)),
            ],
        ]);

        Cycle::query()->create([
            'filiaire_id' => $filiaire->id,
            'school_id' => $filiaire->school_id,
            'name' => $data['name'],
        ]);

        return redirect()->route('admin.cycles.index')->with('success', 'Cycle créé.');
    }

    public function edit(Cycle $cycle): View
    {
        $filiaires = Filiaire::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.cycles.edit', compact('cycle', 'filiaires'));
    }

    public function update(Request $request, Cycle $cycle): RedirectResponse
    {
        $data = $request->validate([
            'filiaire_id' => ['required', 'exists:filiaires,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $filiaire = Filiaire::query()->findOrFail($data['filiaire_id']);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cycles', 'name')
                    ->where(fn ($q) => $q->where('filiaire_id', $filiaire->id)->where('school_id', $filiaire->school_id))
                    ->ignore($cycle->id),
            ],
        ]);

        $cycle->update([
            'filiaire_id' => $filiaire->id,
            'school_id' => $filiaire->school_id,
            'name' => $data['name'],
        ]);

        return redirect()->route('admin.cycles.index')->with('success', 'Cycle mis à jour.');
    }

    public function destroy(Cycle $cycle): RedirectResponse
    {
        $cycle->delete();

        return redirect()->route('admin.cycles.index')->with('success', 'Cycle supprimé.');
    }
}

