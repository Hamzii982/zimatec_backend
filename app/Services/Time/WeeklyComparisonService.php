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
        $unattendedByKey = [];

        foreach ($unlinkedProcesses->groupBy(fn ($p) => "{$p->project_id}-{$p->position_id}-{$p->machine_id}") as $key => $processes) {
            $candidates = $timeRecords->filter(
                fn ($r) => "{$r->project_id}-{$r->position_id}-{$r->machine_id}" === $key
            );

            foreach ($processes as $process) {
                $match = $candidates->first(function ($record) use ($process) {
                    $recordEnd = $record->end_time ?? Carbon::now();

                    return $process->start_time < $recordEnd && $process->end_time > $record->start_time;
                });

                if ($match) {
                    $match->setRelation('processes', $match->processes->push($process));
                } else {
                    $unattendedByKey[$key][] = $process;
                }
            }
        }

        // 4) Session-level rows.
        $sessions = $timeRecords->map(function ($record) {
            $statusSeconds = $record->logs
                ->groupBy(fn ($log) => $log->status->name ?? 'Unbekannt')
                ->map(fn ($logs) => $logs->sum(fn ($l) => $this->duration->seconds($l->start_time, $l->end_time)));

            $machineSeconds = $record->processes->sum(
                fn ($p) => $this->duration->activeSeconds($p)
            );

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
                'process_count' => $record->processes->count(),
                'processes' => $record->processes->map(fn ($p) => [
                    'process_name' => $p->name,
                    'start_time' => $p->start_time,
                    'end_time' => $p->end_time,
                    'duration_seconds' => $this->duration->activeSeconds($p),
                    'source' => $p->time_record_id !== null ? 'manuell' : 'überlappend erkannt',
                ])->values()->all(),
                'logs' => $record->logs->map(fn ($l) => [
                    'status' => $l->status->name ?? null,
                    'start_time' => $l->start_time,
                    'end_time' => $l->end_time,
                ])->values()->all(),
            ];
        });

        // 5) Fully unattended machine-only rows (one per project/position/machine).
        $unattendedRows = collect($unattendedByKey)->map(function ($processes) {
            $first = $processes[0];
            $machineSeconds = collect($processes)->sum(
                fn ($p) => $this->duration->activeSeconds($p)
            );

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
                'process_count' => count($processes),
                'processes' => collect($processes)->map(fn ($p) => [
                    'process_name' => $p->name,
                    'start_time' => $p->start_time,
                    'end_time' => $p->end_time,
                    'duration_seconds' => $this->duration->activeSeconds($p),
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