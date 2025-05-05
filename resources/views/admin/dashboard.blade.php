<!-- resources/views/admin/dashboard.blade.php -->
@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Welcome Card -->
        <div
            class="bg-gradient-to-r from-blue-500 to-blue-700 text-white p-6 rounded-lg shadow-lg transform hover:scale-105 transition">
            <h2 class="text-2xl font-bold">Selamat datang, {{ auth()->user()->name }}! 👋</h2>
            <p class="mt-2 opacity-90">Ringkasan aktivitas dan statistik aplikasi Anda:</p>
        </div>

        <!-- Statistic Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow-xl p-5 flex items-center space-x-4 transform hover:scale-105 transition">
                <div class="p-3 bg-blue-100 rounded-full">
                    <!-- Icon Users -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M20 7a4 4 0 11-8 0 4 4 0 018 0zM12 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold">{{ $userCount }}</h3>
                    <p class="text-sm text-gray-500">Total Users</p>
                </div>
            </div>

            <!-- Uploaded CSV Files -->
            <div class="bg-white rounded-lg shadow-xl p-5 flex items-center space-x-4 transform hover:scale-105 transition">
                <div class="p-3 bg-blue-100 rounded-full">
                    <!-- Icon Upload -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v8m0-8l-3 3m3-3l3 3M12 4v8" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold">{{ $csvCount }}</h3>
                    <p class="text-sm text-gray-500">CSV Files Uploaded</p>
                </div>
            </div>

            <!-- Data Labeling Trend -->
            <div class="bg-white rounded-lg shadow-xl p-5 transform hover:scale-105 transition">
                <h3 class="text-lg font-medium mb-4">Tren Data Labeling</h3>
                <canvas id="dataLabelingChart" class="w-full h-32"></canvas>
            </div>

            <!-- Sentiment Distribution Pie -->
            <div class="bg-white rounded-lg shadow-xl p-5 transform hover:scale-105 transition">
                <h3 class="text-lg font-medium mb-4">Distribusi Sentimen</h3>
                <canvas id="sentimentPieChart" class="w-full h-32"></canvas>
            </div>

            <!-- Quick Actions -->
            <div
                class="bg-white rounded-lg shadow-xl p-5 flex flex-col justify-between transform hover:scale-105 transition">
                <h3 class="text-lg font-medium mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('upload.form') }}"
                        class="block text-center py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Upload
                        CSV</a>
                    <a href="{{ route('viz.index') }}"
                        class="block text-center py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition">Lihat
                        Visualisasi</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Line Chart: Data Labeling
        new Chart(document.getElementById('dataLabelingChart'), {
            type: 'line',
            data: {
                labels: Object.keys(@json($labelCounts)),
                datasets: [{
                    label: 'Jumlah Data per Sentiment',
                    data: Object.values(@json($labelCounts)),
                    fill: false,
                    tension: 0.4,
                    borderWidth: 2
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

        // Pie Chart: Sentiment Distribution
        new Chart(document.getElementById('sentimentPieChart'), {
            type: 'pie',
            data: {
                labels: @json($labelsSent),
                datasets: [{
                    data: @json($dataSent),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
@endpush
