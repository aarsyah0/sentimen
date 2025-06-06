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
                <img src="/assets/pj.png" alt="Logo" class="h-10 w-10">
                <div>
                    <span class="text-2xl font-bold text-white">Sentiment Dashboard</span><br>
                    <span class="text-sm text-white">Manajemen Informatika</span>
                </div>
            </div>
            <a href="{{ route('login') }}"
                class="px-4 py-2 bg-blue-200 text rounded-lg hover:bg-blue-400 transition">Login</a>
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

        <!-- WordCloud Section -->
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

        <!-- Top Features Table -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Top 10 Kata Teratas</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-2 font-medium text-gray-700">#</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Kata</th>
                            <th class="px-4 py-2 font-medium text-gray-700">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($topFeatures as $word => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 font-medium">{{ $word }}</td>
                                <td class="px-4 py-2">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Evaluation Metrics Chart -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Evaluation Metrics</h2>
            <canvas id="evalMetricsChart" class="w-full h-72"></canvas>
        </section>

        <!-- Confusion Matrix Table -->
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">Confusion Matrix</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-2 font-medium text-gray-700">{{ $trueLabelKey }}</th>
                            @foreach ($cmCols as $col)
                                <th class="px-4 py-2 font-medium text-gray-700">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($cmRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ $row[$trueLabelKey] }}</td>
                                @foreach ($cmCols as $col)
                                    <td class="px-4 py-2">{{ $row[$col] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <section class="bg-white p-6 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-semibold text-blue-600 mb-4">
                Confusion Matrix (Uji)
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-blue-50">
                        <tr>
                            {{-- First header cell: the “true” label column name --}}
                            <th class="px-4 py-2 font-medium text-gray-700">{{ $trueLabelKeyUji }}</th>

                            {{-- Then one <th> per predicted label --}}
                            @foreach ($cmColsUji as $col)
                                <th class="px-4 py-2 font-medium text-gray-700">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($cmRowsUji as $row)
                            <tr class="hover:bg-gray-50">
                                {{-- First cell: the actual true‐label value --}}
                                <td class="px-4 py-2 font-medium">{{ $row[$trueLabelKeyUji] }}</td>

                                {{-- One cell per predicted column --}}
                                @foreach ($cmColsUji as $col)
                                    <td class="px-4 py-2">{{ $row[$col] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Pie Chart
            new Chart(document.getElementById('sentimentPie'), {
                type: 'pie',
                data: {
                    labels: {!! json_encode(array_keys($dataSent)) !!},
                    datasets: [{
                        data: {!! json_encode(array_values($dataSent)) !!},
                        backgroundColor: ['#EF4444', '#3B82F6', '#10B981'],
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true
                }
            });

            // WordCloud
            const freqs = @json($wordFrequenciesByLabel);
            Object.entries(freqs).forEach(([lbl, f]) => {
                WordCloud(document.getElementById('wc-' + lbl), {
                    list: Object.entries(f),
                    gridSize: 12,
                    weightFactor: s => s * 1.5,
                    backgroundColor: '#f8fafc',
                    rotateRatio: 0.1,
                    minSize: 14
                });
            });

            // Metrics Bar Chart
            new Chart(document.getElementById('evalMetricsChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($classes) !!},
                    datasets: [{
                            label: 'Precision',
                            data: {!! json_encode($precision) !!},
                            backgroundColor: 'rgba(59,130,246,0.7)'
                        },
                        {
                            label: 'Recall',
                            data: {!! json_encode($recall) !!},
                            backgroundColor: 'rgba(16,185,129,0.7)'
                        },
                        {
                            label: 'F1 Score',
                            data: {!! json_encode($f1) !!},
                            backgroundColor: 'rgba(239,68,68,0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            stepSize: 0.1
                        }
                    }
                }
            });
            new Chart(document.getElementById('evalMetricsChart'), {
                type: 'bar',
                data: {
                    labels: classes,
                    datasets: [{
                        label: 'Precision',
                        data: precision,
                        backgroundColor: 'rgba(59,130,246,0.7)'
                    }, {
                        label: 'Recall',
                        data: recall,
                        backgroundColor: 'rgba(16,185,129,0.7)'
                    }, {
                        label: 'F1 Score',
                        data: f1,
                        backgroundColor: 'rgba(239,68,68,0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            stepSize: 0.1
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
