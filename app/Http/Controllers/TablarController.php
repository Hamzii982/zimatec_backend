<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\MaterialConsumption;

class TablarController extends Controller
{
    public function index()
    {
        $materials = Material::orderBy('name')->get();

        // Map to match existing frontend structure (name, quantity, shelf)
        $mapped = $materials->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'quantity' => $m->quantity,
                'shelf' => $m->tablar,
            ];
        });

        $columns = $mapped->chunk(ceil($mapped->count() / 2));
        $flatList = $mapped->values();

        return view('user.tablar.index', compact('columns', 'flatList'));
    }

    /**
     * Record material consumption (real-time, concurrency-safe)
     */
    public function consume(Request $request)
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $material = DB::transaction(function () use ($data) {
            $material = Material::lockForUpdate()->findOrFail($data['material_id']);

            if ($material->quantity < $data['quantity']) {
                abort(400, 'Nicht genügend Bestand');
            }

            $material->decrement('quantity', $data['quantity']);

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
            ]);

            // 🔥 Determine threshold
            $threshold = $material->threshold ?? 20;

            // 🔥 Check low stock AFTER decrement
            if ($material->quantity <= $threshold) {

                // Optional: prevent duplicate spam notifications
                $alreadyExists = Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%' . $material->name . '%')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$alreadyExists) {
                    Notification::create([
                        'user_id' => null,
                        'type'    => 'low_stock',
                        'message' => "{$material->name} ist im Lager fast leer. Bitte nachbestellen.",
                        'url'     => route('admin.tablar.index'), // adjust if needed
                    ]);
                }
            }

            return $material->fresh();
        });

        return response()->json([
            'success' => true,
            'new_quantity' => $material->quantity,
        ]);
    }
}
