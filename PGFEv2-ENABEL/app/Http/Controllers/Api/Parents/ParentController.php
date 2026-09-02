<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parents;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentRequest;
use App\Models\Parents;
use Illuminate\Http\JsonResponse;

final class ParentController extends Controller
{
    public function index(): JsonResponse
    {
        $parents = Parents::latest()->with('user:id,email')->get();

        return response()->json([
            'data' => $parents,
            'message' => 'Liste des parents récupérée avec succès',
            'success' => true,
            'count' => $parents->count(),
        ]);
    }

    public function show(Parents $parent): JsonResponse
    {
        $parent->load(['students']);

        return response()->json([
            'data' => $parent,
            'message' => 'Parent récupéré avec succès',
            'success' => true,
        ]);
    }

    public function store(ParentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $schoolId = $request->user()?->school_id;
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $existing = $this->findParentByPhone($data['phone_number'], $schoolId);

        if ($existing) {
            if ($schoolId && ! $existing->school_id) {
                $existing->update(['school_id' => $schoolId]);
            }

            return response()->json([
                'data' => $existing->fresh(),
                'message' => 'Ce parent existe déjà avec ce numéro. Il est réutilisé pour l’inscription.',
                'reused' => true,
            ]);
        }

        $parent = Parents::create($data);

        return response()->json([
            'data' => $parent,
            'message' => 'Parent créé avec succès',
            'reused' => false,
        ], 201);
    }

    public function update(ParentRequest $request, Parents $parent): JsonResponse
    {
        $parent->update($request->validated());

        return response()->json([
            'data' => $parent->fresh(),
            'message' => 'Parent mis à jour avec succès',
        ]);
    }

    public function destroy(Parents $parent): JsonResponse
    {
        $parent->delete();

        return response()->json([
            'message' => 'Parent supprimé avec succès',
        ]);
    }

    private function findParentByPhone(string $phoneNumber, ?int $schoolId): ?Parents
    {
        $query = Parents::query()->where('phone_number', $phoneNumber);

        if ($schoolId) {
            $query->where(function ($q) use ($schoolId): void {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        }

        return $query->first();
    }
}
