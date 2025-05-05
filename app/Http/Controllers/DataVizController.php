<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use App\Models\User;

class DataVizController extends Controller
{
    /**
     * Tampilkan dashboard/admin menu
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();

        // Hitung total user
        $userCount = User::count();

        // Ambil semua file CSV di storage/app secara dinamis
        $allFiles = Storage::disk('local')->files();
        $csvFiles = array_filter($allFiles, fn($file) => str_ends_with($file, '.csv'));
        $csvCount = count($csvFiles);

        // Load data_labeling untuk line chart
        $records = $this->loadCsv('data_labeling', $request);
        $labelCounts = is_array($records)
            ? array_count_values(array_column($records, 'sentiment'))
            : [];

        // Load sentiment distribution for pie chart
        $sentDist = $this->loadCsv('sentiment_distribution', $request);
        $labelsSent = is_array($sentDist) ? array_column($sentDist, 'true_label') : [];
        $dataSent = is_array($sentDist) ? array_column($sentDist, 'count') : [];

        // Return view sesuai role dengan data dinamis
        return $user->role === 'admin'
            ? view('admin.dashboard', compact('userCount', 'csvCount', 'labelCounts', 'labelsSent', 'dataSent'))
            : view('user.dashboard', compact('userCount', 'csvCount', 'labelCounts', 'labelsSent', 'dataSent'));
    }



    /**
     * Form upload CSV (admin only)
     */
    public function showUploadForm()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        return view('admin.upload_data');
    }

    /**
     * Proses upload lima file CSV (admin only)
     */
    public function uploadData(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $fields = [
            'sentiment_distribution',
            'confusion_matrix',
            'evaluation_metrics',
            'top_features',
            'data_labeling',
        ];

        // Validasi tiap file CSV
        $rules = [];
        foreach ($fields as $f) {
            $rules[$f] = 'required|file|mimes:csv,txt';
        }
        $request->validate($rules);

        // Simpan file ke storage/app
        foreach ($fields as $field) {
            $file     = $request->file($field);
            $filename = "{$field}.csv";
            Storage::disk('local')->putFileAs('', $file, $filename);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Semua file CSV berhasil di-upload.');
    }

    /**
     * Helper: load CSV records atau redirect jika tidak ada
     */
    protected function loadCsv(string $field, Request $request)
    {
        $filename = "{$field}.csv";

        if (! Storage::disk('local')->exists($filename)) {
            return redirect()
                ->route('upload.form')
                ->with('error', "File “{$filename}” belum di-upload.");
        }

        $path = Storage::disk('local')->path($filename);
        $csv  = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        return iterator_to_array($csv->getRecords());
    }

    /**
     * Tampilkan visualisasi publik
     */
    public function index(Request $request)
    {
        // 1) Sentiment Distribution
        $sent = $this->loadCsv('sentiment_distribution', $request);
        if ($sent instanceof \Illuminate\Http\RedirectResponse) {
            return $sent;
        }
        $labelsSent = array_column($sent, 'true_label');
        $dataSent   = array_column($sent, 'count');

        // 2) Confusion Matrix
        $cmRecords = $this->loadCsv('confusion_matrix', $request);
        if ($cmRecords instanceof \Illuminate\Http\RedirectResponse) {
            return $cmRecords;
        }
        $reader = Reader::createFromPath(
            Storage::disk('local')->path('confusion_matrix.csv'),
            'r'
        );
        $reader->setHeaderOffset(0);
        $headers      = $reader->getHeader();
        $trueLabelKey = array_shift($headers);
        $cmCols       = $headers;
        $cmRows       = iterator_to_array($reader->getRecords());

        // 3) Evaluation Metrics
        $eval = $this->loadCsv('evaluation_metrics', $request);
        if ($eval instanceof \Illuminate\Http\RedirectResponse) {
            return $eval;
        }
        $classes   = array_column($eval, 'kelas');
        $precision = array_column($eval, 'precision');
        $recall    = array_column($eval, 'recall');
        $f1        = array_column($eval, 'f1');

        // 4) Top Features
        $feats      = $this->loadCsv('top_features', $request);
        if ($feats instanceof \Illuminate\Http\RedirectResponse) {
            return $feats;
        }
        $featNames  = array_column($feats, 'feature');
        $featScores = array_map('intval', array_column($feats, 'score'));

        // 5) Word Cloud & Line Chart Data
        $records = $this->loadCsv('data_labeling', $request);
        if ($records instanceof \Illuminate\Http\RedirectResponse) {
            return $records;
        }

        // Hitung jumlah record per sentiment untuk line chart
        $labelCounts = array_count_values(array_column($records, 'sentiment'));

        // Siapkan data word cloud
        $texts = [];
        foreach ($records as $r) {
            $texts[$r['sentiment']][] = $r['text_without_stopwords'];
        }
        $wordLists = [];
        foreach ($texts as $sent => $arr) {
            $freq = [];
            foreach ($arr as $line) {
                foreach (preg_split('/\s+/', $line) as $w) {
                    $w = strtolower(trim($w));
                    if (strlen($w) < 2) continue;
                    $freq[$w] = ($freq[$w] ?? 0) + 1;
                }
            }
            arsort($freq);
            $top = array_slice($freq, 0, 200, true);
            $wordLists[$sent] = array_map(
                fn($w, $c) => [$w, $c],
                array_keys($top),
                array_values($top)
            );
        }

        return view('viz.index', compact(
            'labelsSent','dataSent',
            'cmRows','cmCols','trueLabelKey',
            'classes','precision','recall','f1',
            'featNames','featScores',
            'wordLists',
            'labelCounts' // variabel untuk line chart
        ));
    }
}
