<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Filiaires;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFiliaireRequest;
use App\Models\Filiaire;
use App\Services\Academic\EducationStructureBootstrapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class StoreFiliaireController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(StoreFiliaireRequest $request, EducationStructureBootstrapper $bootstrapper): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user?->school_id;

        if (! $schoolId) {
            return response()->json([
                'message' => "Impossible de déterminer l'école de l'utilisateur connecté.",
                'success' => false,
            ], 422);
        }

        $filiaire = DB::transaction(function () use ($request, $schoolId, $bootstrapper) {
            $filiaire = Filiaire::create([
                'school_id' => $schoolId,
                'name' => $request->name,
                'code' => $request->code,
            ]);

            if ($request->boolean('with_default_cycles', true)) {
                $bootstrapper->attachDefaultSecondaryCycles($filiaire);
            }

            return $filiaire;
        });

        return response()->json([
            'data' => $filiaire->load('cycles.academicLevels'),
            'message' => 'Filière créée avec succès',
        ], 201);
    }
}
