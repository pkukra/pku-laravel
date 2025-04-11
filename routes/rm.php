<?php

use App\Http\Controllers\RM\PasienRujukanController;
use App\Http\Controllers\RM\PasienInapController;
use App\Http\Controllers\Cesemix\RanapMonitController;
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
        Route::put('/update_nomer_sep/{kode_reg}/{kode_reg_kj}', [PasienRujukanController::class, 'update_nomer_sep'])->name('rm.pasien-rujukan.update_nomer_sep');

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
        Route::get('/detail_data/{kode_reg}', [PasienInapController::class, 'show_data'])->name('rm.pasien-inap.detail_data');
        Route::get('/get_keadaan_keluar_rs/{kode_reg}/', [PasienInapController::class, 'get_keadaan_keluar_rs'])->name('rm.pasien-inap.get_keadaan_keluar_rs');
        Route::get('/get_kunjungan_pasien/{kode_reg}/', [PasienInapController::class, 'get_kunjungan_pasien'])->name('rm.pasien-inap.get_kunjungan_pasien');


        Route::get('/list_diagnosa/{kode_reg}', [PasienInapController::class, 'list_diagnosa'])->name('rm.pasien-inap.list_diagnosa');
        Route::post('/cari_penyakit', [PasienInapController::class, 'cari_penyakit'])->name('rm.pasien-inap.cari_penyakit');
        Route::post('/save-diagnosa', [PasienInapController::class, 'save_diagnosa'])->name('rm.pasien-inap.save_diagnosa');
        Route::delete('/diagnosa/{id}', [PasienInapController::class, 'delete_diagnosa'])->name('rm.pasien-inap.delete_diagnosa');

        Route::get('/list_procedure/{kode_reg}', [PasienInapController::class, 'list_procedure'])->name('rm.pasien-inap.list_procedure');
        Route::post('/cari_procedure', [PasienInapController::class, 'cari_procedure'])->name('rm.pasien-inap.cari_procedure');
        Route::post('/save-procedure', [PasienInapController::class, 'save_procedure'])->name('rm.pasien-inap.save_procedure');
        Route::delete('/procedure/{id}', [PasienInapController::class, 'delete_procedure'])->name('rm.pasien-inap.delete_procedure');

        Route::get('/get_resume/{kode_reg}', [PasienInapController::class, 'get_resume'])->name('rm.pasien-inap.get_resume');
        Route::get('/get_hasil_radiologi/{kode_reg}', [PasienInapController::class, 'get_hasil_radiologi'])->name('rm.pasien-inap.get_hasil_radiologi');
        Route::get('/get_berkas_rm/{kode_reg}', [PasienInapController::class, 'get_berkas_rm'])->name('rm.pasien-inap.get_berkas_rm');

        Route::get('/get_all_obat/{kode_reg}', [PasienInapController::class, 'get_all_obat'])->name('rm.pasien-inap.get_all_obat');

        Route::get('/get_nomer_sep/{kode_reg}', [PasienInapController::class, 'get_nomer_sep'])->name('rm.pasien-inap.get_nomer_sep');
        Route::put('/update_nomer_sep/{kode_reg}', [PasienInapController::class, 'update_nomer_sep'])->name('rm.pasien-inap.update_nomer_sep');

        Route::get('/cari_cara_masuk_bpjs', [PasienInapController::class, 'cari_cara_masuk_bpjs'])->name('rm.pasien-inap.cari_cara_masuk_bpjs');
        Route::get('/cari_keadaan_keluar_rs', [PasienInapController::class, 'cari_keadaan_keluar_rs'])->name('rm.pasien-inap.cari_keadaan_keluar_rs');
        Route::get('/cari_rs_rujukan', [PasienInapController::class, 'cari_rs_rujukan'])->name('rm.pasien-inap.cari_rs_rujukan');
        Route::post('/update_cara_masuk_pulang/{kode_reg}', [PasienInapController::class, 'update_cara_masuk_pulang'])->name('rm.pasien-inap.update_cara_masuk_pulang');

        Route::post('/bridging_data_process/{no_sep}', [PasienInapController::class, 'bridging_data_process'])->name('rm.pasien-inap.bridging_data_process');
        Route::post('/bridging_final_process/{kode_reg}/{no_sep}', [PasienInapController::class, 'bridging_final_process'])->name('rm.pasien-inap.bridging_final_process');
        Route::get('/bridging_cetak_klaim/{no_sep}', [PasienInapController::class, 'bridging_cetak_klaim'])->name('rm.pasien-inap.bridging_cetak_klaim');
        Route::get('/bridging_kirim_klaim/{no_sep}', [PasienInapController::class, 'bridging_kirim_klaim'])->name('rm.pasien-inap.bridging_kirim_klaim');

        Route::get('/get_list_cppt/{kode_reg}', [RanapMonitController::class, 'get_list_cppt'])->name('rm.pasien-inap.get_list_cppt');
    });
});
