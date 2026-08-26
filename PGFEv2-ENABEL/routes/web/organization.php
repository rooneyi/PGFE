<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CollecteRapideWebController;
use App\Http\Controllers\Admin\ProvedWebController;
use App\Http\Controllers\Admin\SousDivisionWebController;
use Illuminate\Support\Facades\Route;

/*
| Organisation scolaire : Proved → Sous-division → École
| Voir docs/SPEC-02-organisation-routes.md
*/

Route::middleware('role:super-admin')->group(function () {
    Route::resource('proveds', ProvedWebController::class)->except(['show']);
});

Route::middleware('role:super-admin|admin-proved|admin-sous-division')->group(function () {
    Route::resource('sous-divisions', SousDivisionWebController::class)->except(['show']);
    Route::get('sous-division/switch/{id}', [SousDivisionWebController::class, 'switchSousDivision'])
        ->name('sous-division.switch');
});

Route::middleware('role:admin-proved')->group(function () {
    Route::get('collecte-rapides', [CollecteRapideWebController::class, 'index'])->name('collecte-rapides.index');
    Route::post('collecte-rapides', [CollecteRapideWebController::class, 'store'])->name('collecte-rapides.store');
    Route::get('collecte-rapides/synthese', [CollecteRapideWebController::class, 'synthese'])->name('collecte-rapides.synthese');
    Route::get('collecte-rapides/export', [CollecteRapideWebController::class, 'export'])->name('collecte-rapides.export');
    Route::get('collecte-rapides/export-synthese', [CollecteRapideWebController::class, 'exportSynthese'])->name('collecte-rapides.export-synthese');
    Route::post('collecte-rapides/import', [CollecteRapideWebController::class, 'import'])->name('collecte-rapides.import');
    Route::get('collecte-rapides/{collecte_rapide}/export', [CollecteRapideWebController::class, 'exportOne'])->name('collecte-rapides.export-one');
    Route::get('collecte-rapides/{collecte_rapide}/etape/{step}', [CollecteRapideWebController::class, 'step'])
        ->whereNumber('step')
        ->name('collecte-rapides.step');
    Route::put('collecte-rapides/{collecte_rapide}/etape/{step}', [CollecteRapideWebController::class, 'updateStep'])
        ->whereNumber('step')
        ->name('collecte-rapides.step.update');
    Route::post('collecte-rapides/{collecte_rapide}/submit', [CollecteRapideWebController::class, 'submit'])
        ->name('collecte-rapides.submit');
    Route::post('collecte-rapides/{collecte_rapide}/reopen', [CollecteRapideWebController::class, 'reopen'])
        ->name('collecte-rapides.reopen');
    Route::delete('collecte-rapides/{collecte_rapide}', [CollecteRapideWebController::class, 'destroy'])
        ->name('collecte-rapides.destroy');
});
