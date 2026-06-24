<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Klaim\Inap\SEPController;

Route::prefix('klaim')->group(function () {
    Route::prefix('inap')->group(function () {
        Route::get('/', function () {
            return "hallo from klaim/inap";
        })->name('klaim.inap.list_kamar_bangsal');

        Route::get('/sep/html', [SEPController::class, 'viewHtml'])->name('klaim.inap.sep.html');
        Route::get('/sep', [SEPController::class, 'index'])->name('klaim.inap.sep');

    });
});
