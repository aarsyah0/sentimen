@extends('layouts.app')

@section('title', 'Result Data')

@section('content')
    <div class="container py-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">
                <i class="fas fa-microscope text-primary me-2"></i>Hasil Preprocessing Data
            </h2>
            <p class="text-muted small">Data hasil pembersihan ditampilkan di bawah ini.</p>
        </div>

        {{-- Flash Message --}}
        @if (session('message'))
            <div class="alert alert-info d-flex align-items-center justify-content-between rounded-3">
                <div><i class="fas fa-info-circle me-2"></i>{{ session('message') }}</div>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (isset($data) && $data->count())
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="table-responsive" style="max-height: 480px;">
                    <table class="table table-hover table-striped align-middle table-sm mb-0 text-sm"
                        style="min-width: 600px;">
                        <thead class="table-primary sticky-top shadow-sm">
                            <tr class="text-center text-dark">
                                @foreach (array_keys($data->first()) as $col)
                                    <th class="py-3">{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $row)
                                <tr>
                                    @foreach ($row as $key => $value)
                                        <td class="py-2">
                                            @if (in_array($key, ['label']) && $value !== '')
                                                <span
                                                    class="badge rounded-pill
                                                    @if ($value === 'positif') bg-success
                                                    @elseif ($value === 'negatif') bg-danger
                                                    @else bg-secondary @endif">
                                                    {{ ucfirst($value) }}
                                                </span>
                                            @else
                                                {{ $value !== '' ? $value : '–' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                    <small class="text-muted">
                        Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ $data->total() }} entri
                    </small>
                    {{ $data->links('pagination::bootstrap-5') }}
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('data.download') }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i>Download CSV
                </a>
            </div>
        @else
            <div class="alert alert-warning text-center rounded-pill">
                <i class="fas fa-exclamation-triangle me-2"></i> Tidak ada data tersedia.
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            thead th {
                background-color: #e9f3ff !important;
            }

            .table-responsive::-webkit-scrollbar {
                height: 6px;
                width: 6px;
            }

            .table-responsive::-webkit-scrollbar-thumb {
                background-color: rgba(13, 110, 253, 0.5);
                border-radius: 3px;
            }

            .badge {
                font-size: 0.75rem;
                padding: 0.4em 0.7em;
            }
        </style>
    @endpush
@endsection
