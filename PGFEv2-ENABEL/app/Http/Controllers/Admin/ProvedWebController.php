<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvedRequest;
use App\Models\Proved;
use App\Models\Province;
use Illuminate\Http\Request;

final class ProvedWebController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Proved::class);

        $user = $request->user();
        $query = Proved::query()->with('province:id,name')->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $query->whereKey($user->proved_id);
        }

        $proveds = $query->paginate(20);

        return view('backend.pages.proveds.index', compact('proveds'));
    }

    public function create()
    {
        $this->authorize('create', Proved::class);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.proveds.create', compact('provinces'));
    }

    public function store(ProvedRequest $request)
    {
        $this->authorize('create', Proved::class);
        Proved::create($request->validated());

        return redirect()->route('admin.proveds.index')->with('success', 'Proved créé.');
    }

    public function edit(Proved $proved)
    {
        $this->authorize('update', $proved);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.proveds.edit', compact('proved', 'provinces'));
    }

    public function update(ProvedRequest $request, Proved $proved)
    {
        $this->authorize('update', $proved);
        $proved->update($request->validated());

        return redirect()->route('admin.proveds.index')->with('success', 'Proved mis à jour.');
    }

    public function destroy(Proved $proved)
    {
        $this->authorize('delete', $proved);
        $proved->delete();

        return redirect()->route('admin.proveds.index')->with('success', 'Proved supprimé.');
    }
}
