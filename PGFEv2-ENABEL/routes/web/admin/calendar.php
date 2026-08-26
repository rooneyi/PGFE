<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\MoisWebController;
use App\Http\Controllers\Admin\PeriodWebController;
use App\Http\Controllers\Admin\SchoolYearWebController;
use App\Http\Controllers\Admin\SemesterWebController;
use Illuminate\Support\Facades\Route;

Route::resource('school-years', SchoolYearWebController::class)->names('school-years');
Route::get('school-years/{id}/activate', [SchoolYearWebController::class, 'activate'])->name('school-years.activate');
Route::resource('semesters', SemesterWebController::class)->names('semesters');
Route::resource('periods', PeriodWebController::class)->names('periods');
Route::resource('mois', MoisWebController::class)->names('mois');
