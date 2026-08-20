@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Benutzerprofil</h5>
            <div>
                <a href="{{ route('admin.users') }}" class="btn btn-secondary btn-sm me-1">
                    <i class="bi bi-arrow-left-circle me-1"></i> Zurück
                </a>
            </div>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <strong>Name:</strong>
                <p>{{ $user->name }}</p>
            </div>

            <div class="mb-3">
                <strong>E-Mail:</strong>
                <p>{{ $user->email }}</p>
            </div>

            <div class="mb-3">
                <strong>Rolle:</strong>
                <p>{{ ucfirst($user->role) }}</p>
            </div>

            <div class="mb-3">
                <strong>Erstellt am:</strong>
                <p>{{ $user->created_at->format('d.m.Y H:i') }}</p>
            </div>

            <div class="mb-3">
                <strong>Zuletzt aktualisiert:</strong>
                <p>{{ $user->updated_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
