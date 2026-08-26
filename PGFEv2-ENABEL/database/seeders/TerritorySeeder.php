<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Territory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class TerritorySeeder extends Seeder
{
    public function run(): void
    {
        $provincesByName = Province::query()->pluck('id', 'name');

        $provincesJsonPath = base_path('database/data/provinces.json');
        $territoriesJsonPath = base_path('database/data/territoires.json');

        $provincesPayload = is_file($provincesJsonPath) ? json_decode((string) file_get_contents($provincesJsonPath), true) : [];
        $territoriesPayload = is_file($territoriesJsonPath) ? json_decode((string) file_get_contents($territoriesJsonPath), true) : [];

        $provinceIdToName = collect(is_array($provincesPayload) ? $provincesPayload : [])
            ->filter(fn ($row): bool => is_array($row) && isset($row['id'], $row['name']))
            ->mapWithKeys(fn ($row): array => [(int) $row['id'] => trim((string) $row['name'])]);

        $territories = collect(is_array($territoriesPayload) ? $territoriesPayload : [])
            ->filter(fn ($row): bool => is_array($row) && ! empty($row['territoire']) && isset($row['province_id']))
            ->map(function ($row) use ($provinceIdToName, $provincesByName): ?array {
                $provinceName = $provinceIdToName->get((int) $row['province_id']);
                if (! $provinceName) {
                    return null;
                }

                $provinceId = $provincesByName->get($provinceName);
                if (! $provinceId) {
                    return null;
                }

                return [
                    'name' => trim((string) $row['territoire']),
                    'province_id' => (int) $provinceId,
                ];
            })
            ->filter()
            ->unique(fn ($row): string => $row['province_id'].'|'.$row['name'])
            ->values();

        if ($territories->isEmpty()) {
            $territories = $this->fallbackTerritories();
        }

        $territories->each(function (array $territory): void {
            Territory::query()->firstOrCreate(
                [
                    'province_id' => $territory['province_id'],
                    'name' => $territory['name'],
                ]
            );
        });
    }

    private function fallbackTerritories(): Collection
    {
        return Province::query()
            ->get(['id'])
            ->flatMap(function (Province $province): Collection {
                return collect(['Lubusha', 'Luisha', 'Sud', 'Est', 'Ouest'])
                    ->map(fn (string $name): array => [
                        'name' => $name,
                        'province_id' => $province->id,
                    ]);
            })
            ->values();
    }
}
