<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vide provinces + territoires puis recharge depuis database/data/*.json (RDC uniquement).
 */
final class RefreshProvincesTerritoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('territories')->truncate();
        DB::table('provinces')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->call([
            CountrySeeder::class,
            ProvinceSeeder::class,
            TerritorySeeder::class,
        ]);
    }
}
