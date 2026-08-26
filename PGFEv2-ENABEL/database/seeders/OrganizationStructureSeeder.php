<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Proved;
use App\Models\Province;
use App\Models\School;
use App\Models\SousDivision;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class OrganizationStructureSeeder extends Seeder
{
    public function run(): void
    {
        $province = Province::query()->orderBy('id')->first();

        $proved = Proved::query()->firstOrCreate(
            ['code' => 'PROVED-DEMO'],
            [
                'name' => 'PROVED Démo',
                'province_id' => $province?->id,
                'email' => 'proved-demo@pgfe.local',
            ]
        );

        $sousDivision = SousDivision::query()->firstOrCreate(
            ['proved_id' => $proved->id, 'code' => 'SD-DEMO'],
            ['name' => 'Sous-division Démo']
        );

        School::query()
            ->whereNull('sous_division_id')
            ->update(['sous_division_id' => $sousDivision->id]);

        $adminProved = User::query()->firstOrCreate(
            ['email' => 'admin-proved@demo.local'],
            [
                'name' => 'Admin Proved Démo',
                'password' => Hash::make('password'),
                'proved_id' => $proved->id,
                'school_id' => null,
                'sous_division_id' => null,
            ]
        );
        $adminProved->syncRoles([Role::findByName('admin-proved', 'web')]);

        $adminSd = User::query()->firstOrCreate(
            ['email' => 'admin-sd@demo.local'],
            [
                'name' => 'Admin Sous-division Démo',
                'password' => Hash::make('password'),
                'sous_division_id' => $sousDivision->id,
                'proved_id' => null,
                'school_id' => null,
            ]
        );
        $adminSd->syncRoles([Role::findByName('admin-sous-division', 'web')]);
    }
}
