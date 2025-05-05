<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataVizController;
use Illuminate\Support\Facades\Route;

// Root: redirect ke login jika tamu, atau ke viz.index jika sudah login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('viz.index')
        : redirect()->route('login');
});

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

    // Halaman visualisasi publik
    Route::get('viz', [DataVizController::class, 'index'])->name('viz.index');
});
