@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Positionen – {{ $project->project_name }}
            </h5>

            <div>
                <a href="{{ route('admin.projects') }}"
                   class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Aufträge
                </a>

                <a href="{{ route('admin.projects.positions.create', $project) }}"
                   class="btn btn-secondary btn-sm">
                    <i class="bi bi-plus-circle"></i> Position erstellen
                </a>
            </div>
        </div>

        <div class="card-body">
            @if($positions->isEmpty())
                <p class="text-muted text-center mb-0">
                    Für diesen Auftrag wurden keine Positionen gefunden.
                </p>
            @else
                <div class="table-responsive-wrapper">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Leistung</th>
                                <th class="text-center">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($positions as $index => $position)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $position->name }}</td>
                                    <td>{{ $position->projectService->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.projects.positions.edit', [$project, $position]) }}"
                                        class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form method="POST"
                                            action="{{ route('admin.projects.positions.destroy', [$project, $position]) }}"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Diese Position löschen?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
