<?php

use Illuminate\Support\Facades\Route;
use Modules\KoFood\Http\Controllers\KoFoodController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('kofoods', KoFoodController::class)->names('kofood');
});
