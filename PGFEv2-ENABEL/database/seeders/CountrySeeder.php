<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

final class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::query()->firstOrCreate([
            'name' => 'Democratic Republic of the Congo',
        ]);
    }
}
