<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvedRequest;
use App\Models\Proved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProvedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Proved::class);

        $user = $request->user();
        $query = Proved::query()->with('province:id,name')->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $query->whereKey($user->proved_id);
        }

        return response()->json([
            'data' => $query->get(),
            'message' => 'Liste des proved récupérée avec succès',
        ]);
    }

    public function store(ProvedRequest $request): JsonResponse
    {
        $this->authorize('create', Proved::class);
        $proved = Proved::create($request->validated());

        return response()->json([
            'data' => $proved->load('province'),
            'message' => 'Proved créé avec succès',
        ], 201);
    }

    public function show(Proved $proved): JsonResponse
    {
        $this->authorize('view', $proved);

        return response()->json([
            'data' => $proved->load(['province', 'sousDivisions']),
            'message' => 'Proved récupéré avec succès',
        ]);
    }

    public function update(ProvedRequest $request, Proved $proved): JsonResponse
    {
        $this->authorize('update', $proved);
        $proved->update($request->validated());

        return response()->json([
            'data' => $proved->fresh('province'),
            'message' => 'Proved mis à jour avec succès',
        ]);
    }

    public function destroy(Proved $proved): JsonResponse
    {
        $this->authorize('delete', $proved);
        $proved->delete();

        return response()->json([
            'message' => 'Proved supprimé avec succès',
            'success' => true,
        ]);
    }

    public function sousDivisions(Proved $proved): JsonResponse
    {
        $this->authorize('view', $proved);

        return response()->json([
            'data' => $proved->sousDivisions()->withCount('schools')->orderBy('name')->get(),
            'message' => 'Sous-divisions du proved récupérées avec succès',
        ]);
    }
}
