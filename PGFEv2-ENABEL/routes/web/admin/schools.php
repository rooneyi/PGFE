<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolWebController;
use Illuminate\Support\Facades\Route;

Route::resource('schools', SchoolWebController::class)->names('schools');
Route::get('school/switch/{id}', [SchoolWebController::class, 'switchSchool'])->name('school.switch');
