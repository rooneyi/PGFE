<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RoleExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
Route::get('/search', [AdminController::class, 'globalSearch'])->name('search');
Route::get('/sync/monitoring', [AdminController::class, 'syncMonitoring'])->name('sync.monitoring');
Route::post('/sync/run', [AdminController::class, 'triggerSync'])->name('sync.run');
Route::get('/export-roles-pdf', [RoleExportController::class, 'exportPdf'])->name('export-roles-pdf');
