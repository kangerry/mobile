<?php

use Illuminate\Support\Facades\Route;
use Modules\Kojek\Http\Controllers\KojekController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('kojeks', KojekController::class)->names('kojek');
});
