<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use App\Models\KpiMetric;
use App\Models\VersionKpiMetric;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class SentimentController extends Controller
{
    /**
     * Tampilkan form upload CSV untuk training.
     */
    public function showUploadForm()
    {
        return view('sentiment.upload');
    }

    /**
     * Handle upload CSV, buat folder run baru, panggil Python script untuk training,
     * copy hasil ke public, dan simpan KPI metrics ke database.
     */
    public function handleUploadAndTrain(Request $request)
{
    Log::info('Request content-type: ' . $request->header('Content-Type'));
Log::info('Apakah file ada? ' . ($request->hasFile('csv_file') ? 'YA' : 'TIDAK'));
Log::info('CSV file: ' . json_encode($request->file('csv_file')));

    Log::info('File upload status: ' . json_encode($request->file('csv_file')));
    // dd($request->all(), $request->hasFile('csv_file'), $request->file('csv_file'));

    $request->validate([
        'csv_file' => 'required|file|mimes:csv,txt',
    ]);

    // Tentukan nama folder berdasarkan hari dan tanggal
    $hari = now()->translatedFormat('l'); // Contoh: "Senin"
    $tanggal = now()->format('Ymd');      // Contoh: "20250612"
    $baseDir = storage_path("app/data_processed");

    // Cari semua folder dengan prefix run_{hari}_{tanggal}_*
    $existing = collect(glob($baseDir . "/run_{$hari}_{$tanggal}_*", GLOB_ONLYDIR))
        ->map(function ($dir) use ($hari, $tanggal) {
            $basename = basename($dir);
            $prefix = "run_{$hari}_{$tanggal}_";
            $numStr = substr($basename, strlen($prefix));
            return is_numeric($numStr) ? (int) $numStr : 0;
        })
        ->filter(fn($v) => $v >= 0)
        ->sort()
        ->values();

    $nextNumber = $existing->isEmpty() ? 1 : ($existing->last() + 1);
    $runSuffix = str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    $runName = "run_{$hari}_{$tanggal}_{$runSuffix}";
    $runDir = storage_path("app/data_processed/{$runName}");

    if (!file_exists($runDir)) {
        mkdir($runDir, 0755, true);
    }

    // Simpan CSV ke folder run
    $csvPath = $runDir . '/labeling.csv';
    $request->file('csv_file')->move($runDir, 'labeling.csv');

    // Hitung jumlah data dari CSV (asumsi baris pertama adalah header)
    $dataSize = count(file($csvPath)) - 1;

    // Perpanjang waktu eksekusi PHP
    if (function_exists('set_time_limit')) {
        set_time_limit(0);
    }

    // Siapkan proses training Python
    $python = config('services.python.path');     // e.g., 'python3'
    $script = config('services.python.script');   // e.g., base_path('scripts/sentiment.py')

    $process = new Process([
        $python,
        $script,
        'train',
        '--data', $csvPath,
        '--output-dir', $runDir,
    ]);
    $process->setTimeout(null);

    try {
        $start = microtime(true); // Mulai timer training
        $process->run();
        $durationSec = microtime(true) - $start; // Hitung durasi training

        if (!$process->isSuccessful()) {
            $err = $process->getErrorOutput();
            $out = $process->getOutput();
            return back()->withErrors("Training gagal!\nOutput:\n{$out}\nError:\n{$err}");
        }

        // Copy hasil training ke public storage
        $this->copyResultsToPublic($runDir);

        // Logging untuk debugging: cek folder hasil
        if (!is_dir(storage_path("app/public/results/{$runName}"))) {
            Log::error("Folder public/results tidak ditemukan setelah copy untuk run {$runName}");
        } else {
            Log::info("Folder public/results tersedia untuk run {$runName}");
        }

        // Simpan metrik ke database
        $this->storeKpiMetrics($runDir, $runName, $dataSize, $durationSec);

        return redirect()->route('sentiment.report', ['id' => $runName])
            ->with('message', 'Training selesai. Lihat hasil di halaman report.');
    } catch (\Throwable $e) {
        return back()->withErrors('Exception saat training: ' . $e->getMessage());
    }
}

    /**
     * Copy hasil training dari $outDir ke public/storage/results/{runId}
     */
    protected function copyResultsToPublic(string $outDir)
    {
        $runId = basename($outDir); // misal "run_Senin_20250612_01"
        $publicPath = storage_path("app/public/results/{$runId}");
        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Wordcloud folder
        $wcDir = $outDir . '/wordcloud';
        $pubWcDir = $publicPath . '/wordcloud';
        if (is_dir($wcDir)) {
            if (!file_exists($pubWcDir)) {
                mkdir($pubWcDir, 0755, true);
            }
            foreach (glob($wcDir . '/wordcloud_*.png') as $file) {
                copy($file, $pubWcDir . '/' . basename($file));
            }
        }

        // Files lain yang ingin disalin
        $others = [
            'distribution.png','distribution.csv',
            'tfidf_all.csv',
            'confusion_matrix.csv','evaluation_metrics.csv',
            'evaluation_full.csv','top_features_per_class.csv',
            'top_features.png','evaluation_full.png','ringkasan.txt',
            'mnb_final_model.joblib','tfidf_vectorizer.joblib'
        ];
        foreach ($others as $fname) {
            $src = $outDir . '/' . $fname;
            if (file_exists($src)) {
                copy($src, $publicPath . '/' . $fname);
            }
        }
    }
    /**
     * Copy hasil training dari $outDir ke public/storage/results/{runId}
     */

    /**
     * Parse evaluation_metrics.csv di $outDir dan simpan ke tabel kpi_metrics
     */
protected function storeKpiMetrics(string $outDir, string $runName, int $dataSize, int $trainingDuration)
{
    $evalCsv = $outDir . '/evaluation_metrics.csv';
    if (!file_exists($evalCsv)) {
        Log::warning("KPI: file evaluation_metrics.csv tidak ditemukan untuk run {$runName}");
        // Tetap simpan data_size dan training_duration minimal
        KpiMetric::updateOrCreate(
            ['run_id' => $runName],
            [
                'run_timestamp'     => now(),
                'data_size'         => $dataSize,
                'training_duration' => $trainingDuration,
            ]
        );
        return;
    }

    $rows = array_map('str_getcsv', file($evalCsv));
    if (count($rows) < 2) {
        Log::warning("KPI: evaluation_metrics.csv format tidak sesuai untuk run {$runName}");
        // Simpan minimal
        KpiMetric::updateOrCreate(
            ['run_id' => $runName],
            [
                'run_timestamp'     => now(),
                'data_size'         => $dataSize,
                'training_duration' => $trainingDuration,
            ]
        );
        return;
    }
    $header = $rows[0];
    Log::info("KPI CSV header for run {$runName}: " . implode(', ', $header));

    $dataRows = [];
    foreach (array_slice($rows, 1) as $r) {
        if (count($r) === count($header)) {
            $dataRows[] = array_combine($header, $r);
        }
    }
    if (empty($dataRows)) {
        Log::warning("KPI: tidak ada baris metrik valid di evaluation_metrics.csv untuk run {$runName}");
        KpiMetric::updateOrCreate(
            ['run_id' => $runName],
            [
                'run_timestamp'     => now(),
                'data_size'         => $dataSize,
                'training_duration' => $trainingDuration,
            ]
        );
        return;
    }

    // Inisialisasi
    $accuracy = null; $precision = null; $recall = null; $f1 = null;
    $perClassMetrics = []; // array untuk disimpan JSON
    $classDistribution = null; // akan diisi nanti

    // Parsing evaluation_metrics.csv:
    $lowerHdr = array_map('strtolower', $header);

    // Jika format ringkasan metric,value
    if (in_array('metric', $lowerHdr) && in_array('value', $lowerHdr)) {
        foreach ($dataRows as $row) {
            $m = strtolower($row['metric']);
            $v = (float) $row['value'];
            if ($m === 'accuracy') $accuracy = $v;
            elseif ($m === 'precision') $precision = $v;
            elseif ($m === 'recall') $recall = $v;
            elseif (in_array($m, ['f1','f1-score','f1_score'])) $f1 = $v;

            // Simpan per-class jika metric berformat class-specific, tapi ringkasan biasanya tidak punya
        }
    }
    // Jika format per-class: misal header ["label","precision","recall","f1-score",...]
    elseif (in_array('precision', $lowerHdr) && in_array('recall', $lowerHdr) && (in_array('f1-score', $lowerHdr) || in_array('f1_score', $lowerHdr))) {
        $sumP = $sumR = $sumF1 = 0;
        $count = 0;
        foreach ($dataRows as $row) {
            // Simpan per-class metrics: gunakan label sebagai key jika ada
            $labelKey = null;
            if (isset($row['label'])) {
                $labelKey = $row['label'];
            } elseif (isset($row['class'])) {
                $labelKey = $row['class'];
            }
            $p = isset($row['precision']) ? (float)$row['precision'] : 0;
            $r = isset($row['recall']) ? (float)$row['recall'] : 0;
            if (isset($row['f1-score'])) $fv = (float)$row['f1-score'];
            elseif (isset($row['f1_score'])) $fv = (float)$row['f1_score'];
            else $fv = 0;

            // Tambahkan ke perClassMetrics
            if ($labelKey !== null) {
                $perClassMetrics[$labelKey] = [
                    'precision' => $p,
                    'recall'    => $r,
                    'f1_score'  => $fv,
                ];
            } else {
                // jika tidak ada kolom label, key numeric index
                $perClassMetrics[] = [
                    'precision' => $p,
                    'recall'    => $r,
                    'f1_score'  => $fv,
                ];
            }

            $sumP += $p;
            $sumR += $r;
            $sumF1 += $fv;
            $count++;
        }
        // Macro average
        $precision = $count ? $sumP / $count : null;
        $recall = $count ? $sumR / $count : null;
        $f1 = $count ? $sumF1 / $count : null;

        // Accuracy: coba dari confusion_matrix.csv
        // Jika accuracy belum tersedia, coba ambil dari ringkasan.txt
if ($accuracy === null) {
    $summaryTxt = $outDir . '/ringkasan.txt';
    if (file_exists($summaryTxt)) {
        $lines = file($summaryTxt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (stripos($line, 'Akurasi akhir split:') !== false) {
                if (preg_match('/Akurasi akhir split:\s*([\d,.]+)/i', $line, $matches)) {
                    $percentStr = str_replace(',', '.', $matches[1]); // handle koma
                    $accuracy = floatval($percentStr) / 100;
                    Log::info("KPI: Akurasi diambil dari ringkasan.txt untuk run {$runName} = {$accuracy}");
                }
                break;
            }
        }
    }
}

        // AUC/loss jika ada di evaluation_full.csv atau summary: butuh Python side untuk output
    }
    else {
        Log::warning("KPI: format header evaluation_metrics.csv tidak dikenali untuk run {$runName}");
        // Tetap simpan minimal data_size & training_duration
        KpiMetric::updateOrCreate(
            ['run_id' => $runName],
            [
                'run_timestamp'     => now(),
                'data_size'         => $dataSize,
                'training_duration' => $trainingDuration,
            ]
        );
        return;
    }

    // Class distribution: baca distribution.csv jika ada
    $distCsv = $outDir . '/distribution.csv';
    if (file_exists($distCsv)) {
        $distRows = array_map('str_getcsv', file($distCsv));
        if (count($distRows) >= 2) {
            $hdr = $distRows[0];
            $distArr = [];
            foreach (array_slice($distRows, 1) as $r) {
                if (count($r) === count($hdr)) {
                    $comb = array_combine($hdr, $r);
                    // Asumsikan ada kolom 'label' dan 'count' atau 'frequency'
                    $labelKey = $comb['label'] ?? ($comb['class'] ?? null);
                    $countVal = null;
                    if (isset($comb['count'])) $countVal = (int)$comb['count'];
                    elseif (isset($comb['frequency'])) $countVal = (int)$comb['frequency'];
                    else {
                        // jika header lain, ambil kolom numeric kedua
                        $vals = array_values($comb);
                        $countVal = isset($vals[1]) ? (int)$vals[1] : null;
                    }
                    if ($labelKey !== null && $countVal !== null) {
                        $distArr[$labelKey] = $countVal;
                    }
                }
            }
            $classDistribution = $distArr;
        }
    }

    // Simpan ke DB
    $attrs = [
        'run_timestamp'     => now(),
        'accuracy'          => $accuracy,
        'precision'         => $precision,
        'recall'            => $recall,
        'f1_score'          => $f1,
        'data_size'         => $dataSize,
        'training_duration' => $trainingDuration,
        'class_distribution'=> $classDistribution,
        'per_class_metrics' => $perClassMetrics,

    ];
    try {
        KpiMetric::updateOrCreate(
            ['run_id' => $runName],
            $attrs
        );
        Log::info("KPI: berhasil menyimpan metrik untuk run {$runName}: " .
            "accuracy={$accuracy}, precision={$precision}, recall={$recall}, f1={$f1}, data_size={$dataSize}, training_duration={$trainingDuration}");
    } catch (\Throwable $e) {
        Log::error("KPI: gagal menyimpan ke DB untuk run {$runName}: " . $e->getMessage());
    }
    Log::info("KPI: memanggil storeVersionMetrics untuk run {$runName}, outDir={$outDir}");
    $this->storeVersionMetrics($outDir, $runName);
}

    protected function storeVersionMetrics(string $outDir, string $runName)
{
    Log::info("Version KPI: isi folder {$outDir}: " . json_encode(array_diff(scandir($outDir), ['.', '..'])));

    $csvPath = $outDir . '/df_full_predictions.csv';
    if (!file_exists($csvPath)) {
        Log::warning("Version KPI: file df_full_predictions.csv tidak ditemukan untuk run {$runName}");
        return;
    }

    if (($handle = fopen($csvPath, 'r')) === false) {
        Log::warning("Version KPI: gagal membuka df_full_predictions.csv untuk run {$runName}");
        return;
    }

    $header = fgetcsv($handle);
    if (!$header) {
        Log::warning("Version KPI: header CSV kosong untuk run {$runName}");
        fclose($handle);
        return;
    }

    $lowerHeader = array_map('strtolower', $header);
    $idxVersion = array_search('version', $lowerHeader);
    $idxPred = array_search('predicted_label', $lowerHeader);

    if ($idxVersion === false || $idxPred === false) {
        Log::warning("Version KPI: kolom 'version' atau 'predicted_label' tidak ditemukan di header: " . json_encode($header));
        fclose($handle);
        return;
    }

    // === DETEKSI DINAMIS SEMUA VERSI YANG ADA ===
    $metrics = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($header)) continue;

        $ver = trim($row[$idxVersion]);
        $label = strtolower(trim($row[$idxPred]));

        if (!isset($metrics[$ver])) {
            $metrics[$ver] = ['pos' => 0, 'neg' => 0, 'neu' => 0, 'total' => 0];
        }

        $metrics[$ver]['total']++;
        if (str_contains($label, 'pos')) {
            $metrics[$ver]['pos']++;
        } elseif (str_contains($label, 'neg')) {
            $metrics[$ver]['neg']++;
        } else {
            $metrics[$ver]['neu']++;
        }
    }

    fclose($handle);

    // === SIMPAN KE DB ===
    foreach ($metrics as $ver => $count) {
        $total = $count['total'];
        $data = [
            'count_positive' => $count['pos'],
            'count_negative' => $count['neg'],
            'count_neutral'  => $count['neu'],
            'total'          => $total,
            'pct_positive'   => $total ? round($count['pos'] * 100 / $total, 2) : null,
            'pct_negative'   => $total ? round($count['neg'] * 100 / $total, 2) : null,
            'pct_neutral'    => $total ? round($count['neu'] * 100 / $total, 2) : null,
        ];

        try {
            VersionKpiMetric::updateOrCreate(
                ['run_id' => $runName, 'version' => $ver],
                $data
            );
            Log::info("Version KPI: tersimpan {$ver} run {$runName}: " . json_encode($data));
        } catch (\Throwable $e) {
            Log::error("Version KPI: gagal simpan {$ver} run {$runName}: " . $e->getMessage());
        }
    }
}



    /**
     * Tampilkan report untuk run tertentu.
     * Jika tidak ada ID, redirect ke run terbaru.
     */
    public function showReport(Request $request)
    {
        $runId = $request->get('id');

        if (!$runId) {
            $resultsDir = storage_path("app/public/results");
            $folders = glob($resultsDir . '/run_*', GLOB_ONLYDIR);
            if (empty($folders)) {
                return back()->withErrors('Belum ada hasil training yang tersedia.');
            }
            usort($folders, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });
            $latestFolder = basename($folders[0]);
            return redirect()->route('sentiment.report', ['id' => $latestFolder]);
        }

        $dir = storage_path("app/public/results/{$runId}");
        if (!is_dir($dir)) {
            return back()->withErrors('Hasil training tidak ditemukan.');
        }

        $results = [];

        // distribution
        $distCsv = $dir . '/distribution.csv';
        if (file_exists($distCsv)) {
            $rows = array_map('str_getcsv', file($distCsv));
            $header = array_shift($rows);
            $dist = [];
            foreach ($rows as $r) {
                if (count($r) === count($header)) {
                    $dist[] = array_combine($header, $r);
                }
            }
            $results['distribution'] = $dist;
        }
        $results['distribution_img'] = file_exists($dir . '/distribution.png')
            ? asset("storage/results/{$runId}/distribution.png")
            : null;

        // tfidf (tampil max 50 baris)
        $tfidfCsv = $dir . '/tfidf_all.csv';
        if (file_exists($tfidfCsv)) {
            $rows = array_map('str_getcsv', file($tfidfCsv));
            $header = array_shift($rows);
            $tfidf = [];
            foreach ($rows as $i => $r) {
                if ($i >= 50) break;
                if (count($r) === count($header)) {
                    $tfidf[] = array_combine($header, $r);
                }
            }
            $results['tfidf'] = $tfidf;
        }

        // confusion matrix
        $cmCsv = $dir . '/confusion_matrix.csv';
        if (file_exists($cmCsv)) {
            $rows = array_map('str_getcsv', file($cmCsv));
            $header = array_shift($rows);
            $cm = [];
            foreach ($rows as $r) {
                if (count($r) === count($header)) {
                    $cm[] = array_combine($header, $r);
                }
            }
            $results['confusion'] = $cm;
        }

        // evaluation metrics
        $evalCsv = $dir . '/evaluation_metrics.csv';
        if (file_exists($evalCsv)) {
            $rows = array_map('str_getcsv', file($evalCsv));
            $header = array_shift($rows);
            $eval = [];
            foreach ($rows as $r) {
                if (count($r) === count($header)) {
                    $eval[] = array_combine($header, $r);
                }
            }
            $results['evaluation'] = $eval;
        }
        $results['evaluation_split_img'] = file_exists($dir . '/evaluation_split.png')
            ? asset("storage/results/{$runId}/evaluation_split.png")
            : null;

        // top features
        $topCsv = $dir . '/top_features_per_class.csv';
        if (file_exists($topCsv)) {
            $rows = array_map('str_getcsv', file($topCsv));
            $header = array_shift($rows);
            $top = [];
            foreach ($rows as $r) {
                if (count($r) === count($header)) {
                    $top[] = array_combine($header, $r);
                }
            }
            $results['top_features'] = $top;
        }
        $results['top_features_img'] = file_exists($dir . '/top_features.png')
            ? asset("storage/results/{$runId}/top_features.png")
            : null;

        // full evaluation
        $evalFullCsv = $dir . '/evaluation_full.csv';
        if (file_exists($evalFullCsv)) {
            $rows = array_map('str_getcsv', file($evalFullCsv));
            $header = array_shift($rows);
            $full = [];
            foreach ($rows as $r) {
                if (count($r) === count($header)) {
                    $full[] = array_combine($header, $r);
                }
            }
            $results['evaluation_full'] = $full;
        }
        $results['evaluation_full_img'] = file_exists($dir . '/evaluation_full.png')
            ? asset("storage/results/{$runId}/evaluation_full.png")
            : null;

        // wordclouds
        $wcDir = $dir . '/wordcloud';
        $wcUrls = [];
        if (is_dir($wcDir)) {
            foreach (glob($wcDir . '/wordcloud_*.png') as $file) {
                $name = basename($file);
                $wcUrls[] = [
                    'label' => pathinfo($name, PATHINFO_FILENAME),
                    'url'   => asset("storage/results/{$runId}/wordcloud/{$name}")
                ];
            }
        }
        $results['wordclouds'] = $wcUrls;

        // Daftar semua run tersedia
        $resultsDir = storage_path("app/public/results");
        $allFolders = collect(glob($resultsDir . '/run_*', GLOB_ONLYDIR))
            ->sortByDesc(fn($f) => filemtime($f))
            ->map(fn($f) => basename($f))
            ->values()
            ->toArray();

        return view('sentiment.report', [
            'results'       => $results,
            'runId'         => $runId,
            'availableRuns' => $allFolders,
        ]);
    }

    /**
     * Tampilkan form untuk inference.
     */
    public function showInferForm()
{
    // Ambil run terbaru
    $latest = KpiMetric::orderByDesc('run_timestamp')->first();
    $latestRunId = $latest?->run_id;

    // (Optional) ambil juga semua run untuk dropdown
    $allRunIds = KpiMetric::orderByDesc('run_timestamp')
                         ->pluck('run_id')
                         ->toArray();

    return view('sentiment.infer_form', [
        'latestRunId' => $latestRunId,
        'allRunIds'   => $allRunIds,
    ]);
}

    /**
     * Lakukan inference: panggil Python script dengan model dan vectorizer dari run tertentu.
     */
    public function doInfer(Request $request)
    {
        $request->validate([
            'text'   => 'required|string',
            'run_id' => 'required|string'
        ]);

        $runId = $request->input('run_id');
        $dir = storage_path("app/public/results/{$runId}");

        $modelPath = $dir . '/mnb_final_model.joblib';
        $vectPath = $dir . '/tfidf_vectorizer.joblib';

        if (!file_exists($modelPath) || !file_exists($vectPath)) {
            return back()->withErrors('Model belum tersedia. Jalankan training terlebih dahulu.');
        }

        $texts_json = json_encode([$request->input('text')], JSON_UNESCAPED_UNICODE);
        $python = config('services.python.path');
        $script = config('services.python.script');

        $process = new Process([
            $python,
            $script,
            'infer',
            '--model', $modelPath,
            '--vectorizer', $vectPath,
            '--texts', $texts_json
        ]);
        $process->setTimeout(60);

        try {
            $process->run();
            if (!$process->isSuccessful()) {
                return back()->withErrors('Inference gagal: ' . $process->getErrorOutput());
            }
            $out = json_decode($process->getOutput(), true);
            if (!$out || !isset($out['status']) || $out['status'] !== 'success') {
                return back()->withErrors('Response tidak valid dari Python.');
            }
            $result = $out['results'][0] ?? null;
            return view('sentiment.infer_result', compact('result'));
        } catch (\Throwable $e) {
            return back()->withErrors('Error saat inference: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan Dashboard KPI: ringkasan dan tren metrik dari database.
     */


public function showDashboard(Request $request)
{
    // Ambil semua KPI urut dari paling awal ke akhir
    $kpis = KpiMetric::orderBy('run_timestamp')->get();

    // Siapkan label timestamp dan data metrik
    $labels        = $kpis->map(fn($k) => $k->run_timestamp->format('Y-m-d H:i'))->toArray();
    $accuracyData  = $kpis->map(fn($k) => $k->accuracy ?? 0)->toArray();
    $precisionData = $kpis->map(fn($k) => $k->precision ?? 0)->toArray();
    $recallData    = $kpis->map(fn($k) => $k->recall ?? 0)->toArray();
    $f1Data        = $kpis->map(fn($k) => $k->f1_score ?? 0)->toArray();

    // Statistik umum
    $totalRuns   = $kpis->count();
    $avgAccuracy = $totalRuns > 0 ? $kpis->avg('accuracy') : null;
    $avgF1       = $totalRuns > 0 ? $kpis->avg('f1_score') : null;

    // Ambil data terakhir
    $lastRun       = $kpis->last();
   $lastClassDist = $lastRun?->class_distribution ?? [];

// Pastikan array biasa + ubah ke lowercase
$lastClassDist = collect($lastClassDist)
    ->mapWithKeys(function ($value, $key) {
        return [strtolower($key) => $value];
    })->toArray();

    // Ambil data versi (misal per-platform iOS/Android) dari run terakhir
    $lastRunId = $lastRun?->run_id;
    $versionMetrics = $lastRunId
        ? VersionKpiMetric::where('run_id', $lastRunId)->get()
        : collect();
    $gapThreshold = 15;
    // Ambil metrik versi
    $posCount      = $versionMetrics->pluck('count_positive');
    $negCount      = $versionMetrics->pluck('count_negative');
    $neutralCount  = $versionMetrics->pluck('count_neutral');
    $posPct        = $versionMetrics->pluck('pct_positive');
    $negPct        = $versionMetrics->pluck('pct_negative');
    $neutralPct    = $versionMetrics->pluck('pct_neutral');
    $totalMentions = $versionMetrics->pluck('total');

     // 1) Insight Distribusi Kelas
    $classDist = $lastClassDist;
    $totalCount = array_sum($lastClassDist);
if ($totalCount <= 0) {
    $classMessage = "Tidak ada data distribusi kelas untuk run terakhir.";
    // Untuk Blade, agar persentase tidak ditampilkan 0% misleading
    $classPct = null;

} else {
    $pos = $lastClassDist['positif'] ?? 0;
$neg = $lastClassDist['negatif'] ?? 0;
$neu = $lastClassDist['netral'] ?? 0;

    $posPct = $pos / $totalCount * 100;
    $negPct = $neg / $totalCount * 100;
    $neuPct = $neu / $totalCount * 100;

     $maxVal = max($posPct, $negPct, $neuPct);
    $secondMax = max(array_diff([$posPct, $negPct, $neuPct], [$maxVal]));

     if (($maxVal - $secondMax) > $gapThreshold) {
        if ($maxVal === $posPct) {
            $classMessage = "Distribusi sentimen menunjukkan dominasi sentimen positif sebesar " . round($posPct,1) . "%, yang mengindikasikan persepsi publik yang cenderung mendukung atau puas terhadap topik.";
        } elseif ($maxVal === $negPct) {
            $classMessage = "Sentimen negatif mendominasi dengan proporsi " . round($negPct,1) . "%, yang bisa mencerminkan kekhawatiran atau kritik publik terhadap topik.";
        } else {
            $classMessage = "Mayoritas sentimen tergolong netral (" . round($neuPct,1) . "%), menunjukkan bahwa publik cenderung memberikan respons yang informatif atau tidak emosional.";
        }
    } else {
        $classMessage = "Distribusi sentimen relatif seimbang: Positif " . round($posPct,1) . "%, Negatif " . round($negPct,1) . "%, dan Netral " . round($neuPct,1) . "%. Hal ini menunjukkan keragaman opini publik terhadap topik yang dianalisis.";
    }

    $classPct = [
        'positif' => round($posPct,1),
        'negatif' => round($negPct,1),
        'netral'  => round($neuPct,1),
    ];
}

    // 2) Korelasi Data Size vs Accuracy (Pearson)
    $sizes = $kpis->pluck('data_size')->map(fn($v)=>(float)($v ?? 0))->toArray();
    $accs = $kpis->pluck('accuracy')->map(fn($v)=>(float)($v ?? 0))->toArray();
    $n = count($sizes);
    $corr = 0;
    if ($n > 1) {
        $meanX = array_sum($sizes)/$n;
        $meanY = array_sum($accs)/$n;
        $cov=0; $varX=0; $varY=0;
        for ($i=0;$i<$n;$i++){
            $dx = $sizes[$i] - $meanX;
            $dy = $accs[$i] - $meanY;
            $cov += $dx * $dy;
            $varX += $dx * $dx;
            $varY += $dy * $dy;
        }
        $corr = $cov / sqrt(max($varX*$varY, 1e-9));
    }
    if ($corr > 0.3) {
        $scatterMessage = "Terdapat korelasi positif sedang/kuat antara ukuran data dan akurasi (r=".round($corr,2)."). Menunjukkan akurasi cenderung naik jika data_size bertambah.";
    } elseif ($corr < -0.3) {
        $scatterMessage = "Terdapat korelasi negatif antara ukuran data dan akurasi (r=".round($corr,2)."), perlu diteliti mengapa akurasi menurun saat data_size bertambah.";
    } else {
        $scatterMessage = "Korelasi antara data_size dan akurasi lemah (r=".round($corr,2)."), tidak jelas pola kenaikan akurasi dengan ukuran data.";
    }

    // 3) Insight Tren Metrik (gunakan accuracyData)
    $y = $accuracyData; // asumsikan array float
    $m = count($y);
    $trendMessage = "Tidak cukup data untuk analisis tren.";
    if ($m > 3) {
        // indeks 1..m sebagai x
        $x = range(1, $m);
        $meanX = array_sum($x)/$m;
        $meanY = array_sum($y)/$m;
        $cov=0; $varX=0;
        for ($i=0;$i<$m;$i++){
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $cov += $dx * $dy;
            $varX += $dx * $dx;
        }
        $slope = $cov / max($varX, 1e-9);
        if ($slope > 0.001) {
            $trendMessage = "Tren akurasi meningkat seiring run (slope=".round($slope,4).").";
        } elseif ($slope < -0.001) {
            $trendMessage = "Tren akurasi menurun seiring run (slope=".round($slope,4).").";
        } else {
            $trendMessage = "Tren akurasi relatif datar (slope=".round($slope,4).").";
        }
    }

    // 4) Insight Versi iOS
    $versionMessages = [];
    foreach ($versionMetrics as $vm) {
        $vp = $vm->pct_positive ?? 0;
        $vn = $vm->pct_negative ?? 0;
        $vneu = $vm->pct_neutral ?? 0;
        if ($vn > $vp && $vn > $vneu) {
            $versionMessages[$vm->version] = "Versi {$vm->version} cenderung negatif: {$vn}% negatif dari total {$vm->total}.";
        } elseif ($vp > $vn && $vp > $vneu) {
            $versionMessages[$vm->version] = "Versi {$vm->version} dominan positif: {$vp}% positif.";
        } else {
            $versionMessages[$vm->version] = "Versi {$vm->version} mayoritas netral atau seimbang: Positif {$vp}%, Negatif {$vn}%, Netral {$vneu}%.";
        }
    }
    return view('sentiment.dashboard', compact(
        'kpis',
        'labels', 'accuracyData', 'precisionData', 'recallData', 'f1Data',
        'totalRuns', 'avgAccuracy', 'avgF1',
        'lastRun', 'lastClassDist', 'versionMetrics',
        'posCount','negCount','neutralCount',
        'posPct','negPct','neutralPct','totalMentions','classMessage',
        'scatterMessage',
        'trendMessage',
        'versionMessages'
    ));
}


}
