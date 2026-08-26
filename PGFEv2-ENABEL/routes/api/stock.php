<?php

// Stock routes

use App\Http\Controllers\Api\Stock\DashboardStockController;
use App\Http\Controllers\Api\Stock\StockArticleController;
use App\Http\Controllers\Api\Stock\StockCategoryController;
use App\Http\Controllers\Api\Stock\StockEntryController;
use App\Http\Controllers\Api\Stock\StockExitController;
use App\Http\Controllers\Api\Stock\StockInventoryArticleController;
use App\Http\Controllers\Api\Stock\StockInventoryController;
use App\Http\Controllers\Api\Stock\StockOperationController;
use App\Http\Controllers\Api\Stock\StockProviderController;
use App\Http\Controllers\Api\Stock\StockStateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('stock')
    ->name('stock.')
    ->group(function () {
        Route::apiResource('articles', StockArticleController::class);
        Route::post('inventories/{inventory}/articles', [StockInventoryArticleController::class, 'store']);
        Route::apiResource('categories', StockCategoryController::class);
        Route::apiResource('providers', StockProviderController::class);
        Route::apiResource('entries', StockEntryController::class);
        Route::apiResource('exits', StockExitController::class);
        Route::get('articles/{article}/state', [StockStateController::class, 'show']);
        Route::apiResource('inventories', StockInventoryController::class);
        Route::apiResource('operations', StockOperationController::class);
        Route::get('dashboard', [DashboardStockController::class, 'index']);
    });
