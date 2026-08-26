<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CommuneWebController;
use App\Http\Controllers\Admin\CountryWebController;
use App\Http\Controllers\Admin\ProvinceWebController;
use App\Http\Controllers\Admin\TerritoryWebController;
use Illuminate\Support\Facades\Route;

Route::resource('countries', CountryWebController::class)->names('countries');
Route::post('provinces/import-json', [ProvinceWebController::class, 'importJson'])->name('provinces.import-json');
Route::resource('provinces', ProvinceWebController::class)->names('provinces');
Route::resource('communes', CommuneWebController::class)->names('communes');
Route::post('territories/import-json', [TerritoryWebController::class, 'importJson'])->name('territories.import-json');
Route::resource('territories', TerritoryWebController::class)->names('territories');
