<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineStatus;
use App\Models\Position;
use App\Models\Project;
use App\Models\TimeChangeRequest;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use App\Models\User;
use App\Traits\HandleMachineLogs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $comparison = [];

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

        $year = substr($selectedWeek, 0, 4);
        $week = substr($selectedWeek, 4, 2);

        $fromDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $toDate = Carbon::now()->setISODate($year, $week)->endOfWeek();

        $processTotals = DB::table('processes as pr')
            ->leftJoin('process_pauses as pp', 'pp.process_id', '=', 'pr.id')

            ->select([
                'pr.project_id',
                'pr.position_id',
                'pr.machine_id',

                DB::raw('SUM(TIMESTAMPDIFF(SECOND, pr.start_time, pr.end_time)) as process_seconds'),

                DB::raw('
                    COALESCE(SUM(
                        GREATEST(
                            0,
                            TIMESTAMPDIFF(
                                SECOND,
                                GREATEST(pp.pause_start, pr.start_time),
                                LEAST(COALESCE(pp.pause_end, pr.end_time), pr.end_time)
                            )
                        )
                    ), 0) as pause_seconds
                '),

                DB::raw('COUNT(pr.id) as process_count'),
            ])
            ->whereNotNull('pr.end_time')
            ->whereBetween('pr.start_time', [$fromDate, $toDate])
            ->groupBy('pr.project_id', 'pr.position_id', 'pr.machine_id');

        $logTotals = DB::table('time_logs as tl')
            ->join('time_records as tr', 'tr.id', '=', 'tl.time_record_id')

            ->select([
                'tr.user_id',
                'tr.project_id',
                'tr.position_id',
                'tr.machine_id',

                DB::raw('SUM(TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time)) as log_seconds'),
            ])
            ->whereNotNull('tl.end_time')
            ->whereBetween('tl.start_time', [$fromDate, $toDate])
            ->groupBy('tr.user_id', 'tr.project_id', 'tr.position_id', 'tr.machine_id');

        $groups = DB::query()
            ->fromSub($logTotals, 'lt')

            ->leftJoinSub($processTotals, 'pt', function ($join) {
                $join->on('pt.project_id', '=', 'lt.project_id')
                    ->on('pt.position_id', '=', 'lt.position_id')
                    ->on('pt.machine_id', '=', 'lt.machine_id');
            })
            ->join('users as u', 'u.id', '=', 'lt.user_id')
            ->join('projects as p', 'p.id', '=', 'lt.project_id')
            ->join('positions as pos', 'pos.id', '=', 'lt.position_id')
            ->join('machines as m', 'm.id', '=', 'lt.machine_id')

            ->select([
                'lt.user_id',
                'lt.project_id',
                'lt.position_id',
                'lt.machine_id',

                DB::raw('COALESCE(lt.log_seconds,0) as user_seconds'),
                DB::raw('COALESCE(pt.process_seconds - pt.pause_seconds,0) as machine_seconds'),
                DB::raw('COALESCE(pt.process_count,0) as process_count'),

                'u.name as user_name',

                DB::raw("
                    CASE
                        WHEN u.company = 'ZF' THEN p.auftragsnummer_zf
                        ELSE p.auftragsnummer_zt
                    END as auftragsnummer
                "),

                'p.project_name',
                'pos.name as position_name',
                'm.name as machine_name',
            ])
            ->get();

        $processRows = DB::table('processes')
            ->select('id', 'project_id', 'position_id', 'machine_id',
                'name', 'start_time', 'end_time')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->whereNotNull('end_time')
            ->get()
            ->groupBy(fn ($r) => "$r->project_id-$r->position_id-$r->machine_id"
            );

        $logRows = DB::table('time_logs as tl')
            ->join('time_records as tr', 'tr.id', '=', 'tl.time_record_id')
            ->join('machine_statuses as ms', 'ms.id', '=', 'tl.machine_status_id')

            ->select(
                'tr.user_id', 'tr.project_id', 'tr.position_id', 'tr.machine_id',
                'ms.name as status',
                'tl.start_time', 'tl.end_time'
            )
            ->whereBetween('tl.start_time', [$fromDate, $toDate])
            ->whereNotNull('tl.end_time')
            ->get()
            ->groupBy(fn ($r) => "$r->user_id-$r->project_id-$r->position_id-$r->machine_id"
            );

        foreach ($groups as $g) {

            $procKey = "$g->project_id-$g->position_id-$g->machine_id";
            $logKey = "$g->user_id-$g->project_id-$g->position_id-$g->machine_id";

            $comparison[] = [
                'record' => (object) [
                    'user' => (object) ['name' => $g->user_name],
                    'project' => (object) ['project_name' => $g->project_name, 'auftragsnummer' => $g->auftragsnummer],
                    'Position' => (object) ['name' => $g->position_name],
                    'machine' => (object) ['name' => $g->machine_name],
                ],

                'total_user_time' => $this->hms($g->user_seconds),
                'total_machine_time' => $this->hms($g->machine_seconds),
                'process_count' => $g->process_count,

                'processes' => ($processRows[$procKey] ?? collect())
                    ->map(fn ($p) => [
                        'process_name' => $p->name,
                        'start_time' => $p->start_time,
                        'end_time' => $p->end_time,
                    ])->values()->all(),

                'logs' => ($logRows[$logKey] ?? collect())
                    ->map(fn ($l) => [
                        'status' => $l->status,
                        'start_time' => $l->start_time,
                        'end_time' => $l->end_time,
                    ])->values()->all(),
            ];
        }

        return view('admin.time.compare', compact('comparison', 'weeks', 'selectedWeek'));
    }

    private function hms($sec)
    {
        return gmdate('H:i:s', (int) $sec);
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

                DB::raw('
                    MAX(COALESCE(p.auftragsnummer_zf, p.auftragsnummer_zt)) as auftragsnummer
                '),

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
        $source = '\\\\10.0.0.35\\fz37\\FIDIA\\Program\\LOGFILE.OLD';
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
}
