@extends('layouts.app')

@section('title', 'Report Hasil Training')

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-8 space-y-8">
        {{-- Header --}}
        <div class="text-center">
            <h2 class="text-4xl font-extrabold inline-flex items-center space-x-3">
                <i class="fas fa-chart-bar text-indigo-500 animate-bounce"></i>
                <span>Report Hasil Training</span>
            </h2>
        </div>

        {{-- Flash Message --}}
        @if (session('message'))
            <div class="flex items-center justify-center">
                <div
                    class="bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded-lg shadow-md animate-pulse inline-flex items-center space-x-2">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('message') }}</span>
                    <button type="button" class="ml-4 text-green-700 hover:text-green-900" data-bs-dismiss="alert">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- Filter Form --}}
        <form method="GET" action="{{ route('sentiment.report') }}" class="flex items-center justify-center space-x-2">
            <label for="runSelect" class="font-medium text-gray-700">Pilih Hasil Training:</label>
            <select name="id" id="runSelect" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-shadow">
                @foreach ($availableRuns as $run)
                    <option value="{{ $run }}" {{ $run === $runId ? 'selected' : '' }}>
                        {{ $run }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="space-y-8">
            {{-- Distribusi Sentimen --}}
            @if (!empty($results['distribution']))
                <div
                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition">
                    <div class="bg-indigo-600 text-white px-6 py-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-xl"></i>
                        <h3 class="text-lg font-semibold">Distribusi Sentimen</h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-indigo-700 bg-indigo-50 border-l-4 border-indigo-400 px-4 py-2 rounded">
                            Grafik ini menunjukkan distribusi jumlah data pada masing-masing kategori sentimen.
                        </p>
                        <div class="flex justify-center">
                            <div class="w-full max-w-md">
                                <canvas id="sentimentChart" class="rounded-lg shadow-md"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TF-IDF Top --}}
            @if (!empty($results['tfidf']))
                <div
                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition">
                    <div class="bg-green-600 text-white px-6 py-4 flex items-center">
                        <i class="fas fa-key mr-2 text-xl"></i>
                        <h3 class="text-lg font-semibold">TF-IDF Top (50)</h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-green-700 bg-green-50 border-l-4 border-green-400 px-4 py-2 rounded">
                            Tabel ini menampilkan 50 kata dengan skor TF-IDF tertinggi.
                        </p>
                        <div class="overflow-auto max-h-64">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        @foreach (array_keys($results['tfidf'][0]) as $col)
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">
                                                {{ ucwords(str_replace('_', ' ', $col)) }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($results['tfidf'] as $row)
                                        <tr class="hover:bg-gray-50 transition">
                                            @foreach ($row as $val)
                                                <td class="px-4 py-2 text-sm text-gray-600">{{ $val }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Confusion Matrix --}}
            @if (!empty($results['confusion']))
                @php
                    $matrix = $results['confusion'];
                    $labels = array_keys($matrix[0]);
                    $labelNames = ['netral', 'positif', 'negatif'];
                    foreach ($matrix as &$row) {
                        array_shift($row);
                    }
                    array_shift($labels);
                    unset($row);
                    $maxVal = max(array_map('max', array_map('array_values', $matrix)));
                @endphp
                <div
                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition">
                    <div class="bg-yellow-500 text-white px-6 py-4 flex items-center">
                        <i class="fas fa-th-large mr-2 text-xl"></i>
                        <h3 class="text-lg font-semibold">Confusion Matrix</h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-yellow-700 bg-yellow-50 border-l-4 border-yellow-400 px-4 py-2 rounded">
                            Confusion Matrix memperlihatkan jumlah prediksi benar & salah untuk tiap kelas.
                        </p>
                        <div class="overflow-auto">
                            <table class="min-w-full text-center">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2">Actual ↓ / Predicted →</th>
                                        @foreach ($labels as $pred)
                                            <th class="px-4 py-2">{{ ucfirst($pred) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($matrix as $i => $row)
                                        <tr class="hover:bg-gray-50 transition">
                                            <th class="bg-gray-50 px-4 py-2 font-medium">
                                                {{ ucfirst($labelNames[$i] ?? $i) }}
                                            </th>
                                            @foreach ($labels as $pred)
                                                @php
                                                    $val = floatval($row[$pred]);
                                                    $ratio = $maxVal > 0 ? $val / $maxVal : 0;
                                                    // warna dasar untuk true positive hijau, else biru
                                                    $baseColor =
                                                        $labelNames[$i] === $pred ? [40, 167, 69] : [59, 130, 246];
                                                    // hitung rgba dengan opacity = ratio
                                                    $bgColor = "rgba({$baseColor[0]},{$baseColor[1]},{$baseColor[2]},{$ratio})";
                                                    $textClass =
                                                        $ratio > 0.5 ? 'text-white font-bold' : 'text-gray-800';
                                                @endphp
                                                <td class="px-4 py-2 {{ $val > 0 ? $textClass : 'text-gray-400' }}"
                                                    style="{{ $val > 0 ? "background-color: {$bgColor};" : '' }}">
                                                    {{ $val > 0 ? $val : '0' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif


            {{-- Evaluation Full Data --}}
            @if (!empty($results['evaluation_full']))
                <div
                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition">
                    <div class="bg-blue-600 text-white px-6 py-4 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-xl"></i>
                        <h3 class="text-lg font-semibold">Evaluation Full Data</h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-blue-700 bg-blue-50 border-l-4 border-blue-400 px-4 py-2 rounded">
                            Metrik evaluasi setelah dilatih pada seluruh data.
                        </p>
                        <div class="overflow-auto max-h-48">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        @foreach (array_keys($results['evaluation_full'][0]) as $col)
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">
                                                {{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($results['evaluation_full'] as $row)
                                        <tr class="hover:bg-gray-50 transition">
                                            @foreach ($row as $val)
                                                <td class="px-4 py-2 text-sm text-gray-600">
                                                    {{ is_numeric($val) ? number_format($val, 2) : $val }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (!empty($results['evaluation_full_img']))
                            <div class="mt-4 text-center">
                                <img src="{{ $results['evaluation_full_img'] }}" class="inline-block rounded-lg shadow-md"
                                    alt="Evaluation Full Data">
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Top Features --}}
            @if (!empty($results['top_features_img']))
                <div
                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition">
                    <div class="bg-gray-700 text-white px-6 py-4 flex items-center">
                        <i class="fas fa-star mr-2 text-xl"></i>
                        <h3 class="text-lg font-semibold">Top Features per Class</h3>
                    </div>
                    <div class="p-6 text-center">
                        <img src="{{ $results['top_features_img'] }}" class="inline-block rounded-lg shadow-md"
                            alt="Top Features">
                    </div>
                </div>
            @endif

            {{-- WordClouds --}}
            @if (!empty($results['wordclouds']))
                <div
                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition">
                    <div class="bg-black text-white px-6 py-4 flex items-center">
                        <i class="fas fa-cloud mr-2 text-xl"></i>
                        <h3 class="text-lg font-semibold">WordCloud per Kelas</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach ($results['wordclouds'] as $wc)
                            <div class="text-center">
                                <h5 class="mb-2 font-medium">{{ ucfirst($wc['label']) }}</h5>
                                <img src="{{ $wc['url'] }}" class="inline-block rounded-lg shadow-md"
                                    alt="WC {{ $wc['label'] }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="flex justify-between">
                <a href="{{ route('sentiment.upload') }}"
                    class="inline-flex items-center space-x-2 bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                    <i class="fas fa-upload"></i><span>Upload & Train lagi</span>
                </a>
                <a href="{{ route('sentiment.infer') }}"
                    class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg shadow transition">
                    <i class="fas fa-magic"></i><span>Inferensi</span>
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        Chart.register(ChartDataLabels);
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Ambil dan parse angka
            const rawCounts = @json(collect($results['distribution'])->pluck('count'));
            const counts = rawCounts.map(n => Number(n));
            const labels = @json(collect($results['distribution'])->pluck('label'));
            const ctx = document.getElementById('sentimentChart').getContext('2d');

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: counts, // pakai counts yang sudah Number
                        backgroundColor: ['#4CAF50', '#F44336', '#FFC107'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    animation: {
                        animateRotate: true,
                        duration: 1000,
                        easing: 'easeOutBounce'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 16,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const v = ctx.raw,
                                        total = counts.reduce((a, b) => a + b, 0),
                                        p = total > 0 ? (v / total * 100).toFixed(1) : 0;
                                    return `${ctx.label}: ${v} (${p}%)`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: (value, ctx) => {
                                const data = ctx.chart.data.datasets[0].data;
                                const total = data.reduce((sum, v) => sum + v, 0);
                                return total > 0 ?
                                    ((value / total) * 100).toFixed(1) + '%' :
                                    '';
                            }
                        }
                    }
                }
            });
        });
    </script>
    <script></script>
@endpush
