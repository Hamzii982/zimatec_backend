<?php

namespace App\Services\Time;

use App\Models\Process;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use App\Support\DurationCalculator;
use App\Support\WeekRangeResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Builds the weekly, per-machine attribution overview.
 *
 * Row logic:
 *  - Rustzeit / Mit Aufsicht: summed straight from time_logs, grouped by
 *    (date, project, position, machine, operator).
 *  - Ohne Aufsicht: for every Process this week, check whether it overlaps
 *    ANY Rustzeit/Mit Aufsicht time_log on the same project+position+machine.
 *    If it overlaps at all, it's already covered by that supervised/setup
 *    window and is NOT counted again here. If it does not overlap, its full
 *    (pause-adjusted) duration is "ohne Aufsicht" and needs an operator:
 *      1. If an operator has a TimeRecord for that same job on that same day
 *         (even one with no matching status log), attribute it to them —
 *         if more than one operator worked that job that day, the one whose
 *         session is closest in time to the process wins.
 *      2. If literally nobody worked that job that day, attribute it to
 *         whoever most recently worked that project+position+machine
 *         (before or after this date, whichever is closer in time). These
 *         fallback rows are flagged 'is_fallback_attribution' so the view
 *         can mark them distinctly; management should not read that name
 *         as "present that day."
 *      3. If that job has literally no TimeRecord history at all, the
 *         operator is left null ("—" in the view) — this should be rare/never
 *         in practice but is handled rather than silently guessing a name.
 *
 * One table per machine; machines with no hours at all this week are
 * skipped entirely.
 */
class WeeklyOverviewService
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

        // 1) Rustzeit / Mit Aufsicht logs this week.
        $statusLogs = TimeLog::query()
            ->with([
                'status:id,name',
                'record.user:id,name',
                'record.project:id,project_name,auftragsnummer_zf,auftragsnummer_zt',
                'record.position:id,name',
                'record.machine:id,name',
            ])
            ->whereHas('status', fn ($q) => $q->whereIn('name', ['Rustzeit', 'Mit Aufsicht']))
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        // 2) Every process this week, on any job.
        $processes = Process::query()
            ->with([
                'pauses',
                'project:id,project_name,auftragsnummer_zf,auftragsnummer_zt',
                'position:id,name',
                'machine:id,name',
            ])
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        // 3) Every TimeRecord this week (any status) — used only to know who was
        //    on which job on which day, for attributing leftover process time.
        $timeRecords = TimeRecord::query()
            ->with('user:id,name')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        $jobKey = fn ($projectId, $positionId, $machineId) => "{$projectId}|{$positionId}|{$machineId}";

        // --- Rustzeit / Mit Aufsicht rows -------------------------------------------------
        $rows = collect();

        foreach (
            $statusLogs->groupBy(function ($log) use ($jobKey) {
                $r = $log->record;

                return Carbon::parse($log->start_time)->toDateString().'|'.$jobKey($r->project_id, $r->position_id, $r->machine_id).'|'.$r->user_id;
            }) as $group
        ) {
            $first = $group->first();
            $record = $first->record;

            $rows->push((object) [
                'date' => Carbon::parse($first->start_time)->toDateString(),
                'project_id' => $record->project_id,
                'position_id' => $record->position_id,
                'machine_id' => $record->machine_id,
                'user_id' => $record->user_id,
                'user_name' => $record->user->name ?? null,
                'project' => $record->project,
                'position' => $record->position,
                'machine' => $record->machine,
                'ruestzeit_seconds' => $group->filter(fn ($l) => ($l->status->name ?? null) === 'Rustzeit')
                    ->sum(fn ($l) => $this->duration->seconds($l->start_time, $l->end_time)),
                'mit_aufsicht_seconds' => $group->filter(fn ($l) => ($l->status->name ?? null) === 'Mit Aufsicht')
                    ->sum(fn ($l) => $this->duration->seconds($l->start_time, $l->end_time)),
                'ohne_aufsicht_seconds' => 0,
                'is_fallback_attribution' => false,
            ]);
        }

        // --- Ohne Aufsicht: leftover process time ------------------------------------------
        $statusLogsByJob = $statusLogs->groupBy(
            fn ($l) => $jobKey($l->record->project_id, $l->record->position_id, $l->record->machine_id)
        );

        $recordsByDateJob = $timeRecords->groupBy(
            fn ($r) => Carbon::parse($r->start_time)->toDateString().'|'.$jobKey($r->project_id, $r->position_id, $r->machine_id)
        );

        $historyCache = [];
        $leftoverRows = [];

        foreach ($processes as $process) {
            $key = $jobKey($process->project_id, $process->position_id, $process->machine_id);

            $overlapsLoggedHours = ($statusLogsByJob->get($key) ?? collect())->contains(
                fn ($log) => $process->start_time < $log->end_time && $process->end_time > $log->start_time
            );

            if ($overlapsLoggedHours) {
                continue;
            }

            foreach ($this->duration->processDaySegments($process) as $segment) {
                if ($segment['seconds'] <= 0) {
                    continue;
                }

                $date = $segment['date'];
                $seconds = $segment['seconds'];

                $sameDayRecords = $recordsByDateJob->get("{$date}|{$key}") ?? collect();
                $isFallback = false;

                if ($sameDayRecords->isNotEmpty()) {
                    $chosenRecord = $sameDayRecords->count() === 1
                        ? $sameDayRecords->first()
                        : $this->closestRecordByTime($sameDayRecords, $process);
                } else {
                    $isFallback = true;
                    if (! array_key_exists($key, $historyCache)) {
                        [$pid, $posId, $mid] = explode('|', $key);
                        $historyCache[$key] = TimeRecord::with('user:id,name')
                            ->where('project_id', $pid)
                            ->where('position_id', $posId)
                            ->where('machine_id', $mid)
                            ->orderBy('start_time')
                            ->get();
                    }
                    $history = $historyCache[$key];
                    $chosenRecord = $history->isNotEmpty()
                        ? $history->sortBy(fn ($r) => abs(Carbon::parse($r->start_time)->diffInDays($segment['start'])))->first()
                        : null;
                }

                $user = $chosenRecord->user ?? null;
                $rowKey = "{$date}|{$key}|".($user->id ?? 'none');

                if (! isset($leftoverRows[$rowKey])) {
                    $leftoverRows[$rowKey] = (object) [
                        'date' => $date,
                        'project_id' => $process->project_id,
                        'position_id' => $process->position_id,
                        'machine_id' => $process->machine_id,
                        'user_id' => $user->id ?? null,
                        'user_name' => $user->name ?? null,
                        'project' => $process->project,
                        'position' => $process->position,
                        'machine' => $process->machine,
                        'ruestzeit_seconds' => 0,
                        'mit_aufsicht_seconds' => 0,
                        'ohne_aufsicht_seconds' => 0,
                        'is_fallback_attribution' => false,
                    ];
                }

                $leftoverRows[$rowKey]->ohne_aufsicht_seconds += $seconds;
                $leftoverRows[$rowKey]->is_fallback_attribution = $leftoverRows[$rowKey]->is_fallback_attribution || $isFallback;
            }
        }

        // Merge leftovers into an existing Rustzeit/Mit-Aufsicht row for the same
        // date+job+operator where one exists, otherwise add as its own row.
        foreach ($leftoverRows as $leftover) {
            $match = $rows->first(fn ($r) => $r->date === $leftover->date
                && $r->project_id === $leftover->project_id
                && $r->position_id === $leftover->position_id
                && $r->machine_id === $leftover->machine_id
                && $r->user_id === $leftover->user_id);

            if ($match) {
                $match->ohne_aufsicht_seconds += $leftover->ohne_aufsicht_seconds;
            } else {
                $rows->push($leftover);
            }
        }

        // --- Group into one table per machine ----------------------------------------------
        $machineTables = $rows
            ->filter(fn ($r) => ($r->ruestzeit_seconds + $r->mit_aufsicht_seconds + $r->ohne_aufsicht_seconds) > 0)
            ->groupBy('machine_id')
            ->map(function ($rows) {
                $rows = $rows->sortBy(['date', 'project_id', 'position_id'])->values();

                return [
                    'machine' => $rows->first()->machine,
                    'rows' => $rows,
                    'totals' => (object) [
                        'ruestzeit_seconds' => $rows->sum('ruestzeit_seconds'),
                        'mit_aufsicht_seconds' => $rows->sum('mit_aufsicht_seconds'),
                        'ohne_aufsicht_seconds' => $rows->sum('ohne_aufsicht_seconds'),
                    ],
                ];
            })
            ->sortBy(fn ($t) => $t['machine']->name ?? '')
            ->values();

        return [
            'machineTables' => $machineTables,
            'weeks' => $range->weeks,
            'selectedWeek' => $range->selectedWeek,
        ];
    }

    /**
     * Among same-day candidate TimeRecords for the same job, pick the one whose
     * window is closest in time to the process (0 if the process falls inside it).
     */
    private function closestRecordByTime(Collection $records, $process)
    {
        $processStart = Carbon::parse($process->start_time);
        $processEnd = Carbon::parse($process->end_time);

        return $records->sortBy(function ($record) use ($processStart, $processEnd) {
            $recordStart = Carbon::parse($record->start_time);
            $recordEnd = Carbon::parse($record->end_time ?? Carbon::now());

            if ($processStart->between($recordStart, $recordEnd) || $processEnd->between($recordStart, $recordEnd)) {
                return 0;
            }

            return min(
                abs($processStart->diffInSeconds($recordStart)),
                abs($processStart->diffInSeconds($recordEnd))
            );
        })->first();
    }
}