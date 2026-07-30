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
    
        return view('admin.home.index', compact(
            'projectsCount',
            'usersCount',
            'processesCount',
            'recentProjects',
            'projectLabels',
            'projectData',
            'userLabels',
            'userData',
            'greeting',
            'mostActiveMachine',
            'mostActiveUser',
            'lagersCount',
            'materialsCount',
            'lowStockMaterials',
            'lowStockCount'
        ));
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
