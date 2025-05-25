<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sentiment Analysis Perview</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
    <!-- WordCloud2.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wordcloud2.js/1.0.6/wordcloud2.min.js"></script>
    <style>
        .wc-container {
            width: 400px !important;
            height: 400px !important;
            border: 1px solid #ddd;
            margin: 1rem auto;
            display: block;
            position: relative;
        }
    </style>
</head>

<body class="bg-white text-black antialiased">

    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-blue-800 via-blue-600 to-blue-500 p-4 text-white shadow">
        <div class="max-w-screen-xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="/assets/pj.png" alt="Logo" class="h-12">
                <span class="text-2xl font-bold">Sentiment Dashboard</span>
            </div>
            <div>
                <a href="{{ route('login') }}" class="bg-white text-blue-600 px-4 py-2 rounded">Login</a>
            </div>
        </div>
    </nav>

    <header class="pt-16 bg-white">
        <div class="max-w-screen-xl mx-auto text-center py-16 px-4">
            <h1 class="text-6xl font-extrabold text-blue-600">Sentiment Analysis Perview</h1>
            <p class="mt-4 text-2xl text-gray-800">Professional insights with Naive Bayes & WordCloud</p>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-8 py-12 space-y-12">

        <!-- 1) Pie Chart: Distribusi Sentimen -->
        <section>
            <h2 class="text-3xl font-bold mb-4 text-blue-700">Distribusi Sentimen</h2>
            <canvas id="sentimentPie" class="max-w-md mx-auto"></canvas>
        </section>

        <!-- 2) Line Chart: Akurasi Harian -->
        <section>
            <h2 class="text-3xl font-bold mt-12 mb-4 text-blue-700">Akurasi Harian</h2>
            <canvas id="accuracyLine" class="w-full h-64"></canvas>
        </section>

        <!-- 3) Word Cloud -->
        <section>
            <h2 class="text-3xl font-bold mt-12 mb-4 text-blue-700">WordCloud per Label Sentimen</h2>
            <div class="flex flex-wrap justify-center">
                @foreach (['positive', 'negative', 'neutral'] as $lbl)
                    <div class="w-1/3 text-center p-4">
                        <h3 class="text-xl font-semibold capitalize">{{ $lbl }}</h3>
                        <!-- Inline size ensures WordCloud picks up dimensions -->
                        <div id="wc-{{ $lbl }}" class="wc-container"></div>
                        <ul class="mt-2 text-sm text-gray-700">
                            @foreach ($topFeaturesByLabel[$lbl] as $word => $count)
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- 4) Top Features -->
        <section>
            <h2 class="text-3xl font-bold mt-12 mb-4 text-blue-700">Top 10 Kata Teratas</h2>
            <table class="table-auto w-full text-left border border-gray-300 rounded-lg shadow-sm">
                <thead class="bg-blue-100 text-blue-800">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Kata</th>
                        <th class="px-4 py-2">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topFeatures as $word => $count)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 font-medium">{{ $word }}</td>
                            <td class="px-4 py-2">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <!-- 5) barchart Chart: Precision, Recall, F1 Score -->
        <section>
            <div class="bg-white shadow rounded-lg p-4 overflow-auto">
                <h3 class="text-lg font-medium mb-2">Evaluation Metrics (Full)</h3>
                <canvas id="evalMetricsChart" height="300"></canvas>
            </div>
        </section>

        <!-- 6) Confusion Matrix -->
        <section>
            <h2 class="text-3xl font-bold mt-12 mb-4 text-blue-700">Confusion Matrix</h2>
            <div class="overflow-auto">
                <table class="table-auto min-w-full text-left border border-gray-300 rounded-lg shadow-sm">
                    <thead class="bg-blue-100 text-blue-800">
                        <tr>
                            <th class="px-4 py-2">{{ $trueLabelKey }}</th>
                            @foreach ($cmCols as $col)
                                <th class="px-4 py-2">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
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

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Pie chart
            new Chart(document.getElementById('sentimentPie'), {
                type: 'pie',
                data: {
                    labels: {!! json_encode(array_keys($dataSent)) !!},
                    datasets: [{
                        data: {!! json_encode(array_values($dataSent)) !!},
                        backgroundColor: ['#EF4444', '#3B82F6', '#10B981'],
                        hoverOffset: 10
                    }]
                }
            });

            // Akurasi Harian
            new Chart(document.getElementById('accuracyLine'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($dates) !!},
                    datasets: [{
                        label: 'Akurasi',
                        data: {!! json_encode($accs) !!},
                        borderColor: 'rgba(37,99,235,1)',
                        backgroundColor: 'rgba(147,197,253,0.4)',
                        fill: true,
                        tension: 0.3
                    }]
                }
            });

            // WordCloud per label
            const freqsByLabel = @json($wordFrequenciesByLabel);
            Object.entries(freqsByLabel).forEach(([label, freqObj]) => {
                const list = Object.entries(freqObj);
                WordCloud(document.getElementById('wc-' + label), {
                    list,
                    gridSize: 12,
                    weightFactor: size => size * 1,
                    rotateRatio: 0.1,
                    minSize: 12,
                    backgroundColor: '#f8f9fa'
                });
            });

            // Evaluation Metrics Bar Chart
            new Chart(document.getElementById('evalMetricsChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($classes) !!},
                    datasets: [{
                            label: 'Precision',
                            data: {!! json_encode($precision) !!},
                            backgroundColor: 'rgba(54,162,235,0.7)'
                        },
                        {
                            label: 'Recall',
                            data: {!! json_encode($recall) !!},
                            backgroundColor: 'rgba(255,206,86,0.7)'
                        },
                        {
                            label: 'F1 Score',
                            data: {!! json_encode($f1) !!},
                            backgroundColor: 'rgba(75,192,192,0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 2,
                            ticks: {
                                stepSize: 0.1
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>
