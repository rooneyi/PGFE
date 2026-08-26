<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Stock\StockArticleWebController;
use App\Http\Controllers\Admin\Stock\StockCategoryWebController;
use App\Http\Controllers\Admin\Stock\StockEntryWebController;
use App\Http\Controllers\Admin\Stock\StockExitWebController;
use App\Http\Controllers\Admin\Stock\StockInventoryWebController;
use App\Http\Controllers\Admin\Stock\StockProviderWebController;
use App\Http\Controllers\Admin\Stock\StockStateWebController;
use Illuminate\Support\Facades\Route;

Route::resource('stock-articles', StockArticleWebController::class)->names('stock-articles');
Route::resource('stock-categories', StockCategoryWebController::class)->names('stock-categories');
Route::resource('stock-providers', StockProviderWebController::class)->names('stock-providers');
Route::resource('stock-entries', StockEntryWebController::class)->names('stock-entries');
Route::resource('stock-exits', StockExitWebController::class)->names('stock-exits');
Route::resource('stock-states', StockStateWebController::class)->names('stock-states');
Route::resource('stock-inventories', StockInventoryWebController::class)->names('stock-inventories');
