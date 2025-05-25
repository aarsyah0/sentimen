{{-- resources/views/admin/dashboard.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="container mx-auto p-6 space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-lg font-medium">Total Users</h3>
                <p class="text-2xl font-bold">{{ $userCount }}</p>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-lg font-medium">Total CSV Files</h3>
                <p class="text-2xl font-bold">{{ $csvCount }}</p>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-lg font-medium">Distribusi Sentimen</h3>
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        {{-- Line Chart --}}
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-medium mb-2">Akurasi Harian</h3>
            <canvas id="lineChart"></canvas>
        </div>

        {{-- Confusion Matrix --}}
        <div class="bg-white shadow rounded-lg p-4 overflow-auto">
            <h3 class="text-lg font-medium mb-2">Confusion Matrix</h3>
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        @foreach ($cmHeader as $col)
                            <th class="border px-4 py-2">{{ ucfirst($col) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cmRows as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td class="border px-4 py-2 text-center">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Evaluation Metrics --}}
        {{-- Evaluation Metrics --}}

        <div class="bg-white shadow rounded-lg p-4 overflow-auto">
            <h3 class="text-lg font-medium mb-2">Evaluation Metrics (Full)</h3>

            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        @foreach (array_keys($evalMetrics[0] ?? []) as $col)
                            <th class="border px-4 py-2">{{ ucfirst($col) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($evalMetrics as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td class="border px-4 py-2 text-center">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

    </div>
    <div class="flex justify-end mb-4">
        <a href="{{ route('viz.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow">
            Lihat Visualisasi Publik
        </a>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Pie Chart Sentiment Distribution
        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: {!! json_encode(array_keys($distCounts)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($distCounts)) !!}
                }]
            }
        });

        // Line Chart Akurasi Harian
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($dates) !!},
                datasets: [{
                    label: 'Akurasi',
                    data: {!! json_encode($accuracies) !!},
                    fill: false,
                    tension: 0.1
                }]
            }
        });
    </script>
@endpush
