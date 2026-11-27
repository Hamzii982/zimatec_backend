<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Project;
use App\Models\ProjectService;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Project $project)
    {
        $positions = $project->positions()->with('projectService')->get();

        return view('admin.projects.positions.index', compact('project', 'positions'));
    }

    public function create(Project $project)
    {
        $services = ProjectService::all();

        return view('admin.projects.positions.create', compact('project', 'services'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_service_id' => 'nullable|exists:project_services,id',
        ]);

        $project->positions()->create($validated);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Position erfolgreich erstellt!');
    }

    public function edit(Project $project, Position $position)
    {
        $services = ProjectService::all();

        return view('admin.projects.positions.edit', compact('project', 'position', 'services'));
    }

    public function update(Request $request, Project $project, Position $position)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_service_id' => 'nullable|exists:project_services,id',
        ]);

        $position->update($validated);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Position erfolgreich aktualisiert!');
    }

    public function destroy(Project $project, Position $position)
    {
        $position->delete();

        return back()->with('success', 'Position erfolgreich gelöscht!');
    }
}
