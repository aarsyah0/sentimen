<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sentiment Analysis Dashboard</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
    <!-- WordCloud2.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wordcloud2.js/1.0.6/wordcloud2.min.js"></script>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-blue-800 via-blue-600 to-blue-500 shadow-md sticky top-0 z-10">
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('assets/pj.png') }}" alt="Logo" class="h-10 w-10">
                <div>
                    <span class="text-2xl font-bold text-white">Sentiment Dashboard</span><br>
                    <span class="text-sm text-white">Manajemen Informatika</span>
                </div>
            </div>
            <a href="{{ route('login') }}"
                class="px-4 py-2 bg-blue-200 text-blue-800 rounded-lg hover:bg-blue-400 transition">Login</a>
        </div>
    </nav>

    <!-- Header -->
    <header class="bg-white py-12">
        <div class="max-w-screen-xl mx-auto text-center px-6">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-2">Analisis Sentimen Sistem Operasi iOS 15,16,17
                Menggunakan Naive Bayes</h1>
            <p class="text-lg text-gray-600">Tugas Akhir Oktaviarlen Setya (E31221299)</p>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-6 py-8 space-y-16">
        <!-- Sentiment Distribution Card -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Distribusi Sentimen</h2>
            <div class="flex justify-center">
                <canvas id="sentimentPie" class="w-64 h-64"></canvas>
            </div>
        </section>

        <!-- WordCloud per Label -->
        <section>
            <h2 class="text-2xl font-semibold text-blue-600 mb-6 text-center">WordCloud per Label Sentimen</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach (['positive', 'negative', 'neutral'] as $lbl)
                    <div class="bg-white p-4 rounded-2xl shadow-md flex flex-col items-center">
                        <h3 class="text-xl font-medium capitalize text-gray-700 mb-2">{{ $lbl }}</h3>
                        <div id="wc-{{ $lbl }}" class="w-80 h-80 border border-gray-200 rounded-lg"></div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Top Features: 3 Tables per Label -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Top Kata Teratas per Label</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach (['positive', 'negative', 'neutral'] as $label)
                    <div class="bg-white p-4 rounded-2xl shadow-md border">
                        <h3 class="text-xl font-medium capitalize text-gray-700 mb-2">{{ $label }}</h3>
                        @php
                            // Ambil array fitur untuk label ini; jika tidak ada, jadikan array kosong
                            $features = $topFeaturesByLabel[$label] ?? [];
                        @endphp

                        @if (empty($features))
                            <p class="text-sm text-gray-500">Tidak ada data untuk label {{ $label }}.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left">
                                    <thead class="bg-blue-50">
                                        <tr>
                                            <th class="px-4 py-2 font-medium text-gray-700">Rank</th>
                                            <th class="px-4 py-2 font-medium text-gray-700">Kata</th>

                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($features as $word => $count)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                                <td class="px-4 py-2 font-medium">{{ $word }}</td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
        <!-- Evaluation Metrics (Training) -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Evaluation Metrics (Training)</h2>
            <canvas id="evalMetricsChart" class="w-full h-72"></canvas>
        </section>

        <!-- Evaluation Metrics (Uji) -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Evaluation Metrics (Uji)</h2>
            <canvas id="evalMetricsUjiChart" class="w-full h-72"></canvas>
        </section>

        <!-- Confusion Matrix (Training) -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Confusion Matrix (Training)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-blue-50">
                        <tr>
                            {{-- Kolom pertama: header true label --}}
                            <th class="px-4 py-2 font-medium text-gray-700">{{ $trueLabelKey }}</th>
                            {{-- Kolom prediksi --}}
                            @foreach ($cmCols as $col)
                                <th class="px-4 py-2 font-medium text-gray-700">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cmRows as $row)
                            <tr class="hover:bg-gray-50">
                                {{-- Sel true label --}}
                                <td class="px-4 py-2 font-medium">{{ $row[$trueLabelKey] }}</td>
                                {{-- Sel prediksi --}}
                                @foreach ($cmCols as $col)
                                    <td class="px-4 py-2">{{ $row[$col] }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 1 + count($cmCols) }}" class="px-4 py-2 text-center text-gray-500">
                                    Data confusion matrix training tidak tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Confusion Matrix (Uji) -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Confusion Matrix (Uji)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-2 font-medium text-gray-700">{{ $trueLabelKeyUji }}</th>
                            @foreach ($cmColsUji as $col)
                                <th class="px-4 py-2 font-medium text-gray-700">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cmRowsUji as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ $row[$trueLabelKeyUji] }}</td>
                                @foreach ($cmColsUji as $col)
                                    <td class="px-4 py-2">{{ $row[$col] }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 1 + count($cmColsUji) }}" class="px-4 py-2 text-center text-gray-500">
                                    Data confusion matrix uji tidak tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Data dari Blade ke JS
            const dataSentLabels = {!! json_encode(array_keys($dataSent)) !!};
            const dataSentValues = {!! json_encode(array_values($dataSent)) !!};

            const wordFreqByLabel = {!! json_encode($wordFrequenciesByLabel) !!};
            const classes = {!! json_encode($classes) !!};
            const precision = {!! json_encode($precision) !!};
            const recall = {!! json_encode($recall) !!};
            const f1 = {!! json_encode($f1) !!};

            const classesUji = {!! json_encode($classesUji) !!};
            const precisionUji = {!! json_encode($precisionUji) !!};
            const recallUji = {!! json_encode($recallUji) !!};
            const f1Uji = {!! json_encode($f1Uji) !!};

            // Pie Chart Sentiment Distribution
            const pieCtx = document.getElementById('sentimentPie');
            if (pieCtx) {
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: dataSentLabels,
                        datasets: [{
                            data: dataSentValues,
                            backgroundColor: ['#10B981', '#EF4444', '#3B82F6'],
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            // WordCloud per label
            ['positive', 'negative', 'neutral'].forEach(lbl => {
                const container = document.getElementById('wc-' + lbl);
                if (!container) return;
                const freqs = wordFreqByLabel[lbl] || {};
                const list = Object.entries(freqs);
                if (list.length) {
                    WordCloud(container, {
                        list: list,
                        gridSize: 12,
                        weightFactor: size => size * 1.5,
                        backgroundColor: '#f8fafc',
                        rotateRatio: 0.1,
                        minSize: 14
                    });
                }
            });

            // Evaluation Metrics (training)
            const evalCtx = document.getElementById('evalMetricsChart');
            if (evalCtx) {
                new Chart(evalCtx, {
                    type: 'bar',
                    data: {
                        labels: classes,
                        datasets: [{
                                label: 'Precision',
                                data: precision,
                                backgroundColor: 'rgba(59,130,246,0.7)'
                            },
                            {
                                label: 'Recall',
                                data: recall,
                                backgroundColor: 'rgba(16,185,129,0.7)'
                            },
                            {
                                label: 'F1 Score',
                                data: f1,
                                backgroundColor: 'rgba(239,68,68,0.7)'
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 0.1
                                }
                            }
                        }
                    }
                });
            }

            // Evaluation Metrics (uji)
            const evalUjiCtx = document.getElementById('evalMetricsUjiChart');
            if (evalUjiCtx) {
                new Chart(evalUjiCtx, {
                    type: 'bar',
                    data: {
                        labels: classesUji,
                        datasets: [{
                                label: 'Precision',
                                data: precisionUji,
                                backgroundColor: 'rgba(59,130,246,0.7)'
                            },
                            {
                                label: 'Recall',
                                data: recallUji,
                                backgroundColor: 'rgba(16,185,129,0.7)'
                            },
                            {
                                label: 'F1 Score',
                                data: f1Uji,
                                backgroundColor: 'rgba(239,68,68,0.7)'
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 0.1
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>
