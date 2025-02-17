<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\RM\PasienRujukanController;
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

Route::get('/dashboard', function () {
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

        Route::get('/get_detail_tarif/{kode_reg}', [PasienRujukanController::class, 'get_detail_tarif_transakasi']);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/xxx/{no_sep}', [EklaimController::class, 'index_data']);
});

require __DIR__ . '/auth.php';
