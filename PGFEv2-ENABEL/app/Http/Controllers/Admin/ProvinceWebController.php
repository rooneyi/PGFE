<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

final class ProvinceWebController extends Controller
{
    public function index()
    {
        $provinces = Province::query()->with('country:id,name')->orderBy('name')->paginate(20, ['id', 'name', 'country_id']);

        return view('backend.pages.provinces.index', compact('provinces'));
    }

    public function create()
    {
        return view('backend.pages.provinces.create');
    }

    public function store(Request $request)
    {
        $drCongo = Country::query()->firstOrCreate([
            'name' => 'Democratic Republic of the Congo',
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:provinces,name'],
        ]);
        $data['country_id'] = $drCongo->id;
        Province::create($data);

        return redirect()->route('admin.provinces.index')->with('success', 'Province créée.');
    }

    public function edit(Province $province)
    {
        return view('backend.pages.provinces.edit', compact('province'));
    }

    public function update(Request $request, Province $province)
    {
        $drCongo = Country::query()->firstOrCreate([
            'name' => 'Democratic Republic of the Congo',
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:provinces,name,'.$province->id],
        ]);
        $data['country_id'] = $drCongo->id;
        $province->update($data);

        return redirect()->route('admin.provinces.index')->with('success', 'Province mise à jour.');
    }

    public function destroy(Province $province)
    {
        $province->delete();

        return redirect()->route('admin.provinces.index')->with('success', 'Province supprimée.');
    }

    public function importJson()
    {
        try {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\RefreshProvincesTerritoriesSeeder::class,
                '--force' => true,
            ]);

            return redirect()->route('admin.provinces.index')->with('success', 'Donnees reinitialisees et import JSON provinces/territoires effectue.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.provinces.index')->with('error', "Échec de l'import JSON des provinces.");
        }
    }
}
