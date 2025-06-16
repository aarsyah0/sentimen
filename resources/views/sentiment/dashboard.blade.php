@extends('layouts.app')
@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate__animated animate__fadeIn">

        {{-- Judul Dashboard --}}
        <div class="flex items-center mb-10">
            <i class="fas fa-chart-line text-indigo-500 text-3xl mr-3"></i>
            <h1 class="text-3xl font-extrabold text-indigo-700">Dashboard Sentiment Analysis</h1>
        </div>

        {{-- Ringkasan KPI: Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-12">
            {{-- Total Runs --}}
            <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition flex flex-col justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-200 rounded-full">
                        <i class="fas fa-play-circle text-blue-600"></i>
                    </div>
                    <p class="ml-3 text-sm font-semibold text-blue-700">Total Runs</p>
                </div>
                <p class="text-4xl font-bold text-blue-900 mt-4">{{ $totalRuns }}</p>
                <p class="text-xs text-gray-500 mt-2">Menjalankan pipeline sebanyak {{ $totalRuns }} kali.</p>
            </div>

            {{-- Avg Accuracy --}}
            <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition flex flex-col justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-green-200 rounded-full">
                        <i class="fas fa-percentage text-green-600"></i>
                    </div>
                    <p class="ml-3 text-sm font-semibold text-green-700">Avg Accuracy</p>
                </div>
                <p class="text-4xl font-bold text-green-900 mt-4">
                    {{ $avgAccuracy !== null ? number_format($avgAccuracy * 100, 2) . '%' : '-' }}
                </p>
                <p class="text-xs text-gray-500 mt-2">Rata-rata akurasi model.</p>
            </div>

            {{-- Avg F1-score --}}
            <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition flex flex-col justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-200 rounded-full">
                        <i class="fas fa-balance-scale text-yellow-600"></i>
                    </div>
                    <p class="ml-3 text-sm font-semibold text-yellow-700">Avg F1-score</p>
                </div>
                <p class="text-4xl font-bold text-yellow-900 mt-4">
                    {{ $avgF1 !== null ? number_format($avgF1 * 100, 2) . '%' : '-' }}
                </p>
                <p class="text-xs text-gray-500 mt-2">Rata-rata F1-score model.</p>
            </div>

            {{-- Last Run --}}
            <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition flex flex-col justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-200 rounded-full">
                        <i class="fas fa-history text-gray-600"></i>
                    </div>
                    <p class="ml-3 text-sm font-semibold text-gray-700">Last Run</p>
                </div>
                @if ($lastRun)
                    <div class="mt-4 space-y-1">
                        <p class="text-lg font-medium text-gray-900">ID: {{ $lastRun->run_id }}</p>
                        <p class="text-xs text-gray-500">{{ $lastRun->run_timestamp->format('Y-m-d H:i') }}</p>
                        <p class="text-sm">
                            <span class="text-green-600 font-medium">Acc:</span> {{ number_format($lastRun->accuracy, 4) }},
                            <span class="text-blue-600 font-medium">F1:</span> {{ number_format($lastRun->f1_score, 4) }}
                        </p>
                        <p class="text-xs text-gray-500">Size: {{ $lastRun->data_size }}, Duration:
                            {{ $lastRun->training_duration }}s</p>
                    </div>
                @else
                    <p class="text-sm text-gray-500 mt-4">Belum ada data run.</p>
                @endif
            </div>
        </div>

        {{-- Section: Distribusi Kelas & Data Size vs Accuracy --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-24">
            {{-- Distribusi Kelas --}}
            <div class="h-full">
                <h2 class="text-xl font-semibold text-indigo-700 mb-4 border-b pb-2 border-indigo-200 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-indigo-500"></i>
                    Distribusi Kelas
                </h2>
                <div
                    class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition flex flex-col justify-between h-full min-h-[350px]">
                    <div>
                        <p class="text-sm text-gray-600 mb-4">
                            Proporsi kelas run Terbaru:
                        </p>
                        <p class="text-xs text-gray-500 mb-4">{{ $classMessage }}</p>
                    </div>
                    <div class="w-full h-80">
                        <canvas id="classDistPie"></canvas>
                    </div>
                </div>
            </div>

            {{-- Data Size vs Accuracy --}}
            <div class="h-full">
                <h2 class="text-xl font-semibold text-indigo-700 mb-4 border-b pb-2 border-indigo-200 flex items-center">
                    <i class="fas fa-braille mr-2 text-indigo-500"></i>
                    Data Size vs Accuracy
                </h2>
                <div
                    class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition flex flex-col justify-between h-full min-h-[350px]">
                    <div>
                        <p class="text-sm text-gray-600 mb-4">
                            Scatter plot memetakan setiap run: data_size vs accuracy.<br>
                            Contoh: data_size={{ $lastRun->data_size ?? 0 }},
                            accuracy={{ number_format($lastRun->accuracy ?? 0, 4) }}.
                        </p>
                        <p class="text-xs text-gray-500 mb-4">{{ $scatterMessage }}</p>
                    </div>
                    <canvas id="scatterDataSizeAcc" class="w-full h-48"></canvas>
                </div>
            </div>
        </div>

        {{-- Section: Perbandingan Sentimen per Versi iOS --}}
        <div class="mb-12">
            <h2 class="text-xl font-semibold text-indigo-700 mb-4 border-b pb-2 border-indigo-200 flex items-center">
                <i class="fas fa-bar-chart mr-2 text-indigo-500"></i>
                Perbandingan Sentimen per Versi iOS run Terbaru
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach ($versionMetrics as $vm)
                    @php $slug = Str::slug($vm->version); @endphp
                    <div class="bg-white p-5 rounded-2xl shadow hover:shadow-lg transition flex flex-col items-center">
                        <canvas id="bar{{ $slug }}" class="w-full" style="height: 200px;"></canvas>

                        <p class="text-xs text-gray-500 mt-3 text-center">{{ $versionMessages[$vm->version] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Section: Tren Metrik --}}
        <div>
            <h2 class="text-xl font-semibold text-indigo-700 mb-4 border-b pb-2 border-indigo-200 flex items-center">
                <i class="fas fa-chart-area mr-2 text-indigo-500"></i>
                Tren Metrik
            </h2>
            <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition w-full h-full min-h-[250px]">

                <p class="text-sm text-gray-600 mb-4">
                    Titik awal: {{ $labels[0] ?? '–' }} / Acc={{ $accuracyData[0] ?? '–' }},
                    Titik akhir: {{ end($labels) ?? '–' }} / Acc={{ end($accuracyData) ?? '–' }}.
                </p>
                <p class="text-xs text-gray-500 mb-4">{{ $trendMessage }}</p>
                <canvas id="kpiChart" class="w-full h-[100px]"></canvas>

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
            const labels = @json($labels);
            const accuracyData = @json($accuracyData);
            const precisionData = @json($precisionData);
            const recallData = @json($recallData);
            const f1Data = @json($f1Data);
            const classLabels = @json(array_keys($lastClassDist));
            const classValues = @json(array_values($lastClassDist));
            const verLabels = @json($versionMetrics->pluck('version'));
            const posCnt = @json($versionMetrics->pluck('count_positive'));
            const negCnt = @json($versionMetrics->pluck('count_negative'));
            const neuCnt = @json($versionMetrics->pluck('count_neutral'));
            const posPct = @json($versionMetrics->pluck('pct_positive')).map(x => +x);
            const negPct = @json($versionMetrics->pluck('pct_negative')).map(x => +x);
            const neuPct = @json($versionMetrics->pluck('pct_neutral')).map(x => +x);

            // Fungsi default animasi
            const defaultAnimation = {
                duration: 800,
                easing: 'easeOutQuart'
            };

            // Tren Metrik
            new Chart('kpiChart', {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                            label: 'Accuracy',
                            data: accuracyData,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.1)',
                            tension: 0.5,
                            pointRadius: 3,
                            fill: true
                        },
                        {
                            label: 'Precision',
                            data: precisionData,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34,197,94,0.1)',
                            tension: 0.5,
                            pointRadius: 3,
                            fill: true
                        },
                        {
                            label: 'Recall',
                            data: recallData,
                            borderColor: '#eab308',
                            backgroundColor: 'rgba(234,179,8,0.1)',
                            tension: 0.5,
                            pointRadius: 3,
                            fill: true
                        },
                        {
                            label: 'F1-score',
                            data: f1Data,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.5,
                            pointRadius: 3,
                            fill: true
                        }
                    ]
                },
                options: {
                    animation: defaultAnimation,
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 20
                            }
                        },
                        datalabels: false
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 0
                            }
                        },
                        y: {
                            min: 0,
                            max: 1,
                            title: {
                                display: true,
                                text: 'Value'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        }
                    }
                }
            });


            // Scatter Data Size vs Accuracy
            const scatterData = @json($kpis->map(fn($k) => ['x' => $k->data_size ?? 0, 'y' => $k->accuracy ?? 0]));
            new Chart('scatterDataSizeAcc', {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: 'Data vs Accuracy',
                        data: scatterData,
                        pointRadius: 5
                    }]
                },
                options: {
                    animation: defaultAnimation,
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `Size: ${ctx.raw.x}, Acc: ${ctx.raw.y.toFixed(3)}`
                            }
                        },
                        datalabels: false
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Data Size'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        y: {
                            min: 0,
                            max: 1,
                            title: {
                                display: true,
                                text: 'Accuracy'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        }
                    }
                }
            });

            // Pie Chart Class Distribution
            new Chart('classDistPie', {
                type: 'pie',
                data: {
                    labels: classLabels, // pastikan urutannya: ['Positif','Negatif','Netral']
                    datasets: [{
                        data: classValues,
                        backgroundColor: [
                            '#4CAF50', // Positif
                            '#F44336', // Negatif
                            '#FFC107' // Netral
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    animation: defaultAnimation,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const value = ctx.raw;
                                    const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a +
                                        b, 0);
                                    const pct = (value / total * 100).toFixed(1);
                                    return `${ctx.label}: ${value} (${pct}%)`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            formatter: (value, ctx) => {
                                const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b,
                                    0);
                                const pct = (value / total * 100).toFixed(1);
                                return `${pct}%`;
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 11
                            }
                        }
                    }
                }
            });

            // Bar Charts per versi iOS
            verLabels.forEach((ver, i) => {
                const ctx = document.getElementById('bar' + ver).getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Positif', 'Negatif', 'Netral'],
                        datasets: [{
                            label: `Versi iOS ${ver}`,
                            data: [posCnt[i], negCnt[i], neuCnt[i]],
                            backgroundColor: [
                                '#4CAF50', // Positif → hijau
                                '#F44336', // Negatif → merah
                                '#FFC107' // Netral  → kuning
                            ],
                            borderColor: '#fff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        animation: defaultAnimation,
                        responsive: true,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: `Distribusi Sentimen - Versi iOS ${ver}`
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx =>
                                        `${ctx.label}: ${ctx.raw} (${[posPct, negPct, neuPct][ctx.dataIndex][i].toFixed(1)}%)`
                                }
                            },
                            datalabels: {
                                display: ctx => [posPct, negPct, neuPct][ctx.dataIndex][i] > 0,
                                formatter: (_, ctx) => [posPct, negPct, neuPct][ctx.dataIndex][i]
                                    .toFixed(1) + '%',
                                anchor: 'end',
                                align: 'top',
                                font: {
                                    weight: 'bold',
                                    size: 11
                                }
                            }
                        }
                    }
                });
            });


        });
    </script>
@endpush
