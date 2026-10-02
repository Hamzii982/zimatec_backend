@php
    if (! function_exists('secondsToIndustryMinutes')) {
        function secondsToIndustryMinutes($seconds) {
            $real = (int) round($seconds / 60);
            $ind  = (int) round($seconds / 60 * 5 / 3);

            return sprintf('%02d:%02d (%02d:%02d)', intdiv($real, 60), $real % 60, intdiv($ind, 60), $ind % 60);
        }
    }
@endphp

<div class="zt-lw-meta">{{ $weekLabel }} <span>· {{ $rangeLabel }}</span></div>

@forelse($machineTables as $table)
    <h6 class="zt-lw-machine">{{ $table['machine']->name ?? 'Maschine' }}</h6>

    <div class="table-responsive mb-4">
        <table class="table zt-lw-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Projekt</th>
                    <th>Auftrags-Nr. ZF</th>
                    <th>Auftrags-Nr. ZIMATEC</th>
                    <th>Pos.</th>
                    <th>Rüstzeit</th>
                    <th>mit Aufsicht</th>
                    <th>ohne Aufsicht</th>
                    <th>Bediener</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table['rows'] as $row)
                    <tr>
                        <td>
                            {{ \Carbon\Carbon::parse($row->date)->format('d.m.Y') }}
                            @if($row->is_fallback_attribution)
                                <i class="bi bi-info-circle text-muted"
                                    title="Keine Zeitaufzeichnung an diesem Tag — automatisch dem letzten Bediener dieses Auftrags zugeordnet."></i>
                            @endif
                        </td>
                        <td>{{ $row->project->project_name ?? '—' }}</td>
                        <td>{{ $row->project->auftragsnummer_zf ?? '—' }}</td>
                        <td>{{ $row->project->auftragsnummer_zt ?? '—' }}</td>
                        <td>{{ $row->position->name ?? '—' }}</td>
                        <td>{{ secondsToIndustryMinutes($row->ruestzeit_seconds) }}</td>
                        <td>{{ secondsToIndustryMinutes($row->mit_aufsicht_seconds) }}</td>
                        <td>{{ secondsToIndustryMinutes($row->ohne_aufsicht_seconds) }}</td>
                        <td>{{ $row->user_name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Gesamt</td>
                    <td>{{ secondsToIndustryMinutes($table['totals']->ruestzeit_seconds) }}</td>
                    <td>{{ secondsToIndustryMinutes($table['totals']->mit_aufsicht_seconds) }}</td>
                    <td>{{ secondsToIndustryMinutes($table['totals']->ohne_aufsicht_seconds) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@empty
    <p class="zt-lw-empty">Keine Maschinenlaufstunden für letzte Woche.</p>
@endforelse