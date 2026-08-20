<div id="dailyRecordsContainer{{ $index ?? '' }}">
    {{-- Table will be injected here via JS --}}
    <em>Lade tägliche Aufzeichnungen...</em>
</div>

@push('scripts')
<script>

    function getDayDetails(key, date, week, auftragsnummer, positionId, machineId) {
        const container = document.getElementById(`dayDetails_${key}`);

        if (dayDetailsCache[key]) {
            container.innerHTML = dayDetailsCache[key];
            new bootstrap.Collapse(container, { toggle: true });
            return;
        }

        fetch(`/admin/time/daily-records/details?` +
            `date=${date}&calendar_week=${week}` +
            `&auftragsnummer=${auftragsnummer}` +
            `&position_id=${positionId}` +
            `&machine_id=${machineId}`
        )
            .then(res => res.json())
            .then(data => {
                let html = `
                    <div class="collapse show">
                        <table class="table table-bordered table-sm mt-2">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Mitarbeiter</th>
                                    <th>Start</th>
                                    <th>Ende</th>
                                    <th>Maschinenstatus</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.entries.forEach(entry => {
                    html += `
                        <tr>
                            <td>${entry.user_name}</td>
                            <td>${entry.start_time}</td>
                            <td>${entry.end_time}</td>
                            <td>
                                <span class="badge bg-${entry.machine_status === 'running' ? 'success' : 'secondary'}">
                                    ${entry.machine_status}
                                </span>
                            </td>
                        </tr>
                    `;
                });

            html += '</tbody></table></div>';

            dayDetailsCache[key] = html;
            container.innerHTML = html;

            new bootstrap.Collapse(container, { toggle: true });
        });
    }

    function secondsToHMS(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
    }

    function getDailyRecords(index, week, auftragsnummer, positionId, machineId) {
        const container = document.getElementById(`dailyRecordsContainer${index}`);

        // ✅ Use cache if available
        if (dailyRecordsCache[index]) {
            container.innerHTML = dailyRecordsCache[index];
            return;
        }
        fetch(`/admin/time/daily-records?calendar_week=${week}&auftragsnummer=${auftragsnummer}&position_id=${positionId}&machine_id=${machineId}`, {method: 'GET'})
            .then(response => response.json())
            .then(data => {
                if (data.dailyRecords.length === 0) {
                    container.innerHTML = '<em>Keine täglichen Aufzeichnungen gefunden.</em>';
                    return;
                }

                let tableHtml = `
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Datum</th>
                                <th>Rustzeit</th>
                                <th>Mit Aufsicht</th>
                                <th>Gesamtzeit</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.dailyRecords.forEach(record => {
                    const key = record.daily_key;
                    const rustzeit = secondsToHMS(record.rustzeit_seconds);
                    const mitAufsicht = secondsToHMS(record.mit_aufsicht_seconds);
                    const gesamtzeit = secondsToHMS(parseInt(record.rustzeit_seconds) + parseInt(record.mit_aufsicht_seconds));

                    const rowClass = record.company === 'ZT' ? 'bg-success bg-opacity-10' : 'bg-info bg-opacity-10';

                    tableHtml += `
                        <tr>
                            <td class="${rowClass}">
                                <a href="javascript:void(0)"
                                onclick="getDayDetails(
                                    '${key}',
                                    '${record.record_date}',
                                    ${week},
                                    '${auftragsnummer}',
                                    ${positionId},
                                    ${machineId}
                                )">
                                    ${record.record_date}
                                </a>
                            </td>
                            <td class="${rowClass}">${rustzeit}</td>
                            <td class="${rowClass}">${mitAufsicht}</td>
                            <td class="${rowClass}"><strong>${gesamtzeit}</strong></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="p-0">
                                <div id="dayDetails_${key}" class="collapse ps-2"></div>
                            </td>
                        </tr>
                    `;
                });

                tableHtml += `</tbody></table>`;
                dailyRecordsCache[index] = tableHtml; // ✅ Cache the generated HTML
                container.innerHTML = tableHtml;
            })
            .catch(err => {
                container.innerHTML = `<em>Fehler beim Laden der täglichen Aufzeichnungen.</em>`;
                console.error(err);
            });
    }

    // Optional: Auto-load if index, week, etc. are passed as props
    @if(isset($autoLoad) && $autoLoad)
        document.addEventListener('DOMContentLoaded', function() {
            getDailyRecords('{{ $index }}', '{{ $week }}', '{{ $auftragsnummer }}', '{{ $positionId }}', '{{ $machineId }}');
        });
    @endif
</script>
@endpush