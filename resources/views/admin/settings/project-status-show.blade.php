@extends('admin.layouts.index')

@section('title', $status->exists ? 'Auftragsstatus bearbeiten' : 'Auftragsstatus hinzufügen')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $status->exists ? 'Edit '.$status->name : 'Add New Status' }}</h5>
            <a href="{{ route('admin.settings.project-status') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-book-circle me-1"></i> Alle Auftragsstatus
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.settings.project-status.update', $status->id ?? null) }}" method="POST">
                @csrf
                @method('POST')

                {{-- Name --}}
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Status Name</label>
                    <input type="text" name="name" id="name" class="form-control" 
                           value="{{ old('name', $status->name) }}" required>
                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Color --}}
                <div class="mb-3">
                    <label for="color" class="form-label fw-semibold">Color</label>
                    <input type="color" name="color" id="color" class="form-control form-control-color"
                           value="{{ old('color', $status->color ?? '#000000') }}">
                    @error('color')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Active Checkbox --}}
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="active" name="active" 
                           {{ old('active', $status->active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="active">Active</label>
                </div>

                {{-- Buttons --}}
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-wechsel">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- === AJAX Script for toggle === --}}
<script>
document.querySelectorAll('.toggle-active').forEach((checkbox) => {
    checkbox.addEventListener('change', function() {
        const id = this.dataset.id;
        fetch(`/admin/settings/project-status/toggle/${id}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const status = data.active ? 'activated' : 'deactivated';
                console.log(`Status ${id} ${status}`);
                showAlert(`Machine status ${status} successfully.`, 'success');
            } else {
                showAlert('Something went wrong.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Server error, please try again.', 'danger');
        });
    });
});
</script>
@endsection
