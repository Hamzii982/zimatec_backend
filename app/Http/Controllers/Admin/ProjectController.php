<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Services\Workflow\WorkflowService;
use App\Traits\HandleFiles;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use HandleFiles;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index(Request $request)
    {
        // 1. Build the base query with eager loading
        $query = Project::with(['status', 'positions', 'bauteile']);

        // 2. Filter by Status if selected
        if ($request->filled('status_id')) {
            $query->where('project_status_id', $request->status_id);
        }

        // 3. Search by Name or Order Numbers (ZT / ZF)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                ->orWhere('auftragsnummer_zt', 'like', "%{$search}%")
                ->orWhere('auftragsnummer_zf', 'like', "%{$search}%");
            });
        }

        // 4. Get the filtered projects
        $projects = $query->latest()->get();

        // 5. Fetch all statuses to populate the filter dropdown element
        $statuses = ProjectStatus::all(); 

        return view('admin.projects.index', compact('projects', 'statuses'));

    }

    public function create()
    {
        $statuses = ProjectStatus::all();

        return view('admin.projects.create', compact('statuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kunde' => 'nullable|string|max:255',
            'auftragsnummer_zt' => 'nullable|string|max:255|unique:projects,auftragsnummer_zt',
            'auftragsnummer_zf' => 'nullable|string|max:255|unique:projects,auftragsnummer_zf',
            'project_name' => 'required|string|max:255',
            'project_status_id' => 'required|exists:project_statuses,id',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'attach_to_workflow' => 'sometimes|boolean',
        ]);

        $projectData = $request->only([
            // 'kunde',
            'auftragsnummer_zt',
            'auftragsnummer_zf',
            'project_name',
            'project_status_id',
            'start_time',
            'end_time',
        ]);

        $projectData['from_machine_logs'] = 0;

        if ($request->has('save_to_db')) {
            $project = Project::createOrFirst($projectData);

            if ($request->boolean('attach_to_workflow') && $project) {
                try {
                    app(WorkflowService::class)->attachProject($project, null, $request->user());
                } catch (\RuntimeException $e) {
                    // No active workflow stages configured — silently skip; the admin
                    // can attach the project manually via Admin\Workflow\AssignmentController.
                    \Log::warning('Workflow auto-attach skipped for project '.$project->id.': '.$e->getMessage());
                }
            }
        }

        // Create folder structure
        // $this->createProject($request->kunde, $request->auftragsnummer_zt, $request->project_name);
        return redirect()->route('admin.projects')->with('success', 'Project created successfully!');
    }

    public function createProject($kunde = 'KundeName', $auftragsnummer = '12345', $projekt = 'ProjektName')
    {
        // Base path inside storage/app
        $basePath = storage_path("app/{$kunde}/{$auftragsnummer}_{$projekt}");

        // Define folder structure
        $folders = [
            $basePath.'/001_Historie',
            $basePath.'/01_Eingangsdaten/Schriftliche_Freigabe',
            $basePath.'/02_Arbeitsverzeichnis_In Arbeit/Teile_Bezeichnung',
            $basePath.'/03_Ausgangsdaten',
            $basePath.'/04_Fraesdaten',
            $basePath.'/05_CAD-Daten zum Messen',
            $basePath.'/06_Messberichte',
            $basePath.'/07_Dokumentation',
            $basePath.'/08_Temp/Bauteil 1/Bearbeitungsplan',
            $basePath.'/08_Temp/Bauteil 1/Iges',
            $basePath.'/08_Temp/Bauteil 1/NC-Prg',

        ];

        for ($i = 1; $i <= 10; $i++) {
            $bauteil_dir = $basePath.'/04_Fraesdaten/Bauteil '.$i;
            array_push($folders, $bauteil_dir.'/Bearbeitungsplan');
            array_push($folders, $bauteil_dir.'/Iges');
            array_push($folders, $bauteil_dir.'/NC-Prg');
        }

        // Create folders
        foreach ($folders as $folder) {
            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
        }

        $createFiles = [
            [
                'name' => $basePath.'/001_Historie//'.$auftragsnummer.'_'.$projekt.'_historie',
                'type' => 'xlsx',
            ],
            [
                'name' => $basePath.'/07_Dokumentation//'.$auftragsnummer.'_'.$projekt.'_Bezeichnung_Projektplan',
                'type' => 'xlsx',
            ],
            [
                'name' => $basePath.'/07_Dokumentation/Einzelteilübersicht_Vorlage_'.Carbon::now()->format('d-m-Y'),
                'type' => 'xlsx',
            ],
            [
                'name' => $basePath.'/07_Dokumentation/Dokumentation_TZ_1',
                'type' => 'ppt',
            ],
            [
                'name' => $basePath.'/02_Arbeitsverzeichnis_In Arbeit/0_StandartNotiz_Name',
                'type' => 'txt',
            ],
        ];

        // Create folders
        foreach ($createFiles as $file) {
            $filename = $file['name'];
            $type = $file['type'];
            $data = null;
            if (! file_exists($filename)) {
                $this->createNewFile($filename, $type, $data);
            }
        }

        return $basePath;
    }

    public function edit(Project $project)
    {
        $statuses = ProjectStatus::all();

        return view('admin.projects.edit', compact('project', 'statuses'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'kunde' => 'nullable|string|max:255',
            'auftragsnummer_zt' => 'nullable|string|max:255|unique:projects,auftragsnummer_zt,'.$project->id,
            'auftragsnummer_zf' => 'nullable|string|max:255|unique:projects,auftragsnummer_zf,'.$project->id,
            'project_name' => 'required|string|max:255',
            'project_status_id' => 'required|exists:project_statuses,id',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $project->update($request->only([
            'kunde',
            'auftragsnummer_zt',
            'auftragsnummer_zf',
            'project_name',
            'project_status_id',
            'start_time',
            'end_time',
        ]));

        return redirect()->route('admin.projects')->with('success', 'Project updated successfully!');
    }

    public function show(Project $project)
    {
        $project->load(['status', 'bauteile']);

        return view('admin.projects.show', compact('project'));
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('admin.projects')->with('success', 'Project deleted successfully!');
    }
}
