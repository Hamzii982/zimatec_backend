@extends('user.layouts.index')

@section('title', 'Holzplatten Übersicht')

@section('content')
<div class="container py-4" style="max-width: 1100px;">
    <div class="row g-4">

        {{-- LEFT: Inventory --}}
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header">Neue Platte hinzufügen</div>
                <div class="card-body">
                    <form id="add-sheet-form">
                        <div class="mb-2">
                            <label class="form-label">Bezeichnung</label>
                            <input type="text" class="form-control" name="label" placeholder="z.B. Birke Multiplex">
                        </div>
                        <div class="row">
                            <div class="col-4 mb-2">
                                <label class="form-label">Länge (mm)</label>
                                <input type="number" class="form-control" name="length_mm" required min="1">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Breite (mm)</label>
                                <input type="number" class="form-control" name="width_mm" required min="1">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Dicke (mm)</label>
                                <input type="number" class="form-control" name="thickness_mm" required min="0.1" step="0.1">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-1">
                            <i class="bi bi-plus-lg"></i> Platte hinzufügen
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Lagerbestand</div>
                <ul id="sheet-list" class="list-group list-group-flush"></ul>
            </div>
        </div>

        {{-- RIGHT: Cutting workspace --}}
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header">Zuschnitt-Arbeitsbereich</div>
                <div class="card-body">
                    <div id="no-selection" class="text-muted text-center py-5">
                        Wähle links eine Platte aus dem Lager aus.
                    </div>

                    <div id="workspace" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 id="ws-title" class="mb-0"></h6>
                            <span id="ws-dims" class="badge bg-secondary"></span>
                        </div>

                        <div class="border rounded p-2 mb-3 bg-light text-center">
                            <svg id="preview-svg" width="100%" height="320" viewBox="0 0 500 320" style="cursor: crosshair;"></svg>
                            <small class="text-muted d-block mt-1">Klicke auf die Platte, um den Zuschnitt direkt festzulegen.</small>
                        </div>

                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-4">
                                <label class="form-label mb-1">Länge (mm)</label>
                                <input type="number" id="cut-length" class="form-control form-control-sm" min="1">
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1">Breite (mm)</label>
                                <input type="number" id="cut-width" class="form-control form-control-sm" min="1">
                            </div>
                            <div class="col-4">
                                <button id="btn-cut" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-scissors"></i> Schneiden
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1 d-block">Zuschnitt anwenden auf:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="apply-length" checked>
                                <label class="form-check-label" for="apply-length">Länge</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="apply-width">
                                <label class="form-check-label" for="apply-width">Breite</label>
                            </div>
                        </div>

                        <div class="btn-group w-100 mb-3" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-preset" data-fraction="1">Ganze Platte</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-preset" data-fraction="0.5">Halbe Platte</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-preset" data-fraction="0.25">Viertel Platte</button>
                        </div>

                        <div id="cut-result" class="alert alert-success d-none"></div>
                        <div id="cut-error" class="alert alert-danger d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const state = {
        sheets: @json($sheets),
        selectedId: null,
        geom: null, // {x, y, w, h, scale} of the currently drawn sheet rect in SVG units
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const listEl = document.getElementById('sheet-list');
    const noSelectionEl = document.getElementById('no-selection');
    const workspaceEl = document.getElementById('workspace');
    const svgEl = document.getElementById('preview-svg');
    const lengthEl = document.getElementById('cut-length');
    const widthEl = document.getElementById('cut-width');

    function fmt(n) {
        return Number(n).toLocaleString('de-DE');
    }

    function inStock() {
        return state.sheets.filter(s => s.status === 'in_stock');
    }

    function renderList() {
        listEl.innerHTML = '';
        inStock().forEach(sheet => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center' +
                (sheet.id === state.selectedId ? ' active' : '');
            li.style.cursor = 'pointer';
            li.innerHTML = `
                <div>
                    <div class="fw-semibold">${sheet.code}${sheet.label ? ' — ' + sheet.label : ''}</div>
                    <small class="${sheet.id === state.selectedId ? '' : 'text-muted'}">
                        ${fmt(sheet.length_mm)} × ${fmt(sheet.width_mm)} × ${fmt(sheet.thickness_mm)} mm
                    </small>
                </div>
                <span class="badge bg-success">Lager</span>
            `;
            li.addEventListener('click', () => selectSheet(sheet.id));
            listEl.appendChild(li);
        });

        if (inStock().length === 0) {
            listEl.innerHTML = '<li class="list-group-item text-muted">Kein Bestand vorhanden.</li>';
        }
    }

    function selectSheet(id) {
        state.selectedId = id;
        const sheet = state.sheets.find(s => s.id === id);
        if (!sheet) return;

        noSelectionEl.classList.add('d-none');
        workspaceEl.classList.remove('d-none');

        document.getElementById('ws-title').textContent = sheet.code + (sheet.label ? ' — ' + sheet.label : '');
        document.getElementById('ws-dims').textContent =
            `${fmt(sheet.length_mm)} × ${fmt(sheet.width_mm)} × ${fmt(sheet.thickness_mm)} mm`;

        lengthEl.value = '';
        widthEl.value = '';
        hideMessages();
        renderList();
        drawPreview();
    }

    function currentSheet() {
        return state.sheets.find(s => s.id === state.selectedId);
    }

    function drawPreview(hoverLength = null, hoverWidth = null) {
        const sheet = currentSheet();
        if (!sheet) return;

        const maxW = 460, maxH = 280;
        const scale = Math.min(maxW / sheet.length_mm, maxH / sheet.width_mm);
        const w = sheet.length_mm * scale;
        const h = sheet.width_mm * scale;
        const x = (500 - w) / 2;
        const y = (320 - h) / 2;

        state.geom = { x, y, w, h, scale };

        // Confirmed cut rectangle (from the input fields)
        const cutLength = parseFloat(lengthEl.value);
        const cutWidth = parseFloat(widthEl.value);
        let confirmedRectSvg = '';
        if (cutLength > 0 && cutWidth > 0) {
            const cw = Math.min(cutLength, sheet.length_mm) * scale;
            const ch = Math.min(cutWidth, sheet.width_mm) * scale;
            confirmedRectSvg = `<rect x="${x}" y="${y}" width="${cw}" height="${ch}" fill="rgba(220,53,69,0.35)" stroke="#dc3545" stroke-width="2" stroke-dasharray="4,3" />`;
        }

        // Live hover rectangle (while moving mouse, before click)
        let hoverRectSvg = '';
        let hoverLabelSvg = '';
        if (hoverLength > 0 && hoverWidth > 0) {
            const hw = hoverLength * scale;
            const hh = hoverWidth * scale;
            hoverRectSvg = `<rect x="${x}" y="${y}" width="${hw}" height="${hh}" fill="rgba(220,53,69,0.2)" stroke="#dc3545" stroke-width="1.5" style="transition: width 0.05s linear, height 0.05s linear;" />`;
            hoverLabelSvg = `<text x="${x + hw + 6}" y="${y + hh + 14}" font-size="11" fill="#dc3545">${fmt(Math.round(hoverLength))} × ${fmt(Math.round(hoverWidth))} mm</text>`;
        }

        svgEl.innerHTML = `
            <rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#f5deb3" stroke="#8b5e34" stroke-width="2" />
            ${confirmedRectSvg}
            ${hoverRectSvg}
            ${hoverLabelSvg}
            <rect id="click-overlay" x="${x}" y="${y}" width="${w}" height="${h}" fill="transparent" />
            <text x="250" y="${y - 6}" text-anchor="middle" font-size="12" fill="#555">Länge: ${fmt(sheet.length_mm)} mm</text>
            <text x="${x - 8}" y="${y + h / 2}" text-anchor="end" font-size="12" fill="#555" transform="rotate(-90 ${x - 8} ${y + h / 2})">Breite: ${fmt(sheet.width_mm)} mm</text>
        `;

        attachOverlayEvents();
    }

    function svgPointFromEvent(evt) {
        const pt = svgEl.createSVGPoint();
        pt.x = evt.clientX;
        pt.y = evt.clientY;
        const ctm = svgEl.getScreenCTM().inverse();
        return pt.matrixTransform(ctm);
    }

    function attachOverlayEvents() {
        const overlay = document.getElementById('click-overlay');
        if (!overlay) return;

        overlay.addEventListener('mousemove', (evt) => {
            const sheet = currentSheet();
            if (!sheet || !state.geom) return;

            const p = svgPointFromEvent(evt);
            const dx = Math.min(Math.max(p.x - state.geom.x, 0), state.geom.w);
            const dy = Math.min(Math.max(p.y - state.geom.y, 0), state.geom.h);

            const mmLength = Math.min(Math.round(dx / state.geom.scale), sheet.length_mm);
            const mmWidth = Math.min(Math.round(dy / state.geom.scale), sheet.width_mm);

            drawPreview(mmLength, mmWidth);
        });

        overlay.addEventListener('mouseleave', () => drawPreview());

        overlay.addEventListener('click', (evt) => {
            const sheet = currentSheet();
            if (!sheet || !state.geom) return;

            const p = svgPointFromEvent(evt);
            const dx = Math.min(Math.max(p.x - state.geom.x, 0), state.geom.w);
            const dy = Math.min(Math.max(p.y - state.geom.y, 0), state.geom.h);

            const mmLength = Math.min(Math.max(Math.round(dx / state.geom.scale), 1), sheet.length_mm);
            const mmWidth = Math.min(Math.max(Math.round(dy / state.geom.scale), 1), sheet.width_mm);

            lengthEl.value = mmLength;
            widthEl.value = mmWidth;
            drawPreview();
        });
    }

    function hideMessages() {
        document.getElementById('cut-result').classList.add('d-none');
        document.getElementById('cut-error').classList.add('d-none');
    }

    function showError(msg) {
        const el = document.getElementById('cut-error');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function showResult(cutPiece, remainders) {
        const el = document.getElementById('cut-result');
        let msg = `Zugeschnitten: <strong>${cutPiece.code}</strong> (${fmt(cutPiece.length_mm)} × ${fmt(cutPiece.width_mm)} mm)`;
        if (remainders.length > 0) {
            remainders.forEach(r => {
                msg += `<br>Neue Restplatte: <strong>${r.code}</strong> (${fmt(r.length_mm)} × ${fmt(r.width_mm)} mm) — im Lager verbleibend.`;
            });
        } else {
            msg += `<br>Keine Restplatte — komplette Platte verwendet.`;
        }
        el.innerHTML = msg;
        el.classList.remove('d-none');
    }

    // --- Add sheet ---
    document.getElementById('add-sheet-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        const res = await fetch('{{ route("sheets.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData,
        });

        if (!res.ok) {
            alert('Fehler beim Hinzufügen der Platte.');
            return;
        }

        const data = await res.json();
        state.sheets = data.sheets;
        this.reset();
        renderList();
    });

    // --- Quick presets ---
    document.querySelectorAll('.quick-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            const sheet = currentSheet();
            if (!sheet) return;
            const fraction = parseFloat(btn.dataset.fraction);
            const applyLength = document.getElementById('apply-length').checked;
            const applyWidth = document.getElementById('apply-width').checked;

            if (!applyLength && !applyWidth) {
                showError('Bitte mindestens Länge oder Breite auswählen.');
                return;
            }

            lengthEl.value = Math.round(sheet.length_mm * (applyLength ? fraction : 1));
            widthEl.value = Math.round(sheet.width_mm * (applyWidth ? fraction : 1));
            drawPreview();
        });
    });

    [lengthEl, widthEl].forEach(el => el.addEventListener('input', () => drawPreview()));

    // --- Perform cut ---
    document.getElementById('btn-cut').addEventListener('click', async () => {
        const sheet = currentSheet();
        if (!sheet) return;
        hideMessages();

        const cutLength = parseFloat(lengthEl.value);
        const cutWidth = parseFloat(widthEl.value);

        if (!cutLength || !cutWidth || cutLength <= 0 || cutWidth <= 0) {
            showError('Bitte gültige Länge und Breite eingeben.');
            return;
        }

        const res = await fetch(`/sheets/${sheet.id}/cut`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ cut_length: cutLength, cut_width: cutWidth }),
        });

        const data = await res.json();

        if (!res.ok) {
            showError(data.message || 'Fehler beim Zuschneiden.');
            return;
        }

        state.sheets = data.sheets;
        showResult(data.cut_piece, data.remainders);

        if (data.remainders.length > 0) {
            selectSheet(data.remainders[0].id);
        } else {
            state.selectedId = null;
            noSelectionEl.classList.remove('d-none');
            workspaceEl.classList.add('d-none');
            renderList();
        }
    });

    renderList();
})();
</script>
@endsection