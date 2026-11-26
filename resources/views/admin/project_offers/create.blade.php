@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Neues Auftragsangebot erstellen</h5>
            <a href="{{ route('admin.project_offers.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left-circle"></i> Zurück
            </a>
        </div>

        <div class="card-body">
            {{-- 🔹 Create New Offer --}}
            <form action="{{ route('admin.project_offers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Offer Basic Info --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">Kundenname</label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}">
                        @error('customer_name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="customer_email" class="form-label">Kunden E-Mail</label>
                        <input type="email" name="customer_email" id="customer_email" class="form-control" value="{{ old('customer_email') }}">
                        @error('customer_email') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="subject" class="form-label">Betreff</label>
                        <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject') }}" required>
                        @error('subject') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="assigned_user_id" class="form-label">Zugewiesener Benutzer</label>
                        <select name="assigned_user_id" id="assigned_user_id" class="form-select">
                            <option value="">-- Kein Benutzer --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Beschreibung</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- 🔹 Offer Emails Section --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-envelope me-1"></i> Angebots-E-Mails</h6>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="addEmailBtn">
                            <i class="bi bi-plus-circle"></i> Neue E-Mail hinzufügen
                        </button>
                    </div>

                    <div class="card-body" id="emailsSection">
                        {{-- Email Template (cloned via JS) --}}
                    </div>
                </div>

                {{-- 🔹 Allgemeine Angebotsdateien (Multiple Entries) --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-paperclip me-1"></i> Allgemeine Angebotsdateien</h6>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="addFileBtn">
                            <i class="bi bi-plus-circle"></i> Neue Datei hinzufügen
                        </button>
                    </div>

                    <div class="card-body" id="filesSection"></div>
                </div>

                {{-- 🔹 Submit --}}
                <div class="text-end">
                    <button type="submit" class="btn btn-wechsel">
                        <i class="bi bi-save me-1"></i> Angebot Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 🔹 Email Template JS --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let emailIndex = 0;
        let fileIndex = 0;
        
        const addEmailBtn = document.getElementById('addEmailBtn');
        const emailsSection = document.getElementById('emailsSection');

        const addFileBtn = document.getElementById('addFileBtn');
        const filesSection = document.getElementById('filesSection');

        addEmailBtn.addEventListener('click', () => {
            const currentIndex = emailIndex;
            const emailTemplate = `
                <div class="border rounded p-3 mb-3 bg-light email-item">
                    <div class="d-flex justify-content-between">
                        <h6><i class="bi bi-envelope-fill"></i> E-Mail #${currentIndex + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger removeEmailBtn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Absender</label>
                            <input type="text" name="emails[${currentIndex}][sender]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Empfänger</label>
                            <input type="text" name="emails[${currentIndex}][recipient]" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Betreff</label>
                            <input type="text" name="emails[${currentIndex}][subject]" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nachricht</label>
                            <textarea name="emails[${currentIndex}][body]" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 email-attachments">
                            <label class="form-label">Anhänge</label>
                            <div class="attachments-container"></div>
                            <button type="button" class="btn btn-sm btn-outline-dark addAttachmentBtn mt-2">
                                <i class="bi bi-plus"></i> Datei hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            `;
            emailsSection.insertAdjacentHTML('beforeend', emailTemplate);
            // 📎 Setup attachment button for this new email
            const currentEmail = emailsSection.querySelectorAll('.email-item')[emailsSection.querySelectorAll('.email-item').length - 1];
            const addAttachmentBtn = currentEmail.querySelector('.addAttachmentBtn');
            const attachmentsContainer = currentEmail.querySelector('.attachments-container');
            let attachmentIndex = 0;

            addAttachmentBtn.addEventListener('click', () => {
                const attachmentTemplate = `
                    <div class="row g-2 mb-2 attachment-item">
                        <div class="col-md-4">
                            <input type="text" name="emails[${currentIndex}][attachments][${attachmentIndex}][file_name]" class="form-control" placeholder="Dateiname">
                        </div>
                        <div class="col-md-4">
                            <input type="file" name="emails[${currentIndex}][attachments][${attachmentIndex}][file]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="emails[${currentIndex}][attachments][${attachmentIndex}][description]" class="form-control" placeholder="Beschreibung">
                        </div>
                        <div class="col-md-1 d-grid">
                            <button type="button" class="btn btn-sm btn-outline-danger removeAttachmentBtn" title="Entfernen">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                attachmentsContainer.insertAdjacentHTML('beforeend', attachmentTemplate);
                attachmentIndex++;
            });

            attachmentsContainer.addEventListener('click', e => {
                if (e.target.closest('.removeAttachmentBtn')) {
                    e.target.closest('.attachment-item').remove();
                }
            });

            emailIndex++;
        });

        // 📁 Add File
        addFileBtn.addEventListener('click', () => {
            const fileTemplate = `
                <div class="border rounded p-3 mb-3 bg-light file-item">
                    <div class="d-flex justify-content-between">
                        <h6><i class="bi bi-paperclip"></i> Datei #${fileIndex + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger removeFileBtn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Dateiname</label>
                            <input type="text" name="files[${fileIndex}][file_name]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Datei</label>
                            <input type="file" name="files[${fileIndex}][file]" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="files[${fileIndex}][description]" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>`;
            filesSection.insertAdjacentHTML('beforeend', fileTemplate);
            fileIndex++;
        });

        // Remove email section
        emailsSection.addEventListener('click', (e) => {
            if (e.target.closest('.removeEmailBtn')) {
                e.target.closest('.email-item').remove();
            }
        });

        filesSection.addEventListener('click', e => {
            if (e.target.closest('.removeFileBtn')) e.target.closest('.file-item').remove();
        });
    });
</script>
@endsection
