<?php

namespace App\Services\Time;

use App\Models\Process;
use App\Models\TimeRecord;
use App\Support\DurationCalculator;
use App\Support\WeekRangeResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WeeklyComparisonService
{
    public function __construct(
        private readonly WeekRangeResolver $weekRangeResolver,
        private readonly DurationCalculator $duration,
    ) {
    }

    public function build(Request $request): array
    {
        $range = $this->weekRangeResolver->resolve($request->get('week'));
        $fromDate = $range->fromDate;
        $toDate = $range->toDate;

        // 1) Every user session (TimeRecord) this week, with its status logs and
        //    any processes that are directly FK-linked to it.
        $timeRecords = TimeRecord::query()
            ->with([
                'user:id,name,company',
                'project:id,project_name,auftragsnummer_zf,auftragsnummer_zt',
                'position:id,name',
                'machine:id,name',
                'logs' => fn ($q) => $q->whereNotNull('end_time')->with('status:id,name'),
                'processes' => fn ($q) => $q->whereNotNull('end_time')->with('pauses'),
            ])
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        // 2) Processes read straight from the machine log (time_record_id null) this week.
        $unlinkedProcesses = Process::query()
            ->with(['pauses', 'project:id,project_name,auftragsnummer_zf,auftragsnummer_zt', 'position:id,name', 'machine:id,name'])
            ->whereNull('time_record_id')
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        // 3) Try to attach each unlinked process to an overlapping session on the same job;
        //    whatever's left over is truly unattended.
        $attached = [];        // recordId => list of ['process' => Process, 'seconds' => int]
        $unattendedByKey = []; // key => list of ['process' => Process, 'seconds' => int]

        foreach ($unlinkedProcesses->groupBy(fn ($p) => "{$p->project_id}-{$p->position_id}-{$p->machine_id}") as $key => $processes) {
            $slices = [];

            foreach ($timeRecords as $record) {
                if ("{$record->project_id}-{$record->position_id}-{$record->machine_id}" !== $key) {
                    continue;
                }
                foreach ($record->logs as $log) {
                    if (! in_array($log->status->name ?? null, ['Rustzeit', 'Mit Aufsicht'], true)) {
                        continue;
                    }
                    $slices[] = [Carbon::parse($log->start_time), Carbon::parse($log->end_time), $record->id];
                }
            }

            foreach ($processes as $process) {
                $alloc = $this->duration->allocateProcess($process, $slices);

                foreach ($alloc['owned'] as $recordId => $sec) {
                    if ($sec > 0) {
                        $attached[$recordId][] = ['process' => $process, 'seconds' => $sec];
                    }
                }
                if ($alloc['free_seconds'] > 0) {
                    $unattendedByKey[$key][] = ['process' => $process, 'seconds' => $alloc['free_seconds']];
                }
            }
        }

        // 4) Session-level rows.
        $sessions = $timeRecords->map(function ($record) use ($attached) {
            $statusSeconds = $record->logs
                ->groupBy(fn ($log) => $log->status->name ?? 'Unbekannt')
                ->map(fn ($logs) => $logs->sum(fn ($l) => $this->duration->seconds($l->start_time, $l->end_time)));

            $linked = $record->processes;                 // manually linked, counted in full
            $extra  = collect($attached[$record->id] ?? []);

            $machineSeconds = $linked->sum(fn ($p) => $this->duration->activeSeconds($p))
                + $extra->sum('seconds');

            return [
                'project_id' => $record->project_id,
                'position_id' => $record->position_id,
                'machine_id' => $record->machine_id,
                'record' => (object) [
                    'user' => (object) ['name' => $record->user->name ?? null],
                    'project' => (object) [
                        'project_name' => $record->project->project_name ?? null,
                        'auftragsnummer' => $this->auftragsnummer($record->project, $record->user->company ?? null),
                    ],
                    'Position' => (object) ['name' => $record->position->name ?? null],
                    'machine' => (object) ['name' => $record->machine->name ?? null],
                ],
                'total_user_time' => $this->duration->hms($statusSeconds->sum()),
                'status_seconds' => [
                    'ruestzeit' => $statusSeconds->get('Rustzeit', 0),
                    'mit_aufsicht' => $statusSeconds->get('Mit Aufsicht', 0),
                    'ohne_aufsicht' => $statusSeconds->get('Ohne Aufsicht', 0),
                ],
                'total_machine_time' => $this->duration->hms($machineSeconds),
                'process_count' => $linked->count() + $extra->count(),
                'processes' => $linked->map(fn ($p) => [
                    'process_name' => $p->name,
                    'start_time' => $p->start_time,
                    'end_time' => $p->end_time,
                    'duration_seconds' => $this->duration->activeSeconds($p),
                    'source' => 'manuell',
                ])->concat($extra->map(fn ($x) => [
                    'process_name' => $x['process']->name,
                    'start_time' => $x['process']->start_time,
                    'end_time' => $x['process']->end_time,
                    'duration_seconds' => $x['seconds'],
                    'source' => 'überlappend (anteilig)',
                ]))->values()->all(),
                'logs' => $record->logs->map(fn ($l) => [
                    'start_time' => $l->start_time,
                    'end_time' => $l->end_time,
                    'status' => $l->status->name ?? null,
                ])->values()->all(),
            ];
        });

        // 5) Fully unattended machine-only rows (one per project/position/machine).
        $unattendedRows = collect($unattendedByKey)->map(function ($items) {
            $first = $items[0]['process'];
            $machineSeconds = collect($items)->sum('seconds');

            return [
                'project_id' => $first->project_id,
                'position_id' => $first->position_id,
                'machine_id' => $first->machine_id,
                'record' => (object) [
                    'user' => (object) ['name' => null],
                    'project' => (object) [
                        'project_name' => $first->project->project_name ?? null,
                        // No user attached, so no company to pick auftragsnummer_zf vs _zt from.
                        // Defaulting to _zt here — tell me if unattended runs should use _zf instead.
                        'auftragsnummer' => $this->auftragsnummer($first->project, null),
                    ],
                    'Position' => (object) ['name' => $first->position->name ?? null],
                    'machine' => (object) ['name' => $first->machine->name ?? null],
                ],
                'total_user_time' => $this->duration->hms(0),
                'status_seconds' => ['ruestzeit' => 0, 'mit_aufsicht' => 0, 'ohne_aufsicht' => 0],
                'total_machine_time' => $this->duration->hms($machineSeconds),
                'process_count' => count($items),
                'processes' => collect($items)->map(fn ($item) => [
                    'process_name' => $item['process']->name,
                    'start_time' => $item['process']->start_time,
                    'end_time' => $item['process']->end_time,
                    'duration_seconds' => $item['seconds'],
                    'source' => 'unbeaufsichtigt',
                ])->values()->all(),
                'logs' => [],
            ];
        })->values();

        $comparison = $sessions->values()->concat($unattendedRows);

        // 6) Weekly aggregate per project + position + machine.
        $aggregate = $comparison
            ->groupBy(fn ($row) => "{$row['project_id']}-{$row['position_id']}-{$row['machine_id']}")
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'project' => $first['record']->project,
                    'position' => $first['record']->Position,
                    'machine' => $first['record']->machine,
                    'ruestzeit' => $this->duration->hms($rows->sum(fn ($r) => $r['status_seconds']['ruestzeit'])),
                    'mit_aufsicht' => $this->duration->hms($rows->sum(fn ($r) => $r['status_seconds']['mit_aufsicht'])),
                    'ohne_aufsicht' => $this->duration->hms($rows->sum(fn ($r) => $r['status_seconds']['ohne_aufsicht'])),
                    'total_user_time' => $this->duration->hms($rows->sum(fn ($r) => $this->duration->hmsToSeconds($r['total_user_time']))),
                    'total_machine_time' => $this->duration->hms($rows->sum(fn ($r) => $this->duration->hmsToSeconds($r['total_machine_time']))),
                    'process_count' => $rows->sum('process_count'),
                    'session_count' => $rows->count(),
                ];
            })
            ->values();

        return [
            'comparison' => $comparison,
            'aggregate' => $aggregate,
            'weeks' => $range->weeks,
            'selectedWeek' => $range->selectedWeek,
        ];
    }

    private function auftragsnummer($project, ?string $company)
    {
        if (! $project) {
            return null;
        }

        return $project->auftragsnummer_zf.' / '.$project->auftragsnummer_zt;
    }
}