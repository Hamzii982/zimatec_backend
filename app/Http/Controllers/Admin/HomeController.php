<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Notification;

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
        case (time() >= strtotime('05:00') && time() < strtotime('12:00')):
            $greeting = 'Guten Morgen';
            break;
        case (time() >= strtotime('12:00') && time() < strtotime('18:00')):
            $greeting = 'Guten Tag';
            break;
        case (time() >= strtotime('18:00') && time() < strtotime('22:00')):
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
                return (object)[
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

        return view('admin.home.index', compact(
            'projectsCount',
            'usersCount',
            'processesCount',
            'recentProjects',
            'projectLabels',
            'projectData',
            'userLabels',
            'userData',
            'greeting'
        ));
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
            'success' => true
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
