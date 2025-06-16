@extends('layouts.app')

@section('title', 'Upload Data')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow rounded-4">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4 fw-bold">
                            <i class="fas fa-file-upload me-2"></i>Upload Data CSV
                        </h2>

                        {{-- Flash Messages --}}
                        @if (session('success'))
                            <div class="alert alert-success border-start border-4 border-success">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger border-start border-4 border-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                            </div>
                        @endif

                        {{-- Upload Form --}}
                        <form action="{{ route('upload.submit') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label for="files" class="form-label fw-semibold">
                                    <i class="fas fa-file-csv me-1"></i>Upload File CSV
                                </label>
                                <input type="file" class="form-control" name="files[]" id="files" accept=".csv"
                                    multiple required>
                                <div class="form-text">Pilih satu atau beberapa file berekstensi <code>.csv</code>.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-cloud-upload-alt me-1"></i>Upload & Process
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
