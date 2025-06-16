@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-2xl overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-chart-line mr-3"></i>
                    Hasil Inferensi Sentimen
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-gray-700"><i class="fas fa-quote-left text-blue-500 mr-2"></i><strong>Teks:</strong>
                    {{ $result['text'] }}</p>
                <p class="text-gray-700"><i class="fas fa-bullseye text-green-500 mr-2"></i><strong>Prediksi:</strong>
                    {{ $result['prediction'] }}</p>
                <div>
                    <h5 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-percentage text-purple-500 mr-2"></i>Probabilitas:
                    </h5>
                    <ul class="mt-2 space-y-1">
                        <li class="flex items-center">
                            <i class="fas fa-minus-circle text-gray-500 mr-2"></i>
                            <span class="font-medium">Netral:</span>
                            <span
                                class="ml-auto text-gray-800">{{ number_format($result['probabilities']['Netral'], 4) }}</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-smile text-green-500 mr-2"></i>
                            <span class="font-medium">Positif:</span>
                            <span
                                class="ml-auto text-gray-800">{{ number_format($result['probabilities']['Positif'], 4) }}</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-frown text-red-500 mr-2"></i>
                            <span class="font-medium">Negatif:</span>
                            <span
                                class="ml-auto text-gray-800">{{ number_format($result['probabilities']['Negatif'], 4) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="bg-gray-100 px-6 py-4 text-right">
                <a href="{{ route('sentiment.infer') }}"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
                    <i class="fas fa-redo-alt mr-2"></i>Analisis Lagi
                </a>
            </div>
        </div>
    </div>
@endsection
