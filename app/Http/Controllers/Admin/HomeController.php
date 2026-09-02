<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Notification;
use App\Models\Project;
use App\Models\TimeRecord;
use App\Models\User;
use App\Models\Material;
use App\Models\Lager;
use App\Models\TimeChangeRequest;
use App\Models\ProjectStatus;
use App\Models\Process;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // --- Summary counts ---
        $projectsCount = Project::count();
        $usersCount = User::count();
        $processesCount = \DB::table('processes')->count();
    
        $hour = Carbon::now()->hour;
        switch (time()) {
            case time() >= strtotime('05:00') && time() < strtotime('12:00'):
                $greeting = 'Guten Morgen';
                break;
            case time() >= strtotime('12:00') && time() < strtotime('18:00'):
                $greeting = 'Guten Tag';
                break;
            case time() >= strtotime('18:00') && time() < strtotime('22:00'):
                $greeting = 'Guten Abend';
                break;
            default:
                $greeting = 'Willkommen zurück';
        }
    
        // --- Recent Projects (last 5) ---
        $recentProjects = Project::orderBy('start_time', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                return (object) [
                    'project_name' => $project->project_name,
                    'start_time' => $project->start_time,
                    'end_time' => $project->end_time,
                    'status' => $project->status?->name ?? 'unknown',
                ];
            });
    
        // --- Projects Chart ---
        $projectLabels = Project::latest()
            ->take(5)
            ->pluck('project_name')
            ->map(function ($name) {
                return str_replace(['225054_', '225055_', '225056_'], '', $name); // optional cleanup
            })
            ->toArray();
    
        $projectData = Project::latest()
            ->take(5)
            ->withCount('processes')
            ->pluck('processes_count')
            ->toArray();
    
        // --- Users Chart (registrations per day for last 7 days) ---
        $userLabels = [];
        $userData = [];
        $start = Carbon::today()->subDays(6);
    
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $userLabels[] = $date->format('d M');
            $userData[] = User::whereDate('created_at', $date)->count();
        }
    
        // --- Activity Summary (last 10 days) ---
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(9);
    
        $mostActiveMachine = $this->getMostActiveMachine($startDate, $endDate);
        $mostActiveUser = $this->getMostActiveUser($startDate, $endDate);
    
        // --- Lagers / Materials summary ---
        // NOTE: adjust model/column names below to match your actual schema
        // (Material, MaterialInstance, Lager) — these are my best guess based
        // on the warehouse module you described (min/reorder level per material,
        // current stock derived from summed material_instances).
        $lagersCount = Lager::count();
        $materialsCount = Material::count();
    
        $lowStockQuery = function ($query) {
            $query->whereRaw('quantity <= COALESCE(threshold, 0)');
        };
    
        $lowStockMaterials = Material::where($lowStockQuery)
            ->orderBy('quantity')
            ->take(5)
            ->get();
    
        $lowStockCount = Material::where($lowStockQuery)->count();

        // --- Machine Utilization Heatmap (last 10 days, hourly buckets) ---
        $heatmapStart = Carbon::now()->subDays(9)->startOfDay();
        $heatmapEnd = Carbon::now()->endOfDay();
        $utilizationHeatmap = $this->getMachineUtilizationHeatmap($heatmapStart, $heatmapEnd);
        // $utilizationHeatmap = $this->getMockUtilizationHeatmap();

        // --- Pending Time Change Requests ---
        $pendingTimeChangeRequestsCount = TimeChangeRequest::whereNull('status')->count();
        $pendingTimeChangeRequests = TimeChangeRequest::with('requestedBy')
            ->whereNull('status')
            ->latest()
            ->take(5)
            ->get();

        // --- Project Status Distribution ---
        $projectStatusDistribution = ProjectStatus::withCount('projects')->get();

        // --- Overdue / At-Risk Projects ---
        $now = Carbon::now();
        $overdueAndAtRiskProjects = Project::with('status')
            ->whereNotNull('end_time')
            ->whereHas('status', fn ($q) => $q->where('name', '!=', 'Abgeschlossen'))
            ->where('end_time', '<=', $now->copy()->addDays(7))
            ->orderBy('end_time', 'asc')
            ->take(8)
            ->get();

        // --- Upcoming Deadlines (14 days) ---
        $upcomingDeadlines = Project::with('status')
            ->whereNotNull('end_time')
            ->whereHas('status', fn ($q) => $q->where('name', '!=', 'Abgeschlossen'))
            ->whereBetween('end_time', [$now, $now->copy()->addDays(14)])
            ->orderBy('end_time', 'asc')
            ->take(8)
            ->get();
    
        return view('admin.home.index', compact(
            'projectsCount', 'usersCount', 'processesCount', 'recentProjects',
            'projectLabels', 'projectData', 'userLabels', 'userData', 'greeting',
            'mostActiveMachine', 'mostActiveUser', 'lagersCount', 'materialsCount',
            'lowStockMaterials', 'lowStockCount',
            'utilizationHeatmap', 'pendingTimeChangeRequestsCount', 'pendingTimeChangeRequests',
            'projectStatusDistribution', 'overdueAndAtRiskProjects', 'upcomingDeadlines'
        ));
    }

    private function getMockUtilizationHeatmap(): array
    {
        $machines = ['CNC Fräse 1', 'CNC Fräse 2', 'Säge', 'Kantenanleimer', 'Bohrwerk'];
        $data = [];
        foreach ($machines as $m) {
            $row = [];
            for ($h = 0; $h < 24; $h++) {
                $row[] = ($h >= 7 && $h <= 17) ? round(mt_rand(0, 100) / 100 * 4, 2) : 0;
            }
            $data[] = $row;
        }
        return ['machines' => $machines, 'data' => $data];
    }

    /**
     * Build a [machine][hour] => hours-run matrix for the given date range,
     * with ProcessPause intervals subtracted from each Process's duration.
     */
    private function getMachineUtilizationHeatmap(Carbon $startDate, Carbon $endDate): array
    {
        $processes = Process::with(['machine:id,name', 'pauses'])
            ->whereNotNull('machine_id')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get();

        $machineNames = [];
        $matrix = []; // [machine_id][hour] = seconds

        foreach ($processes as $process) {
            $machine = $process->machine;
            if (!$machine) continue;

            $start = Carbon::parse($process->start_time);
            $end = Carbon::parse($process->end_time);
            if ($end->lte($start)) continue;

            $machineNames[$machine->id] = $machine->name;

            $busyIntervals = [[$start->copy(), $end->copy()]];
            foreach ($process->pauses as $pause) {
                if (!$pause->pause_start || !$pause->pause_end) continue;
                $busyIntervals = $this->subtractInterval(
                    $busyIntervals,
                    Carbon::parse($pause->pause_start),
                    Carbon::parse($pause->pause_end)
                );
            }

            foreach ($busyIntervals as [$s, $e]) {
                $this->distributeToHourBuckets($matrix, $machine->id, $s, $e);
            }
        }

        if (empty($matrix)) {
            return ['machines' => [], 'data' => []];
        }

        $totals = [];
        foreach ($matrix as $machineId => $hours) {
            $totals[$machineId] = array_sum($hours);
        }
        arsort($totals);

        $machines = [];
        $data = [];
        foreach ($totals as $machineId => $total) {
            $machines[] = $machineNames[$machineId];
            $row = [];
            for ($h = 0; $h < 24; $h++) {
                $row[] = round(($matrix[$machineId][$h] ?? 0) / 3600, 2);
            }
            $data[] = $row;
        }

        return ['machines' => $machines, 'data' => $data];
    }

    private function subtractInterval(array $intervals, Carbon $pauseStart, Carbon $pauseEnd): array
    {
        $result = [];
        foreach ($intervals as [$s, $e]) {
            if ($pauseEnd->lte($s) || $pauseStart->gte($e)) {
                $result[] = [$s, $e];
                continue;
            }
            if ($pauseStart->gt($s)) {
                $result[] = [$s, $pauseStart->copy()];
            }
            if ($pauseEnd->lt($e)) {
                $result[] = [$pauseEnd->copy(), $e];
            }
        }
        return $result;
    }

    private function distributeToHourBuckets(array &$matrix, int $machineId, Carbon $start, Carbon $end): void
    {
        $cursor = $start->copy();
        while ($cursor->lt($end)) {
            $hour = (int) $cursor->format('G');
            $nextHourBoundary = $cursor->copy()->minute(0)->second(0)->addHour();
            $segmentEnd = $nextHourBoundary->lt($end) ? $nextHourBoundary : $end->copy();
            
            $seconds = $cursor->diffInSeconds($segmentEnd, true);
            $matrix[$machineId][$hour] = ($matrix[$machineId][$hour] ?? 0) + $seconds;
            $cursor = $segmentEnd;
        }
    }

    /**
     * Get the most active machine in the last N days
     */
    private function getMostActiveMachine($startDate, $endDate)
    {
        $records = TimeRecord::with('machine')
            ->where('machine_id', '!=', null)
            ->whereBetween('start_time', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $machineHours = [];
        foreach ($records as $record) {
            $machine = $record->machine;
            if (!$machine) continue;

            $machineId = $machine->id;
            $machineName = $machine->name;

            if (!isset($machineHours[$machineId])) {
                $machineHours[$machineId] = [
                    'machine' => $machine,
                    'hours' => 0,
                ];
            }

            $duration = $this->calculateDuration($record->start_time, $record->end_time);
            $machineHours[$machineId]['hours'] += $duration;
        }

        if (empty($machineHours)) {
            return null;
        }

        uasort($machineHours, function ($a, $b) {
            return $b['hours'] <=> $a['hours'];
        });

        $topMachine = reset($machineHours);
        return (object) [
            'machine' => $topMachine['machine'],
            'hours' => $topMachine['hours'],
        ];
    }

    /**
     * Get the most active user in the last N days
     */
    private function getMostActiveUser($startDate, $endDate)
    {
        $records = TimeRecord::with('user')
            ->whereNotNull('user_id')
            ->whereBetween('start_time', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $userHours = [];
        foreach ($records as $record) {
            $user = $record->user;
            if (!$user) continue;

            $userId = $user->id;

            if (!isset($userHours[$userId])) {
                $userHours[$userId] = [
                    'user' => $user,
                    'hours' => 0,
                ];
            }

            $duration = $this->calculateDuration($record->start_time, $record->end_time);
            $userHours[$userId]['hours'] += $duration;
        }

        if (empty($userHours)) {
            return null;
        }

        uasort($userHours, function ($a, $b) {
            return $b['hours'] <=> $a['hours'];
        });

        $topUser = reset($userHours);
        return (object) [
            'user' => $topUser['user'],
            'hours' => $topUser['hours'],
        ];
    }

    /**
     * Calculate duration in hours
     */
    private function calculateDuration($startTime, $endTime)
    {
        if (!$endTime) {
            return 0;
        }

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return $start->diffInMinutes($end) / 60;
    }

    public function markRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return response()->json(['success' => true]);
    }

    public function deleteNotification($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function search(Request $request)
    {
        $keyword = $request->input('keyword');

        // Perform the search
        $results = Project::where('project_name', 'LIKE', "%{$keyword}%")
            ->orWhereHas('processes', function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->orWhereHas('status', function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            })
            ->get();
        // $results = Post::where('title', 'LIKE', "%{$keyword}%")
        //    ->orWhere('content', 'LIKE', "%{$keyword}%")
        //    ->get();

        // Return the view with results and the keyword used
        return view('admin.home.search', compact('results', 'keyword'));
    }
}
