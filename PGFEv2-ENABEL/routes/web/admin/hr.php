<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\PersonAffectationWebController;
use App\Http\Controllers\Admin\PersonnelPresenceWebController;
use App\Http\Controllers\Admin\PersonnelWebController;
use Illuminate\Support\Facades\Route;

Route::resource('personnels', PersonnelWebController::class)
    ->only(['index', 'edit', 'update', 'destroy'])
    ->names('personnels');

Route::get('personnel-presences/export', [PersonnelPresenceWebController::class, 'export'])->name('personnel-presences.export');
Route::post('personnel-presences/initialize', [PersonnelPresenceWebController::class, 'initialize'])->name('personnel-presences.initialize');
Route::post('personnel-presences/bulk', [PersonnelPresenceWebController::class, 'bulkUpdate'])->name('personnel-presences.bulk');
Route::get('personnel-presences', [PersonnelPresenceWebController::class, 'index'])->name('personnel-presences.index');
Route::get('person-affectations', [PersonAffectationWebController::class, 'index'])->name('person-affectations.index');
