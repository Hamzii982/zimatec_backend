@extends('admin.layouts.index')

@section('title', 'Settings - Machine Leistung')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Auftragsleistungen</h5>
            <a href="{{ route('admin.settings.project-service.show') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Neue Leistung
            </a>
        </div>

        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Leistung Name</th>
                        <th>Parent</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        function renderServiceTree($services, $parentId = 0, $level = 0) {
                            foreach ($services->where('parent_id', $parentId) as $service) {
                                echo '<tr id="row-'.$service->id.'">';
                                echo '<td>'.$service->id.'</td>';
                                echo '<td>'.str_repeat('&nbsp;&nbsp;&nbsp;— ', $level).$service->name.'</td>';
                                echo '<td>'.($service->parent? $service->parent->name : '—').'</td>';

                                echo '<td>
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input toggle-active"
                                                data-id="'.$service->id.'" '.($service->active ? 'checked' : '').'>
                                        </div>
                                      </td>';

                                echo '<td>
                                        <a href="'.route('admin.settings.project-service.show', $service->id).'" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="'.route('admin.settings.project-service.delete', $service->id).'" 
                                              method="POST" class="d-inline">
                                            '.csrf_field().method_field('DELETE').'
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Delete this service?\')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                      </td>';

                                echo '</tr>';

                                renderServiceTree($services, $service->id, $level + 1);
                            }
                        }
                    @endphp

                    @if($services->count())
                        {!! renderServiceTree($services) !!}
                    @else
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No Records Yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-active').forEach((checkbox) => {
    checkbox.addEventListener('change', function() {
        const id = this.dataset.id;

        fetch(`/admin/settings/project-service/toggle/${id}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            showAlert(`Service ${data.active ? 'activated' : 'deactivated'} successfully.`, 'success');
        })
        .catch(err => {
            console.error(err);
            showAlert('Server error, please try again.', 'danger');
        });
    });
});
</script>
@endsection
