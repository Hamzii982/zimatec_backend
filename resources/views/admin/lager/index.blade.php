@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alle Lager</h5>
            <a href="{{ route('admin.lager.create') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neues Lager
            </a>
        </div>

        <div class="card-body">

            <div class="table-responsive-wrapper">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Beschreibung</th>
                            <th>Aktiv</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lagers as $lager)
                            <tr>
                                <td>{{ $lager->name }}</td>
                                <td>{{ $lager->description }}</td>
                                <td>{{ $lager->is_active ? 'Ja' : 'Nein' }}</td>
                                <td>{{ $lager->status }}</td>
                                <td>{{ $lager->type }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.lager.show', $lager) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.lager.edit', $lager->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('admin.lager.destroy', $lager->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Diesen Lager wirklich löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Keine Lager gefunden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
