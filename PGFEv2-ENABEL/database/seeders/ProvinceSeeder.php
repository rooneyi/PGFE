<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $drCongo = Country::query()->firstOrCreate([
            'name' => 'Democratic Republic of the Congo',
        ]);

        $jsonPath = base_path('database/data/provinces.json');
        $payload = is_file($jsonPath) ? json_decode((string) file_get_contents($jsonPath), true) : [];

        $provinces = collect(is_array($payload) ? $payload : [])
            ->filter(fn ($row): bool => is_array($row) && ! empty($row['name']))
            ->map(fn ($row): array => [
                'name' => trim((string) $row['name']),
            ])
            ->unique('name')
            ->values();

        if ($provinces->isEmpty()) {
            $provinces = $this->fallbackProvinces();
        }

        $provinces->each(function (array $province) use ($drCongo): void {
            Province::query()->firstOrCreate(
                [
                    'country_id' => $drCongo->id,
                    'name' => $province['name'],
                ]
            );
        });
    }

    private function fallbackProvinces(): Collection
    {
        return collect([
            ['name' => 'Kasai-Central'],
            ['name' => 'Kasai-Oriental'],
            ['name' => 'Kasai'],
            ['name' => 'Bandundu'],
            ['name' => 'Nord-Kivu'],
            ['name' => 'Sud-Kivu'],
            ['name' => 'Nord-Ubangi'],
            ['name' => 'Sud-Ubangi'],
        ]);
    }
}
