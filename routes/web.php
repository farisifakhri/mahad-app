<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AttendanceController;
use App\Http\Controllers\Portal\ChildController;
use App\Http\Controllers\Portal\MediaController;
use App\Http\Controllers\Portal\ReportController;
use App\Http\Controllers\Portal\SubmissionController;
use App\Http\Controllers\Portal\ViolationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::get('/dashboard', HomeController::class)->middleware('auth')->name('dashboard');

Route::prefix('portal')->name('portal.')->middleware('auth')->group(function () {
    Route::view('/onboarding', 'portal.onboarding')->middleware('role:mahasantri')->name('onboarding');
    Route::middleware(['role:mahasantri', 'student.linked'])->group(function () {
        Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi');
        Route::get('/pengajuan', [SubmissionController::class, 'index'])->name('pengajuan');
        Route::post('/pengajuan', [SubmissionController::class, 'store'])->name('pengajuan.store');
        Route::get('/pelanggaran', [ViolationController::class, 'index'])->name('pelanggaran');
    });
    Route::get('/anak', [ChildController::class, 'index'])->middleware('role:orang_tua')->name('anak');
    Route::get('/laporan', [ReportController::class, 'index'])->middleware('role:mahasantri|orang_tua')->name('laporan');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/media/{media}', MediaController::class)->middleware('auth')->name('media.download');
