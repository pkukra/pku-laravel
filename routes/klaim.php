<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Klaim\Inap\SEPController;
use App\Http\Controllers\Klaim\Inap\LaporanOKController;
use App\Http\Controllers\Klaim\Inap\LaporanAnastesiController;

Route::prefix('klaim')->group(function () {
    Route::prefix('inap')->group(function () {
        Route::get('/', function () {
            return "hallo from klaim/inap";
        })->name('klaim.inap.list_kamar_bangsal');

        Route::get('/get_all_jok/{kode_reg}', [LaporanOKController::class, 'get_all_jok'])->name('klaim.inap.get_all_jok');

        Route::get('/sep/html', [SEPController::class, 'viewHtml'])->name('klaim.inap.sep.html');
        Route::get('/sep/{kode_reg}', [SEPController::class, 'index'])->name('klaim.inap.sep');
        Route::get('/laporan_anastesi/{kode_reg}', [LaporanAnastesiController::class, 'generatePdf'])->name('klaim.inap.laporan_anastesi');
        Route::get('/laporan_anastesi_snapshot/{kode_reg}', [LaporanAnastesiController::class, 'snapshot'])->name('klaim.inap.laporan_anastesi_snapshot');
    });
});
