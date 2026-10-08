@extends('admin.layouts.index')

@section('content')
@php
    // Guarded with function_exists() — a bare `function` declaration in a Blade
    // file will fatal with "Cannot redeclare function" the moment this view
    // renders twice in one PHP process (e.g. under Octane, queue workers, or
    // simply loading the page twice in one test run). Logic is unchanged.
    if (! function_exists('secondsToIndustryMinutes')) {
        function secondsToIndustryMinutes($seconds) {
            $totalSeconds = (int) round($seconds);
            $hours = intdiv($totalSeconds, 3600);
            $remainderSeconds = $totalSeconds % 3600;

            $industryMinutes = (int) round(($remainderSeconds / 3600) * 100 / 25) * 25;

            if ($industryMinutes >= 100) {
                $industryMinutes = 0;
                $hours++;
            }

            return sprintf('%d,%02d', $hours, $industryMinutes);
        }
    }
@endphp
<div class="zt-compare container">
    <div class="card shadow-sm zt-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Wochenübersicht Maschinenlaufstunden</h5>
        </div>

        <div class="card-body">

            {{-- Week filter --}}
            <div class="mb-4 zt-week-slider" id="weekSlider">
                @foreach($weeks as $week)
                    <button
                        onclick="window.location.href='?week={{ $week['value'] }}'"
                        class="zt-week-btn {{ $selectedWeek == $week['value'] ? 'is-active' : '' }}"
                        data-week="{{ $week['value'] }}">
                        {{ $week['label'] }}
                    </button>
                @endforeach

                <button id="addWeekBtn" class="zt-week-btn zt-week-btn--ghost">+1</button>
            </div>

            @forelse($machineTables as $table)
                @php
                    $tableId = 'machine-table-'.$loop->index;
                    $weekLabel = collect($weeks)->firstWhere('value', $selectedWeek)['label'] ?? $selectedWeek;
                    $machineName = $table['machine']->name ?? 'Maschine';
                    $groupedRows = collect($table['rows'])
                        ->groupBy(function ($row) {
                            return implode('|', [
                                $row->machine_id ?? $row->machine?->id ?? 'null',
                                $row->project_id ?? $row->project?->id ?? 'null',
                                $row->position_id ?? $row->position?->id ?? 'null',
                                $row->user_id ?? 'null',
                            ]);
                        })
                        ->map(function ($group) {
                            $first = $group->first();
                            $dates = $group
                                ->pluck('date')
                                ->unique()
                                ->sort()
                                ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('d.m.Y'))
                                ->values();

                            return [
                                'dates' => $dates,
                                'project' => $first->project,
                                'position' => $first->position,
                                'user_name' => $first->user_name,
                                'is_fallback_attribution' => $group->contains(fn ($row) => (bool) $row->is_fallback_attribution),
                                'ruestzeit_seconds' => $group->sum('ruestzeit_seconds'),
                                'mit_aufsicht_seconds' => $group->sum('mit_aufsicht_seconds'),
                                'ohne_aufsicht_seconds' => $group->sum('ohne_aufsicht_seconds'),
                            ];
                        })
                        ->values();
                @endphp
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="zt-machine-title">
                        Maschinenlaufstunden für CNC-Fräse "{{ $machineName }}"
                        <small class="text-muted fw-normal">— {{ $weekLabel }}</small>
                    </h6>

                    <button
                        type="button"
                        class="zt-export-btn"
                        onclick="exportMachineTable('{{ $tableId }}', '{{ $machineName }}', '{{ $weekLabel }}')">
                        <i class="bi bi-file-earmark-excel"></i> Exportieren
                    </button>
                </div>

                <div class="table-responsive mb-5">
                    <table class="table zt-table zt-table--excel align-middle mb-0" id="{{ $tableId }}">
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
                            @foreach($groupedRows as $groupedRow)
                                <tr>
                                    <td>{{ $groupedRow['dates']->implode(', ') }}</td>
                                    <td>{{ $groupedRow['project']->project_name ?? '—' }}</td>
                                    <td>{{ $groupedRow['project']->auftragsnummer_zf ?? '—' }}</td>
                                    <td>{{ $groupedRow['project']->auftragsnummer_zt ?? '—' }}</td>
                                    <td>{{ $groupedRow['position']->name ?? '—' }}</td>
                                    <td>{{ secondsToIndustryMinutes($groupedRow['ruestzeit_seconds']) }}</td>
                                    <td>{{ secondsToIndustryMinutes($groupedRow['mit_aufsicht_seconds']) }}</td>
                                    <td>{{ secondsToIndustryMinutes($groupedRow['ohne_aufsicht_seconds']) }}</td>
                                    <td>
                                        @if($groupedRow['user_name'])
                                            {{ $groupedRow['user_name'] }}
                                            @if($groupedRow['is_fallback_attribution'])
                                                <i class="bi bi-info-circle text-muted"
                                                    title="Kein Bediener an diesem Tag protokolliert — automatisch dem letzten Bediener dieses Auftrags zugeordnet."></i>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="zt-totals-row">
                                <td colspan="5">Gesamt</td>
                                <td>{{ secondsToIndustryMinutes($table['totals']->ruestzeit_seconds) }}</td>
                                <td>{{ secondsToIndustryMinutes($table['totals']->mit_aufsicht_seconds) }}</td>
                                <td>{{ secondsToIndustryMinutes($table['totals']->ohne_aufsicht_seconds) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @empty
                <p class="zt-empty">Keine Maschinenlaufstunden für diese Woche.</p>
            @endforelse

        </div>
    </div>
</div>

<style>
    .zt-compare {
        --zt-bg: #F5F6F8;
        --zt-ink: #1B1F24;
        --zt-muted: #667085;
        --zt-line: #DFE3E8;
        color: var(--zt-ink);
        font-variant-numeric: tabular-nums;
    }

    .zt-card { border: 1px solid var(--zt-line); border-radius: 10px; overflow: hidden; }
    .zt-card > .card-header { background: var(--zt-ink); color: #fff; border-bottom: none; }
    .zt-card > .card-body { background: var(--zt-bg); }

    .zt-week-slider { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .25rem; }
    .zt-week-btn {
        flex: none; min-width: 96px; height: 44px; padding: 0 .75rem;
        border: 1px solid var(--zt-line); border-radius: 8px; background: #fff;
        font-size: .82rem; font-weight: 500; color: var(--zt-muted);
        transition: border-color .15s, color .15s;
    }
    .zt-week-btn:hover { border-color: var(--zt-ink); color: var(--zt-ink); }
    .zt-week-btn.is-active { background: var(--zt-ink); border-color: var(--zt-ink); color: #fff; }
    .zt-week-btn--ghost { border-style: dashed; }

    .zt-machine-title { font-size: .95rem; font-weight: 600; margin-bottom: .75rem; }

    .zt-export-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid var(--zt-line); border-radius: 8px; background: #fff;
        color: var(--zt-ink); font-size: .8rem; font-weight: 500;
        padding: .4rem .75rem; margin-bottom: .75rem;
        transition: border-color .15s, background .15s;
    }
    .zt-export-btn:hover { border-color: var(--zt-ink); background: #FAFBFC; }

    .zt-table--excel { background: #fff; border: 1px solid var(--zt-line); border-radius: 8px; overflow: hidden; }
    .zt-table--excel thead th {
        font-size: .72rem; font-weight: 600; color: var(--zt-muted);
        border-bottom: 1px solid var(--zt-line); padding: .6rem .7rem; background: #FAFBFC; white-space: nowrap;
    }
    .zt-table--excel tbody td { padding: .55rem .7rem; border-bottom: 1px solid var(--zt-line); font-size: .84rem; white-space: nowrap; }
    .zt-totals-row td { font-weight: 600; background: #FAFBFC; border-top: 2px solid var(--zt-line); }
    .zt-empty { color: var(--zt-muted); font-size: .85rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script>
    document.getElementById('addWeekBtn').addEventListener('click', function () {
        const slider = document.getElementById('weekSlider');
        const buttons = slider.querySelectorAll('.zt-week-btn:not(.zt-week-btn--ghost)');
        const lastButton = buttons[buttons.length - 1];

        let lastWeekValue = lastButton.getAttribute('data-week');
        let year = parseInt(lastWeekValue.slice(0, 4));
        let week = parseInt(lastWeekValue.slice(4, 6));

        week -= 1;
        if (week < 1) {
            week = 52;
            year -= 1;
        }

        let weekStr = week.toString().padStart(2, '0');
        let newWeekValue = year.toString() + weekStr;
        let newWeekLabel = 'KW ' + weekStr + ' / ' + year;

        const newButton = document.createElement('button');
        newButton.setAttribute('onclick', `window.location.href='?week=${newWeekValue}'`);
        newButton.setAttribute('data-week', newWeekValue);
        newButton.className = 'zt-week-btn';
        newButton.innerText = newWeekLabel;

        slider.insertBefore(newButton, this);
    });

    async function exportMachineTable(tableId, machineName, weekLabel) {
        const table = document.getElementById(tableId);
        if (!table) return;

        // 1. Neues Workbook & Sheet erstellen
        const workbook = new ExcelJS.Workbook();
        const sheetName = machineName.substring(0, 31);
        const worksheet = workbook.addWorksheet(sheetName);

        // Page Setup (A4 Querformat für Druck)
        worksheet.pageSetup.orientation = 'landscape';

        // 2. Spaltenbreiten definieren (exakt passend zur Vorlage)
        worksheet.columns = [
            { width: 14 }, // A: Datum
            { width: 24 }, // B: Auftrags-Nr. ZF
            { width: 24 }, // C: Auftrags-Nr. ZIMATEC
            { width: 14 }, // D: Pos.
            { width: 18 }, // E: Rüstzeit
            { width: 18 }, // F: mit Aufsicht
            { width: 18 }, // G: ohne Aufsicht
            { width: 20 }  // H: Bediener
        ];

        // Style-Helfer
        const borderThin = {
            top: { style: 'thin' },
            left: { style: 'thin' },
            bottom: { style: 'thin' },
            right: { style: 'thin' }
        };

        // --- ROW 1: Titelzeile & Woche ---
        worksheet.mergeCells('A1:D1');
        const titleCell = worksheet.getCell('A1');
        titleCell.value = `Maschinenlaufstunden für CNC-Fräse "${machineName}"`;
        titleCell.font = { name: 'Arial', size: 14, bold: true };
        titleCell.alignment = { vertical: 'middle', horizontal: 'left' };

        worksheet.mergeCells('F1:H1');
        const weekCell = worksheet.getCell('F1');
        weekCell.value = `Woche: ${weekLabel}`;
        weekCell.font = { name: 'Arial', size: 12, bold: true, underline: true };
        weekCell.alignment = { vertical: 'middle', horizontal: 'right' };

        // --- ROW 2: Sub-Header Laufstunden ---
        worksheet.mergeCells('E3:G3');
        const headerHours = worksheet.getCell('E3');
        headerHours.value = 'Laufstunden am Tag:';
        headerHours.font = { name: 'Arial', size: 12 };
        headerHours.alignment = { vertical: 'middle', horizontal: 'center' };

        // --- ROW 3: Tabellen-Kopfzeile ---
        const headers = [
            'Datum:', 'Auftrags-Nr. ZF:', 'Auftrags-Nr. ZIMATEC:', 
            'Pos.', 'Rüstzeit', 'mit Aufsicht', 'ohne Aufsicht', 'Bediener:'
        ];
        
        const headerRow = worksheet.getRow(4);
        headerRow.height = 30;

        headers.forEach((text, colIdx) => {
            const cell = headerRow.getCell(colIdx + 1);
            cell.value = text;
            cell.font = { name: 'Arial', size: 10, bold: true };
            cell.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFE0E0E0' } // Hellgrauer Hintergrund
            };
            cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
            cell.border = borderThin;
        });

        // --- ROWS: Daten aus der HTML-Tabelle extrahieren ---
        const rows = table.querySelectorAll('tbody tr:not(.zt-admin-row)');
        let currentRowIdx = 5;

        rows.forEach((tr) => {
            const cells = tr.querySelectorAll('td');
            if (cells.length < 9) return; // Sicherheitsscheck

            const row = worksheet.getRow(currentRowIdx);
            row.height = 20;

            // Werte & Ausrichtungen setzen
            const values = [
                cells[0].innerText.trim(), // Datum
                cells[2].innerText.trim(), // ZF
                cells[3].innerText.trim(), // ZT
                cells[4].innerText.trim(), // Pos
                cells[5].innerText.trim(), // Rüstzeit
                cells[6].innerText.trim(), // mit Aufsicht
                cells[7].innerText.trim(), // ohne Aufsicht
                cells[8].innerText.trim()  // Bediener
            ];

            values.forEach((val, colIdx) => {
                const cell = row.getCell(colIdx + 1);
                cell.value = val;
                cell.font = { name: 'Arial', size: 10 };
                cell.border = borderThin;

                // Zentrieren für Datum, Auftragsnr, Pos
                if ([0, 1, 2, 3].includes(colIdx)) {
                    cell.alignment = { vertical: 'middle', horizontal: 'center' };
                } 
                // Rechtsbündig für Zeiten
                else if ([4, 5, 6].includes(colIdx)) {
                    cell.alignment = { vertical: 'middle', horizontal: 'right' };
                } 
                // Linksbündig für Text
                else {
                    cell.alignment = { vertical: 'middle', horizontal: 'left' };
                }
            });

            currentRowIdx++;
        });

        // --- ROW: Verwaltungshinweis ---
        currentRowIdx++;
        worksheet.mergeCells(`A${currentRowIdx}:H${currentRowIdx}`);
        const adminCell = worksheet.getCell(`A${currentRowIdx}`);
        adminCell.value = 'Ab hier wird nur von der Verwaltung ausgefüllt wenn Zeilen benötigt werden diese darüber einfügen';
        adminCell.font = { name: 'Arial', size: 9, italic: true, color: { argb: 'FF555555' } };
        adminCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFDF2D6' } }; // Sanftes Gelb
        adminCell.alignment = { vertical: 'middle', horizontal: 'center' };
        adminCell.border = borderThin;
        currentRowIdx++;

        // --- ROW: Summenzeile ---
        const tfootRow = table.querySelector('tfoot tr');
        if (tfootRow) {
            const footCells = tfootRow.querySelectorAll('td');
            const sumRow = worksheet.getRow(currentRowIdx);
            sumRow.height = 22;

            worksheet.mergeCells(`A${currentRowIdx}:D${currentRowIdx}`);
            const sumLabelCell = sumRow.getCell(1);
            sumLabelCell.value = 'Gesamt:';
            sumLabelCell.font = { name: 'Arial', size: 10, bold: true };
            sumLabelCell.alignment = { vertical: 'middle', horizontal: 'right' };

            // Werte für Rüstzeit, mit Aufsicht, ohne Aufsicht
            [4, 5, 6].forEach((colIdxInTable, index) => {
                const excelCol = 5 + index; // Spalten E, F, G
                const cell = sumRow.getCell(excelCol);
                cell.value = footCells[index + 1]?.innerText.trim() || '0';
                cell.font = { name: 'Arial', size: 10, bold: true };
                cell.alignment = { vertical: 'middle', horizontal: 'right' };
            });

            // Borders auf die ganze Summenzeile legen
            for (let c = 1; c <= 8; c++) {
                sumRow.getCell(c).border = borderThin;
            }
        }

        // --- Datei erzeugen und Download anstoßen ---
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        
        const safeName = `KW_${weekLabel}_Maschinenlaufstd_ZiMaTec_${machineName}`.replace(/[^a-zA-Z0-9_\-]/g, '_');
        link.download = `${safeName}.xlsx`;
        link.click();
        URL.revokeObjectURL(link.href);
    }
</script>
@endsection