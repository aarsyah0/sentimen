@extends('admin.layouts.app')
@section('title', 'Upload Data CSV')

@section('content')
    <div class="max-w-3xl mx-auto py-8">
        @if (session('success'))
            <div class="mb-6 px-4 py-3 bg-green-100 border border-green-200 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 px-4 py-3 bg-red-100 border border-red-200 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-4">Form Upload CSV</h2>

            <form action="{{ route('upload.data') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                @foreach ([
            'confusion_matrix' => 'Confusion Matrix',
            'data_with_labels' => 'Data with Labels',
            'df_full_predictions' => 'Full Predictions',
            'evaluation_metrics_full' => 'Evaluation Metrics (Full)',
            'confusion_matrixuji' => 'Confusion Matrix (Uji)',
            'evaluation_metricsuji' => 'Evaluation Metrics (Uji Full)',
        ] as $field => $label)
                    <div>
                        <label for="{{ $field }}" class="block text-gray-700 font-medium mb-1">
                            {{ $label }} ({{ $field }}.csv)
                        </label>
                        <input type="file" name="{{ $field }}" id="{{ $field }}" accept=".csv,text/csv"
                            required
                            class="block w-full text-gray-700 bg-gray-50 border border-gray-300 rounded-md
                                   file:mr-4 file:py-2 file:px-4
                                   file:rounded-lg file:border-0
                                   file:text-sm file:font-semibold
                                   file:bg-blue-600 file:text-white
                                   hover:file:bg-blue-700
                                   @error($field) border-red-500 @enderror">
                        @error($field)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg
                           shadow-md transform hover:scale-105 transition duration-200">
                    Upload Empat CSV
                </button>
            </form>
        </div>
    </div>
@endsection
