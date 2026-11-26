@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Auftragsangebote</h5>
            <a href="{{ route('admin.project_offers.create') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neue Angebot
            </a>
        </div>

        <div class="card-body">

            <div class="table-responsive-wrapper">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offers as $offer)
                            <tr>
                                <td>{{ $offer->id }}</td>
                                <td>{{ $offer->customer_name ?? '-' }}</td>
                                <td><a href="{{ route('admin.project_offers.show', $offer) }}" class="text-decoration-none text-dark">{{ $offer->subject }}</a></td>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($offer->status) }}</span></td>
                                <td>{{ $offer->assignedUser->name ?? '-' }}</td>
                                <td>{{ $offer->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.project_offers.show', $offer) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.project_offers.edit', $offer) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.project_offers.destroy', $offer) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button onclick="return confirm('Are you sure?')" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No offers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $offers->links() }}
        </div>
    </div>
</div>
@endsection
