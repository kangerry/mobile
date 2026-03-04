<?php

use Illuminate\Support\Facades\Route;
use Modules\Dompet\Http\Controllers\DompetController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('dompets', DompetController::class)->names('dompet');
});
