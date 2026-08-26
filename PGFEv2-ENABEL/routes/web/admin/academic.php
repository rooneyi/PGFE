<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AcademicLevelWebController;
use App\Http\Controllers\Admin\BulletinWebController;
use App\Http\Controllers\Admin\ClassroomWebController;
use App\Http\Controllers\Admin\CycleWebController;
use App\Http\Controllers\Admin\DeliberationWebController;
use App\Http\Controllers\Admin\FicheCotationWebController;
use App\Http\Controllers\Admin\FiliaireWebController;
use App\Http\Controllers\Admin\IndisciplineWebController;
use App\Http\Controllers\Admin\PlanningWebController;
use App\Http\Controllers\Admin\PresenceWebController;
use App\Http\Controllers\Admin\RepechageWebController;
use App\Http\Controllers\Admin\SchoolActivityWebController;
use App\Http\Controllers\Admin\StudentExitWebController;
use App\Http\Controllers\Admin\StudentTransferWebController;
use App\Http\Controllers\Admin\StudentWebController;
use App\Http\Controllers\Admin\TypeWebController;
use App\Http\Controllers\Admin\ValidationAureatWebController;
use App\Http\Controllers\Admin\VisitWebController;
use Illuminate\Support\Facades\Route;

Route::resource('classrooms', ClassroomWebController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->names('classrooms');

Route::resource('students', StudentWebController::class)
    ->only(['index', 'edit', 'update', 'destroy'])
    ->names('students');

Route::get('students/{student}/transfers', [StudentTransferWebController::class, 'index'])->name('students.transfers.index');
Route::post('students/{student}/transfers', [StudentTransferWebController::class, 'store'])->name('students.transfers.store');

Route::get('presences/export', [PresenceWebController::class, 'export'])->name('presences.export');
Route::get('presences/export-pdf', [PresenceWebController::class, 'exportPdf'])->name('presences.export-pdf');
Route::post('presences/initialize', [PresenceWebController::class, 'initialize'])->name('presences.initialize');
Route::post('presences/classrooms/{classroom}/bulk', [PresenceWebController::class, 'bulkUpdate'])->name('presences.bulk');
Route::patch('presences/{presence}', [PresenceWebController::class, 'update'])->name('presences.update');
Route::get('presences', [PresenceWebController::class, 'index'])->name('presences.index');

Route::get('fiche-cotations/export', [FicheCotationWebController::class, 'export'])->name('fiche-cotations.export');
Route::post('fiche-cotations/initialize', [FicheCotationWebController::class, 'initialize'])->name('fiche-cotations.initialize');
Route::get('fiche-cotations', [FicheCotationWebController::class, 'index'])->name('fiche-cotations.index');

Route::get('deliberations/export', [DeliberationWebController::class, 'export'])->name('deliberations.export');
Route::post('deliberations/initialize', [DeliberationWebController::class, 'initialize'])->name('deliberations.initialize');
Route::patch('deliberations/{deliberation}/validation', [DeliberationWebController::class, 'updateValidation'])->name('deliberations.validation');
Route::get('deliberations', [DeliberationWebController::class, 'index'])->name('deliberations.index');

Route::get('repechages', [RepechageWebController::class, 'index'])->name('repechages.index');
Route::get('validation-aureats/export', [ValidationAureatWebController::class, 'export'])->name('validation-aureats.export');
Route::get('validation-aureats', [ValidationAureatWebController::class, 'index'])->name('validation-aureats.index');
Route::get('indiscipline', [IndisciplineWebController::class, 'index'])->name('indiscipline.index');
Route::get('bulletins/print', [BulletinWebController::class, 'print'])->name('bulletins.print');
Route::get('bulletins', [BulletinWebController::class, 'index'])->name('bulletins.index');
Route::get('student-exits', [StudentExitWebController::class, 'index'])->name('student-exits.index');
Route::get('visits', [VisitWebController::class, 'index'])->name('visits.index');

Route::resource('filiaires', FiliaireWebController::class)->names('filiaires');
Route::resource('cycles', CycleWebController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->names('cycles');
Route::resource('types', TypeWebController::class)->names('types');
Route::resource('academic-levels', AcademicLevelWebController::class)->names('academic-levels');

Route::resource('planning', PlanningWebController::class)
    ->only(['index', 'create', 'store'])
    ->names('planning');
Route::resource('activities', SchoolActivityWebController::class)
    ->only(['index', 'create', 'store'])
    ->names('activities');
