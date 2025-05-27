<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataVizController;
use Illuminate\Support\Facades\Route;

// Root: redirect ke login jika tamu, atau ke viz.index jika sudah login
// Root: selalu redirect ke viz.index
// Root: selalu redirect ke /viz
Route::redirect('/', 'viz');

// 2) Public: halaman visualisasi tanpa auth
Route::get('viz', [DataVizController::class, 'index'])->name('viz.index');

// Guest-only: form & proses login
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
});

// Authenticated-only: logout, dashboard, upload, dan viz
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard utama setelah login
    Route::get('dashboard', [DataVizController::class, 'dashboard'])->name('dashboard');

    // Upload data
    Route::get('upload', [DataVizController::class, 'showUploadForm'])->name('upload.form');
    Route::post('upload', [DataVizController::class, 'uploadData'])->name('upload.data');
});
