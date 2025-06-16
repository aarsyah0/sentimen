@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow rounded-4">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4 fw-bold">
                            <i class="fas fa-upload me-2"></i>Upload CSV Labeling & Train
                        </h2>

                        {{-- Error Handling --}}
                        @if ($errors->any())
                            <div class="alert alert-danger border-start border-4 border-danger">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Form Upload --}}
                        <form method="POST" action="{{ route('sentiment.upload.handle') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label for="csv_file" class="form-label fw-semibold">
                                    <i class="fas fa-file-csv me-1"></i>File CSV
                                </label>
                                <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv"
                                    required>
                                <div class="form-text">Pastikan kolom <code>clean_text</code> dan <code>label</code>
                                    tersedia di file.</div>
                            </div>

                            <button class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-cogs me-1"></i>Upload & Train
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
