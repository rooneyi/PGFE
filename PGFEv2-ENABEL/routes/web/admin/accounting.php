<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Accounting\AccountingWebController;
use Illuminate\Support\Facades\Route;

Route::resource('accounting', AccountingWebController::class)->names('accounting');
