<?php

namespace App\Services\Time;

use App\Models\Machine;
use App\Models\MachineStatus;
use App\Models\Position;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use App\Models\User;
use App\Support\WeekRangeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeRecordService
{
    public function __construct(
        private readonly WeekRangeResolver $weekRangeResolver,
    ) {
    }

    /**
     * Data for the paginated/filterable time records list plus the
     * per-week Rüstzeit / Mit-Aufsicht aggregate table shown alongside it.
     */
    public function getRecordsPageData(Request $request): array
    {
        $query = TimeRecord::with(['user', 'project', 'machine']);

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

        $records = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $range = $this->weekRangeResolver->resolve($request->get('week'));

        $weeklyRecords = DB::table('time_logs as tl')
            ->join('time_records as tr', 'tr.id', '=', 'tl.time_record_id')
            ->join('users as u', 'u.id', '=', 'tr.user_id')
            ->join('projects as p', 'p.id', '=', 'tr.project_id')
            ->join('positions as pos', 'pos.id', '=', 'tr.position_id')
            ->join('machines as m', 'm.id', '=', 'tr.machine_id')
            ->join('machine_statuses as ms', 'ms.id', '=', 'tl.machine_status_id')
            ->whereNotNull('tl.end_time')
            ->whereBetween('tl.start_time', [$range->fromDate, $range->toDate])
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

        return [
            'records' => $records,
            'users' => User::all(),
            'projects' => Project::all(),
            'machines' => Machine::all(),
            'weeks' => $range->weeks,
            'selectedWeek' => $range->selectedWeek,
            'weeklyRecords' => $weeklyRecords,
        ];
    }

    public function getDailyRecords(Request $request)
    {
        $calendarWeek = $request->input('calendar_week');
        $auftragsnummer = $request->input('auftragsnummer');
        $positionId = $request->input('position_id');
        $machineId = $request->input('machine_id');

        return DB::table('time_logs as tl')
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
    }

    public function getDayDetails(Request $request)
    {
        $date = $request->input('date');
        $calendarWeek = $request->input('calendar_week');
        $auftragsnummer = $request->input('auftragsnummer');
        $positionId = $request->input('position_id');
        $machineId = $request->input('machine_id');

        return DB::table('time_logs as tl')
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
    }

    public function getEditRecordData(int $id): array
    {
        $record = TimeRecord::findOrFail($id);

        return [
            'record' => $record,
            'users' => User::all(),
            'projects' => Project::all(),
            'positions' => Position::where('project_id', $record->project_id)->get(),
            'machines' => Machine::all(),
        ];
    }

    public function updateRecord(int $id, array $data): void
    {
        $record = TimeRecord::findOrFail($id);

        $record->update([
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'],
            'position_id' => $data['position_id'],
            'machine_id' => $data['machine_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
        ]);
    }

    public function getPositionsForProject($projectId)
    {
        return Position::where('project_id', $projectId)->get();
    }

    public function deleteRecord(int $id): void
    {
        TimeRecord::findOrFail($id)->delete();
    }

    public function getRecordWithLogs(int $id): TimeRecord
    {
        return TimeRecord::with('logs.status')->findOrFail($id);
    }

    public function getShowData(int $id): array
    {
        $record = TimeRecord::with(['user', 'project', 'machine', 'logs.status'])->findOrFail($id);
        $statuses = MachineStatus::where('active', true)->get();
        $currentLog = $record->logs()->whereNull('end_time')->latest()->first();

        return [
            'record' => $record,
            'statuses' => $statuses,
            'currentLog' => $currentLog,
        ];
    }

    public function endRecord(int $id): void
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
    }

    /**
     * Closes the given log's active window and opens a new one under
     * the requested status. Returns the owning time_record_id so the
     * controller can redirect back to its show page.
     */
    public function switchStatus(TimeLog $log, int $statusId): int
    {
        // Close current log
        if (is_null($log->end_time)) {
            $log->end_time = now();
            $log->save();
        }

        // Create new log
        TimeLog::create([
            'time_record_id' => $log->time_record_id,
            'machine_status_id' => $statusId,
            'start_time' => now(),
        ]);

        return $log->time_record_id;
    }
}