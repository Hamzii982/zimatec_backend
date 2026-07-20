<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lager;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TablarController extends Controller
{
    public function index(Request $request, int $lager_id)
    {
        $lager = Lager::findOrFail($lager_id);

        $query = Material::with('suppliers')
            ->where('lager_id', $lager_id)
            ->orderBy('name');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }
        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->code.'%');
        }
        if ($request->filled('shelf')) {
            $query->where('tablar', 'like', '%'.$request->shelf.'%');
        }
        if ($request->filled('max_qty')) {
            $query->where('quantity', '<=', $request->max_qty);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }
        if ($request->boolean('empty')) {
            $query->empty();
        }
        if ($request->filled('status') && in_array($request->status, ['notified', 'ordered', 'blocked', 'delivered'], true)) {
            $query->forStatus($request->status);
        }

        // Deep-link resolver: when both `name` and `page` are set (e.g. coming back
        // from a show page), jump to the page where the material actually lives.
        if ($request->filled('name') && $request->filled('page')) {
            $unpaginated = (clone $query)->get();
            $targetId = $request->integer('id') ?: null;
            if (! $targetId) {
                $target = $unpaginated->firstWhere('name', $request->name)
                    ?? $unpaginated->first(fn ($m) => str_contains($m->name, $request->name));
                $targetId = $target?->id;
            }
            if ($targetId) {
                $offset = $unpaginated->search(fn ($m) => $m->id === $targetId);
                $targetPage = $offset === false ? 1 : (int) (intdiv((int) $offset, 30) + 1);
                $currentPage = (int) $request->input('page', 1);
                if ($targetPage !== $currentPage) {
                    $params = $request->except(['page']);
                    $params['page'] = $targetPage;
                    $params['highlight'] = $targetId;
                    $params['name'] = $request->name;
                    $params['id'] = $targetId;

                    return redirect()->to(
                        route('admin.tablar.index', ['lager_id' => $lager_id])
                        .'?'.http_build_query($params)
                        .'#material-'.$targetId
                    );
                }
            }
        }

        $materials = $query->paginate(30)->withQueryString();
        $maxQuantity = Material::where('lager_id', $lager_id)->max('quantity') ?? 0;

        return view('admin.tablar.index', compact('materials', 'maxQuantity', 'lager'));
    }

    public function show(Request $request, int $lager_id, int $id)
    {
        $lager = Lager::findOrFail($lager_id);
        $material = Material::where('lager_id', $lager_id)
            ->with(['suppliers' => fn ($q) => $q->orderByDesc('material_suppliers.created_at')])
            ->findOrFail($id);

        $recentSupplier = $material->suppliers->first();

        $supplierListUrl = $recentSupplier
            ? route('admin.tablar.supplier-list', [
                'lager_id' => $lager_id,
                'id' => $id,
                'supplier' => $recentSupplier->id,
            ])
            : null;

        $backToListUrl = route('admin.tablar.index', [
            'lager_id' => $lager_id,
            'name' => $material->name,
            'id' => $material->id,
            'page' => (int) $request->input('page', 1),
        ]);

        $logs = MaterialConsumption::where('material_id', $material->id)
            ->orderByDesc('consumption_time')
            ->paginate(10, ['*'], 'logs_page')
            ->withQueryString();

        return view('admin.tablar.show', compact('material', 'lager', 'recentSupplier', 'supplierListUrl', 'backToListUrl', 'logs'));
    }

    public function updateQuantity(Request $request, int $lager_id, int $id)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:0',
            'reason' => 'nullable|in:add,audit',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id, $id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($id);

            $oldQuantity = $material->quantity;
            $newQuantity = $data['quantity'];
            $delta = $newQuantity - $oldQuantity;

            $material->quantity = $newQuantity;
            $material->save();

            if ($delta !== 0) {
                $type = ($data['reason'] ?? null) === 'audit'
                    ? 'adjust'
                    : ($delta > 0 ? 'restock' : 'adjust');

                MaterialConsumption::create([
                    'material_id' => $material->id,
                    'quantity' => abs($delta),
                    'consumption_type' => $type,
                    'consumption_time' => now(),
                ]);
            }

            return $material;
        });

        return response()->json([
            'success' => true,
            'quantity' => $material->quantity,
            'status' => $material->status,
        ]);
    }

    public function updateStatus(Request $request, int $lager_id, int $id)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($id);

        $data = $request->validate([
            'order_status' => 'nullable|in:notified,ordered,blocked,delivered',
            'order_quantity' => 'nullable|integer|min:1|required_if:order_status,ordered',
        ]);

        $newStatus = $data['order_status'] ?? null;

        DB::transaction(function () use ($material, $newStatus, $data) {
            $locked = Material::where('id', $material->id)->lockForUpdate()->first();

            if ($newStatus === 'ordered') {
                // Store how many units were ordered; not yet in stock.
                $locked->order_quantity = $data['order_quantity'];
            } elseif ($newStatus === 'delivered') {
                // Merge the previously ordered quantity into actual stock.
                $orderedAmount = $locked->order_quantity ?? 0;
                if ($orderedAmount > 0) {
                    $locked->quantity += $orderedAmount;

                    // NEW: log the delivery
                    MaterialConsumption::create([
                        'material_id' => $locked->id,
                        'quantity' => $orderedAmount,
                        'consumption_type' => 'delivery',
                        'consumption_time' => now(),
                    ]);
                }
                $locked->order_quantity = 0;
            } else {
                // notified / blocked / cleared — no pending order quantity
                $locked->order_quantity = 0;
            }

            $locked->order_status = $newStatus;
            $locked->save();
        });

        $material->refresh();

        return response()->json([
            'success' => true,
            'order_status' => $material->order_status,
            'status_label' => $material->status_label,
            'order_quantity' => $material->order_quantity,
            'quantity' => $material->quantity,
        ]);
    }

    public function supplierList(Request $request, int $lager_id, int $id)
    {
        $data = $request->validate([
            'supplier' => 'required|integer|exists:suppliers,id',
        ]);

        $lager = Lager::findOrFail($lager_id);
        $supplier = Supplier::findOrFail($data['supplier']);

        $materials = Material::where('lager_id', $lager_id)
            ->whereHas('suppliers', fn ($q) => $q->where('suppliers.id', $data['supplier']))
            ->with(['suppliers' => fn ($q) => $q->where('suppliers.id', $data['supplier'])])
            ->orderBy('name')
            ->get();

        $materials->each(function ($m) use ($data) {
            $pivot = $m->suppliers->firstWhere('id', $data['supplier'])?->pivot;
            $m->setAttribute('pivot_attached_at', $pivot?->created_at);
        });

        return view('admin.tablar.supplier-list', compact('lager', 'supplier', 'materials'));
    }

    public function store(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        // Normalize empty strings to null before validation
        $request->merge([
            'code' => $request->input('code') ?: null,
            'threshold' => $request->input('threshold') ?: null,
            'type' => $request->input('type') ?: null,
            'order_status' => $request->input('order_status') ?: null,
            'description' => $request->input('description') ?: null,
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:64', Rule::unique('materials', 'code')->whereNotNull('code')],
            'description' => 'nullable|string|max:2000',
            'quantity' => 'required|integer|min:0',
            'tablar' => 'nullable|string|max:50',
            'threshold' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:100',
            'order_status' => 'nullable|in:notified,ordered,blocked,delivered',
            'is_werkzeug' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:4096',
        ]);

        $data['lager_id'] = $lager_id;
        $data['is_werkzeug'] = $request->boolean('is_werkzeug');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('materials', 'public');
        }

        $material = Material::create($data);

        // NEW: log initial stock
        if ($material->quantity > 0) {
            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $material->quantity,
                'consumption_type' => 'restock',
                'consumption_time' => now(),
            ]);
        }

        return response()->json($material);
    }

    public function update(Request $request, int $lager_id, int $id)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($id);

        $request->merge([
            'code' => $request->input('code') ?: null,
            'threshold' => $request->input('threshold') ?: null,
            'type' => $request->input('type') ?: null,
            'order_status' => $request->input('order_status') ?: null,
            'description' => $request->input('description') ?: null,
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:64', Rule::unique('materials', 'code')->ignore($material->id)->whereNotNull('code')],
            'description' => 'nullable|string|max:2000',
            'quantity' => 'required|integer|min:0',
            'tablar' => 'nullable|string|max:50',
            'threshold' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:100',
            'order_status' => 'nullable|in:notified,ordered,blocked,delivered',
            'is_werkzeug' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:4096',
        ]);

        $data['lager_id'] = $lager_id;
        $data['is_werkzeug'] = $request->boolean('is_werkzeug');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($material->image) {
                Storage::disk('public')->delete($material->image);
            }
            $data['image'] = $request->file('image')->store('materials', 'public');
        }

        $oldQuantity = $material->quantity;

        $material->update($data);

        // NEW: log the quantity change, if any
        $delta = $material->quantity - $oldQuantity;
        if ($delta !== 0) {
            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => abs($delta),
                'consumption_type' => $delta > 0 ? 'restock' : 'audit_adjust',
                'consumption_time' => now(),
            ]);
        }

        return response()->json($material);
    }

    public function destroy(int $lager_id, int $id)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($id);
        $material->delete();

        return response()->json(['success' => true]);
    }

    public function getSuppliers(int $lager_id, int $id)
    {
        $material = Material::where('lager_id', $lager_id)->with('suppliers')->findOrFail($id);

        $suppliers = $material->suppliers
            ->sortByDesc(fn ($s) => $s->pivot->created_at)
            ->values();

        foreach ($suppliers as $index => $supplier) {
            $supplier->setAttribute('is_current', $index === 0);
        }

        return response()->json($suppliers);
    }

    public function attach(Request $request, int $lager_id, Material $material)
    {
        abort_unless($material->lager_id === $lager_id, 404);
        $material->suppliers()->syncWithoutDetaching([$request->input('supplier_id')]);

        return response()->json(['success' => true]);
    }

    public function detach(int $lager_id, Material $material, Supplier $supplier)
    {
        abort_unless($material->lager_id === $lager_id, 404);
        $material->suppliers()->detach($supplier->id);

        return response()->json(['success' => true]);
    }

    public function overview(int $lager_id)
    {
        $lager = Lager::findOrFail($lager_id);

        $totalMaterials = Material::where('lager_id', $lager_id)->count();

        $lowStockMaterials = Material::where('lager_id', $lager_id)
            ->whereNotNull('threshold')->where('threshold', '>', 0)
            ->whereRaw(
                '(quantity + COALESCE(on_hold_quantity, 0) + COALESCE(order_quantity, 0)) <= threshold'
            )
            ->orderByRaw('(quantity + COALESCE(on_hold_quantity, 0) + COALESCE(order_quantity, 0))')
            ->get();

        $highestStock = Material::where('lager_id', $lager_id)
            ->orderByDesc('quantity')->take(10)->get();

        $lowestStock = Material::where('lager_id', $lager_id)
            ->orderBy('quantity')->where('quantity', '>', 0)->take(10)->get();

        $topUsed10Days = MaterialConsumption::select('material_id', DB::raw('SUM(quantity) as total_used'))
            ->whereHas('material', fn ($q) => $q->where('lager_id', $lager_id))
            ->where('created_at', '>=', now()->subDays(10))
            ->groupBy('material_id')->orderByDesc('total_used')->with('material')->take(10)->get();

        $topUsed30Days = MaterialConsumption::select('material_id', DB::raw('SUM(quantity) as total_used'))
            ->whereHas('material', fn ($q) => $q->where('lager_id', $lager_id))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('material_id')->orderByDesc('total_used')->with('material')->take(10)->get();

        $recentLogs = MaterialConsumption::with('material')
            ->whereHas('material', fn ($q) => $q->where('lager_id', $lager_id))
            ->orderByDesc('created_at')->paginate(10, ['*'], 'audit_page');

        $shelfActivity = MaterialConsumption::select('materials.tablar', DB::raw('SUM(material_consumption.quantity) as total_used'))
            ->join('materials', 'materials.id', '=', 'material_consumption.material_id')
            ->whereHas('material', fn ($q) => $q->where('lager_id', $lager_id))
            ->groupBy('materials.tablar')->orderByDesc('total_used')->get();

        return view('admin.tablar.overview', compact(
            'lager', 'totalMaterials', 'lowStockMaterials', 'highestStock',
            'lowestStock', 'topUsed10Days', 'topUsed30Days', 'recentLogs', 'shelfActivity'
        ));
    }
}
