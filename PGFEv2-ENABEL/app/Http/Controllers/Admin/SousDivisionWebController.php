<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SousDivisionRequest;
use App\Models\Proved;
use App\Models\SousDivision;
use App\Services\Organization\SchoolScopeResolver;
use Illuminate\Http\Request;

final class SousDivisionWebController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SousDivision::class);

        $user = $request->user();
        $query = SousDivision::query()
            ->with('proved:id,name,code')
            ->withCount('schools')
            ->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $query->where('proved_id', $user->proved_id);
        } elseif ($user->hasRole('admin-sous-division') && $user->sous_division_id) {
            $query->whereKey($user->sous_division_id);
        }

        if ($request->filled('proved_id')) {
            $query->where('proved_id', (int) $request->input('proved_id'));
        }

        $sousDivisions = $query->paginate(20)->appends($request->query());
        $proveds = $user->hasRole('super-admin')
            ? Proved::query()->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        return view('backend.pages.sous-divisions.index', compact('sousDivisions', 'proveds'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', SousDivision::class);

        $user = $request->user();
        $proveds = Proved::query()->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $proveds->whereKey($user->proved_id);
        }

        $proveds = $proveds->get(['id', 'name', 'code']);

        return view('backend.pages.sous-divisions.create', compact('proveds'));
    }

    public function store(SousDivisionRequest $request)
    {
        $this->authorize('create', SousDivision::class);

        $data = $request->validated();
        $user = $request->user();

        if ($user->hasRole('admin-proved')) {
            $data['proved_id'] = $user->proved_id;
        }

        SousDivision::create($data);

        return redirect()->route('admin.sous-divisions.index')->with('success', 'Sous-division créée.');
    }

    public function edit(SousDivision $sousDivision)
    {
        $this->authorize('update', $sousDivision);

        $user = request()->user();
        $proveds = Proved::query()->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $proveds->whereKey($user->proved_id);
        }

        $proveds = $proveds->get(['id', 'name', 'code']);

        return view('backend.pages.sous-divisions.edit', compact('sousDivision', 'proveds'));
    }

    public function update(SousDivisionRequest $request, SousDivision $sousDivision)
    {
        $this->authorize('update', $sousDivision);

        $data = $request->validated();
        $user = $request->user();

        if ($user->hasRole('admin-proved')) {
            $data['proved_id'] = $user->proved_id;
        }

        $sousDivision->update($data);

        return redirect()->route('admin.sous-divisions.index')->with('success', 'Sous-division mise à jour.');
    }

    public function destroy(SousDivision $sousDivision)
    {
        $this->authorize('delete', $sousDivision);
        $sousDivision->delete();

        return redirect()->route('admin.sous-divisions.index')->with('success', 'Sous-division supprimée.');
    }

    public function switchSousDivision(string $id, SchoolScopeResolver $resolver)
    {
        $user = request()->user();
        if (! $user || ! $user->hasRole('admin-proved')) {
            abort(403);
        }

        if ($id === 'all') {
            session()->forget(['selected_sous_division_id', 'selected_sous_division_name']);

            return back();
        }

        $sousDivision = SousDivision::query()->findOrFail((int) $id);

        if (! $resolver->canAccessSousDivision($sousDivision->id, $user)) {
            abort(403);
        }

        session([
            'selected_sous_division_id' => $sousDivision->id,
            'selected_sous_division_name' => $sousDivision->name,
        ]);

        return back();
    }
}
