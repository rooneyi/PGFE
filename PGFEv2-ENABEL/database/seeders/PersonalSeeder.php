<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\Commune;
use App\Models\Country;
use App\Models\Fonction;
use App\Models\Mecanisation;
use App\Models\Personal;
use App\Models\Province;
use App\Models\School;
use App\Models\Territory;
use App\Models\Type;
use Illuminate\Database\Seeder;

final class PersonalSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()->get();
        if ($schools->isEmpty()) {
            $this->command?->warn('PersonalSeeder ignoré : aucune école en base (exécutez SchoolSeeder).');

            return;
        }

        $school = $schools->first();
        $country = Country::query()->find($school->country_id) ?? Country::query()->first();
        $province = Province::query()->where('country_id', $country?->id)->first()
            ?? Province::query()->first();
        $territory = Territory::query()->where('province_id', $province?->id)->first()
            ?? Territory::query()->first();
        $commune = Commune::query()->where('province_id', $province?->id)->first()
            ?? Commune::query()->first();
        $type = Type::query()->find($school->type_id) ?? Type::query()->first();
        $academicLevel = AcademicLevel::query()->first();
        $fonction = Fonction::query()->first();
        $mecanisation = Mecanisation::query()->first();

        if (
            $country === null || $province === null || $territory === null || $commune === null
            || $type === null || $academicLevel === null || $fonction === null || $mecanisation === null
        ) {
            $this->command?->warn('PersonalSeeder ignoré : données de référence manquantes (géo, niveau, fonction, mécanisation).');

            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $assignedSchool = $schools->get(($i - 1) % $schools->count()) ?? $school;

            Personal::firstOrCreate(
                ['matricule' => 'MAT-PERS-'.$i],
                [
                    'name' => 'Enseignant_'.$i,
                    'post_name' => 'PostNom_'.$i,
                    'pre_name' => 'Prenom_'.$i,
                    'email' => 'enseignant'.$i.'@school.com',
                    'gender' => 'Masculin',
                    'civil_status' => 'Célibataire',
                    'country_id' => $country->id,
                    'province_id' => $province->id,
                    'territory_id' => $territory->id,
                    'commune_id' => $commune->id,
                    'school_id' => $assignedSchool->id,
                    'type_id' => $type->id,
                    'physical_address' => 'Adresse '.$i,
                    'birth_date' => now()->subYears(30 + $i)->toDateString(),
                    'birth_place' => 'Ville_'.$i,
                    'identity_card_number' => 'IDCARD'.$i,
                    'father_id' => null,
                    'mother_id' => null,
                    'academic_level_id' => $academicLevel->id,
                    'phone' => '09900000'.$i,
                    'fonction_id' => $fonction->id,
                    'mechanisation_id' => $mecanisation->id,
                ]
            );
        }
    }
}
