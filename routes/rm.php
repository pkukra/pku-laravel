<?php

use App\Http\Controllers\RM\PasienRujukanController;
use App\Http\Controllers\RM\PasienInapController;
use Illuminate\Support\Facades\Route;

Route::prefix('rm')->group(function () {
    Route::get('/', [PasienRujukanController::class, 'index'])->name('rm.index');
    Route::middleware('auth')->prefix('pasien-rujukan')->group(function () {
        Route::get('/list/{no_rm}', [PasienRujukanController::class, 'index_data'])->name('rm.pasien-rujukan.list');
        Route::get('/detail/{kode_reg}', [PasienRujukanController::class, 'show'])->name('rm.pasien-rujukan.detail');
        Route::get('/detail_data/{kode_reg}', [PasienRujukanController::class, 'show_data'])->name('rm.pasien-rujukan.detail_data');
        Route::get('/get_nomer_sep/{kode_reg}/{kode_reg_kj}', [PasienRujukanController::class, 'get_nomer_sep'])->name('rm.pasien-rujukan.get_nomer_sep');
        Route::get('/get_keadaan_keluar_rs/{kode_reg}/', [PasienRujukanController::class, 'get_keadaan_keluar_rs'])->name('rm.pasien-rujukan.get_keadaan_keluar_rs');
        Route::get('/get_kunjungan_pasien/{kode_reg}/', [PasienRujukanController::class, 'get_kunjungan_pasien'])->name('rm.pasien-rujukan.get_kunjungan_pasien');

        Route::get('/list_diagnosa/{kode_reg}', [PasienRujukanController::class, 'list_diagnosa'])->name('rm.pasien-rujukan.list_diagnosa');
        Route::post('/cari_penyakit', [PasienRujukanController::class, 'cari_penyakit'])->name('rm.pasien-rujukan.cari_penyakit');
        Route::post('/save-diagnosa', [PasienRujukanController::class, 'save_diagnosa'])->name('rm.pasien-rujukan.save_diagnosa');
        Route::delete('/diagnosa/{id}', [PasienRujukanController::class, 'delete_diagnosa'])->name('rm.pasien-rujukan.delete_diagnosa');

        Route::get('/list_procedure/{kode_reg}', [PasienRujukanController::class, 'list_procedure'])->name('rm.pasien-rujukan.list_procedure');
        Route::post('/cari_procedure', [PasienRujukanController::class, 'cari_procedure'])->name('rm.pasien-rujukan.cari_procedure');
        Route::post('/save-procedure', [PasienRujukanController::class, 'save_procedure'])->name('rm.pasien-rujukan.save_procedure');
        Route::delete('/procedure/{id}', [PasienRujukanController::class, 'delete_procedure'])->name('rm.pasien-rujukan.delete_procedure');

        Route::get('/get_mr_diagnosa/{kode_reg}', [PasienRujukanController::class, 'get_mr_diagnosa'])->name('rm.pasien-rujukan.get_mr_diagnosa');
        Route::post('/update_catatan_khusus/{kode_reg}', [PasienRujukanController::class, 'update_catatan_khusus'])->name('rm.pasien-rujukan.update_catatan_khusus');
        Route::get('/cari_cara_masuk_bpjs', [PasienRujukanController::class, 'cari_cara_masuk_bpjs'])->name('rm.pasien-rujukan.cari_cara_masuk_bpjs');
        Route::get('/cari_keadaan_keluar_rs', [PasienRujukanController::class, 'cari_keadaan_keluar_rs'])->name('rm.pasien-rujukan.cari_keadaan_keluar_rs');
        Route::get('/cari_rs_rujukan', [PasienRujukanController::class, 'cari_rs_rujukan'])->name('rm.pasien-rujukan.cari_rs_rujukan');
        Route::post('/update_cara_masuk_pulang/{kode_reg_kj}', [PasienRujukanController::class, 'update_cara_masuk_pulang'])->name('rm.pasien-rujukan.update_cara_masuk_pulang');

        Route::get('/get_resume/{kode_reg}', [PasienRujukanController::class, 'get_resume'])->name('rm.pasien-rujukan.get_resume');
        Route::get('/get_hasil_radiologi/{kode_reg}', [PasienRujukanController::class, 'get_hasil_radiologi'])->name('rm.pasien-rujukan.get_hasil_radiologi');

        Route::post('/bridging_data_process/{no_sep}', [PasienRujukanController::class, 'bridging_data_process'])->name('rm.pasien-rujukan.bridging_data_process');
        Route::post('/bridging_final_process/{no_sep}', [PasienRujukanController::class, 'bridging_final_process'])->name('rm.pasien-rujukan.bridging_final_process');
    });
    
    Route::middleware('auth')->prefix('pasien-inap')->group(function () {
        Route::get('/list/{no_rm}', [PasienInapController::class, 'index_data'])->name('rm.pasien-inap.list');
        Route::get('/detail/{kode_reg}', [PasienInapController::class, 'show'])->name('rm.pasien-inap.detail');
    });
});
