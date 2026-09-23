<?php

namespace App\Services\Time;

use App\Models\Process;
use App\Models\TimeChangeRequest;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use Carbon\Carbon;

class TimeChangeRequestService
{
    public function getChangeRequestsData(): array
    {
        $pendingRequests = TimeChangeRequest::with([
            'timeRecord.project',
            'timeRecord.machine',
            'timeRecord.position',
            'timeRecord.logs.status',
            'timeRecord.processes',
            'requestedBy',
        ])->whereNull('status')->latest()->get();

        $processedRequests = TimeChangeRequest::with([
            'timeRecord.project',
            'timeRecord.machine',
            'timeRecord.position',
            'timeRecord.logs.status',
            'timeRecord.processes',
            'requestedBy',
            'approvedBy',
        ])->whereNotNull('status')->latest()->get();

        return compact('pendingRequests', 'processedRequests');
    }

    /**
     * Creates a change request that is immediately marked "accepted" and
     * applies its log payload straight away — the admin "direct edit"
     * flow, as opposed to the request/approve flow below.
     */
    public function storeAndApply(
        int $timeRecordId,
        int $adminId,
        array $logs,
        ?string $reason,
        ?string $recordStartTime,
        ?string $recordEndTime,
    ): void {
        // 1️⃣ Create the change request
        $changeRequest = TimeChangeRequest::create([
            'time_record_id' => $timeRecordId,
            'requested_by' => $adminId,
            'reason' => $reason ?? 'Direct admin change',
            'payload' => json_encode($logs),
            'status' => 'accepted',          // mark as already accepted
            'approved_by' => $adminId,
            'approved_at' => now(),
            'record_start_time' => $recordStartTime,
            'record_end_time' => $recordEndTime,
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
                        'time_record_id' => $timeRecordId,
                        'start_time' => $logData['start_time'] ?? null,
                        'end_time' => $logData['end_time'] ?? null,
                        'machine_status_id' => $logData['status_id'] ?? null,
                    ]);
                }
            }
        }

        if (! empty($recordStartTime) || ! empty($recordEndTime)) {
            $record = TimeRecord::find($timeRecordId);
            if ($record) {
                $record->update([
                    'start_time' => $recordStartTime,
                    'end_time' => $recordEndTime,
                ]);
            }
        }
    }

    public function accept(int $id, int $adminId): void
    {
        $changeRequest = TimeChangeRequest::findOrFail($id);
        $payload = json_decode($changeRequest->payload, true) ?? [];

        // Backward compatibility: requests created before "processes" existed
        // stored payload as a flat array of logs, with no 'logs'/'processes' keys.
        $isLegacyFormat = ! array_key_exists('logs', $payload) && ! array_key_exists('processes', $payload);
        $logsData = $isLegacyFormat ? $payload : ($payload['logs'] ?? []);
        $processesData = $isLegacyFormat ? [] : ($payload['processes'] ?? []);

        foreach ($logsData as $logData) {
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
            } else {
                TimeLog::create([
                    'time_record_id' => $changeRequest->time_record_id,
                    'start_time' => $logData['start_time'] ?? null,
                    'end_time' => $logData['end_time'] ?? null,
                    'machine_status_id' => $logData['status_id'] ?? null,
                ]);
            }
        }

        foreach ($processesData as $processData) {
            if (! empty($processData['id'])) {
                $process = Process::find($processData['id']);
                if ($process) {
                    if (! empty($processData['delete']) && $processData['delete'] === 'true') {
                        $process->delete();
                        continue;
                    }

                    $startTime = $processData['start_time'] ?? $process->start_time;
                    $endTime = $processData['end_time'] ?? null;
                    $seconds = $endTime
                        ? Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime))
                        : $process->total_seconds;

                    $process->update([
                        'name' => $processData['name'] ?? $process->name,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'total_seconds' => $seconds,
                    ]);
                }
            } else {
                $timeRecord = $changeRequest->timeRecord;
                $startTime = $processData['start_time'] ?? null;
                $endTime = $processData['end_time'] ?? null;
                $seconds = ($startTime && $endTime)
                    ? Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime))
                    : 0;

                Process::create([
                    'project_id' => $timeRecord->project_id,
                    'position_id' => $timeRecord->position_id,
                    'procedure_id' => null,
                    'bauteil_id' => null,
                    'machine_id' => $timeRecord->machine_id,
                    'time_record_id' => $changeRequest->time_record_id,
                    'name' => $processData['name'] ?? null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'total_seconds' => $seconds,
                    'source_file' => null,
                ]);
            }
        }

        if (! empty($changeRequest->record_start_time) || ! empty($changeRequest->record_end_time)) {
            $record = TimeRecord::find($changeRequest->time_record_id);
            if ($record) {
                $record->update([
                    'start_time' => $changeRequest->record_start_time ?: $record->start_time,
                    'end_time' => $changeRequest->record_end_time ?: $record->end_time,
                ]);
            }
        }

        $changeRequest->update([
            'status' => 'accepted',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $id, int $adminId): void
    {
        $changeRequest = TimeChangeRequest::findOrFail($id);
        $changeRequest->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }
}