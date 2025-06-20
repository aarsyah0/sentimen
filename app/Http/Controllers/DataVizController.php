<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        // Hitung berapa file .csv di storage/app/public
        $allPublic = Storage::disk('public')->files();
        $csvCount  = count(array_filter($allPublic, fn($f) => str_ends_with($f, '.csv')));

        // 1) Distribusi Sentimen (dari data_with_labels.csv)
        $labelsData = $this->loadCsv('data_with_labels');
        $distCounts = [];
        if (count($labelsData['records']) > 0) {
            $distCounts = array_count_values(
                array_column($labelsData['records'], 'label_str')
            );
        }

        // 2) Confusion Matrix (training) dari confusion_matrix.csv
        $cmTrain = $this->loadCsv('confusion_matrix');
        $cmHeader = $cmTrain['headers'];
        $cmRows   = $cmTrain['records'];

        // 3) Evaluation Metrics (dari evaluation_metrics_full.csv)
        $evalData   = $this->loadCsv('evaluation_metrics_full');
        $evalMetrics = [];

        $firstRow = reset($evalData['records']); // false jika kosong
        if ($firstRow !== false) {
            $firstKeys = array_keys($firstRow);
            $labelKey  = $firstKeys[0] ?? '';

            foreach ($evalData['records'] as $row) {
                if (
                    isset($row[$labelKey], $row['precision'], $row['recall'], $row['f1-score'])
                    && $row[$labelKey] !== ''
                    && is_numeric($row['precision'])
                    && is_numeric($row['recall'])
                    && is_numeric($row['f1-score'])
                ) {
                    $evalMetrics[] = [
                        'label'     => $row[$labelKey],
                        'precision' => (float) $row['precision'],
                        'recall'    => (float) $row['recall'],
                        'f1_score'  => (float) $row['f1-score'],
                        'support'   => isset($row['support']) ? (int) $row['support'] : null,
                    ];
                }
            }
        }

        return view('admin.dashboard', compact(
            'userCount',
            'csvCount',
            'distCounts',
            'cmHeader',
            'cmRows',
            'evalMetrics',
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
     * Proses upload file CSV ke storage/app/public
     */
    public function uploadData(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $fields = [
            'confusion_matrix',
            'data_with_labels',
            'evaluation_metrics_full',
            'confusion_matrixuji',
            'evaluation_metricsuji',
            'top_features_per_class', // ini opsional, bisa diisi atau tidak
            // jika butuh upload top_features_per_class via form, tambahkan di sini
        ];

        // Validasi: setiap field harus file CSV/TXT
        $rules = [];
        foreach ($fields as $f) {
            $rules[$f] = 'required|file|mimes:csv,txt';
        }
        $request->validate($rules);

        // Simpan semua ke disk “public” (storage/app/public)
        foreach ($fields as $f) {
            $file     = $request->file($f);
            $filename = "{$f}.csv";
            Storage::disk('public')->putFileAs('', $file, $filename);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'File-file CSV berhasil di‐upload ke storage/app/public.');
    }

    /**
     * Helper: load CSV dari storage/app/public/{name}.csv
     * SELALU mengembalikan array ['headers'=>[], 'records'=>[]]
     */
    protected function loadCsv(string $name): array
    {
        $filename = "{$name}.csv";

        if (! Storage::disk('public')->exists($filename)) {
            return [
                'headers' => [],
                'records' => [],
            ];
        }

        $path   = Storage::disk('public')->path($filename);
        $reader = Reader::createFromPath($path, 'r');
        $reader->setHeaderOffset(0);

        return [
            'headers' => $reader->getHeader(),
            'records' => iterator_to_array($reader->getRecords()),
        ];
    }

    /**
     * Helper: load top features per class dari CSV storage/app/public/top_features_per_class.csv
     * Mengembalikan array: ['positive' => [...], 'negative' => [...], 'neutral' => [...]]
     */
    protected function loadTopFeaturesPerClass(): array
{
    $filename = 'top_features_per_class.csv';
    if (! Storage::disk('public')->exists($filename)) {
        return [];
    }
    $path = Storage::disk('public')->path($filename);
    $reader = Reader::createFromPath($path, 'r');
    $reader->setHeaderOffset(0);
    $records = iterator_to_array($reader->getRecords());

    $result = [];
    // Mapping nama kelas CSV (bisa kapital) ke key lowercase English
    $mapping = [
        'positif'  => 'positive',
        'positive' => 'positive',
        'negatif'  => 'negative',
        'negative' => 'negative',
        'netral'   => 'neutral',
        'neutral'  => 'neutral',
    ];
    foreach ($records as $row) {
        $clsRaw = $row['class'] ?? null;
        $feat   = $row['feature'] ?? null;
        $rank   = $row['rank'] ?? null;
        if ($clsRaw === null || $feat === null || $rank === null) {
            continue;
        }
        $clsKey = strtolower(trim($clsRaw));
        $labelNorm = $mapping[$clsKey] ?? strtolower($clsKey);
        // Cast rank ke integer jika numeric
        $rankInt = is_numeric($rank) ? (int)$rank : 0;
        if (! isset($result[$labelNorm])) {
            $result[$labelNorm] = [];
        }
        // Gunakan feature string sebagai key, rank sebagai value
        $result[$labelNorm][ $feat ] = $rankInt;
    }
    return $result;
}
    /**
     * Halaman visualisasi Sentiment Analysis (gunakan data dari disk “public”)
     */
    public function index()
{
    // 1) Data Sentimen
    $labelsData = $this->loadCsv('data_with_labels');
    foreach ($labelsData['records'] as &$row) {
        $raw = strtolower($row['label_str'] ?? '');
        $row['label_norm'] = match ($raw) {
            'positif' => 'positive',
            'negatif' => 'negative',
            'netral'  => 'neutral',
            default   => 'neutral',
        };
    }
    unset($row);

    // Distribusi Sentimen
    $labelsSent = array_column($labelsData['records'], 'label_norm');
    $dataSent   = array_count_values($labelsSent);

    // 2) Word Frequencies global (jika masih ingin digunakan)
    $textsAll    = array_filter(array_column($labelsData['records'], 'clean_text'));
    $combinedAll = implode(' ', $textsAll);
    $wordsAll    = str_word_count(strtolower($combinedAll), 1);
    $freqsAll    = array_count_values($wordsAll);
    arsort($freqsAll);
    $wordFrequencies = array_slice($freqsAll, 0, 100, true);

    // 3) Word Frequencies per label untuk WordCloud per label
    $labelsList = ['positive', 'negative', 'neutral'];
    $wordFrequenciesByLabel = [];
    foreach ($labelsList as $lbl) {
        // Kumpulkan semua clean_text dengan label_norm == $lbl
        $texts = array_column(
            array_filter($labelsData['records'], fn($r) => ($r['label_norm'] ?? '') === $lbl),
            'clean_text'
        );
        if (empty($texts)) {
            $wordFrequenciesByLabel[$lbl] = [];
            continue;
        }
        $combined = implode(' ', array_filter($texts));
        $words = str_word_count(strtolower($combined), 1);
        $freqs = array_count_values($words);
        arsort($freqs);
        // Ambil top N (misal 100) untuk performa WordCloud
        $wordFrequenciesByLabel[$lbl] = array_slice($freqs, 0, 100, true);
    }

    // 4) Top Features per Label dari CSV (jika masih diperlukan)
    $topFeaturesByLabel = $this->loadTopFeaturesPerClass();

    // 5) Confusion Matrix (training)
    $cmTrain = $this->loadCsv('confusion_matrix');
    if (count($cmTrain['headers']) > 0) {
        $headers      = $cmTrain['headers'];
        $trueLabelKey = array_shift($headers);
        $cmCols       = $headers;
        $cmRows       = $cmTrain['records'];
    } else {
        $trueLabelKey = '';
        $cmCols       = [];
        $cmRows       = [];
    }

    // 6) Confusion Matrix (Uji)
    $cmUji = $this->loadCsv('confusion_matrixuji');
    if (count($cmUji['headers']) > 0) {
        $headersUji      = $cmUji['headers'];
        $trueLabelKeyUji = array_shift($headersUji);
        $cmColsUji       = $headersUji;
        $cmRowsUji       = $cmUji['records'];
    } else {
        $trueLabelKeyUji = '';
        $cmColsUji       = [];
        $cmRowsUji       = [];
    }

    // 7) Evaluation Metrics (training)
    $evalData = $this->loadCsv('evaluation_metrics_full');
    $classes   = [];
    $precision = [];
    $recall    = [];
    $f1        = [];

    $firstRow = reset($evalData['records']);
    if ($firstRow !== false) {
        $firstKeys = array_keys($firstRow);
        $labelKey  = $firstKeys[0] ?? '';
        foreach ($evalData['records'] as $row) {
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
    }

    // 8) Evaluation Metrics (uji)
    $evalDataUji = $this->loadCsv('evaluation_metricsuji');
    $classesUji   = [];
    $precisionUji = [];
    $recallUji    = [];
    $f1Uji        = [];

    $firstRowUji = reset($evalDataUji['records']);
    if ($firstRowUji !== false) {
        $firstKeysUji = array_keys($firstRowUji);
        $labelKeyUji  = $firstKeysUji[0] ?? '';
        foreach ($evalDataUji['records'] as $row) {
            if (
                isset($row[$labelKeyUji], $row['precision'], $row['recall'], $row['f1-score'])
                && $row[$labelKeyUji] !== ''
                && is_numeric($row['precision'])
                && is_numeric($row['recall'])
                && is_numeric($row['f1-score'])
            ) {
                $classesUji[]   = $row[$labelKeyUji];
                $precisionUji[] = (float) $row['precision'];
                $recallUji[]    = (float) $row['recall'];
                $f1Uji[]        = (float) $row['f1-score'];
            }
        }
    }

    return view('viz.index', compact(
        'dataSent',
        'wordFrequencies',
        'wordFrequenciesByLabel',
        'topFeaturesByLabel',
        'cmCols',
        'cmRows',
        'trueLabelKey',
        'cmColsUji',
        'cmRowsUji',
        'trueLabelKeyUji',
        'classes',
        'precision',
        'recall',
        'f1',
        'classesUji',
        'precisionUji',
        'recallUji',
        'f1Uji'
    ));
}

}
