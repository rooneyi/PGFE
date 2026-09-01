<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\School;
use App\Services\Academic\EducationStructureBootstrapper;
use App\Support\Academic\EducationTracks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class CycleSeeder extends Seeder
{
    public function run(): void
    {
        $filieres = ['Eletrinoique', 'Mecanique', 'Imprimeri', 'Pedagogie'];
        $hasSchoolOnClassrooms = Schema::hasColumn('classrooms', 'school_id');
        $bootstrapper = app(EducationStructureBootstrapper::class);

        School::query()->each(function (School $school) use ($filieres, $hasSchoolOnClassrooms, $bootstrapper): void {
            $bootstrapper->install($school, EducationTracks::keys(), true);

            foreach ($filieres as $filName) {
                $filiaire = $school->filiaires()->firstOrCreate([
                    'name' => $filName,
                    'school_id' => $school->id,
                ], [
                    'code' => mb_substr($filName, 0, 3).'-'.$school->id,
                    'uuid' => (string) Str::uuid(),
                ]);

                $bootstrapper->attachDefaultSecondaryCycles($filiaire);
                $filiaire->load('cycles.academicLevels');

                foreach ($filiaire->cycles as $cycle) {
                    foreach ($cycle->academicLevels as $level) {
                        $attributes = [
                            'name' => $level->name.' - '.$cycle->name.' - '.$filName,
                            'academic_level_id' => $level->id,
                            'filiaire_id' => $filiaire->id,
                        ];
                        if ($hasSchoolOnClassrooms) {
                            $attributes['school_id'] = $school->id;
                        }
                        Classroom::query()->firstOrCreate($attributes);
                    }
                }
            }
        });
    }
}
