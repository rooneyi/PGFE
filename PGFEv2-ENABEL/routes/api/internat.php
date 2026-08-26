<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internat\InternatAffectationController;
use App\Http\Controllers\Api\Internat\InternatChambreController;
use App\Http\Controllers\Api\Internat\InternatDashboardController;
use App\Http\Controllers\Api\Internat\InternatLitController;
use App\Http\Controllers\Api\Internat\InternatPavillonController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('internat')
    ->name('internat.')
    ->group(function () {
        Route::get('dashboard', [InternatDashboardController::class, 'index'])->name('dashboard');

        Route::apiResource('pavillons', InternatPavillonController::class);
        Route::apiResource('chambres', InternatChambreController::class);
        Route::apiResource('lits', InternatLitController::class);

        Route::post('affectations/{affectation}/checkout', [InternatAffectationController::class, 'checkout'])
            ->whereNumber('affectation')
            ->name('affectations.checkout');
        Route::apiResource('affectations', InternatAffectationController::class);
    });
