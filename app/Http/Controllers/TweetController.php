<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TweetController extends Controller
{
    // Menampilkan form input
    public function index()
    {
        return view('tweet.scrap_tweets');
    }

    // Memproses scraping
    public function scrape(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
            'limit'   => 'nullable|integer',
            'token'   => 'required|string',
        ]);

        // Tambahkan "lang:id" ke keyword
        $keyword = trim($request->keyword) . ' lang:id';

        // Limit default 100 jika tidak diisi
        $limit = $request->limit ?? 100;

        // Nama file output berdasarkan keyword
        $safeKeyword = preg_replace('/[^a-z0-9_]/i', '', str_replace(' ', '_', strtolower($request->keyword)));
        $output = "{$safeKeyword}.csv";

        // Jalankan command dengan parameter dari input
        Artisan::call('scrap:tweets', [
            '--keyword' => $keyword,
            '--limit'   => $limit,
            '--output'  => $output,
            '--token'   => $request->token,
        ]);

        // Ambil output dari Artisan dan kirim ke view
        $outputText = Artisan::output();
        return back()
    ->with('status', nl2br($outputText))
    ->with('csv_filename', $output); // penting untuk tombol download

    }
}
