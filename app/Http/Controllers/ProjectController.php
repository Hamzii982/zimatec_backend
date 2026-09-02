<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Traits\HandleMachineLogs;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use HandleMachineLogs;

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
            ->orderByDesc('start_time')
            ->get();

        return view('user.projects.index', compact('projects'));

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
