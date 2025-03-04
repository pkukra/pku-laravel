<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\RM\PasienRujukanController;
use App\Http\Controllers\Cesemix\RanapMonitController;
use App\Http\Controllers\RM\EklaimController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/studentsdashboard', [StudentController::class, 'index'])->name('studentsdashboard.index');
    Route::post('/addStudent', [StudentController::class, 'store'])->name('addStudent.store');
    Route::patch('/updateStudent/{id}', [StudentController::class, 'update'])->name('updateStudent.update');
    Route::delete('/deleteStudent/{id}', [StudentController::class, 'destroy'])->name('deleteStudent.destroy');
});

Route::prefix('rm')->group(function () {
    Route::middleware('auth')->prefix('pasien-rujukan')->group(function () {
        Route::get('/', [PasienRujukanController::class, 'index'])->name('rm.pasien-rujukan.index');
        Route::get('/list/{no_rm}', [PasienRujukanController::class, 'index_data'])->name('rm.pasien-rujukan.list');
        Route::get('/detail/{kode_reg}', [PasienRujukanController::class, 'show'])->name('rm.pasien-rujukan.detail');
        Route::get('/detail_data/{kode_reg}', [PasienRujukanController::class, 'show_data'])->name('rm.pasien-rujukan.detail_data');
        Route::get('/get_nomer_sep/{kode_reg}/{kode_reg_kj}', [PasienRujukanController::class, 'get_nomer_sep'])->name('rm.pasien-rujukan.get_nomer_sep');
        Route::get('/get_keadaan_keluar_rs/{kode_reg}/', [PasienRujukanController::class, 'get_keadaan_keluar_rs'])->name('rm.pasien-rujukan.get_keadaan_keluar_rs');
        Route::get('/get_kunjungan_pasien/{kode_reg}/', [PasienRujukanController::class, 'get_kunjungan_pasien'])->name('rm.pasien-rujukan.get_kunjungan_pasien');

        Route::get('/list_diagnosa/{kode_reg}', [PasienRujukanController::class, 'list_diagnosa'])->name('rm.pasien-rujukan.list_diagnosa');
        Route::post('/cari_penyakit', [PasienRujukanController::class, 'cari_penyakit'])->name('rm.pasien-rujukan.cari_penyakit');
        Route::post('/save-diagnosa', [PasienRujukanController::class, 'save_diagnosa'])->name('rm.pasien-rujukan.save_diagnosa');
        Route::delete('/pasien-rujukan/diagnosa/{id}', [PasienRujukanController::class, 'delete_diagnosa'])->name('rm.pasien-rujukan.delete_diagnosa');

        Route::get('/list_procedure/{kode_reg}', [PasienRujukanController::class, 'list_procedure'])->name('rm.pasien-rujukan.list_procedure');
        Route::post('/cari_procedure', [PasienRujukanController::class, 'cari_procedure'])->name('rm.pasien-rujukan.cari_procedure');
        Route::post('/save-procedure', [PasienRujukanController::class, 'save_procedure'])->name('rm.pasien-rujukan.save_procedure');
        Route::delete('/pasien-rujukan/procedure/{id}', [PasienRujukanController::class, 'delete_procedure'])->name('rm.pasien-rujukan.delete_procedure');

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
});

Route::prefix('casemix')->group(function () {
    Route::middleware('auth')->prefix('ranap-monit')->group(function () {
        Route::get('/', [RanapMonitController::class, 'list_pasien'])->name('casemix.ranap-monit.list_pasien');
        Route::get('/list-pasien_data', [RanapMonitController::class, 'list_pasien_data'])->name('casemix.ranap-monit.list_pasien_data');

        Route::post('/update_monit_row/{kode_reg}', [RanapMonitController::class, 'update_monit_row'])->name('casemix.ranap-monit.update_monit_row');

        Route::delete('/delete_diagnosa/{id}', [RanapMonitController::class, 'delete_diagnosa'])->name('casemix.ranap-monit.delete_diagnosa');
        Route::get('/list_diagnosa/{kode_reg}', [RanapMonitController::class, 'list_diagnosa'])->name('casemix.ranap-monit.list_diagnosa');
        Route::post('/save_diagnosa', [RanapMonitController::class, 'save_diagnosa'])->name('casemix.ranap-monit.save_diagnosa');

        Route::get('/list_procedure/{kode_reg}', [RanapMonitController::class, 'list_procedure'])->name('casemix.ranap-monit.list_procedure');
        Route::post('/save_procedure', [RanapMonitController::class, 'save_procedure'])->name('casemix.ranap-monit.save_procedure');
        Route::delete('/delete_procedure/{id}', [RanapMonitController::class, 'delete_procedure'])->name('casemix.ranap-monit.delete_procedure');

        Route::get('/list_billing_temp/{kode_reg}', [RanapMonitController::class, 'list_billing_temp'])->name('casemix.ranap-monit.list_billing_temp');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/xxx/{no_sep}', [EklaimController::class, 'index_data']);
});

Route::get('/hasil_lab', function () {
    return response()->json([
        'status' => "ok",
        'data' => env("HASIL_LAB_URL"),
    ]);
})->name("common.lab_url");

require __DIR__ . '/auth.php';
