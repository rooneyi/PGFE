<?php

declare(strict_types=1);

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
| Panel Blade legacy (évite le conflit avec le SPA Vue sur /login et /admin).
| Accès : /legacy/login , /legacy/admin/...
*/
Route::prefix('legacy')->group(function () {
    require __DIR__.'/web/auth.php';
    require __DIR__.'/web/admin.php';
});

require __DIR__.'/../routes/sync.php';

/*
| SPA Vue à la racine du même domaine que l'API (ex. apischool.capslockdev.com).
| Les fichiers statiques (assets/, fonts/, …) sont servis directement par Apache/nginx.
*/
Route::get('/{any?}', SpaController::class)
    ->where('any', '^(?!api(?:/|$)|sanctum(?:/|$)|up$|storage(?:/|$)|legacy(?:/|$)|vendor(?:/|$)|build(?:/|$)).*')
    ->name('spa');
