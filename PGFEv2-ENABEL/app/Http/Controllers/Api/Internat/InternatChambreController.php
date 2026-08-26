<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internat;

use App\Http\Controllers\Controller;
use App\Models\InternatChambre;
use App\Models\InternatPavillon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InternatChambreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InternatChambre::query()
            ->with(['pavillon:id,name'])
            ->withCount('lits')
            ->orderBy('name');

        if ($pavillonId = $request->input('pavillon_id')) {
            $query->where('pavillon_id', $pavillonId);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $chambres = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Liste des chambres récupérée avec succès.',
            'data' => $chambres,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pavillon_id' => 'required|exists:internat_pavillons,id',
            'name' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1|max:100',
            'gender' => 'nullable|string|in:mixte,M,F',
        ]);

        // Ensure pavillon is in school scope
        InternatPavillon::findOrFail($validated['pavillon_id']);

        $chambre = InternatChambre::create([
            ...$validated,
            'capacity' => $validated['capacity'] ?? 1,
            'gender' => $validated['gender'] ?? 'mixte',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Chambre créée avec succès.',
            'data' => $chambre->load('pavillon:id,name'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $chambre = InternatChambre::with(['pavillon', 'lits'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Chambre récupérée avec succès.',
            'data' => $chambre,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $chambre = InternatChambre::findOrFail($id);

        $validated = $request->validate([
            'pavillon_id' => 'sometimes|exists:internat_pavillons,id',
            'name' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1|max:100',
            'gender' => 'sometimes|string|in:mixte,M,F',
        ]);

        if (isset($validated['pavillon_id'])) {
            InternatPavillon::findOrFail($validated['pavillon_id']);
        }

        $chambre->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Chambre mise à jour avec succès.',
            'data' => $chambre->fresh()->load('pavillon:id,name'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $chambre = InternatChambre::findOrFail($id);
        $chambre->delete();

        return response()->json([
            'status' => true,
            'message' => 'Chambre supprimée avec succès.',
        ]);
    }
}
