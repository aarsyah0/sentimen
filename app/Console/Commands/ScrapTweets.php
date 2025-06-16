<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use League\Csv\Reader;

class ScrapTweets extends Command
{
    protected $signature = 'scrap:tweets
                            {--keyword= : Kata kunci pencarian tweet (tanpa lang:id)}
                            {--limit=100 : Jumlah maksimal tweet}
                            {--output= : Nama file output (opsional, dibuat otomatis jika kosong)}
                            {--token= : Bearer token Twitter (jika kosong, ambil dari config)}';

    protected $description = 'Scrape tweets via tweet-harvest dan simpan ke public/tweets-data';

    public function handle()
    {
        set_time_limit(0);

        // Ambil dan validasi input
        $keyword = trim($this->option('keyword'));
        $limit = $this->option('limit') ?? 100;
        $token = $this->option('token') ?: config('services.twitter.token');

        if (!$keyword) {
            $this->error('❌ Keyword wajib diisi. Gunakan opsi --keyword=');
            return 1;
        }

        // Bersihkan dan siapkan query
        $cleanKeyword = preg_replace('/\s*lang:id\s*/i', '', $keyword);
        $searchQuery = trim($cleanKeyword) . ' lang:id';

        // Output filename dan path
        $outputFilename = $this->option('output') ?? Str::slug($cleanKeyword) . '.csv';
        $outputDir = public_path('tweets-data');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $outputFilename;

        $this->info("🔍 Mulai scraping: \"{$searchQuery}\" dengan limit {$limit}");
        $this->info("💾 Output disimpan ke: tweets-data/{$outputFilename}");

        // Jalankan tweet-harvest
        $process = new Process([
            '/opt/homebrew/bin/npx', '-y', 'tweet-harvest@2.6.1',
            '-o', $outputFilename, // hanya nama file
            '-s', $searchQuery,
            '--tab', 'LATEST',
            '-l', $limit,
            '--token', $token,
        ]);

        $process->setWorkingDirectory($outputDir); // bekerja dari dalam public/tweets-data
        $process->setTimeout(900);
        $process->setEnv([
            'PATH' => '/opt/homebrew/bin:/usr/local/bin:' . getenv('PATH'),
        ]);

        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Deteksi jika file berada di subfolder tidak sengaja
        if (!file_exists($outputPath)) {
            $fallbackPath = $outputDir . DIRECTORY_SEPARATOR . 'tweets-data' . DIRECTORY_SEPARATOR . $outputFilename;
            if (file_exists($fallbackPath)) {
                $this->warn("⚠️ File ditemukan di lokasi fallback: tweets-data/tweets-data/{$outputFilename}");
                $outputPath = $fallbackPath;
            } else {
                $this->error("❌ File output tidak ditemukan: {$outputPath}");
                return 1;
            }
        }

        // Hitung jumlah baris
        $csv = Reader::createFromPath($outputPath, 'r');
        $csv->setHeaderOffset(0);
        $count = iterator_count($csv->getRecords());

        $this->info("✅ Scraping selesai! Total tweet tersimpan: {$count}");

        return 0;
    }
}
