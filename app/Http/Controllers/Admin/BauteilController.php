<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bauteil;
use App\Models\BauteilMeasurement;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BauteilController extends Controller
{
    public function index()
    {
        $bauteile = Bauteil::with('children')->whereNull('parent_id')->get();

        return view('admin.bauteile.index', compact('bauteile'));
    }

    public function filter($type)
    {
        if ($type === 'werkzeug') {
            $bauteile = Bauteil::where('is_werkzeug', true)
                ->whereNull('parent_id')
                ->with('children')
                ->get();
        } elseif ($type === 'baugruppe') {
            $bauteile = Bauteil::where('is_baugruppe', true)
                ->with('children')
                ->get();
        } else {
            $bauteile = Bauteil::whereNull('parent_id')->with('children')->get();
        }

        return view('admin.bauteile.index', compact('bauteile'));
    }

    public function create(Request $request)
    {
        $projects = Project::all();
        $bauteile = Bauteil::all();
        $selectedProject = Project::find($request->query('project_id'));

        return view('admin.bauteile.create', compact('projects', 'bauteile', 'selectedProject'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'is_werkzeug' => 'boolean',
            'is_baugruppe' => 'boolean',
            'in_house_production' => 'boolean',
            'parent_id' => 'nullable|exists:bauteile,id',
            'image' => 'nullable|image|max:2048',
            'height' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'thickness' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('bauteile', 'public');
        }

        $Bauteil = Bauteil::create($validated);

        $measurement = BauteilMeasurement::create([
            'bauteil_id' => $Bauteil->id,
            'height' => $request->height,
            'width' => $request->width,
            'weight' => $request->weight,
            'depth' => $request->depth,
            'thickness' => $request->thickness,
            'radius' => $request->radius,
            'unit' => $request->unit,
        ]);

        return redirect()->route('admin.bauteile.index')->with('success', 'Bauteil erfolgreich erstellt.');
    }

    public function edit($bauteil)
    {
        $bauteil = Bauteil::findOrFail($bauteil);
        $projects = Project::all();
        $bauteile = Bauteil::where('id', '!=', $bauteil->id)->get();

        return view('admin.bauteile.edit', compact('bauteil', 'projects', 'bauteile'));
    }

    public function update(Request $request, $bauteil)
    {
        $bauteil = Bauteil::findOrFail($bauteil);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'is_werkzeug' => 'boolean',
            'is_baugruppe' => 'boolean',
            'in_house_production' => 'boolean',
            'parent_id' => 'nullable|exists:bauteile,id',
            'image' => 'nullable|image|max:2048',
            'height' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'thickness' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('image')) {
            if ($bauteil->image) {
                Storage::disk('public')->delete($bauteil->image);
            }
            $validated['image'] = $request->file('image')->store('bauteile', 'public');
        }

        $bauteil->update($validated);

        // Update measurement
        if ($bauteil->measurement) {
            $bauteil->measurement->update([
                'height' => $request->height,
                'width' => $request->width,
                'weight' => $request->weight,
                'depth' => $request->depth,
                'thickness' => $request->thickness,
                'radius' => $request->radius,
                'unit' => $request->unit,
            ]);
        }

        return redirect()->route('admin.bauteile.index')->with('success', 'Bauteil erfolgreich aktualisiert.');
    }

    public function destroy($bauteil)
    {
        $bauteil = Bauteil::findOrFail($bauteil);
        if ($bauteil->image) {
            Storage::disk('public')->delete($bauteil->image);
        }
        $bauteil->delete();

        return redirect()->route('admin.bauteile.index')->with('success', 'Bauteil erfolgreich gelöscht.');
    }

    public function show($bauteil)
    {
        $bauteil = Bauteil::findOrFail($bauteil);

        return view('admin.bauteile.show', compact('bauteil'));
    }
}
