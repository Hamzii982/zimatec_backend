<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialThresholdController extends Controller
{
    /**
     * Display all materials with their low-stock thresholds.
     */
    public function index()
    {
        $materials = Material::orderBy('name')->get();

        return view('admin.settings.material-thresholds', compact('materials'));
    }

    /**
     * Show the threshold-edit form for a single material.
     */
    public function show($id)
    {
        $material = Material::findOrFail($id);

        return view('admin.settings.material-threshold-show', compact('material'));
    }

    /**
     * Update the threshold for a single material.
     *
     * Threshold semantics (see Material::scopeLowStock):
     *   null  -> no threshold / never low-stock
     *   0     -> no threshold (same as null)
     *   >=1   -> trigger warning when
     *             (quantity + on_hold_quantity + order_quantity) <= threshold
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'threshold' => 'nullable|integer|min:0',
        ]);

        $material = Material::findOrFail($id);
        $material->threshold = $data['threshold'] ?: null;
        $material->save();

        return redirect()
            ->route('admin.settings.material-thresholds')
            ->with('success', "Schwellenwert für {$material->name} aktualisiert.");
    }

    /**
     * Clear the threshold for a material (set to null).
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->threshold = null;
        $material->save();

        return redirect()
            ->route('admin.settings.material-thresholds')
            ->with('success', "Schwellenwert für {$material->name} entfernt.");
    }
}
