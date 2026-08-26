<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\SousDivisionRequest;
use App\Models\School;
use App\Models\SousDivision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SousDivisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SousDivision::class);

        $user = $request->user();
        $query = SousDivision::query()->with('proved:id,name,code')->withCount('schools')->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $query->where('proved_id', $user->proved_id);
        } elseif ($user->hasRole('admin-sous-division') && $user->sous_division_id) {
            $query->whereKey($user->sous_division_id);
        }

        if ($request->filled('proved_id')) {
            $query->where('proved_id', (int) $request->input('proved_id'));
        }

        return response()->json([
            'data' => $query->get(),
            'message' => 'Liste des sous-divisions récupérée avec succès',
        ]);
    }

    public function store(SousDivisionRequest $request): JsonResponse
    {
        $this->authorize('create', SousDivision::class);

        $data = $request->validated();
        if ($request->user()->hasRole('admin-proved')) {
            $data['proved_id'] = $request->user()->proved_id;
        }

        $sousDivision = SousDivision::create($data);

        return response()->json([
            'data' => $sousDivision->load('proved'),
            'message' => 'Sous-division créée avec succès',
        ], 201);
    }

    public function show(SousDivision $sousDivision): JsonResponse
    {
        $this->authorize('view', $sousDivision);

        return response()->json([
            'data' => $sousDivision->load(['proved', 'schools']),
            'message' => 'Sous-division récupérée avec succès',
        ]);
    }

    public function update(SousDivisionRequest $request, SousDivision $sousDivision): JsonResponse
    {
        $this->authorize('update', $sousDivision);

        $data = $request->validated();
        if ($request->user()->hasRole('admin-proved')) {
            $data['proved_id'] = $request->user()->proved_id;
        }

        $sousDivision->update($data);

        return response()->json([
            'data' => $sousDivision->fresh('proved'),
            'message' => 'Sous-division mise à jour avec succès',
        ]);
    }

    public function destroy(SousDivision $sousDivision): JsonResponse
    {
        $this->authorize('delete', $sousDivision);
        $sousDivision->delete();

        return response()->json([
            'message' => 'Sous-division supprimée avec succès',
            'success' => true,
        ]);
    }

    public function schools(SousDivision $sousDivision): JsonResponse
    {
        $this->authorize('view', $sousDivision);

        return response()->json([
            'data' => School::query()
                ->where('sous_division_id', $sousDivision->id)
                ->orderBy('name')
                ->get(['id', 'name', 'city', 'sous_division_id']),
            'message' => 'Écoles de la sous-division récupérées avec succès',
        ]);
    }
}
