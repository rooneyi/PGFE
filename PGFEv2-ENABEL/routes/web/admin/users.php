<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\RegistrationWebController;
use App\Http\Controllers\Admin\RoleWebController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::resource('users', UsersController::class)->names('users');
Route::resource('roles', RoleWebController::class)->names('roles');
Route::post('roles/assign', [RoleWebController::class, 'assign'])->name('roles.assign');
Route::post('roles/revoke', [RoleWebController::class, 'revoke'])->name('roles.revoke');
Route::resource('registrations', RegistrationWebController::class)->names('registrations');
