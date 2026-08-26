<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internat;

use App\Http\Controllers\Controller;
use App\Models\InternatAffectation;
use App\Models\InternatLit;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class InternatAffectationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InternatAffectation::query()
            ->with([
                'student:id,firstname,lastname,matricule',
                'lit:id,code,chambre_id,status',
                'lit.chambre:id,name,pavillon_id',
                'lit.chambre.pavillon:id,name',
                'schoolYear:id,name',
            ])
            ->orderByDesc('date_entree');

        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        if ($schoolYearId = $request->input('school_year_id')) {
            $query->where('school_year_id', $schoolYearId);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $affectations = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Liste des affectations récupérée avec succès.',
            'data' => $affectations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'lit_id' => 'required|exists:internat_lits,id',
            'school_year_id' => 'required|exists:school_years,id',
            'date_entree' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $lit = InternatLit::findOrFail($validated['lit_id']);

        if ($lit->status !== InternatLit::STATUS_LIBRE) {
            return response()->json([
                'status' => false,
                'message' => 'Ce lit n\'est pas disponible (statut: '.$lit->status.').',
            ], 422);
        }

        $activeForStudent = InternatAffectation::query()
            ->where('student_id', $student->id)
            ->where('statut', InternatAffectation::STATUT_ACTIVE)
            ->where('school_year_id', $validated['school_year_id'])
            ->exists();

        if ($activeForStudent) {
            return response()->json([
                'status' => false,
                'message' => 'Cet élève a déjà une affectation active pour cette année scolaire.',
            ], 422);
        }

        $activeForLit = InternatAffectation::query()
            ->where('lit_id', $lit->id)
            ->where('statut', InternatAffectation::STATUT_ACTIVE)
            ->exists();

        if ($activeForLit) {
            return response()->json([
                'status' => false,
                'message' => 'Ce lit a déjà une affectation active.',
            ], 422);
        }

        $affectation = DB::transaction(function () use ($validated, $lit) {
            $affectation = InternatAffectation::create([
                'student_id' => $validated['student_id'],
                'lit_id' => $validated['lit_id'],
                'school_year_id' => $validated['school_year_id'],
                'date_entree' => $validated['date_entree'],
                'notes' => $validated['notes'] ?? null,
                'statut' => InternatAffectation::STATUT_ACTIVE,
            ]);

            $lit->update(['status' => InternatLit::STATUS_OCCUPE]);

            return $affectation;
        });

        return response()->json([
            'status' => true,
            'message' => 'Affectation créée avec succès.',
            'data' => $affectation->load([
                'student:id,firstname,lastname,matricule',
                'lit:id,code,chambre_id,status',
                'lit.chambre:id,name,pavillon_id',
                'lit.chambre.pavillon:id,name',
                'schoolYear:id,name',
            ]),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $affectation = InternatAffectation::with([
            'student',
            'lit.chambre.pavillon',
            'schoolYear',
        ])->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Affectation récupérée avec succès.',
            'data' => $affectation,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $affectation = InternatAffectation::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'date_entree' => 'sometimes|date',
        ]);

        $affectation->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Affectation mise à jour avec succès.',
            'data' => $affectation->fresh()->load([
                'student:id,firstname,lastname,matricule',
                'lit:id,code,chambre_id,status',
                'lit.chambre:id,name,pavillon_id',
                'lit.chambre.pavillon:id,name',
            ]),
        ]);
    }

    public function checkout(Request $request, string $id): JsonResponse
    {
        $affectation = InternatAffectation::findOrFail($id);

        if ($affectation->statut !== InternatAffectation::STATUT_ACTIVE) {
            return response()->json([
                'status' => false,
                'message' => 'Cette affectation n\'est plus active.',
            ], 422);
        }

        $validated = $request->validate([
            'date_sortie' => 'nullable|date',
        ]);

        DB::transaction(function () use ($affectation, $validated) {
            $affectation->update([
                'statut' => InternatAffectation::STATUT_TERMINEE,
                'date_sortie' => $validated['date_sortie'] ?? now()->toDateString(),
            ]);

            $lit = InternatLit::find($affectation->lit_id);
            if ($lit && $lit->status === InternatLit::STATUS_OCCUPE) {
                $lit->update(['status' => InternatLit::STATUS_LIBRE]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Élève libéré du lit avec succès.',
            'data' => $affectation->fresh()->load([
                'student:id,firstname,lastname,matricule',
                'lit:id,code,chambre_id,status',
            ]),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $affectation = InternatAffectation::findOrFail($id);

        DB::transaction(function () use ($affectation) {
            if ($affectation->statut === InternatAffectation::STATUT_ACTIVE) {
                $lit = InternatLit::find($affectation->lit_id);
                if ($lit && $lit->status === InternatLit::STATUS_OCCUPE) {
                    $lit->update(['status' => InternatLit::STATUS_LIBRE]);
                }
            }
            $affectation->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Affectation supprimée avec succès.',
        ]);
    }
}
