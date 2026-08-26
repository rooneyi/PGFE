<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Organization\ProvedController;
use App\Http\Controllers\Api\Organization\SousDivisionController;
use Illuminate\Support\Facades\Route;

/*
| API Organisation — SPEC-02
*/

Route::middleware(['auth:sanctum', 'role:super-admin|admin-proved|admin-sous-division'])
    ->prefix('organization')
    ->name('organization.')
    ->group(function () {
        Route::middleware('role:super-admin|admin-proved')->group(function () {
            Route::get('proveds', [ProvedController::class, 'index'])->name('proveds.index');
            Route::post('proveds', [ProvedController::class, 'store'])->middleware('role:super-admin')->name('proveds.store');
            Route::get('proveds/{proved}', [ProvedController::class, 'show'])->name('proveds.show');
            Route::put('proveds/{proved}', [ProvedController::class, 'update'])->name('proveds.update');
            Route::delete('proveds/{proved}', [ProvedController::class, 'destroy'])->middleware('role:super-admin')->name('proveds.destroy');
            Route::get('proveds/{proved}/sous-divisions', [ProvedController::class, 'sousDivisions'])->name('proveds.sous-divisions');
        });

        Route::get('sous-divisions', [SousDivisionController::class, 'index'])->name('sous-divisions.index');
        Route::post('sous-divisions', [SousDivisionController::class, 'store'])->middleware('role:super-admin|admin-proved')->name('sous-divisions.store');
        Route::get('sous-divisions/{sous_division}', [SousDivisionController::class, 'show'])->name('sous-divisions.show');
        Route::put('sous-divisions/{sous_division}', [SousDivisionController::class, 'update'])->middleware('role:super-admin|admin-proved')->name('sous-divisions.update');
        Route::delete('sous-divisions/{sous_division}', [SousDivisionController::class, 'destroy'])->middleware('role:super-admin|admin-proved')->name('sous-divisions.destroy');
        Route::get('sous-divisions/{sous_division}/schools', [SousDivisionController::class, 'schools'])->name('sous-divisions.schools');
    });
