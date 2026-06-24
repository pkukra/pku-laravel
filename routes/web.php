<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Cesemix\RanapMonitController;
use App\Http\Middleware\CheckRole;


use App\Http\Controllers\ICDImportController;

Route::get('/icd-import', [ICDImportController::class, 'form']);
Route::post('/icd-import', [ICDImportController::class, 'import'])->name('icd.import');

Route::get('/db', function () {
    return env('DB_SQLSIMRS_HOST') . ' <br> ' . env('DB_SQLEMR_HOST') . ' <br> ' . env('DB_HOST');
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

Route::prefix('casemix')->group(function () {
    Route::middleware('auth')->prefix('ranap-monit')->group(function () {
        Route::get('/list_kamar_bangsal', [RanapMonitController::class, 'list_kamar_bangsal'])->name('casemix.ranap-monit.list_kamar_bangsal');

        Route::get('/', [RanapMonitController::class, 'list_pasien'])->name('casemix.ranap-monit.list_pasien');
        Route::get('/list_pasien_data', [RanapMonitController::class, 'list_pasien_data'])->name('casemix.ranap-monit.list_pasien_data');
        Route::get('/list_billing_temp/{kode_reg}', [RanapMonitController::class, 'list_billing_temp'])->name('casemix.ranap-monit.list_billing_temp');

        Route::get('/list_procedure/{kode_reg}', [RanapMonitController::class, 'list_procedure'])->name('casemix.ranap-monit.list_procedure');
        Route::post('/save_procedure', [RanapMonitController::class, 'save_procedure'])->middleware(['auth', CheckRole::class . ':superadmin,koder'])->name('casemix.ranap-monit.save_procedure');
        Route::delete('/delete_procedure/{id}', [RanapMonitController::class, 'delete_procedure'])->middleware(['auth', CheckRole::class . ':superadmin,koder'])->name('casemix.ranap-monit.delete_procedure');

        Route::post('/update_monit_row/{kode_reg}', [RanapMonitController::class, 'update_monit_row'])->middleware(['auth'])->name('casemix.ranap-monit.update_monit_row');

        Route::get('/get_list_cppt/{kode_reg}', [RanapMonitController::class, 'get_list_cppt'])->name('casemix.pasien-inap.get_list_cppt');

        Route::get('/list_diagnosa/{kode_reg}', [RanapMonitController::class, 'list_diagnosa'])->name('casemix.ranap-monit.list_diagnosa');
        Route::post('/save_diagnosa', [RanapMonitController::class, 'save_diagnosa'])->middleware(['auth', CheckRole::class . ':superadmin,koder'])->name('casemix.ranap-monit.save_diagnosa');
        Route::delete('/delete_diagnosa/{id}', [RanapMonitController::class, 'delete_diagnosa'])->middleware(['auth', CheckRole::class . ':superadmin,koder'])->name('casemix.ranap-monit.delete_diagnosa');

        Route::get('/download-xls', [RanapMonitController::class, 'download_pasien_data'])->name('casemix.ranap-monit.download_pasien_data_xls');
    });
});

Route::get('/hasil_lab', function () {
    return response()->json([
        'status' => "ok",
        'data' => env("HASIL_LAB_URL"),
    ]);
})->name("common.lab_url");

// Muat file routes rm.php
require_once __DIR__ . '/rm.php';
require_once __DIR__ . '/klaim.php';

require __DIR__ . '/auth.php';
