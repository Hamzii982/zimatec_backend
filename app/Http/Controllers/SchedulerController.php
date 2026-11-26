<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Notification;
use App\Models\ProductionSchedule;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SchedulerController extends Controller
{
    /**
     * Display the visual scheduler timeline.
     */
    public function index()
    {
        $projects = Project::orderBy('project_name')->get();
        $machines = Machine::where('active', 1)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('user.scheduler.index', compact('projects', 'machines', 'users'));
    }

    /**
     * Fetch scheduler events for a date range.
     */
    public function getEvents(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        $events = ProductionSchedule::with(['user', 'machine', 'project'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            })
            ->get();

        return response()->json($events);
    }

    /**
     * Store a new schedule slot.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'machine_id' => 'required|exists:machines,id',
            'project_id' => 'required|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string',
        ]);

        // Validate overlapping appointments on the same machine
        $overlap = ProductionSchedule::where('machine_id', $request->machine_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Diese Maschine ist im angegebenen Zeitraum bereits belegt!',
            ], 422);
        }

        $data = $request->all();
        $data['type'] = 'machine'; // Default to machine

        $schedule = ProductionSchedule::create($data);

        // Create notification for employee if scheduled
        if ($schedule->user_id) {
            $projectName = $schedule->project ? $schedule->project->project_name : 'Allgemein';
            $startDate = Carbon::parse($schedule->start_time)->format('d.m.Y H:i');
            $endDate = Carbon::parse($schedule->end_time)->format('d.m.Y H:i');

            Notification::create([
                'user_id' => $schedule->user_id,
                'type' => 'scheduler',
                'message' => "Sie wurden für den Auftrag '{$projectName}' vom {$startDate} bis {$endDate} eingeteilt.",
                'url' => route('scheduler.index'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich erstellt.',
            'event' => $schedule->load(['user', 'machine', 'project']),
        ]);
    }

    /**
     * Update an existing schedule slot.
     */
    public function update(Request $request, $id)
    {
        $schedule = ProductionSchedule::findOrFail($id);

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'machine_id' => 'required|exists:machines,id',
            'project_id' => 'required|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string',
        ]);

        // Validate overlapping appointments on the same machine
        $overlap = ProductionSchedule::where('machine_id', $request->machine_id)
            ->where('id', '!=', $id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Diese Maschine ist im angegebenen Zeitraum bereits belegt!',
            ], 422);
        }

        $oldUserId = $schedule->user_id;
        $data = $request->all();
        $data['type'] = 'machine'; // Default to machine

        $schedule->update($data);

        // Notify user if changed or assigned new
        if ($schedule->user_id && ($oldUserId !== $schedule->user_id || $schedule->isDirty(['start_time', 'end_time']))) {
            $projectName = $schedule->project ? $schedule->project->project_name : 'Allgemein';
            $startDate = Carbon::parse($schedule->start_time)->format('d.m.Y H:i');
            $endDate = Carbon::parse($schedule->end_time)->format('d.m.Y H:i');

            Notification::create([
                'user_id' => $schedule->user_id,
                'type' => 'scheduler',
                'message' => "Ihr Einsatzplan für den Auftrag '{$projectName}' wurde geändert (Zeitraum: {$startDate} bis {$endDate}).",
                'url' => route('scheduler.index'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich aktualisiert.',
            'event' => $schedule->load(['user', 'machine', 'project']),
        ]);
    }

    /**
     * Delete a schedule slot.
     */
    public function destroy($id)
    {
        $schedule = ProductionSchedule::findOrFail($id);

        // Notify user if shift cancelled
        if ($schedule->user_id) {
            $projectName = $schedule->project ? $schedule->project->project_name : 'Allgemein';
            $startDate = Carbon::parse($schedule->start_time)->format('d.m.Y H:i');

            Notification::create([
                'user_id' => $schedule->user_id,
                'type' => 'scheduler',
                'message' => "Ihr Einsatz am {$startDate} für den Auftrag '{$projectName}' wurde abgesagt.",
                'url' => route('scheduler.index'),
            ]);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich gelöscht.',
        ]);
    }
}
