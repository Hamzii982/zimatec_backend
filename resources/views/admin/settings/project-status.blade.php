@extends('admin.layouts.index')

@section('title', 'Settings - Project Statuses')

@section('content')
<div class="zt-compare container mt-4">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Projektstatus</h5>
            <a href="{{ route('admin.settings.project-status.show') }}" class="zt-export-btn">
                <i class="bi bi-plus-circle"></i> Neue Projektstatus
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Farbe</th>
                            <th>Aktiv</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statuses as $status)
                            <tr id="row-{{ $status->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-bold">{{ $status->name }}</td>
                                <td>
                                    <span class="zt-badge" style="background-color: {{ $status->color }}; color: #fff;">
                                        {{ $status->color }}
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input toggle-active"
                                            data-id="{{ $status->id }}" {{ $status->active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.settings.project-status.show', $status->id) }}" class="zt-icon-btn zt-icon-btn--edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('admin.settings.project-status.delete', $status->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="zt-icon-btn zt-icon-btn--danger" onclick="return confirm('Delete this status?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="zt-empty text-center py-4">No Records Yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .zt-compare {
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink, #1B1F24);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line, #DFE3E8); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--zt-ink, #1B1F24); color: #fff; border-bottom: none; }
    .zt-card > .card-body { background: var(--zt-bg, #F5F6F8); }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
        color: #fff; font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; text-decoration: none;
        transition: background .15s;
    }
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }

    .zt-empty { color: var(--zt-muted, #667085); font-size: .85rem; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line, #DFE3E8); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted, #667085);
        border-bottom: 1px solid var(--zt-line, #DFE3E8); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line, #DFE3E8); font-size: .84rem; }

    .zt-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--zt-line, #DFE3E8);
        background: #fff; color: var(--zt-muted, #667085); text-decoration: none;
    }
    .zt-icon-btn--edit:hover { border-color: var(--zt-ink, #1B1F24); color: var(--zt-ink, #1B1F24); }
    .zt-icon-btn--danger { color: #B3261E; }
    .zt-icon-btn--danger:hover { border-color: #B3261E; background: #FBEAE9; }

    .form-check-input.toggle-active:checked {
        background-color: var(--zt-ink, #1B1F24);
        border-color: var(--zt-ink, #1B1F24);
    }
    .zt-badge {
        display: inline-block; padding: .2rem .55rem; border-radius: 6px;
        font-size: .72rem; font-weight: 600;
    }
</style>

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
                showAlert(`Project status ${status} successfully.`, 'success');
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