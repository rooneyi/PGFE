<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internat;

use App\Http\Controllers\Controller;
use App\Models\InternatChambre;
use App\Models\InternatLit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InternatLitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InternatLit::query()
            ->with(['chambre:id,name,pavillon_id', 'chambre.pavillon:id,name'])
            ->orderBy('code');

        if ($chambreId = $request->input('chambre_id')) {
            $query->where('chambre_id', $chambreId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where('code', 'like', "%{$search}%");
        }

        $lits = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Liste des lits récupérée avec succès.',
            'data' => $lits,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chambre_id' => 'required|exists:internat_chambres,id',
            'code' => 'required|string|max:100',
            'status' => 'nullable|string|in:libre,occupe,hors_service',
        ]);

        InternatChambre::findOrFail($validated['chambre_id']);

        $exists = InternatLit::where('chambre_id', $validated['chambre_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Un lit avec ce code existe déjà dans cette chambre.',
            ], 422);
        }

        $lit = InternatLit::create([
            ...$validated,
            'status' => $validated['status'] ?? InternatLit::STATUS_LIBRE,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Lit créé avec succès.',
            'data' => $lit->load(['chambre:id,name,pavillon_id', 'chambre.pavillon:id,name']),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $lit = InternatLit::with(['chambre.pavillon', 'affectations' => function ($q) {
            $q->where('statut', 'active')->with('student:id,firstname,lastname,matricule');
        }])->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Lit récupéré avec succès.',
            'data' => $lit,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $lit = InternatLit::findOrFail($id);

        $validated = $request->validate([
            'chambre_id' => 'sometimes|exists:internat_chambres,id',
            'code' => 'sometimes|string|max:100',
            'status' => 'sometimes|string|in:libre,occupe,hors_service',
        ]);

        if (isset($validated['chambre_id'])) {
            InternatChambre::findOrFail($validated['chambre_id']);
        }

        if (isset($validated['code']) || isset($validated['chambre_id'])) {
            $chambreId = $validated['chambre_id'] ?? $lit->chambre_id;
            $code = $validated['code'] ?? $lit->code;
            $duplicate = InternatLit::where('chambre_id', $chambreId)
                ->where('code', $code)
                ->where('id', '!=', $lit->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'status' => false,
                    'message' => 'Un lit avec ce code existe déjà dans cette chambre.',
                ], 422);
            }
        }

        $lit->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Lit mis à jour avec succès.',
            'data' => $lit->fresh()->load(['chambre:id,name,pavillon_id', 'chambre.pavillon:id,name']),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $lit = InternatLit::findOrFail($id);

        if ($lit->status === InternatLit::STATUS_OCCUPE) {
            return response()->json([
                'status' => false,
                'message' => 'Impossible de supprimer un lit occupé. Libérez d\'abord l\'affectation.',
            ], 422);
        }

        $lit->delete();

        return response()->json([
            'status' => true,
            'message' => 'Lit supprimé avec succès.',
        ]);
    }
}
