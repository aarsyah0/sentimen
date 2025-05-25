<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use League\Csv\Reader;
use App\Models\User;

class DataVizController extends Controller
{
    /**
     * Halaman Dashboard Admin: hitung jumlah user & CSV
     */
public function dashboard(Request $request)
{
    $userCount = User::count();
    $allFiles  = Storage::disk('local')->files();
    $csvFiles  = array_filter($allFiles, fn($f) => str_ends_with($f, '.csv'));
    $csvCount  = count($csvFiles);

    // Load distribusi sentimen
    $labelsData = $this->loadCsv('data_with_labels');
    if ($labelsData instanceof RedirectResponse) {
        return $labelsData; // redirect if file missing
    }
    $labelsSent = array_column($labelsData, 'label_str');
    $distCounts = array_count_values($labelsSent);

    // Load confusion matrix
    $cmRecords = $this->loadCsv('confusion_matrix');
    if ($cmRecords instanceof RedirectResponse) {
        return $cmRecords; // redirect if file missing
    }
    $reader = Reader::createFromPath(Storage::disk('local')->path('confusion_matrix.csv'), 'r');
    $reader->setHeaderOffset(0);
    $cmHeader = $reader->getHeader();
    $cmRows = iterator_to_array($reader->getRecords());

    // Load akurasi harian
    $predRaw = $this->loadCsv('df_full_predictions');
    if ($predRaw instanceof RedirectResponse) {
        return $predRaw;
    }
    $byDate = [];
    foreach ($predRaw as $row) {
        $d = $row['tweet_date'];
        $byDate[$d]['total'] = ($byDate[$d]['total'] ?? 0) + 1;
        $byDate[$d]['correct'] = ($byDate[$d]['correct'] ?? 0)
                               + (($row['label_name'] === $row['predicted_label']) ? 1 : 0);
    }
    uksort($byDate, fn($a, $b) => strtotime($a) <=> strtotime($b));
    $dates = array_keys($byDate);
    $accuracies = array_map(fn($g) => round($g['correct'] / $g['total'], 4), $byDate);

    // Load Evaluation Metrics
$evalRaw = $this->loadCsv('evaluation_metrics_full');
$evalMetrics = array_filter($evalRaw, function ($row) {
    return isset($row[''], $row['precision'], $row['recall'], $row['f1-score']) &&
           $row[''] !== '' &&
           is_numeric($row['precision']);
});
$evalMetrics = array_values($evalMetrics);


    return view('admin.dashboard', compact(
        'userCount', 'csvCount', 'distCounts', 'dates', 'accuracies', 'evalMetrics',
        'cmHeader', 'cmRows'   // <-- add these here!
    ));
}


    /**
     * Tampilkan form upload CSV
     */
    public function showUploadForm()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }
        return view('admin.upload_data');
    }

    /**
     * Proses upload keempat file CSV
     */
    public function uploadData(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $fields = [
            'confusion_matrix',
            'data_with_labels',
            'df_full_predictions',
            'evaluation_metrics_full',
        ];
        // Validasi tiap field
        $rules = [];
        foreach ($fields as $f) {
            $rules[$f] = 'required|file|mimes:csv,txt';
        }
        $request->validate($rules);

        // Simpan
        foreach ($fields as $f) {
            $file     = $request->file($f);
            $filename = "{$f}.csv";
            Storage::disk('local')->putFileAs('', $file, $filename);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Keempat file CSV berhasil di-upload.');
    }

    /**
     * Helper: load CSV atau redirect ke upload
     */
    protected function loadCsv(string $name)
    {
        $filename = "{$name}.csv";
        if (! Storage::disk('local')->exists($filename)) {
            return redirect()
                ->route('admin.upload.form')
                ->with('error', "File {$filename} belum di-upload.");
        }
        $path = Storage::disk('local')->path($filename);
        $csv  = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        return iterator_to_array($csv->getRecords());
        dd(Storage::exists('evaluation_metrics_full.csv'));

    }

    /**
     * Halaman visualisasi Sentiment Analysis
     */
public function index()
{
    $labelsData = $this->loadCsv('data_with_labels');
    if ($labelsData instanceof RedirectResponse) {
        return $labelsData;
    }

    // --- Normalize label_str ke bahasa Inggris lowercase ---
    foreach ($labelsData as &$row) {
        $raw = strtolower($row['label_str']); // 'positif','negatif','netral'
        if ($raw === 'positif')    $row['label_norm'] = 'positive';
        elseif ($raw === 'negatif') $row['label_norm'] = 'negative';
        elseif ($raw === 'netral')  $row['label_norm'] = 'neutral';
        else                        $row['label_norm'] = 'neutral';
    }
    unset($row);

    // Pie chart: gunakan 'label_norm'
    $labelsSent = array_column($labelsData, 'label_norm');
    $dataSent   = array_count_values($labelsSent);

    // --- Overall word frequencies & top features ---
    $textsAll    = array_filter(array_column($labelsData, 'clean_text'));
    $combinedAll = implode(' ', $textsAll);
    $wordsAll    = str_word_count(strtolower($combinedAll), 1);
    $freqsAll    = array_count_values($wordsAll);
    arsort($freqsAll);
    $wordFrequencies = array_slice($freqsAll, 0, 100, true);
    $topFeatures     = array_slice($freqsAll, 0, 10, true);

    // --- Per‐label word frequencies & top features ---
    $labelsList = ['positive','negative','neutral'];
    $wordFrequenciesByLabel = [];
    $topFeaturesByLabel     = [];

    foreach ($labelsList as $lbl) {
        $texts = array_column(
            array_filter($labelsData, fn($row) => $row['label_norm'] === $lbl),
            'clean_text'
        );
        $combined = implode(' ', $texts);
        $words    = str_word_count(strtolower($combined), 1);
        $freqs    = array_count_values($words);
        arsort($freqs);

        $wordFrequenciesByLabel[$lbl] = array_slice($freqs, 0, 100, true);
        $topFeaturesByLabel[$lbl]     = array_slice($freqs, 0, 10, true);
    }

    // 2) Confusion Matrix
    $cmRecords = $this->loadCsv('confusion_matrix');
    if ($cmRecords instanceof RedirectResponse) {
        return $cmRecords;
    }
    $reader       = Reader::createFromPath(Storage::disk('local')->path('confusion_matrix.csv'), 'r');
    $reader->setHeaderOffset(0);
    $headers      = $reader->getHeader();
    $trueLabelKey = array_shift($headers);
    $cmCols       = $headers;
    $cmRows       = iterator_to_array($reader->getRecords());

    // 3) Evaluation Metrics
    $evalRaw = $this->loadCsv('evaluation_metrics_full');
    if ($evalRaw instanceof RedirectResponse) {
        return $evalRaw;
    }
    $evalFiltered = array_filter($evalRaw, function ($row) {
        return isset($row[''], $row['precision'], $row['recall'], $row['f1-score'])
            && $row[''] !== ''
            && is_numeric($row['precision'])
            && is_numeric($row['recall'])
            && is_numeric($row['f1-score']);
    });
    $classes   = array_column($evalFiltered, '');
    $precision = array_map('floatval', array_column($evalFiltered, 'precision'));
    $recall    = array_map('floatval', array_column($evalFiltered, 'recall'));
    $f1        = array_map('floatval', array_column($evalFiltered, 'f1-score'));

    // 4) Akurasi Harian
    $predRaw = $this->loadCsv('df_full_predictions');
    if ($predRaw instanceof RedirectResponse) {
        return $predRaw;
    }
    $byDate = [];
    foreach ($predRaw as $row) {
        $d = $row['tweet_date'];
        $byDate[$d]['total']   = ($byDate[$d]['total'] ?? 0) + 1;
        $byDate[$d]['correct'] = ($byDate[$d]['correct'] ?? 0)
                               + (($row['label_name'] === $row['predicted_label']) ? 1 : 0);
    }
    uksort($byDate, fn($a, $b) => strtotime($a) <=> strtotime($b));
    $dates = array_keys($byDate);
    $accs  = array_map(fn($g) => round($g['correct'] / $g['total'], 4), $byDate);

    // Return ke view dengan semua variabel
    return view('viz.index', compact(
        'dataSent',
        'wordFrequencies', 'topFeatures',
        'wordFrequenciesByLabel', 'topFeaturesByLabel',
        'cmCols', 'cmRows', 'trueLabelKey',
        'classes', 'precision', 'recall', 'f1',
        'dates', 'accs'
    ));
}
}
