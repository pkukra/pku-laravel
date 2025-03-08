<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Cesemix\RanapMonitController;
use App\Http\Controllers\RM\EklaimController;

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
        Route::get('/', [RanapMonitController::class, 'list_pasien'])->name('casemix.ranap-monit.list_pasien');
        Route::post('/update_monit_row/{kode_reg}', [RanapMonitController::class, 'update_monit_row'])->name('casemix.ranap-monit.update_monit_row');
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

// Muat file routes rm.php
require_once __DIR__ . '/rm.php';

require __DIR__ . '/auth.php';
