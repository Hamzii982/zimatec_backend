<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\Machine;
use App\Models\TimeRecord;
use App\Models\TimeLog;
use App\Models\MachineStatus;
use Illuminate\Http\Request;
use App\Models\TimeChangeRequest;
use App\Models\Notification;
use App\Models\Process;
use Carbon\Carbon;
use Laravel\Pail\ValueObjects\Origin\Console;

class TimeRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = TimeRecord::with(['user', 'project', 'position', 'machine']);

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
        $users = User::where('machine_user', true)->get();
        $projects = Project::all();
        $machines = Machine::all();

        $selectedUser = $request->user_id
            ? $users->firstWhere('id', $request->user_id)
            : null;

        return view('user.time_records.index', compact('records', 'users', 'projects', 'machines', 'selectedUser'));
    }
    /**
     * Show form to create a new time record
     */
    // public function create()
    // {
    //     $users = User::where('machine_user', true)->get();
    //     $projects = Project::all();
    //     $machines = Machine::all();
    //     $statuses = MachineStatus::where('active',true)->get();

    //     return view('user.time_records.create', compact('users', 'projects', 'machines', 'statuses'));
    // }

    public function create(Request $request)
    {
        $users = User::where('machine_user', true)->get();

        $projects = Project::with('positions')
            ->orderBy('project_name')
            ->get();

        $machines = Machine::all();

        $statuses = MachineStatus::where('active', true)->get();

        $selectedUser = $request->user_id
            ? $users->firstWhere('id', $request->user_id)
            : null;

        return view(
            'user.time_records.create',
            compact('users', 'projects', 'machines', 'statuses', 'selectedUser')
        );
    }
    /**
     * Store a new record (initial start)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'position_id' => 'required|exists:positions,id',
            'machine_id' => 'required|exists:machines,id',
            'status_id' => 'required|exists:machine_statuses,id',
            'manual_process' => 'nullable|boolean',
            'manual_process_name' => 'nullable|string|required_if:manual_process,1',
        ]);

        // Check if an open record already exists for this user/project/machine
        $existingRecord = TimeRecord::where('user_id', $request->user_id)
            ->where('project_id', $request->project_id)
            ->where('position_id', $request->position_id)
            ->where('machine_id', $request->machine_id)
            ->whereNull('end_time')
            ->first();

        if ($existingRecord) {
            return redirect()->back()
                ->withErrors(['duplicate' => 'Für diesen Benutzer, dieses Projekt und diesen Maschine existiert bereits ein laufender Datensatz..'])
                ->withInput();
        }

        $record = TimeRecord::create([
            'user_id' => $request->user_id,
            'project_id' => $request->project_id,
            'position_id' => $request->position_id,
            'machine_id' => $request->machine_id,
            'start_time' => now(),
        ]);

        $this->changeAllOtherLogs($record->id);

        // Immediately create the first log
        TimeLog::create([
            'time_record_id' => $record->id,
            'machine_status_id' => $request->status_id,
            'start_time' => now(),
        ]);

        // Create manual process if requested
        if (!empty($validated['manual_process']) && $validated['manual_process'] == 1) {

            Process::create([
                'project_id' => $validated['project_id'],
                'position_id' => $validated['position_id'],
                'procedure_id' => null,
                'bauteil_id' => null,
                'machine_id' => $validated['machine_id'],
                'time_record_id' => $record->id,
                'name' => $validated['manual_process_name'],
                'start_time' => now(),
                'end_time' => null,
                'total_seconds' => 0,
                'source_file' => null,
            ]);
        }

        return redirect()->route('time-records.show', $record->id);
    }

    public function show($id)
    {
        // Load record with relationships
        $record = TimeRecord::with(['user', 'project', 'position', 'machine', 'logs.status'])->findOrFail($id);

        // Get all statuses for the status-switching buttons
        $statuses = MachineStatus::where('active',true)->get();

        // Find current log (the one still open)
        $currentLog = $record->logs()->whereNull('end_time')->latest()->first();

        // Pass to the view
        return view('user.time_records.show', compact('record', 'statuses', 'currentLog'));
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

        $this->closeRunningManualProcess($record->id);

        return redirect()->route('time-records.list')->with('success', 'Die Sitzung wurde erfolgreich beendet.');
    }

    public function switch(Request $request, TimeLog $log)
    {
        $request->validate([
            'status_id' => 'required|exists:machine_statuses,id',
        ]);

        $this->changeAllOtherLogs($log->time_record_id);

        // Close current log
        if (is_null($log->end_time)) {
            $log->end_time = now();
            $log->save();
        }

        // Create new log
        TimeLog::create([
            'time_record_id' => $log->time_record_id,
            'machine_status_id' => $request->status_id,
            'start_time' => now(),
        ]);

        $this->closeRunningManualProcess($log->time_record_id);

        if ($request->manual_process == 1 && $request->manual_process_name) {

            $record = TimeRecord::find($log->time_record_id);

            Process::create([
                'time_record_id' => $record->id,
                'project_id' => $record->project_id,
                'position_id' => $record->position_id,
                'machine_id' => $record->machine_id,
                'name' => $request->manual_process_name,
                'start_time' => now(),
                'end_time' => null,
                'total_seconds' => 0,
            ]);
        }

        // Redirect back to the same record page
        return redirect()->route('time-records.show', $log->time_record_id)
                        ->with('success', 'Status erfolgreich umgeschaltet.');
    }

    public function changeAllOtherLogs($time_record_id)
    {
        $currentRecord = TimeRecord::find($time_record_id);
        $userId = $currentRecord->user_id;

        // get the 'ohne_aufsicht' status id (adjust as needed)
        $ohneAufsichtStatusId = MachineStatus::where('name', 'Ohne Aufsicht')->value('id');

        /**
         * Step 1: Handle other running records of this user
         */
        $otherRunningRecords = TimeRecord::where('user_id', $userId)
            ->where('id', '!=', $currentRecord->id)
            ->whereHas('logs', function ($q) {
                $q->whereNull('end_time');
            })
            ->with(['logs' => function ($q) {
                $q->whereNull('end_time');
            }])
            ->get();

        foreach ($otherRunningRecords as $record) {
            foreach ($record->logs as $runningLog) {
                // Close running log if not already "ohne_aufsicht"
                if ($runningLog->machine_status_id != $ohneAufsichtStatusId) {
                    $runningLog->end_time = now();
                    $runningLog->save();
    
                    // Create a new log for "ohne_aufsicht"
                    TimeLog::create([
                        'time_record_id' => $record->id,
                        'machine_status_id' => $ohneAufsichtStatusId,
                        'start_time' => now(),
                    ]);
                }
            }
        }
    }

    public function changeRequest($record_id)
    {
        $record = TimeRecord::with('logs.status')->findOrFail($record_id);

        return view('user.time_records.request-form', compact('record'));
    }

    public function storeChangeRequest(Request $request, $record_id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'logs' => 'required|array',
        ]);

        $user_id = TimeRecord::where('id', $record_id)->first()->user_id;

        // Handle "Sonstiges" (other) reason
        $reason = $request->reason;
        if ($request->reason === 'Sonstiges' && $request->other_reason) {
            $reason = $request->other_reason;
        }

        // Save to a ChangeRequest model (for approval workflow)
        TimeChangeRequest::create([
            'time_record_id' => $record_id,
            'requested_by' => $user_id,
            'reason' => $reason,
            'payload' => json_encode($request->logs),
            'record_start_time' => $request->record_start_time,
            'record_end_time' => $request->record_end_time,
        ]);

        $user_name = User::where('id', $user_id)->first()->user_name;

        // ✅ Create admin notification
        Notification::create([
            'user_id' => $user_id,
            'type'    => 'change_request',
            'message' => 'New time change request submitted by ' . $user_name,
            'url' => route('admin.time.change'),
        ]);

        return redirect()->route('time-records.show', $record_id)
            ->with('success', 'Änderungsantrag erfolgreich übermittelt.');
    }

    public function endProcess(Process $process)
    {
        if (!$process->end_time) {

            $end = now();
            $seconds = Carbon::parse($process->start_time)->diffInSeconds($end);

            $process->update([
                'end_time' => $end,
                'total_seconds' => $seconds,
            ]);
        }

        return back();
    }

    protected function closeRunningManualProcess(int $timeRecordId): void
    {
        $runningProcess = Process::where('time_record_id', $timeRecordId)
            ->whereNull('end_time')
            ->latest()
            ->first();

        if (!$runningProcess) {
            return;
        }

        $end = now();
        $seconds = $runningProcess->start_time
            ? Carbon::parse($runningProcess->start_time)->diffInSeconds($end)
            : 0;

        $runningProcess->update([
            'end_time' => $end,
            'total_seconds' => $seconds,
        ]);
    }

}
