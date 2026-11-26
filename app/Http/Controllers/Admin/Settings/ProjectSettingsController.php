<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;

class ProjectSettingsController extends Controller
{
    /**
     * Display all Project statuses.
     */
    public function projectStatus()
    {
        $statuses = ProjectStatus::all();

        return view('admin.settings.project-status', compact('statuses'));
    }

    public function projectStatusShow($id = null)
    {
        $status = $id ? ProjectStatus::findOrFail($id) : new ProjectStatus;

        return view('admin.settings.project-status-show', compact('status'));
    }

    /**
     * Update the given Project status.
     */
    public function projectStatusUpdate(Request $request, $id = null)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        if ($id) {
            // Update existing
            $status = ProjectStatus::findOrFail($id);
            $status->update([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'active' => $request->has('active'),
            ]);
            $message = 'Work order status updated successfully!';
        } else {
            // Create new
            $status = ProjectStatus::create([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'active' => $request->has('active'),
            ]);
            $message = 'New work order status created successfully!';
        }

        return redirect()->route('admin.settings.project-status.show', $status->id)
            ->with('success', $message);
    }

    /**
     * Toggle active/inactive.
     */
    public function toggleProjectStatus(Request $request, $id)
    {
        $status = ProjectStatus::findOrFail($id);
        $status->active = ! $status->active;
        $status->save();

        return response()->json(['success' => true, 'active' => $status->active]);
    }

    /**
     * Delete work order status.
     */
    public function deleteProjectStatus($id)
    {
        ProjectStatus::findOrFail($id)->delete();

        return back()->with('success', 'Work order status deleted successfully.');
    }
}
