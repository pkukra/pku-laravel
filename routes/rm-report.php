<?php

use App\Http\Controllers\ReportRM\RL51RajalController;
use App\Http\Controllers\ReportRM\RL51RanapController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


Route::prefix('rm-report')->group(function () {
    Route::get('/rajal', [RL51RajalController::class, 'all_penyakit_index'])->name('rm_report.all_penyakit_index');
    Route::get('/rajal/all_penyakit_data', [RL51RajalController::class, 'all_penyakit_data'])->name('rm_report.all_penyakit_index_data');

    Route::get('/by_code', [RL51RajalController::class, 'index'])->name('rm_report.by_code.index');
    Route::get('/by_code_data', [RL51RajalController::class, 'index_data'])->name('rm_report.by_code_data.index_data');
    

    //ranap
    Route::get('/ranap', [RL51RanapController::class, 'all_penyakit_index'])->name('rm_report.all_penyakit_ranap_index');
    Route::get('/ranap/all_penyakit_data', [RL51RanapController::class, 'all_penyakit_data'])->name('rm_report.all_penyakit_ranap_index_data');
    
    Route::get('/ranap/by_code', [RL51RanapController::class, 'index'])->name('rm_report.ranap_by_code');
    Route::get('/ranap/by_code_data', [RL51RanapController::class, 'index_data'])->name('rm_report.ranap_by_code_data');

    
});
