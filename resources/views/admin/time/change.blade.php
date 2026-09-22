@extends('admin.layouts.index')

@section('content')
<div class="zt-compare container">

    {{-- ======================
        Pending Requests
    ======================= --}}
    <div class="card shadow-sm zt-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Ausstehende Nachtragsanträge</h5>
            <span class="zt-count-pill">{{ $pendingRequests->count() }}</span>
        </div>

        <div class="card-body">
            <div class="zt-request-list">
                @forelse($pendingRequests as $record)
                    @include('admin.time.partials.request-card', ['record' => $record, 'pending' => true])
                @empty
                    <div class="zt-empty text-center py-4">Es wurden keine ausstehenden Anfragen gefunden.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ======================
        Processed Requests
    ======================= --}}
    <div class="card shadow-sm zt-card">
        <div class="card-header zt-card-header--muted">
            <h5 class="mb-0">Zuvor bearbeitete Anfragen</h5>
        </div>

        <div class="card-body">
            <div class="zt-request-list">
                @forelse($processedRequests as $record)
                    @include('admin.time.partials.request-card', ['record' => $record, 'pending' => false])
                @empty
                    <div class="zt-empty text-center py-4">Bisher wurden noch keine Anfragen bearbeitet.</div>
                @endforelse
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
        --zt-warn: #B5750A;
        --zt-warn-bg: #FCF1E1;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--zt-ink); color: #fff; border-bottom: none; }
    .zt-card > .card-body { background: var(--zt-bg); }
    .zt-card-header--muted { background: var(--zt-muted); color: #fff; border-bottom: none; }

    .zt-count-pill {
        background: rgba(255,255,255,.15); color: #fff; font-size: .75rem; font-weight: 600;
        padding: .15rem .6rem; border-radius: 20px;
    }

    .zt-empty { color: var(--zt-muted); font-size: .85rem; }

    /* --- Request list/cards --- */
    .zt-request-list { display: flex; flex-direction: column; gap: .75rem; }

    .zt-request-card { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }

    .zt-request-header {
        display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
        padding: .85rem 1rem; cursor: pointer; user-select: none;
    }
    .zt-request-header:hover { background: #FAFBFC; }

    .zt-request-main { flex: 1 1 220px; min-width: 0; }
    .zt-request-title { font-weight: 700; font-size: .9rem; display: block; }
    .zt-request-sub { font-size: .78rem; color: var(--zt-muted); display: block; }

    .zt-request-meta { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

    .zt-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .74rem; font-weight: 600; color: var(--zt-muted);
        background: #F2F3F5; border-radius: 20px; padding: .25rem .65rem; white-space: nowrap;
    }
    .zt-chip--changed { color: var(--zt-warn); background: var(--zt-warn-bg); }
    .zt-chip i { font-size: .7rem; }

    .zt-badge { display: inline-block; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
    .zt-badge--success { background: #E4F5EC; color: #1E7A46; }
    .zt-badge--danger { background: #FBEAE9; color: #B3261E; }
    .zt-badge--pending { background: #EEF0F2; color: var(--zt-muted); }

    .zt-chevron { transition: transform .15s ease; color: var(--zt-muted); }
    .zt-request-card.is-open .zt-chevron { transform: rotate(180deg); }

    .zt-request-body { display: none; border-top: 1px solid var(--zt-line); padding: 1rem; background: #FAFBFC; }
    .zt-request-card.is-open .zt-request-body { display: block; }

    .zt-reason-line { font-size: .82rem; margin-bottom: .85rem; }
    .zt-reason-line strong { color: var(--zt-ink); }

    .zt-diff-title { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--zt-muted); margin: .75rem 0 .5rem; }

    .zt-subtable { border: 1px solid var(--zt-line); border-radius: 6px; overflow: hidden; font-size: .78rem; width: 100%; }
    .zt-subtable thead th { text-align: center; font-weight: 600; padding: .4rem; }
    .zt-subtable tbody td { padding: .4rem; text-align: center; border-top: 1px solid var(--zt-line); }
    .zt-subtable--danger thead { background: #FBEAE9; color: #B3261E; }
    .zt-subtable--danger tbody tr { background: #FEF5F4; }
    .zt-subtable--success thead { background: #E4F5EC; color: #1E7A46; }
    .zt-subtable--success tbody tr { background: #F4FBF7; }

    .zt-request-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; flex-wrap: wrap; gap: .5rem; }
    .zt-review-meta { font-size: .78rem; color: var(--zt-muted); }

    .zt-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        border-radius: 8px; font-size: .8rem; font-weight: 500;
        padding: .35rem .75rem; border: 1px solid var(--zt-line); background: #fff;
    }
    .zt-btn--success { background: #1E7A46; border-color: #1E7A46; color: #fff; }
    .zt-btn--danger { background: #B3261E; border-color: #B3261E; color: #fff; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.zt-request-header').forEach(header => {
            header.addEventListener('click', function () {
                this.closest('.zt-request-card').classList.toggle('is-open');
            });
        });
    });
</script>
@endsection