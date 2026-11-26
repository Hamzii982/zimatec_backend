@extends('admin.layouts.index')

@section('title', 'Select Email Template for Offer #'.$offer->id)

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Select Email Template for Offer #{{ $offer->id }}</h5>
            <a class="btn btn-success btn-sm" href="{{ route('admin.project_offers.email_preview', [$offer, null]) }}">
                <i class="bi bi-pencil-square me-1"></i> Erstelle Custom Email
            </a>
        </div>

        <div class="card-body">
            @if($templates->isEmpty())
                <p class="text-muted">Keine aktiven E-Mail-Vorlagen für Auftragsangebote gefunden.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>Note / Tags</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $index => $template)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $template->subject }}</td>
                                    <td>{{ Str::limit($template->description, 50) }}</td>
                                    <td>
                                        @if($template->note)
                                            <code>{{ $template->note }}</code>
                                        @else
                                            <span class="text-muted">No tags</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.project_offers.email_preview', [$offer, $template]) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-envelope-paper me-1"></i> Template Benutzen
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <a href="{{ route('admin.project_offers.show', $offer) }}" class="btn btn-secondary mt-3">
                <i class="bi bi-arrow-left me-1"></i> Zuruck Zum Angebot
            </a>
        </div>
    </div>
</div>

<script>
function previewTemplate(templateId) {
    // hide custom email form if template is selected
    document.getElementById('custom_email_form').style.display = 'none';

    if(!templateId) {
        document.getElementById('template_preview').innerHTML = '';
        return;
    }

    fetch(`/admin/project_offers/{{ $offer->id }}/email_templates/preview/${templateId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('template_preview').innerHTML = html;
        });
}

function toggleCustomEmail() {
    const form = document.getElementById('custom_email_form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';

    // clear template preview if custom email is opened
    if(form.style.display === 'block') {
        document.getElementById('template_preview').innerHTML = '';
        document.getElementById('template_id').value = '';
    }
}
</script>
@endsection
