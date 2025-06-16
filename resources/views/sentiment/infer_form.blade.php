@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="max-w-xl mx-auto bg-white shadow-lg rounded-2xl overflow-hidden">
            <div class="bg-indigo-600 px-6 py-4">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-magic mr-2"></i>
                    Inferensi Sentimen
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i>Kesalahan!</strong>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('sentiment.infer.do') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="run_id" class="block text-gray-700 font-semibold mb-1">
                            <i class="fas fa-list-alt text-indigo-500 mr-1"></i>Pilih Run
                        </label>
                        <select name="run_id" id="run_id"
                            class="block w-full border border-gray-300 rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                            @foreach ($allRunIds as $id)
                                <option value="{{ $id }}"
                                    {{ old('run_id', $latestRunId) === $id ? 'selected' : '' }}>
                                    {{ $id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="text" class="block text-gray-700 font-semibold mb-1">
                            <i class="fas fa-comment-alt text-green-500 mr-1"></i>Masukkan Teks
                        </label>
                        <textarea name="text" id="text" rows="4"
                            class="block w-full border border-gray-300 rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500" required>{{ old('text') }}</textarea>
                    </div>

                    <div class="text-right">
                        <button
                            class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                            <i class="fas fa-cogs mr-2"></i>Analisis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
