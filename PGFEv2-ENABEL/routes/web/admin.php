<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin web — point d'entrée
|--------------------------------------------------------------------------
| Chaque domaine métier a son fichier dans routes/web/admin/
| (aligné sur routes/api/*.php).
*/

Route::middleware([
    'web',
    'auth',
    'role:super-admin|admin-proved|admin-sous-division|admin-ecole|enseignant|comptable|stoker|rh|inspecteur|disciplinaire',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        require __DIR__.'/admin/dashboard.php';
        require __DIR__.'/organization.php';
        require __DIR__.'/admin/schools.php';
        require __DIR__.'/admin/geo.php';
        require __DIR__.'/admin/academic.php';
        require __DIR__.'/admin/hr.php';
        require __DIR__.'/admin/users.php';
        require __DIR__.'/admin/accounting.php';
        require __DIR__.'/admin/calendar.php';
        require __DIR__.'/admin/infra.php';
        require __DIR__.'/admin/stock.php';
    });
