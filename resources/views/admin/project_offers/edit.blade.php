@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <h5 class="mb-0">Auftragsangebot bearbeiten</h5>
            <a href="{{ route('admin.project_offers.show', $offer) }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left-circle"></i> Zurück
            </a>
        </div>

        <div class="card-body">

            {{-- MAIN UPDATE FORM --}}
            <form action="{{ route('admin.project_offers.update', $offer) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- BASIC INFO --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Kundenname</label>
                        <input type="text" name="customer_name" class="form-control"
                               value="{{ old('customer_name', $offer->customer_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kunden E-Mail</label>
                        <input type="email" name="customer_email" class="form-control"
                               value="{{ old('customer_email', $offer->customer_email) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Betreff</label>
                        <input type="text" name="subject" class="form-control"
                               value="{{ old('subject', $offer->subject) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Zugewiesener Benutzer</label>
                        <select name="assigned_user_id" class="form-select">
                            <option value="">-- Kein Benutzer --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ $offer->assigned_user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Beschreibung</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $offer->description) }}</textarea>
                    </div>
                </div>

                <hr class="my-4">


                {{-- ========================== --}}
                {{-- EXISTING EMAILS --}}
                {{-- ========================== --}}
                <h5 class="mb-3">E-Mails</h5>

                @foreach($offer->emails as $index => $email)
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>E-Mail #{{ $index }}</strong>
                            <form class="d-inline"
                                  action="{{ route('admin.project_offers.emails.destroy', [$offer, $email]) }}"
                                  method="POST"
                                  onsubmit="return confirm('Diese E-Mail wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>

                        {{-- Email fields --}}
                        <div class="row g-2">
                            <input type="hidden" name="emails[{{ $email->id }}][id]" value="{{ $email->id }}">

                            <div class="col-md-6">
                                <label class="form-label">Absender</label>
                                <input type="text" class="form-control"
                                       name="emails[{{ $email->id }}][sender]"
                                       value="{{ $email->sender }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Empfänger</label>
                                <input type="text" class="form-control"
                                       name="emails[{{ $email->id }}][recipient]"
                                       value="{{ $email->recipient }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Betreff</label>
                                <input type="text" class="form-control"
                                       name="emails[{{ $email->id }}][subject]"
                                       value="{{ $email->subject }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Nachricht</label>
                                <textarea class="form-control" rows="3"
                                          name="emails[{{ $email->id }}][body]">{{ $email->body }}</textarea>
                            </div>
                        </div>

                        {{-- Attachments --}}
                        @if($email->files->count())
                            <div class="mt-3">
                                <strong>Anhänge:</strong>
                                @foreach($email->files as $att)
                                    <div class="border rounded p-2 mt-2 bg-white">
                                        <div class="row g-2 align-items-center">

                                            <input type="hidden"
                                                   name="emails[{{ $email->id }}][attachments][{{ $att->id }}][id]"
                                                   value="{{ $att->id }}">

                                            <div class="col-md-4">
                                                <input type="text" class="form-control"
                                                       name="emails[{{ $email->id }}][attachments][{{ $att->id }}][file_name]"
                                                       value="{{ $att->file_name }}">
                                            </div>

                                            <div class="col-md-4">
                                                <input type="file" class="form-control"
                                                       name="emails[{{ $email->id }}][attachments][{{ $att->id }}][file]">
                                            </div>

                                            <div class="col-md-3">
                                                <input type="text" class="form-control"
                                                       name="emails[{{ $email->id }}][attachments][{{ $att->id }}][description]"
                                                       value="{{ $att->description }}">
                                            </div>

                                            <div class="col-md-1">
                                                <a href="{{ asset('storage/' . $att->file_path) }}"
                                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <form action="{{ route('admin.project_offers.files.destroy', [$offer, $att->id]) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Datei wirklich löschen?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>
                @endforeach

                {{-- ADD NEW EMAIL BUTTON --}}
                <div id="newEmails"></div>

                <button type="button" class="btn btn-outline-dark mb-4" id="addEmailBtn">
                    <i class="bi bi-plus-circle"></i> Neue E-Mail hinzufügen
                </button>



                <hr class="my-4">


                {{-- ========================== --}}
                {{-- EXISTING DIRECT FILES --}}
                {{-- ========================== --}}
                <h5 class="mb-3">Dateien</h5>

                @foreach($offer->files as $file)
                    @if(!$file->offer_email_id)
                        <div class="border rounded p-3 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $file->file_name }}</strong>

                                <div>
                                    <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-download"></i>
                                    </a>

                                    <form action="{{ route('admin.project_offers.files.destroy', [$offer, $file]) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Datei wirklich löschen?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Neuer Dateiname</label>
                                    <input type="text" name="files[{{ $file->id }}][file_name]"
                                           class="form-control" value="{{ $file->file_name }}">
                                    <input type="hidden" name="files[{{ $file->id }}][id]" value="{{ $file->id }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Neue Datei hochladen</label>
                                    <input type="file" name="files[{{ $file->id }}][file]" class="form-control">
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="form-label">Beschreibung</label>
                                    <textarea name="files[{{ $file->id }}][description]" class="form-control" rows="2">{{ $file->description }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach


                {{-- ADD NEW FILE --}}
                <div id="newFiles"></div>

                <button type="button" class="btn btn-outline-dark mt-2 mb-4" id="addFileBtn">
                    <i class="bi bi-plus-circle"></i> Neue Datei hinzufügen
                </button>


                {{-- SUBMIT --}}
                <div class="text-end">
                    <button class="btn btn-wechsel">
                        <i class="bi bi-save"></i> Änderungen speichern
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

{{-- JS for Adding New Email + New File --}}
<script>
let emailIndex = 0;
let fileIndex = 0;

document.getElementById('addEmailBtn').addEventListener('click', function () {
    let html = `
        <div class="border rounded p-3 mb-3 bg-light">
            <h6>Neue E-Mail</h6>
            <div class="row g-2 mt-2">
                <div class="col-md-6">
                    <input type="text" name="emails[new_${emailIndex}][sender]" class="form-control" placeholder="Absender">
                </div>
                <div class="col-md-6">
                    <input type="text" name="emails[new_${emailIndex}][recipient]" class="form-control" placeholder="Empfänger">
                </div>
                <div class="col-12 mt-2">
                    <input type="text" name="emails[new_${emailIndex}][subject]" class="form-control" placeholder="Betreff">
                </div>
                <div class="col-12 mt-2">
                    <textarea name="emails[new_${emailIndex}][body]" class="form-control" rows="3" placeholder="Nachricht"></textarea>
                </div>
            </div>
        </div>
    `;
    document.getElementById('newEmails').insertAdjacentHTML('beforeend', html);
    emailIndex++;
});

document.getElementById('addFileBtn').addEventListener('click', function () {
    let html = `
        <div class="border rounded p-3 mb-3 bg-light">
            <h6>Neue Datei</h6>
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="files[new_${fileIndex}][file_name]" class="form-control" placeholder="Dateiname">
                </div>
                <div class="col-md-6">
                    <input type="file" name="files[new_${fileIndex}][file]" class="form-control" required>
                </div>
                <div class="col-12 mt-2">
                    <textarea name="files[new_${fileIndex}][description]" class="form-control" rows="2" placeholder="Beschreibung"></textarea>
                </div>
            </div>
        </div>
    `;
    document.getElementById('newFiles').insertAdjacentHTML('beforeend', html);
    fileIndex++;
});
</script>

@endsection
