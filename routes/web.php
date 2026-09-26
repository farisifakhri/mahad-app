<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AttendanceController;
use App\Http\Controllers\Portal\ChildController;
use App\Http\Controllers\Portal\SubmissionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::get('/dashboard', HomeController::class)->middleware('auth')->name('dashboard');

Route::prefix('portal')->name('portal.')->middleware('auth')->group(function () {
    Route::middleware('role:mahasantri')->group(function () {
        Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi');
        Route::get('/pengajuan', [SubmissionController::class, 'index'])->name('pengajuan');
    });
    Route::get('/anak', [ChildController::class, 'index'])->middleware('role:orang_tua')->name('anak');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
