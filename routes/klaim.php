<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Klaim\Inap\SEPController;
use App\Http\Controllers\Klaim\Inap\LaporanOKController;
use App\Http\Controllers\Klaim\Inap\LaporanAnastesiController;
use App\Http\Controllers\Klaim\Inap\PenunjangLainController;
use App\Http\Controllers\Klaim\Inap\KlaimController;
use App\Http\Controllers\RM\PasienInapController;

Route::prefix('klaim')->group(function () {
    Route::prefix('inap')->group(function () {
        Route::get('/', function () {
            return "hallo from klaim/inap";
        })->name('klaim.inap.list_kamar_bangsal');

        Route::get('/get_all_jok/{kode_reg}', [LaporanOKController::class, 'get_all_jok'])->name('klaim.inap.get_all_jok');
        Route::get('/get_kode_reg_jalan/{kode_reg_rbi}', [KlaimController::class, 'getKodeRegRJByInap'])->name('klaim.inap.get_kode_reg_jalan');

        Route::get('/sep/html', [SEPController::class, 'viewHtml'])->name('klaim.inap.sep.html');
        Route::get('/sep/{kode_reg}', [SEPController::class, 'index'])->name('klaim.inap.sep');
        Route::get('/laporan_anastesi/{kode_reg}', [LaporanAnastesiController::class, 'generatePdf'])->name('klaim.inap.laporan_anastesi');
        Route::get('/laporan_anastesi_snapshot/{kode_reg}', [LaporanAnastesiController::class, 'snapshot'])->name('klaim.inap.laporan_anastesi_snapshot');

        Route::get('/penunjang_lain/{kode_reg}', [PenunjangLainController::class, 'list'])->name('klaim.inap.penunjang_lain.list');
        Route::post('/penunjang_lain/{kode_reg}', [PenunjangLainController::class, 'upload'])->name('klaim.inap.penunjang_lain.upload');
        Route::get('/penunjang_lain/{kode_reg}/{id}', [PenunjangLainController::class, 'download'])->name('klaim.inap.penunjang_lain.download');
        Route::delete('/penunjang_lain/{kode_reg}/{id}', [PenunjangLainController::class, 'delete'])->name('klaim.inap.penunjang_lain.delete');
        Route::get('/cetak_klaim/{no_sep}', [PasienInapController::class, 'bridging_cetak_klaim'])->name('klaim.inap.cetak_klaim');
    });
});
