<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use League\Csv\Reader;
use App\Models\User;
use Carbon\Carbon;

class DataVizController extends Controller
{
    /**
     * Halaman Dashboard Admin: hitung jumlah user & CSV
     */
public function dashboard(Request $request)
{
    $userCount = User::count();
    $allFiles  = Storage::disk('local')->files();
    $csvCount  = count(array_filter($allFiles, fn($f) => str_ends_with($f, '.csv')));

    // 1) Distribusi Sentimen
    $labelsData = $this->loadCsv('data_with_labels');
    if ($labelsData instanceof RedirectResponse) {
        return $labelsData;
    }
    $distCounts = array_count_values(array_column($labelsData, 'label_str'));

    // 2) Confusion Matrix
    $cmCsv    = Storage::disk('local')->path('confusion_matrix.csv');
    $reader   = Reader::createFromPath($cmCsv, 'r');
    $reader->setHeaderOffset(0);
    $cmHeader = $reader->getHeader();
    $cmRows   = iterator_to_array($reader->getRecords());

    // 3) Akurasi Harian
    $predRaw = $this->loadCsv('df_full_predictions');
    if ($predRaw instanceof RedirectResponse) {
        return $predRaw;
    }
    $byDate = [];
    foreach ($predRaw as $row) {
        if (empty($row['tweet_date'])) {
            continue;
        }
        // parse tanggal dari format M/D/YYYY ke Y-m-d
        try {
            $d = Carbon::createFromFormat('n/j/Y', $row['tweet_date'])
                       ->format('Y-m-d');
        } catch (\Exception $e) {
            // jika gagal, lewati baris ini
            continue;
        }
        $true = $row['label'] ?? null;
        $pred = $row['predicted_label'] ?? null;

        $byDate[$d]['total']   = ($byDate[$d]['total'] ?? 0) + 1;
        $byDate[$d]['correct'] = ($byDate[$d]['correct'] ?? 0)
                               + (($true === $pred) ? 1 : 0);
    }
    ksort($byDate); // sudah dalam format Y-m-d, ksort cukup
    $dates      = array_keys($byDate);
    $accuracies = array_map(fn($g) => round($g['correct'] / $g['total'], 4), $byDate);

    // 4) Evaluation Metrics
    $evalRaw = $this->loadCsv('evaluation_metrics_full');
    if ($evalRaw instanceof RedirectResponse) {
        return $evalRaw;
    }
    $evalMetrics = [];
    foreach ($evalRaw as $row) {
        // kolom label ada di 'Unnamed: 0'
        if (
            isset($row[''], $row['precision'], $row['recall'], $row['f1-score'])
            && $row[''] !== ''
            && is_numeric($row['precision'])
        ) {
            $evalMetrics[] = [
                'label'     => $row[''],
                'precision' => (float) $row['precision'],
                'recall'    => (float) $row['recall'],
                // rename key jadi tanpa hyphen supaya mudah di Blade
                'f1_score'  => (float) $row['f1-score'],
                'support'   => isset($row['support']) ? (int) $row['support'] : null,
            ];
        }
    }

    return view('admin.dashboard', compact(
        'userCount',
        'csvCount',
        'distCounts',
        'cmHeader',
        'cmRows',
        'dates',
        'accuracies',
        'evalMetrics'
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
    // 1) Data Sentimen
    $labelsData = $this->loadCsv('data_with_labels');
    if ($labelsData instanceof RedirectResponse) {
        return $labelsData;
    }

    // Normalize ke bahasa Inggris lowercase
    foreach ($labelsData as &$row) {
        $raw = strtolower($row['label_str']);
        $row['label_norm'] = match ($raw) {
            'positif' => 'positive',
            'negatif' => 'negative',
            'netral'  => 'neutral',
            default   => 'neutral'
        };
    }
    unset($row);

    // Pie chart: distribusi berdasarkan label_norm
    $labelsSent           = array_column($labelsData, 'label_norm');
    $dataSent             = array_count_values($labelsSent);

    // 2) Word Frequencies
    $textsAll             = array_filter(array_column($labelsData, 'clean_text'));
    $combinedAll          = implode(' ', $textsAll);
    $wordsAll             = str_word_count(strtolower($combinedAll), 1);
    $freqsAll             = array_count_values($wordsAll);
    arsort($freqsAll);
    $wordFrequencies      = array_slice($freqsAll, 0, 100, true);
    $topFeatures          = array_slice($freqsAll, 0, 10, true);

    $labelsList           = ['positive','negative','neutral'];
    $wordFrequenciesByLabel = [];
    $topFeaturesByLabel     = [];
    foreach ($labelsList as $lbl) {
        $texts    = array_column(
                      array_filter($labelsData, fn($r) => $r['label_norm'] === $lbl),
                      'clean_text'
                    );
        $words    = str_word_count(strtolower(implode(' ', $texts)), 1);
        $freqs    = array_count_values($words);
        arsort($freqs);

        $wordFrequenciesByLabel[$lbl] = array_slice($freqs, 0, 100, true);
        $topFeaturesByLabel[$lbl]     = array_slice($freqs, 0, 10, true);
    }

    // 3) Confusion Matrix
    $cmRecords = $this->loadCsv('confusion_matrix');
    if ($cmRecords instanceof RedirectResponse) {
        return $cmRecords;
    }
    $reader      = Reader::createFromPath(
                       Storage::disk('local')->path('confusion_matrix.csv'),
                       'r'
                   );
    $reader->setHeaderOffset(0);
    $headers     = $reader->getHeader();
    $trueLabelKey= array_shift($headers);   // ambil kolom pertama sebagai label
    $cmCols      = $headers;                // sisa header jadi kolom prediksi
    $cmRows      = iterator_to_array($reader->getRecords());

    // 4) Evaluation Metrics (dinamis)
    $evalRaw     = $this->loadCsv('evaluation_metrics_full');
    if ($evalRaw instanceof RedirectResponse) {
        return $evalRaw;
    }
    // cari key kolom pertama (label)
    $firstKeys   = array_keys($evalRaw[0] ?? []);
    $labelKey    = $firstKeys[0] ?? '';

    $classes     = [];
    $precision   = [];
    $recall      = [];
    $f1          = [];

    foreach ($evalRaw as $row) {
        if (
            isset($row[$labelKey], $row['precision'], $row['recall'], $row['f1-score'])
            && $row[$labelKey] !== ''
            && is_numeric($row['precision'])
            && is_numeric($row['recall'])
            && is_numeric($row['f1-score'])
        ) {
            $classes[]   = $row[$labelKey];
            $precision[] = (float) $row['precision'];
            $recall[]    = (float) $row['recall'];
            $f1[]        = (float) $row['f1-score'];
        }
    }


    // Kirim semua ke view
    return view('viz.index', compact(
        'dataSent',
        'wordFrequencies', 'topFeatures',
        'wordFrequenciesByLabel', 'topFeaturesByLabel',
        'cmCols', 'cmRows', 'trueLabelKey',
        'classes', 'precision', 'recall', 'f1'
    ));
}

}
