@extends('user.layouts.index')

@section('content')
    <style>
    /* Page-local uniform selection styles for time-records page */
    :root{
        --tz-primary: #0b3d91; /* dark blue */
        --tz-muted: #545b61; /* updated muted for better contrast */
        --tz-light: #f5f6f8;
    }

    /* ensure small muted texts use the page's muted variable for consistent contrast */
    .text-muted { color: var(--tz-muted) !important; }

    .btn-unselected {
        background-color: transparent !important;
        color: #212529 !important;
        border: 1px solid rgba(33,37,41,0.06) !important;
        box-shadow: none !important;
        transition: background-color .12s ease, color .12s ease, box-shadow .12s ease, border-color .12s ease;
        border-radius: 0.5rem;
    }

    .btn-selected {
        background-color: var(--tz-primary) !important;
        color: #ffffff !important;
        border-color: var(--tz-primary) !important;
        box-shadow: 0 10px 30px rgba(11,61,145,0.12) !important;
        border-radius: 0.5rem;
    }

    .project-wrapper .project-auftrag {
        color: var(--tz-muted) !important;
        transition: color .12s ease;
    }

    .project-btn.btn-selected .project-auftrag {
        color: rgba(255,255,255,0.92) !important;
    }

    /* Ensure labels for status radios show selected state */
    input[name="status_id"] + label {
        transition: background-color .12s ease, color .12s ease, border-color .12s ease;
        border-radius: 0.5rem;
    }
    input[name="status_id"]:checked + label {
        background-color: var(--tz-primary) !important;
        color: #fff !important;
        border-color: var(--tz-primary) !important;
        box-shadow: 0 8px 24px rgba(11,61,145,0.10) !important;
    }

    /* Focus styles for keyboard users */
    .btn-selected:focus,
    input[name="status_id"]:checked + label:focus {
        outline: 3px solid rgba(11,61,145,0.14);
        outline-offset: 2px;
    }
    @media (max-width: 576px) {
        .project-list-container { max-height: 220px; }
    }

    /* ========== Selected User Records Block ========== */
    .zt-records {
        --brand-blue: #002752;
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-records-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-records-card > .card-header { background: var(--brand-blue); color: #fff; border-bottom: none; padding: 1rem 1.25rem; }
    .zt-records-card > .card-body { background: var(--zt-bg); }
    .zt-header-sub { font-weight: 400; opacity: .85; font-size: .9rem; }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid rgba(255,255,255,.25); border-radius: 8px; background: transparent;
        color: #fff; font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; text-decoration: none;
        transition: background .15s;
    }
    
    .zt-export-btn:hover { background: rgba(255,255,255,.1); color: #fff; }
</style>

<div class="container mt-4 mb-5">
    <div class="zt-time zt-records">
        <div class="card shadow-sm zt-records-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    <span class="fw-bold">{{ $selectedUser->name }}</span>
                    <span class="zt-header-sub">— Neue Zeit Erfassung</span>
                </h5>
                <a href="{{ route('time-records.list') }}" class="zt-export-btn">
                    <i class="bi bi-plus-circle me-1"></i> Alle Aufzeichnung
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('time-records.store') }}" method="POST">
                    @csrf
                    
                    <!-- STEP 1: USER SELECTION -->
                    <div id="step-user" class="mb-4">
                        <h6>Bediener Auswahlen</h6>
                    
                        @if(!$selectedUser)
                            <div class="row g-2">
                                @foreach($users as $user)
                                    <div class="col-md-3">
                                        <button type="button"
                                                class="btn-unselected w-100 user-btn btn"
                                                data-user-id="{{ $user->id }}"
                                                data-company="{{ $user->company }}">
                                            <i class="bi bi-person"></i> {{ $user->name }}
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="row g-2">
                                @foreach($users as $user)
                                    @if($user->id == $selectedUser->id)
                                        <div class="col-md-3">
                                            @php 
                                                $isActive = isset($selectedUser) && $selectedUser->id == $user->id;
                                            @endphp
                                            <button type="button"
                                                    class="btn-selected active w-100 user-btn btn"
                                                    data-user-id="{{ $user->id }}"
                                                    data-company="{{ $user->company }}"
                                                    disabled
                                                    aria-disabled="true">
                                                <i class="bi bi-person"></i> {{ $user->name }}
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <input type="hidden" name="user_id" id="user_id" value="{{ $selectedUser->id ?? '' }}">

                    <!-- STEP 2: PROJECT SELECTION (UX UPGRADED) -->
                    <div id="step-project" class="mb-4 {{ isset($selectedUser) ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h6 class="mb-0">Projekte Auswahlen</h6>
                            
                            <!-- Search Input -->
                            <div class="input-group" style="max-width: 300px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" id="project-search" class="form-control border-start-0 ps-0" placeholder="Projekt / Auftragsnr..." autocomplete="off">
                            </div>
                        </div>
                    
                        <!-- Scrollable Project Container -->
                        <div class="row g-2 project-list-container" style="max-height: 200px; overflow-y: auto; overflow-x: hidden;">
                            @foreach($projects as $project)
                                <!-- project-wrapper with data-search attribute for JS filtering -->
                                <div class="col-md-4 project-wrapper" data-search="{{ strtolower($project->project_name . ' ' . $project->auftragsnummer_zt . ' ' . $project->auftragsnummer_zf) }}">
                                    <button type="button"
                                            class="btn-unselected btn w-100 project-btn py-3"
                                            data-project-id="{{ $project->id }}"
                                            data-zt="{{ $project->auftragsnummer_zt }}"
                                            data-zf="{{ $project->auftragsnummer_zf }}"
                                            data-positions='@json($project->positions)'>
                                        <strong class="d-block text-truncate">{{ $project->project_name }}</strong>
                                        <small class="text-muted d-block project-auftrag mt-1">
                                            @if(isset($selectedUser))
                                                {{ 
                                                    $selectedUser->company === 'ZF' 
                                                        ? "(ZF: " . ($project->auftragsnummer_zf ?? '—') . ", ZT: " . ($project->auftragsnummer_zt ?? '—') . ")" 
                                                        : "(ZT: " . ($project->auftragsnummer_zt ?? '—') . ", ZF: " . ($project->auftragsnummer_zf ?? '—') . ")" 
                                                }}
                                            @endif
                                        </small>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <!-- No Results Message -->
                        <div id="no-projects-msg" class="text-center text-muted mt-4 d-none">
                            <i class="bi bi-search fs-3 d-block mb-2 text-secondary"></i>
                            Keine Projekte gefunden.
                        </div>
                    </div>
                    
                    <input type="hidden" name="project_id" id="project_id">

                    <!-- STEP 3: POSITION SELECTION -->
                    <div id="step-position" class="mb-4 d-none">
                        <h6>Position Auswahlen</h6>
                    
                        <div id="positions-container" class="row g-2"></div>
                    </div>
                    
                    <input type="hidden" name="position_id" id="position_id">

                    <!-- STEP 4: MACHINE SELECTION -->
                    <input type="hidden" name="machine_id" id="machine_id">

                    <div id="step-machine" class="mb-4 d-none">
                        <h6>Maschine Auswahlen</h6>

                        <div class="row g-2">
                            @foreach($machines as $machine)
                                <div class="col-md-3">
                                    <button type="button"
                                            class="btn-unselected btn w-100 machine-btn"
                                            data-id="{{ $machine->id }}">
                                        <i class="bi bi-cpu"></i> {{ $machine->name }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- STEP 5: STATUS SELECTION -->
                    <div id="step-status" class="mb-4 d-none">
                        <h6>Status</h6>
                    
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="btn-group">
                                @foreach($statuses as $status)
                                    @php
                                        $isMitAufsicht = strtolower($status->name ?? '') === 'mit aufsicht';
                                    @endphp
                                    <input type="radio"
                                        class="btn-check"
                                        name="status_id"
                                        id="status-{{ $status->id }}"
                                        value="{{ $status->id }}"
                                        required>

                                    <label class="btn btn-unselected"
                                        for="status-{{ $status->id }}">
                                        {{ $status->name }}
                                    </label>
                                @endforeach
                            </div>
                            @if($isMitAufsicht)
                                <div class="d-none d-flex align-items-center gap-2" id="manual-process-wrap">
                                    <div class="form-check m-0">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            id="manual-process-checkbox"
                                            name="manual_process"
                                            value="1">
                                        <label class="form-check-label ms-1" for="manual-process-checkbox">
                                            Manueller Prozess
                                        </label>
                                    </div>
                                    <div id="manual-process-name-wrap" class="d-none ms-3">
                                        <input type="text"
                                            class="form-control form-control-sm border-dark-subtle shadow-sm"
                                            id="manual-process-name"
                                            name="manual_process_name"
                                            placeholder="Prozess Name"
                                            autocomplete="off">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success d-none" id="start-btn">
                        <i class="bi bi-play-circle"></i> Start
                    </button>
                    
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let selectedCompany = null;
    /* ========== UTIL HELPERS ========== */
    function activateButton(button, selector) {
        document.querySelectorAll(selector).forEach(b => {
            b.classList.remove(
                'btn-primary', 'btn-success', 'btn-dark', 'active',
                'btn-outline-dark', 'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-info',
                'btn-selected'
            );
            if (!b.classList.contains('btn-unselected')) {
                b.classList.add('btn-unselected');
            }
        });

        button.classList.remove('btn-unselected');
        button.classList.add('btn-selected', 'active');
    }

    // Normalize buttons on load so visual state is consistent
    document.addEventListener('DOMContentLoaded', () => {
        ['.user-btn', '.project-btn', '.position-btn', '.machine-btn'].forEach(sel => {
            document.querySelectorAll(sel).forEach(b => {
                b.classList.remove('btn-primary', 'btn-success', 'btn-dark', 'btn-outline-dark', 'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-info');
                if (!b.classList.contains('btn-unselected') && !b.classList.contains('btn-selected')) {
                    b.classList.add('btn-unselected');
                }
            });
        });
    });
    
    document.querySelectorAll('.user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // ignore clicks on disabled (pre-selected) user
            if (btn.disabled) return;

            activateButton(btn, '.user-btn');
            document.getElementById('user_id').value = btn.dataset.userId;
            selectedCompany = btn.dataset.company;

            // Update project labels AFTER user selection
            document.querySelectorAll('.project-btn').forEach(projectBtn => {
                const label = projectBtn.querySelector('.project-auftrag');
                label.textContent = selectedCompany === 'ZF'
                    ? `(ZF: ${projectBtn.dataset.zf ?? '—'})`
                    : `(ZT: ${projectBtn.dataset.zt ?? '—'})`;
            });

            document.getElementById('step-project').classList.remove('d-none');

            // Auto-focus search input if on a device with keyboard, skip for pure touch
            const searchInput = document.getElementById('project-search');
            if(searchInput && window.innerWidth > 768) {
                searchInput.focus();
            }
        });
    });
    
    /* ========== STEP 2: PROJECT ========== */
    document.querySelectorAll('.project-btn').forEach(btn => {
    
        btn.addEventListener('click', () => {
            activateButton(btn, '.project-btn');
            document.getElementById('project_id').value = btn.dataset.projectId;
    
            const positions = JSON.parse(btn.dataset.positions);
            const container = document.getElementById('positions-container');
            container.innerHTML = '';
    
            positions.forEach(pos => {
                container.insertAdjacentHTML('beforeend', `
                    <div class="col-md-4">
                        <button type="button"
                                class="btn btn-unselected w-100 position-btn py-2"
                                data-id="${pos.id}">
                            ${pos.name}
                        </button>
                    </div>
                `);
            });
    
            document.getElementById('step-position').classList.remove('d-none');
        });
    });

    /* ========== PROJECT LIVE SEARCH LOGIC ========== */
    const searchInput = document.getElementById('project-search');
    const projectWrappers = document.querySelectorAll('.project-wrapper');
    const noProjectsMsg = document.getElementById('no-projects-msg');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            projectWrappers.forEach(wrapper => {
                const searchableText = wrapper.getAttribute('data-search');
                
                if (searchableText.includes(searchTerm)) {
                    wrapper.classList.remove('d-none');
                    visibleCount++;
                } else {
                    wrapper.classList.add('d-none');
                }
            });

            // Show/Hide "No projects found" message
            if (visibleCount === 0) {
                noProjectsMsg.classList.remove('d-none');
            } else {
                noProjectsMsg.classList.add('d-none');
            }
        });
    }
    
    /* ========== STEP 3: POSITION ========== */
    document.addEventListener('click', e => {
        // Use closest to handle clicks on inner elements if you ever add icons
        const btn = e.target.closest('.position-btn');
        if (btn) {
            activateButton(btn, '.position-btn');
            document.getElementById('position_id').value = btn.dataset.id;
    
            document.getElementById('step-machine').classList.remove('d-none');
        }
    });

    /* ========== STEP 4: MACHINE BUTTONS ========== */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.machine-btn');
        if (btn) {
            activateButton(btn, '.machine-btn');

            document.getElementById('machine_id').value = btn.dataset.id;

            document.getElementById('step-status').classList.remove('d-none');
            document.getElementById('start-btn').classList.remove('d-none');
        }
    });

    /* ========== MANUAL PROCESS (Mit Aufsicht) ========== */
    const manualProcessWrap = document.getElementById('manual-process-wrap');
    const manualProcessCheckbox = document.getElementById('manual-process-checkbox');
    const manualProcessNameWrap = document.getElementById('manual-process-name-wrap');
    const manualProcessName = document.getElementById('manual-process-name');

    function toggleManualProcessUI() {
        if (!manualProcessWrap || !manualProcessCheckbox || !manualProcessNameWrap || !manualProcessName) {
            return;
        }

        const selectedStatus = document.querySelector('input[name="status_id"]:checked');
        const isMitAufsicht = selectedStatus && selectedStatus.nextElementSibling
            ? selectedStatus.nextElementSibling.textContent.trim().toLowerCase() === 'mit aufsicht'
            : false;

        if (!isMitAufsicht) {
            manualProcessWrap.classList.add('d-none');
            manualProcessCheckbox.checked = false;
            manualProcessNameWrap.classList.add('d-none');
            manualProcessName.required = false;
            manualProcessName.value = '';
            return;
        }

        manualProcessWrap.classList.remove('d-none');
        if (manualProcessCheckbox.checked) {
            manualProcessNameWrap.classList.remove('d-none');
            manualProcessName.required = true;
        } else {
            manualProcessNameWrap.classList.add('d-none');
            manualProcessName.required = false;
            manualProcessName.value = '';
        }
    }

    document.addEventListener('change', e => {
        if (e.target.name === 'status_id' || e.target.id === 'manual-process-checkbox') {
            toggleManualProcessUI();
        }
    });
</script>    
@endsection