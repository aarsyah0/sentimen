<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sentiment Analysis Perview</title>
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
    <!-- WordCloud2.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wordcloud2.js/1.0.6/wordcloud2.min.js"></script>
</head>

<body class="bg-white text-black antialiased">

    <!-- Navbar -->
    <nav class="relative z-20 bg-gradient-to-r from-blue-800 via-blue-600 to-blue-500 shadow-2xl backdrop-blur-md">
        <div class="max-w-screen-xl mx-auto flex items-center justify-between px-8 py-5">
            <div class="flex items-center space-x-4">
                <div class="transform transition-transform duration-500 ease-in-out">
                    <img src="/assets/pj.png" alt="Polije Logo"
                        class="h-12 w-auto drop-shadow-[0_4px_8px_rgba(0,0,0,0.4)] transform transition-transform duration-300 hover:scale-125" />

                </div>
                <span class="text-3xl font-extrabold text-white tracking-tight drop-shadow-md">Sentiment
                    Dashboard</span>
            </div>
            <div class="hidden md:flex items-center space-x-8">
                <a href="#"
                    class="text-white font-medium hover:text-blue-100 transition duration-300 ease-in-out">Home</a>
                <a href="#"
                    class="text-white font-medium hover:text-blue-100 transition duration-300 ease-in-out">About</a>
                <a href="#"
                    class="text-white font-medium hover:text-blue-100 transition duration-300 ease-in-out">Contact</a>
                <!-- Login Button -->
                <a href="{{ route('login') }}"
                    class="ml-4 px-4 py-2 bg-white text-blue-600 font-semibold rounded-lg shadow hover:bg-blue-50 transition">
                    Login
                </a>
            </div>
            <div class="md:hidden">
                <button class="text-white focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>


    <!-- Hero -->
    <header class="bg-white pt-16">
        <div class="max-w-screen-xl mx-auto py-16 px-8 text-center">
            <h1 class="text-6xl font-extrabold text-blue-600 drop-shadow-lg">Sentiment Analysis Perview</h1>
            <p class="mt-4 text-2xl text-gray-800">Professional insights with Naive Bayes & WordCloud</p>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-8 py-12 space-y-12">

        <!-- Charts Overview -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <h2 class="col-span-full text-4xl font-bold text-gray-900 text-center">Charts Overview</h2>

            <!-- Sentiment Distribution Card -->
            <div
                class="bg-white rounded-3xl shadow-2xl p-8 transform hover:rotate-1 hover:scale-105 transition ease-in-out duration-500">
                <h3 class="text-2xl font-semibold mb-6">Distribusi Sentimen</h3>
                <canvas id="sentChart" class="w-full h-64"></canvas>
            </div>

            <!-- Confusion Matrix Card -->
            <div
                class="bg-white rounded-3xl shadow-2xl p-8 transform hover:-rotate-1 hover:scale-105 transition ease-in-out duration-500 overflow-auto">
                <h3 class="text-2xl font-semibold mb-6">Confusion Matrix</h3>
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="py-3 text-left">True ↓ / Pred →</th>
                            @foreach ($cmCols as $col)
                                <th class="py-3 text-left text-gray-700">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cmRows as $row)
                            <tr class="border-b">
                                <th class="py-3 text-left font-medium text-gray-800">{{ $row[$trueLabelKey] }}</th>
                                @foreach ($cmCols as $col)
                                    @php
                                        $v = intval($row[$col]);
                                        $m = max(array_column($cmRows, $col)) ?: 1;
                                        $shade = 240 - intval(140 * ($v / $m));
                                    @endphp
                                    <td class="py-3 text-center"
                                        style="background:rgb({{ $shade }},{{ $shade }},255)">
                                        {{ $v }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Precision & Recall Card -->
            <div
                class="bg-white rounded-3xl shadow-2xl p-8 transform hover:rotate-1 hover:scale-105 transition ease-in-out duration-500">
                <h3 class="text-2xl font-semibold mb-6">Precision & Recall</h3>
                <canvas id="prChart" class="w-full h-64"></canvas>
            </div>

            <!-- F1-Score Card -->
            <div
                class="bg-white rounded-3xl shadow-2xl p-8 transform hover:-rotate-1 hover:scale-105 transition ease-in-out duration-500">
                <h3 class="text-2xl font-semibold mb-6">F1-Score</h3>
                <canvas id="f1Chart" class="w-full h-64"></canvas>
            </div>

            <!-- Top Features Card spans full width -->
            <div
                class="col-span-full bg-white rounded-3xl shadow-2xl p-8 transform hover:scale-105 transition ease-in-out duration-500">
                <h3 class="text-2xl font-semibold mb-6">Top Fitur Teratas</h3>
                <canvas id="featChart" class="w-full h-64"></canvas>
            </div>

        </section>

        <!-- Word Clouds Section -->
        <section class="space-y-8">
            <h2 class="text-4xl font-bold text-gray-900 text-center">Word Clouds per Sentimen</h2>
            <div class="grid grid-cols-1 gap-8">
                @php $labelMap = [0 => 'Negatif', 1 => 'Netral', 2 => 'Positif']; @endphp
                @foreach ($labelMap as $code => $label)
                    <div
                        class="bg-white rounded-3xl shadow-2xl p-8 transform hover:rotate-1 hover:scale-105 transition ease-in-out duration-500">
                        <h1 class="text-xl font-semibold mb-4 text-center">{{ $label }}</h1>
                        <div id="wc-{{ $code }}" class="w-[500px] h-[500px] mx-auto"></div>
                    </div>
                @endforeach
            </div>
        </section>



    </main>

    <script>
        const sentimentColors = ['rgba(239,68,68,0.8)', 'rgba(234,179,8,0.8)', 'rgba(16,185,129,0.8)'];
        // Sentiment bars use colors per category
        new Chart(document.getElementById('sentChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($labelsSent) !!},
                datasets: [{
                    data: {!! json_encode($dataSent) !!},
                    backgroundColor: {!! json_encode($dataSent) !!}.map((_, i) => sentimentColors[i])
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Precision & Recall charts: each dataset colored by sentiment
        new Chart(document.getElementById('prChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($classes) !!},
                datasets: [{
                        label: 'Precision',
                        data: {!! json_encode($precision) !!},
                        backgroundColor: sentimentColors
                    },
                    {
                        label: 'Recall',
                        data: {!! json_encode($recall) !!},
                        backgroundColor: sentimentColors
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1.2
                    }
                }
            }
        });

        // F1-Score chart
        new Chart(document.getElementById('f1Chart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($classes) !!},
                datasets: [{
                    data: {!! json_encode($f1) !!},
                    backgroundColor: sentimentColors
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1
                    }
                }
            }
        });

        // Feature importance remains blue
        new Chart(document.getElementById('featChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($featNames) !!},
                datasets: [{
                    data: {!! json_encode($featScores) !!},
                    backgroundColor: 'rgba(0,123,255,0.85)'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        @foreach ([0, 1, 2] as $code)
            WordCloud(document.getElementById('wc-{{ $code }}'), {
                list: {!! json_encode($wordLists[$code] ?? []) !!},
                gridSize: 8,
                weightFactor: 2,
                rotateRatio: 0.1
            });
        @endforeach
    </script>

</body>

</html>
