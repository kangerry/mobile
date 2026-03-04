<?php

use Illuminate\Support\Facades\Route;
use Modules\KoFood\Http\Controllers\KoFoodController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('kofoods', KoFoodController::class)->names('kofood');
});
