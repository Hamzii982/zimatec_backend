@extends('admin.layouts.index')

@section('title', 'Admin Search Results')

@section('content')
<div class="container py-5">
    
    @if($results->isEmpty())
        <div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 60vh;">
            <div class="display-1 fw-bold mb-3 mt-5" style="background: linear-gradient(45deg, #6a11cb 0%, #2575fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Keine Ergebnisse
            </div>
            <h2 class="text-muted fw-light mb-4">
                Wir konnten nichts für <span class="fw-bold text-dark italic">"{{ $keyword }}"</span> finden.
            </h2>
            
            <a href="{{ url()->previous() }}" class="btn btn-outline-primary btn-lg rounded-pill px-5 shadow-sm transition">
                <i class="bi bi-arrow-left me-2"></i> Zurück zur vorherigen Seite
            </a>
        </div>
    @else
        <div class="row mb-4">
            <div class="col">
                <h2 class="h4 border-bottom pb-3">
                    Suchergebnisse für <span class="text-primary">"{{ $keyword }}"</span>
                </h2>
            </div>
        </div>

        <div class="row g-4">
            @foreach($results as $result)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary">{{ $result->project_name }}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                ZF: {{ $result->auftragsnummer_zf }}
                            </h6>
                            <p class="card-text text-secondary small">Details zum Projekt können hier stehen...</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="{{ route('admin.projects.show', $result->id) }}" class="btn btn-sm btn-light">Ansehen</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<style>
    /* Subtle hover effect for the back button */
    .btn-outline-primary:hover {
        transform: translateY(-2px);
        transition: all 0.2s ease-in-out;
    }
</style>
@endsection