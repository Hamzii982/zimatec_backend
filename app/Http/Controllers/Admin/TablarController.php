<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use App\Models\MaterialConsumption;

class TablarController extends Controller
{
    public function index()
    {
        $materials = Material::orderBy('name')->get();

        $maxQuantity = $materials->max('quantity') ?? 0;

        return view('admin.tablar.index', compact('materials', 'maxQuantity'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'tablar' => 'nullable|string|max:50',
            'threshold' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:100',
        ]);

        $material = Material::create($data);

        return response()->json($material);
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'tablar' => 'nullable|string|max:50',
            'threshold' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:100',
        ]);

        $material->update($data);

        return response()->json($material);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();

        return response()->json(['success' => true]);
    }

    public function overview()
    {
        // =========================
        // STOCK METRICS
        // =========================

        $totalMaterials = Material::count();

        $lowStockMaterials = Material::whereColumn('quantity', '<=', DB::raw('COALESCE(threshold, 20)'))
            ->orderBy('quantity')
            ->get();

        $highestStock = Material::orderByDesc('quantity')
            ->take(10)
            ->get();

        $lowestStock = Material::orderBy('quantity')
            ->where('quantity', '>', 0)
            ->take(10)
            ->get();

        // =========================
        // USAGE ANALYTICS
        // =========================

        $topUsed10Days = MaterialConsumption::select(
                'material_id',
                DB::raw('SUM(quantity) as total_used')
            )
            ->where('created_at', '>=', now()->subDays(10))
            ->groupBy('material_id')
            ->orderByDesc('total_used')
            ->with('material')
            ->take(10)
            ->get();

        $topUsed30Days = MaterialConsumption::select(
                'material_id',
                DB::raw('SUM(quantity) as total_used')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('material_id')
            ->orderByDesc('total_used')
            ->with('material')
            ->take(10)
            ->get();

        // =========================
        // RECENT ACTIVITY (AUDIT)
        // =========================

        $recentLogs = MaterialConsumption::with('material')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        // =========================
        // DISTRIBUTION BY SLOTS (TABLAR USAGE)
        // =========================

        $shelfActivity = MaterialConsumption::select(
                'materials.tablar',
                DB::raw('SUM(material_consumption.quantity) as total_used')
            )
            ->join('materials', 'materials.id', '=', 'material_consumption.material_id')
            ->groupBy('materials.tablar')
            ->orderByDesc('total_used')
            ->get();

        // =========================
        // RETURN VIEW
        // =========================

        return view('admin.tablar.overview', compact(
            'totalMaterials',
            'lowStockMaterials',
            'highestStock',
            'lowestStock',
            'topUsed10Days',
            'topUsed30Days',
            'recentLogs',
            'shelfActivity'
        ));
    }
}
