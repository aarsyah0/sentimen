<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataVizController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;
use App\Http\Controllers\TweetController;
use App\Http\Controllers\SentimentController;

Route::get('/new', [DataController::class, 'form'])->name('upload.form'); // <-- tambahkan name di sini
Route::post('/upload', [DataController::class, 'upload'])->name('upload.submit');
Route::get('/data', [DataController::class, 'result'])->name('data.result');
Route::get('/download', [DataController::class, 'download'])->name('data.download');

Route::get('/', [SentimentController::class, 'showDashboard'])->name('index');
Route::get('/sentiment/upload', [SentimentController::class, 'showUploadForm'])->name('sentiment.upload');
Route::post('/sentiment/upload', [SentimentController::class, 'handleUploadAndTrain'])->name('sentiment.upload.handle');
Route::get('/sentiment/report', [SentimentController::class, 'showReport'])->name('sentiment.report');
Route::get('/sentiment/infer', [SentimentController::class, 'showInferForm'])->name('sentiment.infer');
Route::post('/sentiment/infer', [SentimentController::class, 'doInfer'])->name('sentiment.infer.do');



// Route untuk tampilan form
Route::get('/scrap-tweets', [TweetController::class, 'index'])->name('scrap.tweets.form');

// Route untuk memproses POST scraping
Route::post('/scrap-tweets', [TweetController::class, 'scrape'])->name('scrap.tweets');

Route::get('/sentiment/dashboard', [SentimentController::class, 'showDashboard'])
    ->name('sentiment.dashboard');
// Route::redirect('/', 'viz');


// Route::get('viz', [DataVizController::class, 'index'])->name('viz.index');


// Route::middleware('guest')->group(function () {
//     Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
//     Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
// });


// Route::middleware('auth')->group(function () {

//     Route::post('logout', [AuthController::class, 'logout'])->name('logout');

//     Route::get('dashboard', [DataVizController::class, 'dashboard'])->name('dashboard');

//     Route::get('upload', [DataVizController::class, 'showUploadForm'])->name('upload.form');
//     Route::post('upload', [DataVizController::class, 'uploadData'])->name('upload.data');
// });
