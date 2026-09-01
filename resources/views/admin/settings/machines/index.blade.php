@extends('admin.layouts.index')

@section('title', 'Settings - Machines')

@section('content')
<div class="zt-compare container mt-4">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Machines</h5>
            <a href="{{ route('admin.settings.machines.show') }}" class="zt-export-btn">
                <i class="bi bi-plus-circle"></i> Neue Maschine
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table zt-table zt-table--excel align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Active</th>
                            <th>Company</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($machines as $machine)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-bold">{{ $machine->name }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="checkbox"
                                            class="form-check-input toggle-active"
                                            data-id="{{ $machine->id }}"
                                            {{ $machine->active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>{{ $machine->company }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.settings.machines.show', $machine->id) }}"
                                        class="zt-icon-btn zt-icon-btn--edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="{{ route('admin.settings.machines.delete', $machine->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="zt-icon-btn zt-icon-btn--danger"
                                                    onclick="return confirm('Delete this machine?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
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

    /* Toggle switch tint, matching the ink color */
    .form-check-input.toggle-active:checked {
        background-color: var(--zt-ink, #1B1F24);
        border-color: var(--zt-ink, #1B1F24);
    }
</style>

<script>
document.querySelectorAll('.toggle-active').forEach(el => {
    el.addEventListener('change', function () {
        fetch(`/admin/settings/machines/toggle/${this.dataset.id}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Something went wrong');
            }
        });
    });
});
</script>
@endsection