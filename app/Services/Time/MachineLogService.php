<?php

namespace App\Services\Time;

use App\Repositories\MachineLogRepository;
use App\Support\DurationCalculator;
use App\Support\NetworkFileCopier;
use App\Support\WeekRangeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineLogService
{
    public function __construct(
        private readonly WeekRangeResolver $weekRangeResolver,
        private readonly MachineLogRepository $machineLogRepository,
        private readonly DurationCalculator $durationCalculator,
        private readonly NetworkFileCopier $networkFileCopier,
    ) {
    }

    public function getWeeklyLogs(Request $request): array
    {
        $range = $this->weekRangeResolver->resolve($request->get('week'));

        $weeklyRecords = DB::table('processes as pr')
            ->leftJoin('process_pauses as pp', 'pp.process_id', '=', 'pr.id')
            ->join('projects as p', 'p.id', '=', 'pr.project_id')
            ->leftJoin('positions as po', 'po.id', '=', 'pr.position_id')
            ->join('machines as m', 'm.id', '=', 'pr.machine_id')
            ->whereBetween('pr.start_time', [$range->fromDate, $range->toDate])
            ->whereNotNull('pr.end_time')
            ->select([
                DB::raw('YEARWEEK(pr.start_time, 1) as calendar_week'),
                // Company (derived from project)
                DB::raw('MAX(m.company) as company'),
                DB::raw("
                    CONCAT_WS(' / ', 
                        NULLIF(p.auftragsnummer_zf, ''), 
                        NULLIF(p.auftragsnummer_zt, '')
                    ) as auftragsnummer
                "),
                DB::raw('COALESCE(po.name, \'\') as position_name'),
                'm.name as machine_name',
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, pr.start_time, pr.end_time)) as process_seconds'),
                // TOTAL PAUSE TIME
                DB::raw('
                    SUM(
                        GREATEST(
                            0,
                            TIMESTAMPDIFF(
                                SECOND,
                                GREATEST(pp.pause_start, pr.start_time),
                                LEAST(
                                    COALESCE(pp.pause_end, pr.end_time),
                                    pr.end_time
                                )
                            )
                        )
                    ) as pause_seconds
                '),
            ])
            ->groupBy([
                'calendar_week',
                'p.id',
                'auftragsnummer',
                'po.id',
                'm.id',
                'm.name',
                'po.name',
            ])
            ->orderByDesc('calendar_week')
            ->get();

        return [
            'weeks' => $range->weeks,
            'weeklyRecords' => $weeklyRecords,
            'selectedWeek' => $range->selectedWeek,
        ];
    }

    public function getLegacyLogs(Request $request): array
    {
        $data = $this->machineLogRepository->filter($request);
        $data['projects'] = $this->annotateActiveTime($data['projects']);

        return $data;
    }

    private function annotateActiveTime($projects)
    {
        foreach ($projects as $project) {
            // Fill in the pauses relation that the repository doesn't eager-load
            $project->processes->loadMissing('pauses');
            $project->procedures->each(fn ($procedure) => $procedure->processes->loadMissing('pauses'));
            $project->bauteile->each(function ($bauteil) {
                $bauteil->processes->loadMissing('pauses');
                $bauteil->procedures->each(fn ($procedure) => $procedure->processes->loadMissing('pauses'));
            });

            $this->setActiveSeconds($project->processes);

            foreach ($project->procedures as $procedure) {
                $this->setActiveSeconds($procedure->processes);
            }

            foreach ($project->bauteile as $bauteil) {
                $this->setActiveSeconds($bauteil->processes);
                foreach ($bauteil->procedures as $procedure) {
                    $this->setActiveSeconds($procedure->processes);
                }
            }

            $project->aktive_zeit = $project->processes->sum('active_seconds')
                + $project->procedures->sum(fn ($p) => $p->processes->sum('active_seconds'))
                + $project->bauteile->sum(function ($b) {
                    return $b->processes->sum('active_seconds')
                        + $b->procedures->sum(fn ($p) => $p->processes->sum('active_seconds'));
                });
        }

        return $projects;
    }

    private function setActiveSeconds($processes): void
    {
        foreach ($processes as $process) {
            $process->active_seconds = $this->durationCalculator->legacyActiveSeconds($process);
        }
    }

    public function parseLog(): array
    {
        $source = config('app.machine_log_path'); // e.g., '\\\\10.0.0.35\\fz37\\FIDIA\\Program\\LOGFILE.OLD'
        $destination = storage_path('app\public\LOGFILE.OLD');

        $this->networkFileCopier->copy($source, $destination);

        return [
            'status' => 'success',
            'message' => 'Datei kopiert von server!',
        ];
    }

    /**
     * Parses a copied LOGFILE.OLD via the `parse:drilllog` artisan command.
     * Not currently wired up to any route (its call from parseLog() was
     * commented out in the original too) — kept available for when it's
     * needed.
     */
    public function parseMachineLogs($logFile = null)
    {
        if (! file_exists($logFile)) {
            $sourceFile = '\\\\10.0.0.35\\fz37\\FIDIA\\Program\\LOGFILE.OLD';
            copy($sourceFile, $logFile);
        }

        $cmd = "php artisan parse:drilllog \"$logFile\"";

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, base_path());

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $status = proc_close($process);

        if ($status !== 0) {
            return [
                'status' => 'error',
                'message' => $error ?: 'Unknown error',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Log parsed successfully!',
        ];
    }
}