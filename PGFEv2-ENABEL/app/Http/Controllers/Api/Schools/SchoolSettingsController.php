<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Schools;

use App\Enums\SchoolTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolSettingsRequest;
use App\Models\Cycle;
use App\Models\Filiaire;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Type;
use App\Services\Academic\EducationStructureBootstrapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SchoolSettingsController extends Controller
{
    public function show(Request $request, EducationStructureBootstrapper $bootstrapper): JsonResponse
    {
        $school = $this->resolveSchool($request);
        if (! $school) {
            return response()->json([
                'message' => 'Aucune école rattachée à cet utilisateur.',
            ], 422);
        }

        $year = SchoolYear::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'data' => [
                'school' => $school->load('type'),
                'year' => $year,
                'sections' => Filiaire::query()->where('school_id', $school->id)->orderBy('name')->get(),
                'cycles' => Cycle::query()->where('school_id', $school->id)->with('filiaire')->orderBy('name')->get(),
                'education_tracks' => $bootstrapper->status($school),
                'type' => SchoolTypeEnum::FORMEL->value,
            ],
            'message' => 'Paramètres de l’école récupérés.',
        ]);
    }

    public function update(SchoolSettingsRequest $request): JsonResponse
    {
        $school = $this->resolveSchool($request);
        if (! $school) {
            return response()->json([
                'message' => 'Aucune école rattachée à cet utilisateur.',
            ], 422);
        }

        $formel = Type::query()->firstOrCreate(['title' => SchoolTypeEnum::FORMEL->value]);

        $school->update([
            ...$request->validated(),
            'type_id' => $formel->id,
        ]);

        $year = SchoolYear::query()->updateOrCreate(
            ['school_id' => $school->id, 'name' => '2026-2027'],
            [
                'is_active' => true,
                'description' => 'Année scolaire 2026-2027',
            ]
        );

        return response()->json([
            'data' => [
                'school' => $school->fresh('type'),
                'year' => $year,
                'type' => SchoolTypeEnum::FORMEL->value,
            ],
            'message' => 'Paramètres de l’école enregistrés.',
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
