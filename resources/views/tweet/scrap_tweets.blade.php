@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow rounded-4">
                    <div class="card-body p-5">
                        <h1 class="card-title h3 fw-bold mb-4">
                            <i class="fas fa-hashtag me-2"></i> Scrape Tweets dari Twitter
                        </h1>

                        @if (session('status'))
                            <div class="alert alert-info border-start border-4 border-primary">
                                <pre class="mb-0 small text-dark">{{ session('status') }}</pre>
                            </div>
                        @endif

                        <form action="{{ route('scrap.tweets') }}" method="POST">
                            @csrf

                            {{-- Keyword --}}
                            <div class="mb-3">
                                <label for="keyword" class="form-label fw-semibold">
                                    <i class="fas fa-search me-1"></i> Versi iOS
                                </label>
                                <select name="keyword" id="keyword"
                                    class="form-select @error('keyword') is-invalid @enderror">
                                    @foreach (['ios15', 'ios16', 'ios17'] as $ios)
                                        <option value="{{ $ios }}"
                                            {{ old('keyword', 'ios15') === $ios ? 'selected' : '' }}>
                                            {{ strtoupper($ios) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilih versi iOS yang ingin di-scrape (bahasa otomatis lang:id).</div>
                                @error('keyword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            {{-- Limit --}}
                            <div class="mb-3">
                                <label for="limit" class="form-label fw-semibold">
                                    <i class="fas fa-list-ol me-1"></i> Limit Tweet
                                </label>
                                <input type="number" name="limit" id="limit" min="1"
                                    value="{{ old('limit', 1000) }}"
                                    class="form-control @error('limit') is-invalid @enderror">
                                @error('limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Token --}}
                            <div class="mb-3">
                                <label for="token" class="form-label fw-semibold">
                                    <i class="fas fa-key me-1"></i> Twitter Auth Token
                                </label>

                                <div class="input-group">
                                    <input type="password" name="token" id="token" value="{{ old('token') }}"
                                        class="form-control @error('token') is-invalid @enderror"
                                        placeholder="Masukkan Bearer Token Twitter kamu">
                                    <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleTokenVisibility()">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>

                                <div class="form-text">Token bersifat rahasia, jangan disebar.</div>

                                @error('token')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>


                            {{-- Button & Download --}}
                            <div class="d-flex align-items-center gap-3 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-rocket me-1"></i> Mulai Scrape
                                </button>

                                @php
                                    $filename = session('csv_filename');
                                    $mainPath = public_path('tweets-data/' . $filename);
                                    $fallbackPath = public_path('tweets-data/tweets-data/' . $filename);

                                    $fileExists = $filename && (file_exists($mainPath) || file_exists($fallbackPath));

                                    $fileUrl = null;
                                    if ($fileExists) {
                                        $fileUrl = file_exists($mainPath)
                                            ? asset('tweets-data/' . $filename)
                                            : asset('tweets-data/tweets-data/' . $filename);
                                    }
                                @endphp

                                @if ($fileExists)
                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-success">
                                        <i class="fas fa-file-csv me-1"></i> Download CSV
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleTokenVisibility() {
            const input = document.getElementById("token");
            const icon = document.getElementById("eyeIcon");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
@endpush
