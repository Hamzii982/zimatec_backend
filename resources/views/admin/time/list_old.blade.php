@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alle Zeitaufzeichnungen</h5>
            {{-- <a href="{{ route('time-records.create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neue Aufzeichnung
            </a> --}}
        </div>

        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <select name="user_id" class="form-select">
                        <option value="">Alle Benutzer</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="project_id" class="form-select">
                        <option value="">Alle Aufträge</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->project_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="machine_id" class="form-select">
                        <option value="">Alle Maschinen</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>
                                {{ $machine->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Alle Status</option>
                        <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Aktiv</option>
                        <option value="ended" {{ request('status')=='ended' ? 'selected' : '' }}>Beendet</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-filter me-2">Filtern</button>
                    <a href="{{ route('admin.time.records') }}" class="btn btn-secondary">Zurücksetzen</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Bediener</th>
                            <th>Auftrag</th>
                            <th>Maschine</th>
                            <th>Start</th>
                            <th>Ende</th>
                            <th>Dauer</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $index => $record)
                            <tr>
                                <td>{{ $records->firstItem() + $index }}</td>
                                <td>{{ $record->user->name }}</td>
                                <td>
                                    {{ $record->project->project_name }}
                                    <small class="text-muted d-block project-auftrag">
                                        {{ "ZF: ".$record->project->auftragsnummer_zf ." ZT: " .$record->project->auftragsnummer_zt }}
                                    </small>
                                </td>
                                <td>{{ $record->machine->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($record->start_time)->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($record->end_time)
                                        {{ \Carbon\Carbon::parse($record->end_time)->format('d.m.Y H:i') }}
                                    @else
                                        <span class="badge bg-success">Läuft</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->end_time)
                                        {{ \Carbon\Carbon::parse($record->start_time)->diff($record->end_time) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.time.show', $record->id) }}" class="btn btn-outline-ansehen btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a href="{{ route('admin.time.edit', $record->id) }}" class="btn btn-sm btn-secondary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="{{ route('admin.time.delete', $record->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this status?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    Keine Zeitaufzeichnungen gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $records->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
