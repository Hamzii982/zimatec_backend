<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index()
    {
        $projects = Project::with('status')
            ->withCount('positions')
            ->withCount('bauteile')
            ->withMax('fraesdata', 'created_at')
            ->orderByDesc('start_time')
            ->get();
        $statuses = ProjectStatus::query()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return view('user.projects.index', compact('projects', 'statuses'));

    }

    public function projectLogs(Request $request)
    {
        $data = $this->getMachineLogs($request);

        return view('user.projects.logs', $data);
    }

    public function parseLog()
    {
        $file = storage_path('app/public/logs/LOGFILE.OLD');

        return $this->parseMachineLogs($file);
    }
}
