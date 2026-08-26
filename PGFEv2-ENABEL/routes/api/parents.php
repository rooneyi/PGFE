<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Parents\ParentAccountController;
use App\Http\Controllers\Api\Parents\ParentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('parents', ParentController::class);
    Route::post('parents/{parent}/account', [ParentAccountController::class, 'store'])
        ->whereNumber('parent')
        ->name('parents.account.store');
});
