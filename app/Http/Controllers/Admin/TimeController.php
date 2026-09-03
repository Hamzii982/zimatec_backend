<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineStatus;
use App\Models\Position;
use App\Models\Project;
use App\Models\Process;
use App\Models\TimeChangeRequest;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use App\Models\User;
use App\Traits\HandleMachineLogs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TimeController extends Controller
{
    use HandleMachineLogs;

    public function records(Request $request)
    {
        $query = TimeRecord::with(['user', 'project', 'machine']);

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('end_time');
            } elseif ($request->status === 'ended') {
                $query->whereNotNull('end_time');
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->date);
        }

        // Pagination
        $records = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // For filter dropdowns
        $users = User::all();
        $projects = Project::all();
        $machines = Machine::all();

        $weeks = [];
        $today = Carbon::now();
        $selectedWeek = request()->get('week', $today->format('oW')); // e.g., 202603
        $maxWeeks = 5;

        // Start from current week
        $i = 0;
        while (true) {
            $weekStart = (clone $today)->startOfWeek()->subWeeks($i);
            $weekNumber = $weekStart->format('oW');

            $weeks[] = [
                'label' => 'KW '.$weekStart->format('W').' / '.$weekStart->format('o'),
                'value' => $weekNumber,
            ];

            $i++;

            // Stop conditions:
            // 1. Reached maxWeeks AND selectedWeek is already in the list
            // 2. Or the last generated week matches selectedWeek
            if (count($weeks) >= $maxWeeks && in_array($selectedWeek, array_column($weeks, 'value'))) {
                break;
            }
            if ($weekNumber === $selectedWeek) {
                break;
            }
        }

        // Extract year and week number
        $year = substr($selectedWeek, 0, 4);
        $weekNumber = substr($selectedWeek, 4, 2);

        // Set $fromDate as start of that week
        $fromDate = Carbon::now()->setISODate($year, $weekNumber)->startOfWeek();
        $toDate = Carbon::now()->setISODate($year, $weekNumber)->endOfWeek();

        $weeklyRecords = DB::table('time_logs as tl')
            ->join('time_records as tr', 'tr.id', '=', 'tl.time_record_id')
            ->join('users as u', 'u.id', '=', 'tr.user_id')
            ->join('projects as p', 'p.id', '=', 'tr.project_id')
            ->join('positions as pos', 'pos.id', '=', 'tr.position_id')
            ->join('machines as m', 'm.id', '=', 'tr.machine_id')
            ->join('machine_statuses as ms', 'ms.id', '=', 'tl.machine_status_id')

            ->whereNotNull('tl.end_time')
            ->whereBetween('tl.start_time', [$fromDate, $toDate])

            ->select([
                DB::raw('YEARWEEK(tl.start_time, 1) as calendar_week'),
                'u.company',

                DB::raw("
                    CONCAT_WS(' / ', 
                        NULLIF(p.auftragsnummer_zf, ''), 
                        NULLIF(p.auftragsnummer_zt, '')
                    ) as auftragsnummer
                "),

                'pos.id as position_id',
                'm.id as machine_id',
                'pos.name as position_name',
                'm.name as machine_name',

                DB::raw("
                    SUM(
                        CASE WHEN ms.name = 'Rustzeit'
                        THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time)
                        ELSE 0 END
                    ) as rustzeit_seconds
                "),

                DB::raw("
                    SUM(
                        CASE WHEN ms.name = 'Mit Aufsicht'
                        THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time)
                        ELSE 0 END
                    ) as mit_aufsicht_seconds
                "),
            ])

            ->groupBy([
                'calendar_week',
                'u.company',
                'auftragsnummer',
                'pos.id',
                'm.id',
                'm.name',
                'pos.name',
            ])

            ->orderByDesc('calendar_week')
            ->get();
        // dd($weeklyRecords);

        return view('admin.time.list', compact('weeklyRecords', 'weeks', 'selectedWeek', 'records', 'users', 'projects', 'machines'));
    }

    public function dailyRecords(Request $request)
    {
        $calendarWeek = $request->input('calendar_week');
        $auftragsnummer = $request->input('auftragsnummer');
        $positionId = $request->input('position_id');
        $machineId = $request->input('machine_id');

        $dailyRecords = DB::table('time_logs as tl')
            ->join('time_records as tr', 'tr.id', '=', 'tl.time_record_id')
            ->join('users as u', 'u.id', '=', 'tr.user_id')
            ->join('projects as p', 'p.id', '=', 'tr.project_id')
            ->join('positions as pos', 'pos.id', '=', 'tr.position_id')
            ->join('machines as m', 'm.id', '=', 'tr.machine_id')
            ->join('machine_statuses as ms', 'ms.id', '=', 'tl.machine_status_id')

            ->whereNotNull('tl.end_time')
            ->whereRaw('YEARWEEK(tl.start_time, 1) = ?', [$calendarWeek])
            ->where(function ($query) use ($auftragsnummer) {
                $query->whereRaw("(u.company = 'ZF' AND COALESCE(p.auftragsnummer_zf, '') = ?)", [$auftragsnummer])
                    ->orWhereRaw("(u.company = 'ZT' AND COALESCE(p.auftragsnummer_zt, '') = ?)", [$auftragsnummer]);
            })
            ->where('pos.id', $positionId)
            ->where('m.id', $machineId)

            ->select([
                DB::raw('DATE(tl.start_time) as record_date'),
                'u.company',
                DB::raw("
                    CASE
                        WHEN u.company = 'ZF' THEN p.auftragsnummer_zf
                        ELSE p.auftragsnummer_zt
                    END as auftragsnummer
                "),
                'pos.id as position_id',
                'm.id as machine_id',
                'pos.name as position_name',
                'm.name as machine_name',
                DB::raw("
                    SUM(
                        CASE WHEN ms.name = 'Rustzeit' THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) ELSE 0 END
                    ) as rustzeit_seconds
                "),
                DB::raw("
                    SUM(
                        CASE WHEN ms.name = 'Mit Aufsicht' THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) ELSE 0 END
                    ) as mit_aufsicht_seconds
                "),
            ])

            ->groupBy([
                'record_date',
                'u.company',
                'auftragsnummer',
                'pos.id',
                'm.id',
                'm.name',
                'pos.name',
            ])

            ->orderBy('record_date', 'asc')
            ->get()
            ->map(function ($row) {
                // Add the daily_key in PHP
                $row->daily_key = "{$row->record_date}-{$row->auftragsnummer}-{$row->position_id}-{$row->machine_id}";

                return $row;
            });

        return response()->json(['dailyRecords' => $dailyRecords]);
    }

    public function dayDetails(Request $request)
    {
        $date = $request->input('date');
        $calendarWeek = $request->input('calendar_week');
        $auftragsnummer = $request->input('auftragsnummer');
        $positionId = $request->input('position_id');
        $machineId = $request->input('machine_id');

        $entries = DB::table('time_logs as tl')
            ->join('time_records as tr', 'tr.id', '=', 'tl.time_record_id')
            ->join('users as u', 'u.id', '=', 'tr.user_id')
            ->join('projects as p', 'p.id', '=', 'tr.project_id')
            ->join('machine_statuses as ms', 'ms.id', '=', 'tl.machine_status_id')
            ->whereDate('tl.start_time', $date)
            ->whereRaw('YEARWEEK(tl.start_time, 1) = ?', [$calendarWeek])
            ->where(function ($query) use ($auftragsnummer) {
                $query->whereRaw("(u.company = 'ZF' AND COALESCE(p.auftragsnummer_zf, '') = ?)", [$auftragsnummer])
                    ->orWhereRaw("(u.company = 'ZT' AND COALESCE(p.auftragsnummer_zt, '') = ?)", [$auftragsnummer]);
            })
            ->where('tr.position_id', $positionId)
            ->where('tr.machine_id', $machineId)
            ->whereNotNull('tl.end_time')
            ->orderBy('tl.start_time')
            ->get([
                'u.name as user_name',
                'tl.start_time',
                'tl.end_time',
                'ms.name as machine_status',
            ]);

        return response()->json(['entries' => $entries]);
    }

    public function editRecord(Request $request, $id)
    {

        $record = TimeRecord::findOrFail($id);

        // Load dropdown data
        $users = User::all();
        $projects = Project::all();
        $positions = Position::where('project_id', $record->project_id)->get();
        $machines = Machine::all();

        return view('admin.time.record-edit', compact('record', 'users', 'projects', 'positions', 'machines'));
    }

    public function updateRecord(Request $request, $id)
    {

        $record = TimeRecord::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'position_id' => 'required|exists:positions,id',
            'machine_id' => 'required|exists:machines,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $record->update([
            'user_id' => $request->user_id,
            'project_id' => $request->project_id,
            'position_id' => $request->position_id,
            'machine_id' => $request->machine_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()
            ->route('admin.time.records')
            ->with('success', 'Time record updated successfully.');
    }

    public function thisProjectPositions(Request $request)
    {
        $projectId = $request->input('projectId');
        $positions = Position::where('project_id', $projectId)->get();

        return response()->json(['positions' => $positions]);
    }

    public function deleteRecord($id)
    {
        TimeRecord::findOrFail($id)->delete();

        return back()->with('success', 'Machine status deleted successfully.');
    }

    public function changeTimeLogs($id)
    {
        $record = TimeRecord::with('logs.status')->findOrFail($id);

        return view('admin.time.change-logs', compact('record'));
    }

    public function storeAndApproveLogs(Request $request, $id)
    {
        $request->validate([
            'logs' => 'required|array',
            'reason' => 'nullable|string|max:1000',
        ]);

        $adminId = auth()->id();

        // 1️⃣ Create the change request
        $changeRequest = TimeChangeRequest::create([
            'time_record_id' => $id,
            'requested_by' => $adminId,
            'reason' => $request->reason ?? 'Direct admin change',
            'payload' => json_encode($request->logs),
            'status' => 'accepted',          // mark as already accepted
            'approved_by' => $adminId,
            'approved_at' => now(),
            'record_start_time' => $request->record_start_time,
            'record_end_time' => $request->record_end_time,
        ]);

        // 2️⃣ Apply the logs immediately
        $payload = json_decode($changeRequest->payload, true);

        if (is_array($payload)) {
            foreach ($payload as $logData) {
                // Update existing log
                if (! empty($logData['id'])) {
                    $log = TimeLog::find($logData['id']);
                    if ($log) {
                        if (! empty($logData['delete']) && $logData['delete'] === 'true') {
                            $log->delete();
                        } else {
                            $log->update([
                                'start_time' => $logData['start_time'] ?? $log->start_time,
                                'end_time' => $logData['end_time'] ?? $log->end_time,
                                'machine_status_id' => $logData['status_id'] ?? $log->machine_status_id,
                            ]);
                        }
                    }
                }
                // Create new log
                else {
                    TimeLog::create([
                        'time_record_id' => $id,
                        'start_time' => $logData['start_time'] ?? null,
                        'end_time' => $logData['end_time'] ?? null,
                        'machine_status_id' => $logData['status_id'] ?? null,
                    ]);
                }
            }
        }

        if (! empty($request->record_start_time) || ! empty($request->record_end_time)) {
            $record = TimeRecord::find($id);
            if ($record) {
                $record->update([
                    'start_time' => $request->record_start_time,
                    'end_time' => $request->record_end_time,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Changes applied and recorded successfully.');
    }

    public function show($id)
    {
        // Load record with relationships
        $record = TimeRecord::with(['user', 'project', 'machine', 'logs.status'])->findOrFail($id);

        // Get all statuses for the status-switching buttons
        $statuses = MachineStatus::where('active', true)->get();

        // Find current log (the one still open)
        $currentLog = $record->logs()->whereNull('end_time')->latest()->first();

        // Pass to the view
        return view('admin.time.show', compact('record', 'statuses', 'currentLog'));
    }

    public function end($id)
    {
        $record = TimeRecord::with('logs')->findOrFail($id);

        // 1️⃣ Close any open log
        $activeLog = $record->logs()->whereNull('end_time')->latest()->first();
        if ($activeLog) {
            $activeLog->end_time = now();
            $activeLog->save();
        }

        // 2️⃣ Close the record
        $record->end_time = now();
        $record->save();

        return redirect()->route('admin.time.records')->with('success', 'Session ended successfully.');
    }

    public function switch(Request $request, TimeLog $log)
    {
        $request->validate([
            'status_id' => 'required|exists:machine_statuses,id',
        ]);

        // Close current log
        if (is_null($log->end_time)) {
            $log->end_time = now();
            $log->save();
        }

        // Create new log
        $newLog = TimeLog::create([
            'time_record_id' => $log->time_record_id,
            'machine_status_id' => $request->status_id,
            'start_time' => now(),
        ]);

        // Redirect back to the same record page
        return redirect()->route('admin.time.show', $log->time_record_id)
            ->with('success', 'Status switched successfully.');
    }

    public function compare(Request $request)
    {
        $today = Carbon::now();
        $selectedWeek = $request->get('week', $today->format('oW'));
        $maxWeeks = 5;
    
        $weeks = [];
        $i = 0;
        while (true) {
            $weekStart = (clone $today)->startOfWeek()->subWeeks($i);
            $weekNumber = $weekStart->format('oW');
    
            $weeks[] = [
                'label' => 'KW '.$weekStart->format('W').' / '.$weekStart->format('o'),
                'value' => $weekNumber,
            ];
    
            $i++;
    
            if (count($weeks) >= $maxWeeks && in_array($selectedWeek, array_column($weeks, 'value'))) {
                break;
            }
    
            if ($weekNumber === $selectedWeek) {
                break;
            }
        }
    
        $year = substr($selectedWeek, 0, 4);
        $week = substr($selectedWeek, 4, 2);
        $fromDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $toDate = Carbon::now()->setISODate($year, $week)->endOfWeek();
    
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
                ->map(fn ($logs) => $logs->sum(fn ($l) => $this->seconds($l->start_time, $l->end_time)));
    
            $machineSeconds = $record->processes->sum(
                fn ($p) => $this->seconds($p->start_time, $p->end_time) - $this->pauseSeconds($p)
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
                'total_user_time' => $this->hms($statusSeconds->sum()),
                'status_seconds' => [
                    'ruestzeit' => $statusSeconds->get('Rustzeit', 0),
                    'mit_aufsicht' => $statusSeconds->get('Mit Aufsicht', 0),
                    'ohne_aufsicht' => $statusSeconds->get('Ohne Aufsicht', 0),
                ],
                'total_machine_time' => $this->hms($machineSeconds),
                'process_count' => $record->processes->count(),
                'processes' => $record->processes->map(fn ($p) => [
                    'process_name' => $p->name,
                    'start_time' => $p->start_time,
                    'end_time' => $p->end_time,
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
                fn ($p) => $this->seconds($p->start_time, $p->end_time) - $this->pauseSeconds($p)
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
                'total_user_time' => $this->hms(0),
                'status_seconds' => ['ruestzeit' => 0, 'mit_aufsicht' => 0, 'ohne_aufsicht' => 0],
                'total_machine_time' => $this->hms($machineSeconds),
                'process_count' => count($processes),
                'processes' => collect($processes)->map(fn ($p) => [
                    'process_name' => $p->name,
                    'start_time' => $p->start_time,
                    'end_time' => $p->end_time,
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
                    'ruestzeit' => $this->hms($rows->sum(fn ($r) => $r['status_seconds']['ruestzeit'])),
                    'mit_aufsicht' => $this->hms($rows->sum(fn ($r) => $r['status_seconds']['mit_aufsicht'])),
                    'ohne_aufsicht' => $this->hms($rows->sum(fn ($r) => $r['status_seconds']['ohne_aufsicht'])),
                    'total_user_time' => $this->hms($rows->sum(fn ($r) => $this->hmsToSeconds($r['total_user_time']))),
                    'total_machine_time' => $this->hms($rows->sum(fn ($r) => $this->hmsToSeconds($r['total_machine_time']))),
                    'process_count' => $rows->sum('process_count'),
                    'session_count' => $rows->count(),
                ];
            })
            ->values();
    
        return view('admin.time.compare', compact('comparison', 'aggregate', 'weeks', 'selectedWeek'));
    }
    
    private function seconds($start, $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }
    
        return abs(Carbon::parse($start)->diffInSeconds(Carbon::parse($end)));
    }
    
    private function pauseSeconds($process): int
    {
        return $process->pauses->sum(function ($pause) use ($process) {
            $start = Carbon::parse(max($pause->pause_start, $process->start_time));
            $end = Carbon::parse(min($pause->pause_end ?? $process->end_time, $process->end_time));
    
            return max(0, $end->diffInSeconds($start));
        });
    }
    
    private function auftragsnummer($project, ?string $company)
    {
        if (! $project) {
            return null;
        }
    
        return $project->auftragsnummer_zf . ' / ' . $project->auftragsnummer_zt;
    }
    
    private function hms(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
    
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
    
    private function hmsToSeconds(string $hms): int
    {
        [$h, $m, $s] = array_map('intval', explode(':', $hms));
    
        return $h * 3600 + $m * 60 + $s;
    }

    public function machineLogs(Request $request)
    {
        $weeks = [];
        $today = Carbon::now();
        $selectedWeek = $request->get('week', $today->format('oW'));
        $maxWeeks = 5;

        $i = 0;
        while (true) {
            $weekStart = (clone $today)->startOfWeek()->subWeeks($i);
            $weekNumber = $weekStart->format('oW');

            $weeks[] = [
                'label' => 'KW '.$weekStart->format('W').' / '.$weekStart->format('o'),
                'value' => $weekNumber,
            ];

            $i++;

            if (
                count($weeks) >= $maxWeeks &&
                in_array($selectedWeek, array_column($weeks, 'value'))
            ) {
                break;
            }

            if ($weekNumber === $selectedWeek) {
                break;
            }
        }

        /* ================= DATE RANGE ================= */

        $year = substr($selectedWeek, 0, 4);
        $week = substr($selectedWeek, 4, 2);

        $fromDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $toDate = Carbon::now()->setISODate($year, $week)->endOfWeek();

        /* ================= MACHINE WEEKLY RECORDS ================= */

        $weeklyRecords = DB::table('processes as pr')
            ->leftJoin('process_pauses as pp', 'pp.process_id', '=', 'pr.id')
            ->join('projects as p', 'p.id', '=', 'pr.project_id')
            ->leftJoin('positions as po', 'po.id', '=', 'pr.position_id')
            ->join('machines as m', 'm.id', '=', 'pr.machine_id')

            ->whereBetween('pr.start_time', [$fromDate, $toDate])
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

                // DB::raw("'Fräsmaschine' as machine_name"),
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

        return view('admin.time.logs', compact('weeks', 'weeklyRecords', 'selectedWeek'));
    }

    public function machineLogsOld(Request $request)
    {
        $data = $this->getMachineLogs($request);

        return view('admin.time.logs_old', $data);
    }

    public function parseLog()
    {
        $source = config('app.machine_log_path'); // e.g., '\\\\10.0.0.35\\fz37\\FIDIA\\Program\\LOGFILE.OLD'
        $destination = storage_path('app\public\LOGFILE.OLD');

        $this->copyNetworkFile($source, $destination);
        // $file = storage_path('app/public/logs/LOGFILE.OLD');
        // return $this->parseMachineLogs($file);

        return response()->json([
            'status' => 'success',
            'message' => 'Datei kopiert von server!',
        ]);
    }

    private function copyNetworkFile($source, $destination)
    {
        try {
            if (! copy($source, $destination)) {
                throw new \Exception("Failed to copy file from $source to $destination");
            }
        } catch (\Exception $e) {
            dd($e);
            // Handle error (log it, notify someone, etc.)
            \Log::error('Error copying network file: '.$e->getMessage());
        }
    }

    public function change(Request $request)
    {
        $pendingRequests = TimeChangeRequest::with(['timeRecord.project', 'timeRecord.machine'])->whereNull('status')->latest()->get();
        $processedRequests = TimeChangeRequest::with(['timeRecord.project', 'timeRecord.machine'])->whereNotNull('status')->latest()->get();

        return view('admin.time.change', compact('pendingRequests', 'processedRequests'));
    }

    public function acceptChange($id)
    {
        $changeRequest = TimeChangeRequest::findOrFail($id);

        // Decode payload into PHP array
        $payload = json_decode($changeRequest->payload, true);

        if (is_array($payload)) {
            foreach ($payload as $logData) {
                // Update existing log
                if (! empty($logData['id'])) {
                    $log = TimeLog::find($logData['id']);
                    if ($log) {
                        if (! empty($logData['delete']) && $logData['delete'] === 'true') {
                            $log->delete();
                        } else {
                            $log->update([
                                'start_time' => $logData['start_time'] ?? $log->start_time,
                                'end_time' => $logData['end_time'] ?? $log->end_time,
                                'machine_status_id' => $logData['status_id'] ?? $log->machine_status_id,
                            ]);
                        }
                    }
                }
                // Create new log
                else {
                    TimeLog::create([
                        'time_record_id' => $changeRequest->time_record_id,
                        'start_time' => $logData['start_time'] ?? null,
                        'end_time' => $logData['end_time'] ?? null,
                        'machine_status_id' => $logData['status_id'] ?? null,
                    ]);
                }
            }
        }

        if (! empty($changeRequest->record_start_time) || ! empty($changeRequest->record_end_time)) {
            $record = TimeRecord::find($changeRequest->time_record_id);
            if ($record) {
                $record->update([
                    'start_time' => $changeRequest->record_start_time,
                    'end_time' => $changeRequest->record_end_time,
                ]);
            }
        }

        $changeRequest->update([
            'status' => 'accepted',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Change request accepted successfully.');
    }

    public function rejectChange($id)
    {
        $changeRequest = TimeChangeRequest::findOrFail($id);
        $changeRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('error', 'Change request rejected.');
    }

    
    /**
     *
     * Row logic, per your answers:
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
     *         (before or after this date, whichever is closer in time) — per
     *         your instruction. These fallback rows are flagged
     *         'is_fallback_attribution' so the view can mark them distinctly;
     *         management should not read that name as "present that day."
     *      3. If that job has literally no TimeRecord history at all, the
     *         operator is left null ("—" in the view) — this should be rare/never
     *         in practice but is handled rather than silently guessing a name.
     *
     * One table per machine; machines with no hours at all this week are
     * skipped entirely.
     */
    public function weeklyOverview(Request $request)
    {
        $today = Carbon::now();
        $selectedWeek = $request->get('week', $today->format('oW'));
        $maxWeeks = 5;
    
        $weeks = [];
        $i = 0;
        while (true) {
            $weekStart = (clone $today)->startOfWeek()->subWeeks($i);
            $weekNumber = $weekStart->format('oW');
    
            $weeks[] = [
                'label' => 'KW '.$weekStart->format('W').' / '.$weekStart->format('o'),
                'value' => $weekNumber,
            ];
    
            $i++;
    
            if (count($weeks) >= $maxWeeks && in_array($selectedWeek, array_column($weeks, 'value'))) {
                break;
            }
    
            if ($weekNumber === $selectedWeek) {
                break;
            }
        }
    
        $year = substr($selectedWeek, 0, 4);
        $week = substr($selectedWeek, 4, 2);
        $fromDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $toDate = Carbon::now()->setISODate($year, $week)->endOfWeek();
    
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
                    ->sum(fn ($l) => $this->seconds($l->start_time, $l->end_time)),
                'mit_aufsicht_seconds' => $group->filter(fn ($l) => ($l->status->name ?? null) === 'Mit Aufsicht')
                    ->sum(fn ($l) => $this->seconds($l->start_time, $l->end_time)),
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
    
            $date = Carbon::parse($process->start_time)->toDateString();
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
                    ? $history->sortBy(fn ($r) => abs(Carbon::parse($r->start_time)->diffInDays($process->start_time)))->first()
                    : null;
            }
    
            $user = $chosenRecord->user ?? null;
            $seconds = $this->seconds($process->start_time, $process->end_time) - $this->pauseSeconds($process);
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
                    'is_fallback_attribution' => $isFallback,
                ];
            }
    
            $leftoverRows[$rowKey]->ohne_aufsicht_seconds += $seconds;
            $leftoverRows[$rowKey]->is_fallback_attribution = $leftoverRows[$rowKey]->is_fallback_attribution || $isFallback;
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
    
        return view('admin.time.weekly-overview', compact('machineTables', 'weeks', 'selectedWeek'));
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
