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
        <div class="bg-white shadow rounded-lg p-4 overflow-auto">
            <h3 class="text-lg font-medium mb-2">Evaluation Metrics (Full)</h3>
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-4 py-2">Label</th>
                        <th class="border px-4 py-2">Precision</th>
                        <th class="border px-4 py-2">Recall</th>
                        <th class="border px-4 py-2">F1 Score</th>
                        <th class="border px-4 py-2">Support</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($evalMetrics as $m)
                        <tr>
                            <td class="border px-4 py-2 text-center">{{ $m['label'] }}</td>
                            <td class="border px-4 py-2 text-center">{{ $m['precision'] }}</td>
                            <td class="border px-4 py-2 text-center">{{ $m['recall'] }}</td>
                            <td class="border px-4 py-2 text-center">{{ $m['f1_score'] }}</td>
                            <td class="border px-4 py-2 text-center">{{ $m['support'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border px-4 py-2 text-center text-gray-500">
                                No evaluation metrics data.
                            </td>
                        </tr>
                    @endforelse
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
    <!-- Luxon (global build), Chart.js & Luxon Adapter -->
    <script src="https://cdn.jsdelivr.net/npm/luxon@2/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1"></script>

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
    </script>
@endpush
