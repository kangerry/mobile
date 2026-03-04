<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifikasi\Http\Controllers\NotifikasiController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('notifikasis', NotifikasiController::class)->names('notifikasi');
});
