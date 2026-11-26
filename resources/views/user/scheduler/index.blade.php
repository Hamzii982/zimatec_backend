@extends('user.layouts.index')

@section('title', 'Ressourcenplanung')

@section('content')
<div class="container py-5">
    <!-- Header Area -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h1 class="h2 mb-0 text-slate-800 fw-bold d-flex align-items-center gap-2" style="color: #002752;">
                <i class="bi bi-calendar-range text-primary"></i> Ressourcenplanung
            </h1>
            <p class="text-muted mb-0">Belegung von Maschinen im Werkstattbetrieb planen</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
            <!-- View toggle -->
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-outline-primary active" id="btnViewDay" onclick="switchView('day')">
                    <i class="bi bi-clock me-1"></i> Tag
                </button>
                <button type="button" class="btn btn-outline-primary" id="btnViewWeek" onclick="switchView('week')">
                    <i class="bi bi-calendar-week me-1"></i> Woche
                </button>
            </div>

            <button class="btn btn-primary shadow-sm" onclick="openCreateModal()">
                <i class="bi bi-plus-circle me-1"></i> Neuer Termin
            </button>
        </div>
    </div>

    <!-- Navigation & Filter Bar -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Time navigation -->
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-white border shadow-sm" onclick="navigateTime(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-sm btn-white border shadow-sm" onclick="navigateToday()">
                    Heute
                </button>
                <button class="btn btn-sm btn-white border shadow-sm" onclick="navigateTime(1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <span class="fs-5 fw-bold text-dark ms-2" id="currentDateDisplay">Lädt...</span>
            </div>

            <!-- Search & Filters -->
            <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 justify-content-md-end">
                <div class="position-relative" style="min-width: 200px;">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" id="resourceSearch" class="form-control form-control-sm ps-5 bg-white border" placeholder="Maschine suchen..." oninput="applyFilters()">
                </div>
                <div style="min-width: 220px;">
                    <select id="projectFilter" class="form-select form-select-sm bg-white border" onchange="applyFilters()">
                        <option value="">Alle Aufträge</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">
                                {{ $project->project_name }} ({{ $project->auftragsnummer_zf ?? $project->auftragsnummer_zt ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Board Wrapper -->
    <div class="timeline-board shadow-sm">
        <!-- Single scroll container holds BOTH header and body so columns can never drift apart -->
        <div class="timeline-scroll" id="timelineScroll">
            <div class="timeline-grid" id="timelineGrid">
                <!-- Timeline Grid Header -->
                <div class="timeline-header" id="timelineHeaderRow">
                    <!-- Resource header col -->
                    <div class="timeline-header-cell resource-header">Maschine</div>
                    <!-- Time header cols will be rendered via JS -->
                    <div class="timeline-header-columns" id="timelineHeaderTimeColumns"></div>
                </div>

                <!-- Timeline Grid Body -->
                <div class="timeline-body" id="timelineBody">
                    @foreach($machines as $machine)
                        <div class="timeline-row" data-resource-type="machine" data-resource-id="{{ $machine->id }}" data-search-name="{{ strtolower($machine->name) }}">
                            <div class="resource-title">
                                <div class="fw-bold">{{ $machine->name }}</div>
                                <div class="text-muted small">{{ $machine->company }}</div>
                            </div>
                            <div class="timeline-track" data-resource-type="machine" data-resource-id="{{ $machine->id }}">
                                <!-- Event cards will be placed here -->
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scheduling Create/Edit Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="scheduleForm" onsubmit="saveSchedule(event)">
                @csrf
                <input type="hidden" id="eventId" name="id">
                <input type="hidden" id="modalType" name="type" value="machine">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="scheduleModalLabel">Termin planen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Auftrag selection -->
                    <div class="mb-3">
                        <label for="modalProject" class="form-label fw-bold">Auftrag</label>
                        <select id="modalProject" name="project_id" class="form-select" required>
                            <option value="">-- Auftrag wählen --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">
                                    {{ $project->project_name }} ({{ $project->auftragsnummer_zf ?? $project->auftragsnummer_zt ?? 'Allgemein' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <!-- Machine Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="modalMachine" class="form-label fw-bold">Maschine</label>
                            <select id="modalMachine" name="machine_id" class="form-select" required>
                                <option value="">-- Maschine wählen --</option>
                                @foreach($machines as $machine)
                                    <option value="{{ $machine->id }}">{{ $machine->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Employee Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="modalUser" class="form-label fw-bold">Mitarbeiter</label>
                            <select id="modalUser" name="user_id" class="form-select">
                                <option value="">-- Keiner --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Start Time -->
                        <div class="col-md-6 mb-3">
                            <label for="modalStart" class="form-label fw-bold">Startzeit</label>
                            <input type="datetime-local" id="modalStart" name="start_time" class="form-control" required>
                        </div>

                        <!-- End Time -->
                        <div class="col-md-6 mb-3">
                            <label for="modalEnd" class="form-label fw-bold">Endzeit</label>
                            <input type="datetime-local" id="modalEnd" name="end_time" class="form-control" required>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="modalNotes" class="form-label fw-bold">Notizen / Aufgaben</label>
                        <textarea id="modalNotes" name="notes" class="form-control" rows="3" placeholder="Notizen..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" id="btnDeleteEvent" class="btn btn-danger d-none" onclick="deleteSchedule()">Löschen</button>
                    <button type="submit" id="btnSaveEvent" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    :root {
        --timeline-row-height: 80px;
        --resource-col-width: 250px;
        --time-col-width: 90px; /* fixed width per hour/day column, used by BOTH header and rows */
        --slate-100: #f1f5f9;
        --slate-200: #e2e8f0;
        --slate-700: #334155;
        --slate-800: #1e293b;
        --primary-soft: #eff6ff;
        --num-slots: 16;
    }

    .btn-white {
        background-color: #fff;
        color: var(--slate-700);
        border-color: var(--slate-200);
    }
    .btn-white:hover {
        background-color: #f8fafc;
        color: var(--slate-800);
    }

    .btn-primary {
        background-color: #002752;
        border-color: #002752;
    }
    .btn-primary:hover {
        background-color: #001a3d;
        border-color: #001a3d;
    }

    .btn-outline-primary {
        color: #002752;
        border-color: #002752;
    }
    .btn-outline-primary:hover, .btn-outline-primary.active {
        background-color: #002752;
        border-color: #002752;
        color: #fff;
    }

    /* ============================================================
       TIMELINE LAYOUT
       Header and rows both use the SAME grid-template-columns
       (resource col + N equal time columns) on the SAME element
       width, inside ONE scroll container. This guarantees the
       header hour labels always line up with the row cells below
       them — there is no second layout system (e.g. background-size)
       that can drift out of sync.
       ============================================================ */

    .timeline-board {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--slate-200);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* The ONE scroll container: scrolls both horizontally (many hour
       columns) and vertically (many machine rows). Header stays
       pinned to the top via sticky, resource column stays pinned to
       the left via sticky on its cells. */
    .timeline-scroll {
        max-height: calc(100vh - 320px);
        min-height: 320px;
        overflow: auto;
    }

    .timeline-grid {
        display: inline-block;
        min-width: 100%;
    }

    .timeline-header {
        display: grid;
        grid-template-columns: var(--resource-col-width) repeat(var(--num-slots), minmax(var(--time-col-width), 1fr));
        background: #f8fafc;
        border-bottom: 2px solid var(--slate-200);
        position: sticky;
        top: 0;
        z-index: 6;
    }

    .timeline-header-columns {
        display: grid;
        grid-template-columns: subgrid;
        grid-column: 2 / -1;
    }

    .timeline-header-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 16px;
        font-weight: 600;
        color: var(--slate-700);
        font-size: 0.85rem;
    }

    .resource-header {
        justify-content: flex-start;
        background: #f8fafc;
        border-right: 2px solid var(--slate-200);
        position: sticky;
        left: 0;
        z-index: 7;
    }

    .time-header-cell {
        border-right: 1px solid var(--slate-200);
        flex-direction: column;
        height: 50px;
    }

    .timeline-body {
        display: block;
    }

    .timeline-row {
        display: grid;
        grid-template-columns: var(--resource-col-width) repeat(var(--num-slots), minmax(var(--time-col-width), 1fr));
        border-bottom: 1px solid var(--slate-100);
        min-height: var(--timeline-row-height);
        transition: background-color 0.15s ease;
    }
    .timeline-row:hover {
        background-color: #fafbfc;
    }

    .resource-title {
        padding: 12px 16px;
        border-right: 2px solid var(--slate-200);
        background: #fff;
        position: sticky;
        left: 0;
        z-index: 4;
        box-shadow: 2px 0 5px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .timeline-track {
        grid-column: 2 / -1;
        position: relative;
        background-image: linear-gradient(to right, var(--slate-100) 1px, transparent 1px);
        background-size: calc(100% / var(--num-slots)) 100%;
        min-height: var(--timeline-row-height);
    }

    /* Event Cards */
    .event-card {
        position: absolute;
        top: 6px;
        height: calc(100% - 12px);
        border-radius: 8px;
        padding: 6px 12px;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        border-left: 4px solid rgba(0, 0, 0, 0.25);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        z-index: 10;
        user-select: none;
    }

    .event-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        z-index: 11;
    }

    .event-card .event-title {
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-card .event-user {
        font-size: 0.7rem;
        font-weight: 600;
        opacity: 0.95;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-card .event-time {
        font-size: 0.65rem;
        opacity: 0.9;
        margin-top: 1px;
    }

    .event-card .event-notes {
        font-size: 0.65rem;
        opacity: 0.85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: auto;
    }

    /* Highlight current user animation (if they match client ID) */
    .event-card.user-highlight {
        outline: 3px solid #f59e0b;
        outline-offset: 1px;
        animation: pulse-border 2.5s infinite;
    }

    @keyframes pulse-border {
        0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
        100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    /* Resize handles */
    .event-resize-handle {
        position: absolute;
        top: 0;
        width: 6px;
        height: 100%;
        cursor: ew-resize;
        z-index: 2;
    }
    .event-resize-handle-left { left: 0; }
    .event-resize-handle-right { right: 0; }

    /* Dragging styles */
    .event-card.dragging {
        opacity: 0.7;
        transform: scale(0.98);
        cursor: grabbing;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
</style>
@endpush

@push('scripts')
<script>
    // System settings - Scheduler is public, so everyone is manager (isAdmin = true)
    const isAdmin = true;
    const currentUserId = null; // Public workshop dashboard

    // State management
    let currentView = 'day'; // 'day' or 'week'
    let currentDate = new Date(); // Active viewing date
    let rawEvents = []; // Cached array of fetched events
    let dragElement = null;
    let dragStartX = 0;
    let dragStartLeft = 0;
    let dragStartWidth = 0;
    let dragType = null; // 'move', 'resize-left', 'resize-right'
    let dragOriginalEventObj = null;

    // Time ranges configuration
    const DAY_START_HOUR = 6;
    const DAY_END_HOUR = 22;
    const TOTAL_DAY_HOURS = DAY_END_HOUR - DAY_START_HOUR;

    // Load bootstrap instance
    let scheduleModalInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        scheduleModalInstance = new bootstrap.Modal(document.getElementById('scheduleModal'));

        // Load initial data
        loadScheduler();

        // Drag/Resize listeners
        document.addEventListener('mousemove', handleDragMove);
        document.addEventListener('mouseup', handleDragEnd);
    });

    // Switch between Daily and Weekly views
    function switchView(view) {
        currentView = view;
        document.getElementById('btnViewDay').classList.toggle('active', view === 'day');
        document.getElementById('btnViewWeek').classList.toggle('active', view === 'week');
        loadScheduler();
    }

    // Time navigation controls
    function navigateTime(direction) {
        if (currentView === 'day') {
            currentDate.setDate(currentDate.getDate() + direction);
        } else {
            currentDate.setDate(currentDate.getDate() + (direction * 7));
        }
        loadScheduler();
    }

    function navigateToday() {
        currentDate = new Date();
        loadScheduler();
    }

    // Generate HSL colors dynamically for project uniformity
    function getProjectColor(projectId, projectName) {
        if (!projectId) return '#64748b'; // default slate grey
        let hash = 0;
        const str = projectName + projectId;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        const hue = Math.abs(hash % 360);
        return `hsl(${hue}, 60%, 45%)`; // beautiful pastel/vibrant tones
    }

    // Determine start & end bounds of the active date range
    function getDateRange() {
        const start = new Date(currentDate);
        const end = new Date(currentDate);

        if (currentView === 'day') {
            start.setHours(0, 0, 0, 0);
            end.setHours(23, 59, 59, 999);
        } else {
            // Get Monday of current week
            const day = start.getDay();
            const diff = start.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is sunday
            start.setDate(diff);
            start.setHours(0, 0, 0, 0);

            end.setDate(start.getDate() + 6);
            end.setHours(23, 59, 59, 999);
        }

        return { start, end };
    }

    // Main fetch & render function
    function loadScheduler() {
        const { start, end } = getDateRange();

        // Update header UI display label
        updateHeaderDisplay(start, end);

        // Generate X-Axis Column Headers
        renderTimelineHeaders(start);

        // Fetch events via AJAX
        const formattedStart = start.toISOString().split('T')[0] + ' 00:00:00';
        const formattedEnd = end.toISOString().split('T')[0] + ' 23:59:59';

        fetch(`/scheduler/events?start=${encodeURIComponent(formattedStart)}&end=${encodeURIComponent(formattedEnd)}`)
            .then(res => res.json())
            .then(events => {
                rawEvents = events;
                renderEvents();
            })
            .catch(err => {
                console.error('Fehler beim Laden der Termine:', err);
                Swal.fire('Fehler', 'Die Termine konnten nicht geladen werden.', 'error');
            });
    }

    // Update Date Display Label
    function updateHeaderDisplay(start, end) {
        const display = document.getElementById('currentDateDisplay');
        const formatOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

        if (currentView === 'day') {
            display.innerText = currentDate.toLocaleDateString('de-DE', formatOptions);
        } else {
            const startStr = start.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
            const endStr = end.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const weekNumber = getWeekNumber(start);
            display.innerText = `Woche ${weekNumber} (${startStr} - ${endStr})`;
        }
    }

    function getWeekNumber(d) {
        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
        return Math.ceil((((d - yearStart) / 86400000) + 1)/7);
    }

    // Render X-Axis Time/Day Grid Columns
    // IMPORTANT: --num-slots is set on the SAME grid (.timeline-grid) that both
    // the header row and every body row read from, via grid-template-columns.
    // This is the single source of truth for column count/alignment.
    function renderTimelineHeaders(weekStart) {
        const container = document.getElementById('timelineHeaderTimeColumns');
        container.innerHTML = '';
        const gridEl = document.getElementById('timelineGrid');

        if (currentView === 'day') {
            gridEl.style.setProperty('--num-slots', TOTAL_DAY_HOURS);

            for (let i = DAY_START_HOUR; i < DAY_END_HOUR; i++) {
                const hourHeader = document.createElement('div');
                hourHeader.className = 'time-header-cell d-flex flex-column align-items-center justify-content-center';
                hourHeader.innerHTML = `<span class="fw-bold">${String(i).padStart(2, '0')}:00</span>`;
                container.appendChild(hourHeader);
            }
        } else {
            gridEl.style.setProperty('--num-slots', 7);

            const days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
            const tempDate = new Date(weekStart);

            for (let i = 0; i < 7; i++) {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'time-header-cell d-flex flex-column align-items-center justify-content-center';
                const formattedDate = tempDate.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
                dayHeader.innerHTML = `<span class="fw-bold d-block">${days[i]}</span><span class="small text-muted">${formattedDate}</span>`;
                container.appendChild(dayHeader);
                tempDate.setDate(tempDate.getDate() + 1);
            }
        }
    }

    // Clear and Redraw schedule event cards on the timeline tracks
    function renderEvents() {
        document.querySelectorAll('.timeline-track').forEach(track => {
            track.innerHTML = '';
        });

        const projectFilterId = document.getElementById('projectFilter').value;
        const { start: viewStart, end: viewEnd } = getDateRange();

        rawEvents.forEach(event => {
            if (projectFilterId && event.project_id != projectFilterId) return;

            const eventStart = new Date(event.start_time);
            const eventEnd = new Date(event.end_time);

            let leftPercent = 0;
            let widthPercent = 0;

            if (currentView === 'day') {
                const timelineStart = new Date(currentDate);
                timelineStart.setHours(DAY_START_HOUR, 0, 0, 0);
                const timelineEnd = new Date(currentDate);
                timelineEnd.setHours(DAY_END_HOUR, 0, 0, 0);

                const activeStart = Math.max(eventStart.getTime(), timelineStart.getTime());
                const activeEnd = Math.min(eventEnd.getTime(), timelineEnd.getTime());

                if (activeStart >= activeEnd) return;

                const totalTimelineMs = TOTAL_DAY_HOURS * 60 * 60 * 1000;
                leftPercent = ((activeStart - timelineStart.getTime()) / totalTimelineMs) * 100;
                widthPercent = ((activeEnd - activeStart) / totalTimelineMs) * 100;
            } else {
                const totalTimelineMs = 7 * 24 * 60 * 60 * 1000;
                const activeStart = Math.max(eventStart.getTime(), viewStart.getTime());
                const activeEnd = Math.min(eventEnd.getTime(), viewEnd.getTime());

                if (activeStart >= activeEnd) return;

                leftPercent = ((activeStart - viewStart.getTime()) / totalTimelineMs) * 100;
                widthPercent = ((activeEnd - activeStart) / totalTimelineMs) * 100;
            }

            const card = document.createElement('div');
            card.className = 'event-card';
            card.style.left = `${leftPercent}%`;
            card.style.width = `${widthPercent}%`;

            const projectColor = getProjectColor(event.project_id, event.project ? event.project.project_name : 'Allgemein');
            card.style.backgroundColor = projectColor;
            card.setAttribute('data-event-id', event.id);

            const title = event.project ? event.project.project_name : 'Allgemein';
            const userName = event.user ? event.user.name : 'Kein Mitarbeiter';
            const timeFormat = { hour: '2-digit', minute: '2-digit' };
            const timeStr = `${eventStart.toLocaleTimeString('de-DE', timeFormat)} - ${eventEnd.toLocaleTimeString('de-DE', timeFormat)}`;
            const notesStr = event.notes ? event.notes : '';

            card.innerHTML = `
                <div class="event-title">${title}</div>
                <div class="event-user"><i class="bi bi-person-fill me-1"></i>${userName}</div>
                <div class="event-time"><i class="bi bi-clock me-1"></i>${timeStr}</div>
                ${notesStr ? `<div class="event-notes">${notesStr}</div>` : ''}
            `;

            // Drag handles
            const resizeLeft = document.createElement('div');
            resizeLeft.className = 'event-resize-handle event-resize-handle-left';
            resizeLeft.addEventListener('mousedown', (e) => initDrag(e, card, 'resize-left', event));
            card.appendChild(resizeLeft);

            const resizeRight = document.createElement('div');
            resizeRight.className = 'event-resize-handle event-resize-handle-right';
            resizeRight.addEventListener('mousedown', (e) => initDrag(e, card, 'resize-right', event));
            card.appendChild(resizeRight);

            // MouseDown for dragging card
            card.addEventListener('mousedown', (e) => {
                if (e.target.classList.contains('event-resize-handle')) return;
                initDrag(e, card, 'move', event);
            });

            // Click to Edit
            card.addEventListener('click', (e) => {
                if (card.classList.contains('dragging-disabled')) return;
                openEditModal(event);
            });

            if (event.machine_id) {
                const track = document.querySelector(`.timeline-row[data-resource-type="machine"][data-resource-id="${event.machine_id}"] .timeline-track`);
                if (track) track.appendChild(card);
            }
        });

        applyFilters();
    }

    // Apply Search/Filter logic locally
    function applyFilters() {
        const searchVal = document.getElementById('resourceSearch').value.toLowerCase();

        document.querySelectorAll('.timeline-row').forEach(row => {
            const name = row.getAttribute('data-search-name');
            if (name.includes(searchVal)) {
                row.style.removeProperty('display');
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
        });
    }

    // ==========================================
    // DRAG AND DROP / INTERACTION LOGIC
    // ==========================================
    function initDrag(e, card, type, eventObj) {
        e.preventDefault();
        e.stopPropagation();

        dragElement = card;
        dragType = type;
        dragOriginalEventObj = eventObj;
        dragStartX = e.clientX;
        dragStartLeft = parseFloat(card.style.left);
        dragStartWidth = parseFloat(card.style.width);

        card.classList.add('dragging');
        card.classList.add('dragging-disabled');
    }

    function handleDragMove(e) {
        if (!dragElement) return;

        const deltaX = e.clientX - dragStartX;
        const track = dragElement.parentElement;
        const trackWidth = track.clientWidth;
        const deltaPercent = (deltaX / trackWidth) * 100;

        if (dragType === 'move') {
            let newLeft = dragStartLeft + deltaPercent;
            newLeft = Math.max(0, Math.min(newLeft, 100 - dragStartWidth));
            dragElement.style.left = `${newLeft}%`;
        } else if (dragType === 'resize-left') {
            let newLeft = dragStartLeft + deltaPercent;
            let newWidth = dragStartWidth - deltaPercent;

            const minWidthPercent = (0.5 / (currentView === 'day' ? TOTAL_DAY_HOURS : 168)) * 100;
            if (newWidth > minWidthPercent && newLeft >= 0) {
                dragElement.style.left = `${newLeft}%`;
                dragElement.style.width = `${newWidth}%`;
            }
        } else if (dragType === 'resize-right') {
            let newWidth = dragStartWidth + deltaPercent;

            const minWidthPercent = (0.5 / (currentView === 'day' ? TOTAL_DAY_HOURS : 168)) * 100;
            if (newWidth > minWidthPercent && (dragStartLeft + newWidth) <= 100) {
                dragElement.style.width = `${newWidth}%`;
            }
        }
    }

    function handleDragEnd(e) {
        if (!dragElement) return;

        const el = dragElement;
        el.classList.remove('dragging');
        setTimeout(() => {
            el.classList.remove('dragging-disabled');
        }, 150);

        const track = el.parentElement;
        if (!track) {
            dragElement = null;
            return;
        }

        const leftPercent = parseFloat(el.style.left);
        const widthPercent = parseFloat(el.style.width);

        let newStart = new Date();
        let newEnd = new Date();

        if (currentView === 'day') {
            const timelineStart = new Date(currentDate);
            timelineStart.setHours(DAY_START_HOUR, 0, 0, 0);
            const totalTimelineMs = TOTAL_DAY_HOURS * 60 * 60 * 1000;

            const startMs = timelineStart.getTime() + (leftPercent / 100) * totalTimelineMs;
            const endMs = startMs + (widthPercent / 100) * totalTimelineMs;

            newStart = new Date(startMs);
            newEnd = new Date(endMs);
        } else {
            const { start: viewStart } = getDateRange();
            const totalTimelineMs = 7 * 24 * 60 * 60 * 1000;

            const startMs = viewStart.getTime() + (leftPercent / 100) * totalTimelineMs;
            const endMs = startMs + (widthPercent / 100) * totalTimelineMs;

            newStart = new Date(startMs);
            newEnd = new Date(endMs);
        }

        newStart = roundDateToMinutes(newStart, 15);
        newEnd = roundDateToMinutes(newEnd, 15);

        updateEventTimes(dragOriginalEventObj.id, newStart, newEnd);
        dragElement = null;
    }

    function roundDateToMinutes(date, minutes) {
        const ms = 1000 * 60 * minutes;
        return new Date(Math.round(date.getTime() / ms) * ms);
    }

    function updateEventTimes(id, start, end) {
        const formattedStart = formatLocalISO(start);
        const formattedEnd = formatLocalISO(end);

        fetch(`/scheduler/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                project_id: dragOriginalEventObj.project_id,
                type: 'machine',
                user_id: dragOriginalEventObj.user_id,
                machine_id: dragOriginalEventObj.machine_id,
                start_time: formattedStart,
                end_time: formattedEnd,
                notes: dragOriginalEventObj.notes
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadScheduler();
            } else {
                Swal.fire('Fehler', data.message || 'Der Termin konnte nicht verschoben werden.', 'error');
                loadScheduler();
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Fehler', 'Kommunikation mit Server fehlgeschlagen.', 'error');
            loadScheduler();
        });
    }

    function formatLocalISO(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
    }

    // ==========================================
    // MODAL OPERATIONS
    // ==========================================
    function openCreateModal() {
        document.getElementById('scheduleForm').reset();
        document.getElementById('eventId').value = '';
        document.getElementById('btnDeleteEvent').classList.add('d-none');
        document.getElementById('btnSaveEvent').classList.remove('d-none');
        document.getElementById('scheduleModalLabel').innerText = 'Neuen Termin planen';

        const now = new Date();
        now.setMinutes(0, 0, 0);
        document.getElementById('modalStart').value = toLocalDatetimeString(now);
        const end = new Date(now);
        end.setHours(end.getHours() + 2);
        document.getElementById('modalEnd').value = toLocalDatetimeString(end);

        scheduleModalInstance.show();
    }

    function openEditModal(event) {
        document.getElementById('eventId').value = event.id;
        document.getElementById('modalProject').value = event.project_id || '';
        document.getElementById('modalMachine').value = event.machine_id || '';
        document.getElementById('modalUser').value = event.user_id || '';

        document.getElementById('modalStart').value = toLocalDatetimeString(new Date(event.start_time));
        document.getElementById('modalEnd').value = toLocalDatetimeString(new Date(event.end_time));
        document.getElementById('modalNotes').value = event.notes || '';

        document.getElementById('scheduleModalLabel').innerText = 'Termin bearbeiten';

        document.getElementById('btnDeleteEvent').classList.remove('d-none');
        document.getElementById('btnSaveEvent').classList.remove('d-none');

        scheduleModalInstance.show();
    }

    function toLocalDatetimeString(date) {
        const tzoffset = date.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(date.getTime() - tzoffset)).toISOString().slice(0, 16);
        return localISOTime;
    }

    function saveSchedule(e) {
        e.preventDefault();

        const id = document.getElementById('eventId').value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `/scheduler/${id}` : '/scheduler';

        const payload = {
            project_id: document.getElementById('modalProject').value,
            type: 'machine',
            user_id: document.getElementById('modalUser').value || null,
            machine_id: document.getElementById('modalMachine').value || null,
            start_time: document.getElementById('modalStart').value,
            end_time: document.getElementById('modalEnd').value,
            notes: document.getElementById('modalNotes').value
        };

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                scheduleModalInstance.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Erfolgreich',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                loadScheduler();
            } else {
                Swal.fire('Validierungsfehler', data.message || 'Die Eingaben sind fehlerhaft.', 'warning');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Fehler', 'Die Daten konnten nicht gespeichert werden.', 'error');
        });
    }

    function deleteSchedule() {
        const id = document.getElementById('eventId').value;
        if (!id) return;

        Swal.fire({
            title: 'Sind Sie sicher?',
            text: "Dieser Eintrag wird endgültig gelöscht!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ja, löschen!',
            cancelButtonText: 'Abbrechen'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/scheduler/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        scheduleModalInstance.hide();
                        Swal.fire('Gelöscht!', data.message, 'success');
                        loadScheduler();
                    } else {
                        Swal.fire('Fehler', 'Konnte nicht gelöscht werden.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Fehler', 'Kommunikation mit Server fehlgeschlagen.', 'error');
                });
            }
        });
    }
</script>
@endpush
@endsection