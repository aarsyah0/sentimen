<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


class DataController extends Controller
{
    public function form()
    {
        return view('form');
    }

    public function upload(Request $request)
{
    set_time_limit(300);
    $request->validate([
        'files'   => 'required|array',
        'files.*' => 'file|mimes:csv,txt'
    ]);

   // Simpan file baru sambil mempertahankan kamus
$uploadPath = storage_path('app/uploads');
// Pastikan folder ada
if (!file_exists($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}
// Hapus semua CSV selain kamus
foreach (glob($uploadPath.'/*.csv') as $f) {
    $name = basename($f);
    if (in_array($name, ['kbbi.csv', 'singkatan-lib.csv'])) {
        continue;
    }
    @unlink($f);
}
    // (opsional) jika ada file lain di processed, bersihkan semua:
    $processedDir = storage_path('app/processed');
    foreach (glob($processedDir.'/*.csv') as $f) {
        @unlink($f);
    }

    // Siapkan folder uploads
    $uploadPath = storage_path('app/uploads');
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    // Bersihkan folder uploads agar hanya berisi file baru
    foreach (glob($uploadPath.'/*.csv') as $f) {
        @unlink($f);
    }

    // Simpan setiap file upload
    foreach ($request->file('files') as $file) {
        $orig = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();
        $name = $orig . '_' . time() . '.' . $ext;
        $file->move($uploadPath, $name);
    }

    // Jalankan skrip Python
    $scriptPath = base_path('scripts/preprocess.py');
    $venvPython = '/Users/sutanarsyahnugraha/Documents/Joki/sentiment/scripts/venv/bin/python';
    $command = escapeshellcmd("$venvPython $scriptPath") . ' 2>&1';
    exec($command, $output, $exitCode);
    // dd($output, $exitCode);

    \Log::info('Python preprocess output:', $output);
    \Log::info('Python preprocess exitCode: '.$exitCode);

    if ($exitCode !== 0) {
        return back()->with('error', 'Preprocessing gagal dijalankan.')->with('debug', $output);
    }

    return redirect()->route('data.result')->with('success', 'Preprocessing berhasil.');
}



public function result(Request $request)
{
    $file = storage_path('app/processed/data.csv');

    if (!file_exists($file)) {
        return back()->with('error', 'File hasil tidak ditemukan.');
    }

    $rows = array_map('str_getcsv', file($file));
    if (empty($rows)) {
        return back()->with('error', 'File hasil kosong.');
    }

    // Extract headers and raw data
    $headers = array_shift($rows);
    $dataArray = array_map(fn($row) => array_combine($headers, $row), $rows);

    // Wrap in a Collection
    $collection = collect($dataArray);

    // Pagination parameters
    $perPage = 10;
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $currentItems = $collection
        ->slice(($currentPage - 1) * $perPage, $perPage)
        ->all();

    // Create paginator
    $paginator = new LengthAwarePaginator(
        $currentItems,
        $collection->count(),
        $perPage,
        $currentPage,
        [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]
    );

    // Pass the paginator to your view as $data
    return view('results', ['data' => $paginator]);
}

    public function download()
    {
        $path = storage_path('app/processed/data.csv');
        if (!file_exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download($path, 'preprocessed_data.csv');
    }
}
