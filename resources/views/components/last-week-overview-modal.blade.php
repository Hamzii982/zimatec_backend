<div class="modal fade zt-lw" id="lastWeekModal" tabindex="-1"
     aria-labelledby="lastWeekModalLabel" aria-hidden="true"
     data-url="{{ route('time-records.overview.last-week') }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content zt-lw-content">
            <div class="modal-header zt-lw-header">
                <h5 class="modal-title" id="lastWeekModalLabel">
                    <i class="bi bi-calendar-week me-2"></i>Letzte Woche
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body zt-lw-body" data-lw-body></div>
        </div>
    </div>
</div>

<style>
    .zt-lw {
        --brand-blue: #002752;
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }
    .zt-lw-content { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-lw-header { background: var(--brand-blue); color: #fff; border-bottom: none; }
    .zt-lw-body { background: var(--zt-bg); }
    .zt-lw-meta { font-size: .85rem; font-weight: 600; margin-bottom: 1rem; }
    .zt-lw-meta span { font-weight: 400; color: var(--zt-muted); }
    .zt-lw-machine { font-size: .95rem; font-weight: 600; margin-bottom: .6rem; }
    .zt-lw-table { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-lw-table thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-lw-table tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; white-space: nowrap; }
    .zt-lw-table tfoot td { font-weight: 600; background: #FAFBFC; border-top: 2px solid var(--zt-line); padding: .55rem .7rem; font-size: .84rem; }
    .zt-lw-empty { color: var(--zt-muted); font-size: .85rem; margin: 0; }
</style>

<script>
    (function () {
        const modalEl = document.getElementById('lastWeekModal');
        const body = modalEl.querySelector('[data-lw-body]');
        const spinner = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
        let loaded = false;

        body.innerHTML = spinner;

        modalEl.addEventListener('show.bs.modal', async function () {
            if (loaded) return;
            body.innerHTML = spinner;
            try {
                const res = await fetch(modalEl.dataset.url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                });
                if (!res.ok) throw new Error(res.status);
                body.innerHTML = await res.text();
                loaded = true;
            } catch (e) {
                body.innerHTML = '<p class="zt-lw-empty">Fehler beim Laden. Bitte erneut öffnen.</p>';
            }
        });
    })();
</script>