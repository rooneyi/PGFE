<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Schools;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Filiaire;
use App\Models\School;
use App\Services\Academic\EducationStructureBootstrapper;
use App\Support\Academic\EducationTracks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class EducationTracksController extends Controller
{
    public function show(Request $request, EducationStructureBootstrapper $bootstrapper): JsonResponse
    {
        $school = $this->resolveSchool($request);
        if (! $school) {
            return response()->json([
                'message' => 'Aucune école rattachée à cet utilisateur.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'tracks' => $bootstrapper->status($school),
                'secondary_levels' => EducationTracks::secondaryLevels(),
                'sections' => Filiaire::query()->where('school_id', $school->id)->orderBy('name')->get(),
                'cycles' => Cycle::query()->where('school_id', $school->id)->with(['filiaire', 'academicLevels'])->orderBy('name')->get(),
            ],
            'message' => 'Structure scolaire récupérée.',
        ]);
    }

    public function store(Request $request, EducationStructureBootstrapper $bootstrapper): JsonResponse
    {
        $school = $this->resolveSchool($request);
        if (! $school) {
            return response()->json([
                'message' => 'Aucune école rattachée à cet utilisateur.',
            ], 422);
        }

        $validated = $request->validate([
            'tracks' => ['required', 'array', 'min:1'],
            'tracks.*' => ['string', Rule::in(EducationTracks::keys())],
            'trim_secondary' => ['sometimes', 'boolean'],
        ]);

        $result = $bootstrapper->install(
            $school,
            $validated['tracks'],
            $request->boolean('trim_secondary', true),
        );

        return response()->json([
            'data' => [
                ...$result,
                'tracks' => $bootstrapper->status($school),
                'sections' => Filiaire::query()->where('school_id', $school->id)->orderBy('name')->get(),
                'cycles' => Cycle::query()->where('school_id', $school->id)->with(['filiaire', 'academicLevels'])->orderBy('name')->get(),
            ],
            'message' => 'Structure scolaire installée (maternelle, primaire, 7e-8e). Les cycles secondaires vont de la 1ère à la 4ème.',
        ]);
    }

    private function resolveSchool(Request $request): ?School
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        if ($user->school_id) {
            return School::query()->find($user->school_id);
        }

        return School::query()->first();
    }
}
