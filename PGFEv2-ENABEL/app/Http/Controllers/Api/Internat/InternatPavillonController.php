<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internat;

use App\Http\Controllers\Controller;
use App\Models\InternatPavillon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InternatPavillonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InternatPavillon::query()->withCount('chambres')->orderBy('name');

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $pavillons = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Liste des pavillons récupérée avec succès.',
            'data' => $pavillons,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:mixte,M,F',
            'notes' => 'nullable|string',
        ]);

        $pavillon = InternatPavillon::create([
            ...$validated,
            'gender' => $validated['gender'] ?? 'mixte',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pavillon créé avec succès.',
            'data' => $pavillon,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $pavillon = InternatPavillon::with(['chambres.lits'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Pavillon récupéré avec succès.',
            'data' => $pavillon,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $pavillon = InternatPavillon::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|string|in:mixte,M,F',
            'notes' => 'nullable|string',
        ]);

        $pavillon->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Pavillon mis à jour avec succès.',
            'data' => $pavillon->fresh(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $pavillon = InternatPavillon::findOrFail($id);
        $pavillon->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pavillon supprimé avec succès.',
        ]);
    }
}
