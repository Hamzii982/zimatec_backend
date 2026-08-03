// Full material list from PHP — flat, all shelves
const allMaterials = window.tablarData.flatList;
const storagePath = window.tablarData.storagePath ?? '/storage/';
const consumeUrl    = window.tablarData.consumeUrl;
const returnUrl     = window.tablarData.returnUrl;
const reserveUrl    = window.tablarData.reserveUrl;
const settleReservationUrl = window.tablarData.settleReservationUrl;
const orderBaseUrl  = window.tablarData.orderRequestBase;
const sheetOptionsUrlBase = window.tablarData.sheetOptionsUrlBase;
const sheetCutUrl = window.tablarData.sheetCutUrl;
const sheetSearchUrl = window.tablarData.sheetSearchUrl;

// Group by shelf for fast lookup: { "A1": [...], "B2": [...] }
const byShelf = {};
allMaterials.forEach(m => {
    const key = m.shelf ?? 'Unbekannt';
    if (!byShelf[key]) byShelf[key] = [];
    byShelf[key].push(m);
});

let currentShelf = null;
let currentShelfMaterials = [];
let selectedMaterial = null;

let selectedSheetMaterial = null; // { id, name }
let selectedSheetOption = null;   // { type, sheet_id, length_mm, width_mm, thickness_mm }
let sheetGeom = null;             // svg draw geometry for click-to-cut
let selectedCorner = 'top-left';
let selectedSheetAxis = null;     // 'length' | 'width' | null (null = let backend auto-pick)

const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function openSheetModal(id, name) {
    selectedSheetMaterial = { id, name };
    document.getElementById('sheetModalTitle').innerText = name;

    document.getElementById('sheetPickStep').classList.remove('d-none');
    document.getElementById('sheetCutStep').classList.add('d-none');
    document.getElementById('sheetOptionsList').innerHTML = `
        <div class="text-center text-muted py-4">
            <span class="spinner-border spinner-border-sm me-2"></span> Lädt...
        </div>`;

    new bootstrap.Modal(document.getElementById('sheetModal')).show();

    try {
        const res = await fetch(`${sheetOptionsUrlBase}/${id}/sheet-options`);
        if (!res.ok) throw new Error(await res.text());
        const data = await res.json();
        renderSheetOptions(data.options);
    } catch (e) {
        document.getElementById('sheetOptionsList').innerHTML = `
            <div class="alert alert-danger">Fehler beim Laden der Plattenoptionen.</div>`;
    }

    const searchResult = document.getElementById('sheetSearchResult');
    if (searchResult) searchResult.classList.add('d-none');
    const lengthInput = document.getElementById('sheetSearchLength');
    const widthInput = document.getElementById('sheetSearchWidth');
    if (lengthInput) lengthInput.value = '';
    if (widthInput) widthInput.value = '';
}

async function ungroupSheet(sheetId) {
    await fetch(window.tablarData.ungroupUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ sheet_id: sheetId }),
    });
    const res = await fetch(`${sheetOptionsUrlBase}/${selectedSheetMaterial.id}/sheet-options`);
    const data = await res.json();
    renderSheetOptions(data.options);
}

function measureFromCorner(p, geom, corner) {
    const dxLeft = Math.min(Math.max(p.x - geom.x, 0), geom.w);
    const dyTop  = Math.min(Math.max(p.y - geom.y, 0), geom.h);
    const dx = (corner === 'top-right' || corner === 'bottom-right') ? geom.w - dxLeft : dxLeft;
    const dy = (corner === 'bottom-left' || corner === 'bottom-right') ? geom.h - dyTop : dyTop;
    return { dx, dy };
}

function renderSheetOptions(options) {
    const container = document.getElementById('sheetOptionsList');

    if (options.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-4">Kein Bestand vorhanden.</div>`;
        return;
    }

    container.innerHTML = options.map((opt) => `
        <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border material-item">
            <div onclick='selectSheetOption(${JSON.stringify(opt)})' style="cursor:pointer; flex:1;">
                <div class="fw-semibold">
                    ${opt.label}
                    ${opt.sibling_group_id ? `<span class="badge bg-light text-dark border ms-1">Reste-Paar #${opt.sibling_display_number} (${opt.sibling_position})</span>` : ''}
                </div>
                <small class="text-muted">
                    ${opt.length_mm ? Math.round(opt.length_mm) + ' × ' + Math.round(opt.width_mm) + ' mm' : 'Größe unbekannt'}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                ${opt.sibling_group_id ? `<button class="btn btn-sm btn-outline-secondary" onclick='ungroupSheet(${opt.sheet_id})'>Trennen</button>` : ''}
                <span class="badge ${opt.type === 'full' ? 'bg-success' : 'bg-info text-dark'}">
                    ${opt.type === 'full' ? 'Auf Lager: ' + opt.quantity : 'Zugeschnitten'}
                </span>
            </div>
        </div>
    `).join('');
}

function selectSheetOption(opt) {
    selectedSheetOption = opt;
    selectedSheetAxis = null;

    document.getElementById('sheetPickStep').classList.add('d-none');
    document.getElementById('sheetCutStep').classList.remove('d-none');

    document.getElementById('sheetCutTitle').innerText =
        selectedSheetMaterial.name + (opt.type === 'full' ? ' — Volle Platte' : ' — ' + opt.label);
    document.getElementById('sheetCutDims').innerText =
        `${Math.round(opt.length_mm)} × ${Math.round(opt.width_mm)} mm`;

    document.getElementById('sheetCutLength').value = '';
    document.getElementById('sheetCutWidth').value = '';
    hideSheetMessages();
    updateAxisGroupVisibility();
    drawSheetPreview();
}

function updateAxisGroupVisibility() {
    const opt = selectedSheetOption;
    const group = document.getElementById('sheetCutAxisGroup');
    if (!group) return;

    const lengthRadio = document.getElementById('sheetCutAxisLength');
    const widthRadio = document.getElementById('sheetCutAxisWidth');
    const hint = document.getElementById('sheetCutAxisHint');

    if (!opt || opt.axis_choice_available !== true || !opt.length_mm || !opt.width_mm) {
        group.classList.add('d-none');
        if (lengthRadio) lengthRadio.checked = false;
        if (widthRadio) widthRadio.checked = false;
        return;
    }

    const cutLength = parseFloat(document.getElementById('sheetCutLength').value);
    const cutWidth = parseFloat(document.getElementById('sheetCutWidth').value);

    const bothRemainders = cutLength > 0 && cutWidth > 0
        && cutLength < opt.length_mm
        && cutWidth < opt.width_mm;

    if (!bothRemainders) {
        group.classList.add('d-none');
        if (lengthRadio) lengthRadio.checked = false;
        if (widthRadio) widthRadio.checked = false;
        return;
    }

    group.classList.remove('d-none');
    const autoAxis = opt.length_mm <= opt.width_mm ? 'length' : 'width';
    const shorter = Math.min(opt.length_mm, opt.width_mm);
    if (hint) hint.textContent = ` (empfohlen: ${Math.round(shorter)} mm)`;
    if (lengthRadio) lengthRadio.checked = autoAxis === 'length';
    if (widthRadio) widthRadio.checked = autoAxis === 'width';
}

function backToSheetPick() {
    document.getElementById('sheetCutStep').classList.add('d-none');
    document.getElementById('sheetPickStep').classList.remove('d-none');
}

function drawSheetPreview(hoverLength = null, hoverWidth = null) {
    const opt = selectedSheetOption;
    if (!opt) return;

    const svgEl = document.getElementById('sheetPreviewSvg');
    const maxW = 460, maxH = 260;
    const scale = Math.min(maxW / opt.length_mm, maxH / opt.width_mm);
    const w = opt.length_mm * scale;
    const h = opt.width_mm * scale;
    const x = (500 - w) / 2;
    const y = (300 - h) / 2;

    sheetGeom = { x, y, w, h, scale };

    const cutLength = parseFloat(document.getElementById('sheetCutLength').value);
    const cutWidth = parseFloat(document.getElementById('sheetCutWidth').value);
    let confirmedRectSvg = '';
    if (cutLength > 0 && cutWidth > 0) {
        const cw = Math.min(cutLength, opt.length_mm) * scale;
        const ch = Math.min(cutWidth, opt.width_mm) * scale;
        const pos = rectPosForCorner(selectedCorner, sheetGeom, cw, ch);
        confirmedRectSvg = `<rect x="${pos.rx}" y="${pos.ry}" width="${cw}" height="${ch}" fill="rgba(220,53,69,0.35)" stroke="#dc3545" stroke-width="2" stroke-dasharray="4,3" />`;
    }
    
    let hoverRectSvg = '', hoverLabelSvg = '';
    if (hoverLength > 0 && hoverWidth > 0) {
        const hw = hoverLength * scale;
        const hh = hoverWidth * scale;
        const pos = rectPosForCorner(selectedCorner, sheetGeom, hw, hh);
        hoverRectSvg = `<rect x="${pos.rx}" y="${pos.ry}" width="${hw}" height="${hh}" fill="rgba(220,53,69,0.2)" stroke="#dc3545" stroke-width="1.5" />`;
        hoverLabelSvg = `<text x="${pos.rx + hw + 6}" y="${pos.ry + hh + 14}" font-size="11" fill="#dc3545">${Math.round(hoverLength)} × ${Math.round(hoverWidth)} mm</text>`;
    }

    svgEl.innerHTML = `
        <rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#f5deb3" stroke="#8b5e34" stroke-width="2" />
        ${confirmedRectSvg}
        ${hoverRectSvg}
        ${hoverLabelSvg}
        <rect id="sheetClickOverlay" x="${x}" y="${y}" width="${w}" height="${h}" fill="transparent" />
        <text x="250" y="${y - 6}" text-anchor="middle" font-size="12" fill="#555">Länge: ${Math.round(opt.length_mm)} mm</text>
        <text x="${x - 8}" y="${y + h / 2}" text-anchor="end" font-size="12" fill="#555" transform="rotate(-90 ${x - 8} ${y + h / 2})">Breite: ${Math.round(opt.width_mm)} mm</text>
    `;

    attachSheetOverlayEvents();
}

function sheetSvgPointFromEvent(evt) {
    const svgEl = document.getElementById('sheetPreviewSvg');
    const pt = svgEl.createSVGPoint();
    pt.x = evt.clientX;
    pt.y = evt.clientY;
    const ctm = svgEl.getScreenCTM().inverse();
    return pt.matrixTransform(ctm);
}

function attachSheetOverlayEvents() {
    const overlay = document.getElementById('sheetClickOverlay');
    if (!overlay) return;
    const opt = selectedSheetOption;

    overlay.addEventListener('mousemove', (evt) => {
        if (!opt || !sheetGeom) return;
        const p = sheetSvgPointFromEvent(evt);
        const { dx, dy } = measureFromCorner(p, sheetGeom, selectedCorner);
        const mmLength = Math.min(Math.round(dx / sheetGeom.scale), opt.length_mm);
        const mmWidth = Math.min(Math.round(dy / sheetGeom.scale), opt.width_mm);
        drawSheetPreview(mmLength, mmWidth);
    });

    overlay.addEventListener('mouseleave', () => drawSheetPreview());

    overlay.addEventListener('click', (evt) => {
        if (!opt || !sheetGeom) return;
        const p = sheetSvgPointFromEvent(evt);
        const { dx, dy } = measureFromCorner(p, sheetGeom, selectedCorner);
        const mmLength = Math.min(Math.max(Math.round(dx / sheetGeom.scale), 1), opt.length_mm);
        const mmWidth = Math.min(Math.max(Math.round(dy / sheetGeom.scale), 1), opt.width_mm);
        document.getElementById('sheetCutLength').value = mmLength;
        document.getElementById('sheetCutWidth').value = mmWidth;
        updateAxisGroupVisibility();
        drawSheetPreview();
    });
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('corner-btn')) {
        document.querySelectorAll('.corner-btn').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        selectedCorner = e.target.dataset.corner;
        drawSheetPreview();
    }
});

function rectPosForCorner(corner, geom, wPx, hPx) {
    switch (corner) {
        case 'top-right':    return { rx: geom.x + geom.w - wPx, ry: geom.y };
        case 'bottom-left':  return { rx: geom.x, ry: geom.y + geom.h - hPx };
        case 'bottom-right': return { rx: geom.x + geom.w - wPx, ry: geom.y + geom.h - hPx };
        default:              return { rx: geom.x, ry: geom.y }; // top-left
    }
}

document.addEventListener('input', (e) => {
    if (e.target.id === 'sheetCutLength' || e.target.id === 'sheetCutWidth') {
        updateAxisGroupVisibility();
        drawSheetPreview();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    if (e.target.id === 'sheetSearchLength' || e.target.id === 'sheetSearchWidth') {
        e.preventDefault();
        runSheetSearch();
    }
});

document.addEventListener('change', (e) => {
    if (e.target.name === 'sheetCutAxis') {
        selectedSheetAxis = e.target.value;
    }
});

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('sheet-preset')) {
        const opt = selectedSheetOption;
        if (!opt) return;

        const fraction = parseFloat(e.target.dataset.fraction);
        const applyLength = document.getElementById('sheetApplyLength').checked;
        const applyWidth = document.getElementById('sheetApplyWidth').checked;

        if (!applyLength && !applyWidth) {
            showSheetError('Bitte mindestens Länge oder Breite auswählen.');
            return;
        }

        document.getElementById('sheetCutLength').value = Math.round(opt.length_mm * (applyLength ? fraction : 1));
        document.getElementById('sheetCutWidth').value = Math.round(opt.width_mm * (applyWidth ? fraction : 1));
        drawSheetPreview();
    }
});

function hideSheetMessages() {
    document.getElementById('sheetCutResult').classList.add('d-none');
    document.getElementById('sheetCutError').classList.add('d-none');
}

function showSheetError(msg) {
    const el = document.getElementById('sheetCutError');
    el.textContent = msg;
    el.classList.remove('d-none');
}

async function performSheetCut() {
    const opt = selectedSheetOption;
    if (!opt || !selectedSheetMaterial) return;
    hideSheetMessages();

    const cutLength = parseFloat(document.getElementById('sheetCutLength').value);
    const cutWidth = parseFloat(document.getElementById('sheetCutWidth').value);

    if (!cutLength || !cutWidth || cutLength <= 0 || cutWidth <= 0) {
        showSheetError('Bitte gültige Länge und Breite eingeben.');
        return;
    }

    const btn = document.getElementById('btnSheetCut');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Wird geschnitten...`;

    try {
        const body = {
            material_id: selectedSheetMaterial.id,
            sheet_id: opt.type === 'full' ? null : opt.sheet_id,
            cut_length: cutLength,
            cut_width: cutWidth,
        };
        if (selectedSheetAxis !== null) {
            body.cut_axis = selectedSheetAxis;
        }

        const res = await fetch(sheetCutUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body),
        });

        if (!res.ok) throw new Error(await res.text());
        const data = await res.json();

        selectedSheetAxis = null;

        // Keep the local material cache (quantity badge) in sync
        const m = allMaterials.find(x => x.id === selectedSheetMaterial.id);
        if (m) m.quantity = data.new_quantity;
        filterMaterials();
        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

        let msg = `Zugeschnitten: <strong>${Math.round(cutLength)} × ${Math.round(cutWidth)} mm</strong>`;
        if (data.remainders.length > 0) {
            data.remainders.forEach(r => {
                msg += `<br>Restplatte gespeichert: ${Math.round(r.length_mm)} × ${Math.round(r.width_mm)} mm (${r.code})`;
            });
        } else {
            msg += `<br>Keine Restplatte übrig.`;
        }
        document.getElementById('sheetCutResult').innerHTML = msg;
        document.getElementById('sheetCutResult').classList.remove('d-none');

        // Refresh the options list so the picker reflects new stock,
        // then send the user back to pick their next action.
        const refreshed = await fetch(`${sheetOptionsUrlBase}/${selectedSheetMaterial.id}/sheet-options`);
        const refreshedData = await refreshed.json();
        renderSheetOptions(refreshedData.options);
        setTimeout(() => backToSheetPick(), 1200);

    } catch (e) {
        showSheetError('Fehler beim Zuschneiden: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

async function runSheetSearch() {
    if (!selectedSheetMaterial) return;

    const lengthInput = document.getElementById('sheetSearchLength');
    const widthInput = document.getElementById('sheetSearchWidth');
    const resultEl = document.getElementById('sheetSearchResult');
    const lengthMm = parseFloat(lengthInput.value);
    const widthMm = parseFloat(widthInput.value);

    if (!lengthMm || !widthMm || lengthMm <= 0 || widthMm <= 0) {
        resultEl.className = 'alert alert-warning mb-0 mt-2';
        resultEl.textContent = 'Bitte gültige Länge und Breite eingeben.';
        resultEl.classList.remove('d-none');
        return;
    }

    const btn = document.getElementById('btnSheetSearch');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

    try {
        const res = await fetch(`${sheetSearchUrl}/${selectedSheetMaterial.id}/sheet-search`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({
                material_id: selectedSheetMaterial.id,
                length_mm: lengthMm,
                width_mm: widthMm,
            }),
        });

        if (!res.ok) throw new Error(await res.text());
        const data = await res.json();

        if (!data.sheet) {
            resultEl.className = 'alert alert-warning mb-0 mt-2';
            resultEl.textContent = 'Kein passendes Stück gefunden.';
            resultEl.classList.remove('d-none');
            return;
        }

        // Build an option object compatible with selectSheetOption().
        const opt = {
            type: data.sheet.type,
            sheet_id: data.sheet.sheet_id,
            label: data.sheet.code ?? (data.sheet.type === 'full' ? 'Volle Platte' : 'Zugeschnitten'),
            length_mm: data.sheet.length_mm,
            width_mm: data.sheet.width_mm,
            thickness_mm: data.sheet.thickness_mm,
            quantity: data.sheet.quantity,
            axis_choice_available: !!data.sheet.axis_choice_available,
        };

        resultEl.classList.add('d-none');
        selectSheetOption(opt);
    } catch (e) {
        resultEl.className = 'alert alert-danger mb-0 mt-2';
        resultEl.textContent = 'Fehler bei der Suche: ' + e.message;
        resultEl.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

// Helper function to build image markup
function generateImageHtml(image, name) {
    if (image) {
        const fullSrc = `${storagePath}/${image}`.replace(/\/+/g, '/').replace(':/', '://');
        return `<img src="${fullSrc}" alt="${name}" width="60" height="60" class="rounded border img-thumbnail-clickable me-3" onclick="maximizeImage(event, '${fullSrc}')">`;
    }
    return `
        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center me-3" style="width:60px; height:60px; min-width:60px;">
            <i class="bi bi-box-seam"></i>
        </div>`;
}

// Helper function to build order status or order button markup
function generateOrderHtml(m) {
    if (m.order_status) {
        const statusText = window.tablarData.statusTranslations[m.order_status] ?? ucfirst(m.order_status);
        const qtyText = (m.order_status === 'ordered' && m.order_quantity)
            ? ` · ${m.order_quantity} ${m.unit ?? 'Stk.'}`
            : '';
        return `<span class="badge bg-warning text-dark ms-2"><i class="bi bi-clock-history me-1"></i>${statusText}${qtyText}</span>`;
    }
    return `
        <button class="btn btn-sm btn-outline-primary ms-2" onclick="event.stopPropagation(); triggerOrder(${m.id})">
            Bestellen
        </button>`;
}

// ─── SHELF SELECTION ──────────────────────────────────────────────────────────

function filterShelves() {
    const q = document.getElementById('shelfSearch').value.toLowerCase();
    document.querySelectorAll('.shelf-tile').forEach(tile => {
        tile.style.display = tile.dataset.shelf.includes(q) ? '' : 'none';
    });
}

function selectShelf(shelf) {
    currentShelf = shelf;
    currentShelfMaterials = byShelf[shelf] ?? [];

    document.getElementById('selectedShelfLabel').innerText = shelf;
    document.getElementById('materialSearch').value = '';
    document.getElementById('shelfStep').classList.add('d-none');
    document.getElementById('materialStep').classList.remove('d-none');

    renderMaterials(currentShelfMaterials);

    // Auto-focus search after short delay (modal animation)
    setTimeout(() => document.getElementById('materialSearch').focus(), 150);
}

function goBackToShelves() {
    currentShelf = null;
    document.getElementById('materialStep').classList.add('d-none');
    document.getElementById('shelfStep').classList.remove('d-none');
    document.getElementById('shelfSearch').value = '';
    filterShelves(); // reset shelf tiles
}

// ─── MATERIAL LIST ────────────────────────────────────────────────────────────

function filterMaterials() {
    const q = document.getElementById('materialSearch').value.toLowerCase();
    const filtered = q
        ? currentShelfMaterials.filter(m => m.name.toLowerCase().includes(q))
        : currentShelfMaterials;
    renderMaterials(filtered);
}

function renderMaterials(materials) {
    const container = document.getElementById('materialList');

    if (materials.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Kein Material gefunden.
        </div>`;
        return;
    }

    container.innerHTML = materials.map(m => {
        const outOfStock = m.quantity+m.on_hold_quantity <= 0;
        const threshold  = m.threshold ?? 0;
        const onHold     = m.on_hold_quantity ?? 0;
        const orderQty   = m.order_quantity ?? 0;
        const available  = m.available_total ?? (m.quantity + onHold + orderQty);
        const isReserved = onHold > 0;
        const modalType = window.tablarData.isHolzLager
            ? 'openSheetModal'
            : (isReserved ? 'openReserveModal' : 'openMaterialModal');
        const badgeClass = outOfStock
            ? 'bg-secondary'
            : available > threshold ? 'bg-success' : 'bg-danger';
        const badgeText  = outOfStock ? 'Kommt gleich' : m.quantity + ' ' + (m.unit ?? 'Stk.');
        const leftoverBadge = (window.tablarData.isHolzLager && m.leftover_sheet_count > 0)
            ? `<span class="badge bg-secondary ms-1"><i class="bi bi-scissors me-1"></i>${m.leftover_sheet_count} Reste</span>`
            : '';

        const imageTemplate = generateImageHtml(m.image, m.name);
        const orderTemplate = generateOrderHtml(m);
        const onHoldText = isReserved
            ? `<span class="badge bg-info text-dark ms-2"><i class="bi bi-clock-history me-1"></i>Reserviert: ${onHold} ${m.unit ?? 'Stk.'}</span>`
            : '';
        const orderText = orderQty > 0
            ? `<span class="badge bg-warning text-dark ms-2"><i class="bi bi-truck me-1"></i>Bestellt: ${orderQty} ${m.unit ?? 'Stk.'}</span>`
            : '';

        if (outOfStock) {
            return `
            <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border bg-light text-muted"
                style="cursor: not-allowed;"
                onclick="Swal.fire('Nicht verfügbar', 'Bitte warten Sie auf Nachschub.', 'info')">
                <div class="d-flex align-items-center">
                    ${imageTemplate}
                    <div>
                        <span class="text-decoration-line-through fw-semibold">${m.name}</span>
                        <div class="mt-1">${orderTemplate}</div>
                    </div>
                </div>
                <span class="badge ${badgeClass}">${badgeText}</span>
            </div>`;
        }

        return `
        <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border material-item"
            onclick="${modalType}(${m.id}, '${m.name}', ${m.quantity}, '${m.shelf}', ${onHold}, '${m.unit ?? 'Stk.'}')">
            <div class="d-flex align-items-center">
                ${imageTemplate}
                <div>
                    <span class="fw-semibold">${m.name} ${onHoldText}${orderText}</span>
                    <div class="mt-1">${orderTemplate}</div>
                </div>
            </div>
            <span class="text-end">
                <span class="badge ${badgeClass} fs-6">${badgeText}</span>
                ${leftoverBadge}
            </span>
        </div>`;
    }).join('');
}

// ─── MODAL ────────────────────────────────────────────────────────────────────

function openMaterialModal(id, name, quantity, shelf, onHoldQuantity, unit) {
    selectedMaterial = { id, name, quantity, shelf, onHoldQuantity, unit };

    document.getElementById('modalMaterialName').innerText = name;
    document.getElementById('modalShelf').innerText        = 'Tablar: ' + shelf;
    document.getElementById('modalAvailable').innerText    = quantity + ' ' + (selectedMaterial.unit ?? 'Stk.');

    const input = document.getElementById('counterInput');
    input.value = 1;
    // When returning, max configuration shouldn't block entering high amounts, so we remove programmatic max cap.
    input.removeAttribute('max'); 

    new bootstrap.Modal(document.getElementById('materialModal')).show();
}

function openReserveModal(id, name, quantity, shelf, onHoldQuantity, unit) {
    selectedMaterial = { id, name, quantity, shelf, onHoldQuantity, unit };

    document.getElementById('reserveModalMaterialName').innerText = name;
    document.getElementById('reserveModalShelf').innerText        = 'Tablar: ' + shelf;
    document.getElementById('reserveModalAvailable').innerText    = quantity + ' ' + (selectedMaterial.unit ?? 'Stk.');
    document.getElementById('reserveModalOnHold').innerText       = onHoldQuantity;

    const input = document.getElementById('reserveCounterInput');
    input.value = 0;
    input.max   = onHoldQuantity;

    updateReserveHint();

    new bootstrap.Modal(document.getElementById('reserveModal')).show();
}

function updateReserveHint() {
    const val = parseInt(document.getElementById('reserveCounterInput').value) || 0;
    const consumed = (selectedMaterial?.onHoldQuantity ?? 0) - val;
    document.getElementById('reserveModalConsumedHint').innerText =
        `${val} ${selectedMaterial?.unit ?? 'Stk.'} gehen zurück ins Lager, ${consumed} ${selectedMaterial?.unit ?? 'Stk.'} gelten als verbraucht.`;
}

function validateReserveInput(input) {
    let val = parseInt(input.value);
    const max = selectedMaterial?.onHoldQuantity ?? 0;
    if (isNaN(val) || val < 0) val = 0;
    if (val > max) val = max;
    input.value = val;
    updateReserveHint();
}

function increaseReserve() {
    const input = document.getElementById('reserveCounterInput');
    const val   = parseInt(input.value);
    if (val < selectedMaterial.onHoldQuantity) input.value = val + 1;
    updateReserveHint();
}

function decreaseReserve() {
    const input = document.getElementById('reserveCounterInput');
    const val   = parseInt(input.value);
    if (val > 0) input.value = val - 1;
    updateReserveHint();
}

async function confirmReservationSettlement() {
    const returnQty = parseInt(document.getElementById('reserveCounterInput').value);
    if (!selectedMaterial || isNaN(returnQty)) return;

    const btn = document.querySelector('#reserveModal .btn-primary');
    const originalContent = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Lädt...`;

    try {
        const res = await fetch(settleReservationUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, return_quantity: returnQty })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) {
            m.quantity = data.new_quantity;
            m.on_hold_quantity = data.on_hold_quantity;
        }

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('reserveModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

    } catch (e) {
        alert('Fehler beim Abschließen der Reservierung: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

function validateManualInput(input) {
    let val = parseInt(input.value);
    if (isNaN(val) || val < 1) input.value = 1;
}

function increase() {
    const input = document.getElementById('counterInput');
    const val   = parseInt(input.value);
    if (val < selectedMaterial.quantity) input.value = val + 1;
}

function decrease() {
    const input = document.getElementById('counterInput');
    const val   = parseInt(input.value);
    if (val > 1) input.value = val - 1;
}

// ─── CONFIRM CONSUMPTION ──────────────────────────────────────────────────────

async function confirmConsumption() {
    const amountTaken = parseInt(document.getElementById('counterInput').value);
    if (!selectedMaterial || isNaN(amountTaken)) return;

    if (amountTaken > selectedMaterial.quantity) {
        alert('Es kann nicht mehr entnommen werden als verfügbar ist!');
        return;
    }

    const btn = document.querySelector('#materialModal .btn-primary');
    const originalContent = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = `<span class="spinner-border spinner-border-sm me-2"></span> Wird gebucht...`;

    try {
        const res = await fetch(consumeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, quantity: amountTaken })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) m.quantity = data.new_quantity;

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

    } catch (e) {
        alert('Fehler beim Buchen: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

// ─── CONFIRM RETURN (EINLAGERN) ────────────────────────────────────────────────

async function confirmReturn() {
    const amountReturned = parseInt(document.getElementById('counterInput').value);
    if (!selectedMaterial || isNaN(amountReturned)) return;

    const btn = document.querySelector('#materialModal .btn-danger');
    const originalContent = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = `<span class="spinner-border spinner-border-sm me-2"></span> Lädt...`;

    try {
        const res = await fetch(returnUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, quantity: amountReturned })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) m.quantity = data.new_quantity;

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }

    } catch (e) {
        alert('Fehler beim Einlagern: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

async function confirmReservation() {
    const amountReserved = parseInt(document.getElementById('counterInput').value);
    if (!selectedMaterial || isNaN(amountReserved)) return;

    const btn = document.querySelector('#materialModal .btn-secondary');
    const originalContent = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = `<span class="spinner-border spinner-border-sm me-2"></span> Lädt...`;

    try {
        const res = await fetch(`${reserveUrl}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ material_id: selectedMaterial.id, quantity: amountReserved })
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === selectedMaterial.id);
        if (m) {
            m.on_hold_quantity = data.on_hold_quantity;
            m.quantity = data.new_quantity;
        }

        filterMaterials();
        bootstrap.Modal.getInstance(document.getElementById('materialModal')).hide();

        if (!document.getElementById('nameStep').classList.contains('d-none')) {
            filterByName();
        }
        
    } catch (e) {
        alert('Fehler beim Reservieren: ' + e.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalContent;
    }
}

// ─── ORDER TRIGGER PLUG ───────────────────────────────────────────────────────

async function triggerOrder(materialId) {
    try{
        const res = await fetch(`${orderBaseUrl}/${materialId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }
        });

        if (!res.ok) throw new Error(await res.text());

        const data = await res.json();

        const m = allMaterials.find(x => x.id === materialId);
        if (m) m.order_status = data.order_status;

        filterMaterials();
        
        alert('Admin hat Bestellung request gestellt. Bitte warten Sie auf Bestätigung.');

    } catch (e) {
        alert('Fehler beim mitteillen Admin: ' + e.message);
    }
}

// ─── MODE SWITCH ──────────────────────────────────────────────────────────────

function switchMode(mode) {
    if (mode === 'shelf') {
        document.getElementById('shelfStep').classList.remove('d-none');
        document.getElementById('materialStep').classList.add('d-none');
        document.getElementById('nameStep').classList.add('d-none');
        document.getElementById('tabShelf').classList.add('active');
        document.getElementById('tabName').classList.remove('active');
    } else {
        document.getElementById('nameStep').classList.remove('d-none');
        document.getElementById('shelfStep').classList.add('d-none');
        document.getElementById('materialStep').classList.add('d-none');
        document.getElementById('tabName').classList.add('active');
        document.getElementById('tabShelf').classList.remove('active');
        setTimeout(() => document.getElementById('globalNameSearch').focus(), 150);
    }
}

// ─── GLOBAL NAME SEARCH ───────────────────────────────────────────────────────

function filterByName() {
    const q = document.getElementById('globalNameSearch').value.toLowerCase().trim();
    const container = document.getElementById('globalNameResults');

    if (q.length < 1) {
        container.innerHTML = '';
        return;
    }

    const filtered = allMaterials.filter(m => m.name.toLowerCase().includes(q));

    if (filtered.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            Kein Material gefunden.
        </div>`;
        return;
    }

    container.innerHTML = filtered.map(m => {
        const outOfStock = m.quantity + m.on_hold_quantity <= 0;
        const threshold  = m.threshold ?? 0;
        const on_hold = m.on_hold_quantity ?? 0;
        const orderQty = m.order_quantity ?? 0;
        const available  = m.available_total ?? (m.quantity + on_hold + orderQty);
        const isReserved = m.on_hold_quantity > 0;
        const badgeClass = outOfStock ? 'bg-secondary'
            : available > threshold ? 'bg-success' : 'bg-danger';
        const modalType = window.tablarData.isHolzLager
            ? 'openSheetModal'
            : (isReserved ? 'openReserveModal' : 'openMaterialModal');
        const badgeText  = outOfStock ? 'Kommt gleich' : m.quantity + ' ' + (m.unit ?? 'Stk.');
        const leftoverBadge = (window.tablarData.isHolzLager && m.leftover_sheet_count > 0)
            ? `<span class="badge bg-secondary ms-1"><i class="bi bi-scissors me-1"></i>${m.leftover_sheet_count} Reste</span>`
            : '';

        const shelfHint = m.shelf
            ? `<span class="text-muted small ms-2"><i class="bi bi-geo-alt me-1"></i>${m.shelf}</span>`
            : '';

        const imageTemplate = generateImageHtml(m.image, m.name);
        const orderTemplate = generateOrderHtml(m);

        const onHoldText = isReserved ? `<span class="badge bg-info text-dark ms-2"><i class="bi bi-clock-history me-1"></i>Reserviert: ${on_hold} ${m.unit ?? 'Stk.'}</span>` : '';
        const orderText = orderQty > 0
            ? `<span class="badge bg-warning text-dark ms-2"><i class="bi bi-truck me-1"></i>Bestellt: ${orderQty} ${m.unit ?? 'Stk.'}</span>`
            : '';

        if (outOfStock) {
            return `
            <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border bg-light text-muted"
                style="cursor: not-allowed;"
                onclick="Swal.fire('Nicht verfügbar', 'Bitte warten Sie auf Nachschub.', 'info')">
                <div class="d-flex align-items-center">
                    ${imageTemplate}
                    <div>
                        <span class="text-decoration-line-through fw-semibold">${m.name}</span>
                        ${shelfHint}
                        <div class="mt-1">${orderTemplate}</div>
                    </div>
                </div>
                <span class="text-end">
                    <span class="badge ${badgeClass} fs-6">${badgeText}</span>
                    ${leftoverBadge}
                </span>
            </div>`;
        }

        return `
        <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded border material-item"
                onclick="${modalType}(${m.id}, '${m.name}', ${m.quantity}, '${m.shelf ?? ''}', ${on_hold}, '${m.unit ?? 'Stk.'}')">
            <div class="d-flex align-items-center">
                ${imageTemplate}
                <div>
                    <span class="fw-semibold">${m.name} ${onHoldText}${orderText}</span>
                    ${shelfHint}
                    <div class="mt-1">${orderTemplate}</div>
                </div>
            </div>
            <span class="text-end">
                <span class="badge ${badgeClass} fs-6">${badgeText}</span>
                ${leftoverBadge}
            </span>
        </div>`;
    }).join('');
}

// ─── EXPOSE FUNCTIONS CALLED FROM HTML ATTRIBUTES ────────────────────────────
window.filterShelves       = filterShelves;
window.selectShelf         = selectShelf;
window.goBackToShelves     = goBackToShelves;
window.filterMaterials     = filterMaterials;
window.openMaterialModal   = openMaterialModal;
window.openReserveModal              = openReserveModal;
window.increaseReserve               = increaseReserve;
window.decreaseReserve               = decreaseReserve;
window.validateReserveInput          = validateReserveInput;
window.confirmReservationSettlement  = confirmReservationSettlement;
window.validateManualInput = validateManualInput;
window.increase            = increase;
window.decrease            = decrease;
window.confirmConsumption  = confirmConsumption;
window.confirmReturn       = confirmReturn;
window.triggerOrder        = triggerOrder;
window.switchMode          = switchMode;
window.filterByName        = filterByName;
window.openSheetModal   = openSheetModal;
window.backToSheetPick  = backToSheetPick;
window.performSheetCut  = performSheetCut;
window.runSheetSearch   = runSheetSearch;