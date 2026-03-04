<?php

use Illuminate\Support\Facades\Route;
use Modules\Dompet\Http\Controllers\DompetController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('dompets', DompetController::class)->names('dompet');
});
