<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Services\Time\MachineLogService;
use App\Services\Time\TimeChangeRequestService;
use App\Services\Time\TimeRecordService;
use App\Services\Time\WeeklyComparisonService;
use App\Services\Time\WeeklyOverviewService;
use Illuminate\Http\Request;

class TimeController extends Controller
{
    public function __construct(
        private readonly TimeRecordService $timeRecordService,
        private readonly TimeChangeRequestService $timeChangeRequestService,
        private readonly MachineLogService $machineLogService,
        private readonly WeeklyComparisonService $weeklyComparisonService,
        private readonly WeeklyOverviewService $weeklyOverviewService,
    ) {
    }

    public function records(Request $request)
    {
        return view('admin.time.list', $this->timeRecordService->getRecordsPageData($request));
    }

    public function dailyRecords(Request $request)
    {
        return response()->json(['dailyRecords' => $this->timeRecordService->getDailyRecords($request)]);
    }

    public function dayDetails(Request $request)
    {
        return response()->json(['entries' => $this->timeRecordService->getDayDetails($request)]);
    }

    public function editRecord(Request $request, $id)
    {
        return view('admin.time.record-edit', $this->timeRecordService->getEditRecordData($id));
    }

    public function updateRecord(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'position_id' => 'required|exists:positions,id',
            'machine_id' => 'required|exists:machines,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $this->timeRecordService->updateRecord($id, $validated);

        return redirect()
            ->route('admin.time.records')
            ->with('success', 'Time record updated successfully.');
    }

    public function thisProjectPositions(Request $request)
    {
        $positions = $this->timeRecordService->getPositionsForProject($request->input('projectId'));

        return response()->json(['positions' => $positions]);
    }

    public function deleteRecord($id)
    {
        $this->timeRecordService->deleteRecord($id);

        return back()->with('success', 'Machine status deleted successfully.');
    }

    public function changeTimeLogs($id)
    {
        $record = $this->timeRecordService->getRecordWithLogs($id);

        return view('admin.time.change-logs', compact('record'));
    }

    public function storeAndApproveLogs(Request $request, $id)
    {
        $request->validate([
            'logs' => 'required|array',
            'reason' => 'nullable|string|max:1000',
        ]);

        $this->timeChangeRequestService->storeAndApply(
            $id,
            auth()->id(),
            $request->logs,
            $request->reason,
            $request->record_start_time,
            $request->record_end_time,
        );

        return redirect()->back()->with('success', 'Changes applied and recorded successfully.');
    }

    public function show($id)
    {
        return view('admin.time.show', $this->timeRecordService->getShowData($id));
    }

    public function end($id)
    {
        $this->timeRecordService->endRecord($id);

        return redirect()->route('admin.time.records')->with('success', 'Session ended successfully.');
    }

    public function switch(Request $request, TimeLog $log)
    {
        $request->validate([
            'status_id' => 'required|exists:machine_statuses,id',
        ]);

        $timeRecordId = $this->timeRecordService->switchStatus($log, $request->status_id);

        // Redirect back to the same record page
        return redirect()->route('admin.time.show', $timeRecordId)
            ->with('success', 'Status switched successfully.');
    }

    public function compare(Request $request)
    {
        return view('admin.time.compare', $this->weeklyComparisonService->build($request));
    }

    public function machineLogs(Request $request)
    {
        return view('admin.time.logs', $this->machineLogService->getWeeklyLogs($request));
    }

    public function machineLogsOld(Request $request)
    {
        return view('admin.time.logs_old', $this->machineLogService->getLegacyLogs($request));
    }

    public function parseLog()
    {
        return response()->json($this->machineLogService->parseLog());
    }

    public function change(Request $request)
    {
        return view('admin.time.change', $this->timeChangeRequestService->getChangeRequestsData());
    }

    public function acceptChange($id)
    {
        $this->timeChangeRequestService->accept($id, auth()->id());

        return redirect()->back()->with('success', 'Änderungsantrag erfolgreich übernommen.');
    }

    public function rejectChange($id)
    {
        $this->timeChangeRequestService->reject($id, auth()->id());

        return redirect()->back()->with('error', 'Änderungsantrag abgelehnt.');
    }

    public function weeklyOverview(Request $request)
    {
        return view('admin.time.weekly-overview', $this->weeklyOverviewService->build($request));
    }
}