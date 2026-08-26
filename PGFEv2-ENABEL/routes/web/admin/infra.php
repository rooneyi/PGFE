<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Infra\InfraBailleurWebController;
use App\Http\Controllers\Admin\Infra\InfraCategoryWebController;
use App\Http\Controllers\Admin\Infra\InfraDashboardWebController;
use App\Http\Controllers\Admin\Infra\InfraEquipementWebController;
use App\Http\Controllers\Admin\Infra\InfraEtatWebController;
use App\Http\Controllers\Admin\Infra\InfraInfrastructureInventaireWebController;
use App\Http\Controllers\Admin\Infra\InfraInfrastructureWebController;
use App\Http\Controllers\Admin\Infra\InfraInventaireWebController;
use Illuminate\Support\Facades\Route;

Route::get('infra/dashboard', [InfraDashboardWebController::class, 'index'])->name('infra.dashboard');
Route::resource('infra-categories', InfraCategoryWebController::class)->names('infra-categories');
Route::resource('infra-bailleurs', InfraBailleurWebController::class)->names('infra-bailleurs');
Route::resource('infra-equipements', InfraEquipementWebController::class)->names('infra-equipements');
Route::resource('infra-inventaires', InfraInventaireWebController::class)->names('infra-inventaires');
Route::resource('infra-infrastructures', InfraInfrastructureWebController::class)->names('infra-infrastructures');
Route::resource('infra-etats', InfraEtatWebController::class)->names('infra-etats');
Route::resource('infra-infrastructure-inventaires', InfraInfrastructureInventaireWebController::class)->names('infra-infrastructure-inventaires');
