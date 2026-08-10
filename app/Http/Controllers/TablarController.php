<?php

namespace App\Http\Controllers;

use App\Models\Lager;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MaterialSheet;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function App\Helpers\new_notification;

class TablarController extends Controller
{
    public function lagerSelect()
    {
        $lagers = Lager::withCount('materials')->orderBy('name')->get();

        return view('user.tablar.lager-select', compact('lagers'));
    }

    public function index(int $lager_id)
    {
        $lager = Lager::findOrFail($lager_id);

        $materials = Material::where('lager_id', $lager_id)
            ->orderBy('tablar')
            ->orderBy('name')
            ->get();

        // Only relevant for Holz lager — cheap no-op query result for everyone else.
        $leftoverCounts = [];
        if ($lager->type === 'holz') {
            $leftoverCounts = MaterialSheet::whereIn('material_id', $materials->pluck('id'))
                ->inStock()
                ->cutSheets()
                ->selectRaw('material_id, COUNT(*) as cnt')
                ->groupBy('material_id')
                ->pluck('cnt', 'material_id');
        }

        $flatList = $materials->map(fn ($m) => [
            'id' => $m->id,
            'code' => $m->code,
            'name' => $m->name,
            'description' => $m->description,
            'on_hold_quantity' => $m->on_hold_quantity,
            'order_quantity' => $m->order_quantity,
            'quantity' => $m->quantity,
            'available_total' => $m->available_total,
            'shelf' => $m->tablar,
            'threshold' => $m->threshold,
            'type' => $m->type,
            'unit' => $m->unit,
            'image' => $m->image,
            'order_status' => $m->order_status,
            'status' => $m->status,
            'leftover_sheet_count' => $leftoverCounts[$m->id] ?? 0,
        ])->values();

        $shelves = $materials->pluck('tablar')->unique()->sort()->values();

        $statusTranslations = [
            'notified' => 'Bedarf gemeldet',
            'ordered' => 'Bestellt',
            'blocked' => 'Blockiert',
            'delivered' => 'Geliefert',
        ];

        return view('user.tablar.index', compact('flatList', 'shelves', 'statusTranslations', 'lager'));
    }

    /**
     * Record material consumption (real-time, concurrency-safe)
     */
    public function consume(Request $request, int $lager_id)
    {
        $lager = Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            if ($material->quantity < $data['quantity']) {
                abort(400, 'Nicht genügend Bestand');
            }

            $material->decrement('quantity', $data['quantity']);

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
                'consumption_type' => 'use',
                'consumption_time' => now(),
            ]);

            $threshold = (int) ($material->threshold ?? 0);

            if ($threshold > 0 && $material->available_total <= $threshold) {
                $alreadyExists = Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (! $alreadyExists) {
                    new_notification(
                        type: 'low_stock',
                        message: "{$material->name} ist im Lager fast leer. Bitte nachbestellen.",
                        url: route('admin.tablar.show', ['lager_id' => $lager_id, 'id' => $material->id]),
                    );
                }
            }

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity]);
    }

    /**
     * Record material return (real-time, concurrency-safe)
     */
    public function return(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            $material->increment('quantity', $data['quantity']);

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
                'consumption_type' => 'return',
                'consumption_time' => now(),
            ]);

            $threshold = (int) ($material->threshold ?? 0);

            if ($threshold > 0 && $material->available_total > $threshold) {
                Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->delete();
            }

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity]);
    }

    public function reserve(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            if ($material->quantity < $data['quantity']) {
                abort(400, 'Nicht genügend Bestand für die Reservierung');
            }

            $material->decrement('quantity', $data['quantity']);
            $material->increment('on_hold_quantity', $data['quantity']);

            // NEW: log the reservation for admin visibility
            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => $data['quantity'],
                'consumption_type' => 'reserve',
                'consumption_time' => now(),
            ]);

            return $material->fresh();
        });

        return response()->json(['success' => true, 'new_quantity' => $material->quantity, 'on_hold_quantity' => $material->on_hold_quantity]);
    }

    /**
     * Settle a reservation: part goes back to available stock,
     * the rest is permanently consumed from the on-hold amount.
     */
    public function settleReservation(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'return_quantity' => 'required|integer|min:0',
        ]);

        $material = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            $onHold = $material->on_hold_quantity;

            if ($data['return_quantity'] > $onHold) {
                abort(400, 'Menge übersteigt die reservierte Menge');
            }

            $returned = $data['return_quantity'];
            $consumed = $onHold - $returned;

            // Whole reservation batch is closed out
            $material->decrement('on_hold_quantity', $onHold);

            if ($returned > 0) {
                $material->increment('quantity', $returned);

                MaterialConsumption::create([
                    'material_id' => $material->id,
                    'quantity' => $returned,
                    'consumption_type' => 'return',
                    'consumption_time' => now(),
                ]);
            }

            if ($consumed > 0) {
                MaterialConsumption::create([
                    'material_id' => $material->id,
                    'quantity' => $consumed,
                    'consumption_type' => 'use',
                    'consumption_time' => now(),
                ]);
            }

            $threshold = (int) ($material->threshold ?? 0);

            if ($returned > 0 && $threshold > 0 && $material->available_total > $threshold) {
                Notification::where('type', 'low_stock')
                    ->where('message', 'like', '%'.$material->name.'%')
                    ->whereDate('created_at', now()->toDateString())
                    ->delete();
            }

            return $material->fresh();
        });

        return response()->json([
            'success' => true,
            'new_quantity' => $material->quantity,
            'on_hold_quantity' => $material->on_hold_quantity,
        ]);
    }

    // ─── ORDER REQUEST ───────────────────────────────────────────────────────

    public function orderRequest(int $lager_id, $materialId)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($materialId);

        if ($material->order_status !== null) {
            return response()->json(['message' => 'Bestellung bereits angefragt.'], 400);
        }

        $material->order_status = 'notified';
        $material->save();

        // 2. Check if a notification for this specific order request already exists today
        $alreadyExists = Notification::where('type', 'order_request')
            ->where('message', 'like', '%'.$material->name.'%')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        // 3. Create the notification if it doesn't exist
        if (! $alreadyExists) {

            new_notification(
                type: 'order_request',
                message: "Bestellungsanfrage für {$material->name} im Lager wurde gestellt.",
                url: route('admin.tablar.show', ['lager_id' => $lager_id, 'id' => $material->id]),
            );
        }

        return response()->json([
            'message' => 'Bestellung angefragt.',
            'order_status' => $material->order_status,
        ]);
    }

    public function cancelNotification(int $lager_id, int $id)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($id);

        if ($material->order_status !== 'notified') {
            return response()->json(['message' => 'Nur gemeldete Bedarfsanfragen können storniert werden.'], 400);
        }

        $material->order_status = null;
        $material->order_quantity = 0;
        $material->save();

        return response()->json([
            'success' => true,
            'order_status' => $material->order_status,
            'order_quantity' => $material->order_quantity,
        ]);
    }

    public function confirmDelivery(int $lager_id, int $id)
    {
        $material = DB::transaction(function () use ($lager_id, $id) {
            $material = Material::where('lager_id', $lager_id)->lockForUpdate()->findOrFail($id);

            if (!in_array($material->order_status, ['ordered', 'delivered'], true)) {
                abort(400, 'Bestätigung nur möglich, wenn Material bestellt oder geliefert wurde.');
            }

            $orderedAmount = $material->order_quantity ?? 0;

            if ($orderedAmount > 0) {
                $material->quantity += $orderedAmount;

                MaterialConsumption::create([
                    'material_id' => $material->id,
                    'quantity' => $orderedAmount,
                    'consumption_type' => 'delivery',
                    'consumption_time' => now(),
                ]);
            }

            $material->order_quantity = 0;
            $material->order_status = null; // confirmed & merged into stock — clear the flag
            $material->save();

            return $material;
        });

        return response()->json([
            'success' => true,
            'order_status' => $material->order_status,
            'order_quantity' => $material->order_quantity,
            'quantity' => $material->quantity,
        ]);
    }

    public function sheetOptions(int $lager_id, int $material_id)
    {
        $material = Material::where('lager_id', $lager_id)->findOrFail($material_id);

        if (! $material->isSheetMaterial()) {
            abort(400, 'Dieses Material gehört nicht zum Holzlager.');
        }

        $cutSheets = $material->sheets()->inStock()->cutSheets()->orderBy('sibling_group_id')->orderBy('id')->get();

        $grouped = $cutSheets->groupBy('sibling_group_id');

        $referenceFullSheet = $material->sheets()->inStock()->fullSheets()->first();

        $options = [];

        if ($material->quantity > 0) {
            $options[] = [
                'type' => 'full',
                'sheet_id' => null, // backend picks any available full-sheet row when cutting
                'label' => 'Volle Platte',
                'quantity' => $material->quantity,
                'length_mm' => $referenceFullSheet?->length_mm,
                'width_mm' => $referenceFullSheet?->width_mm,
                'thickness_mm' => $referenceFullSheet?->thickness_mm,
                'axis_choice_available' => (bool) ($referenceFullSheet?->length_mm && $referenceFullSheet?->width_mm),
            ];
        }

        $groupNumber = 0;
        foreach ($grouped as $groupId => $group) {
            $displayNumber = $groupId ? ++$groupNumber : null;

            $group->values()->each(function ($s, $i) use (&$options, $groupId, $group, $displayNumber) {
                $options[] = [
                    'type' => 'cut',
                    'sheet_id' => $s->id,
                    'label' => $s->code,
                    'quantity' => 1,
                    'length_mm' => $s->length_mm,
                    'width_mm' => $s->width_mm,
                    'thickness_mm' => $s->thickness_mm,
                    'sibling_group_id' => $groupId,
                    'sibling_display_number' => $displayNumber,
                    'sibling_position' => $groupId ? ($i + 1).'/'.$group->count() : null,
                    'axis_choice_available' => (bool) ($s->length_mm && $s->width_mm),
                ];
            });
        }

        return response()->json([
            'material_id' => $material->id,
            'options' => $options,
        ]);
    }

    // ─── FIND SHEET BY SIZE ────────────────────────────────────────────────────

    /**
     * Find the smallest in-stock sheet (full or leftover) that fits the requested
     * size in either orientation. Returns null when nothing matches.
     */
    public function findSheetForSize(Request $request, int $lager_id, int $material_id)
    {
        $data = $request->validate([
            'length_mm' => 'required|numeric|min:0.1',
            'width_mm' => 'required|numeric|min:0.1',
        ]);

        $material = Material::where('lager_id', $lager_id)->findOrFail($material_id);

        if (! $material->isSheetMaterial()) {
            abort(400, 'Dieses Material gehört nicht zum Holzlager.');
        }

        $reqL = (float) $data['length_mm'];
        $reqW = (float) $data['width_mm'];

        $row = MaterialSheet::where('material_id', $material->id)
            ->where('status', 'in_stock')
            ->where(function ($q) use ($reqL, $reqW) {
                $q->where(function ($a) use ($reqL, $reqW) {
                    $a->where('length_mm', '>=', $reqL)->where('width_mm', '>=', $reqW);
                })->orWhere(function ($b) use ($reqL, $reqW) {
                    $b->where('length_mm', '>=', $reqW)->where('width_mm', '>=', $reqL);
                });
            })
            ->orderByRaw('(length_mm * width_mm) ASC')
            ->orderBy('id', 'ASC')
            ->first();

        if (! $row) {
            return response()->json([
                'material_id' => $material->id,
                'sheet' => null,
            ]);
        }

        return response()->json([
            'material_id' => $material->id,
            'sheet' => [
                'type' => $row->isFullSheet() ? 'full' : 'cut',
                'sheet_id' => $row->isFullSheet() ? null : $row->id,
                'code' => $row->code,
                'length_mm' => (float) $row->length_mm,
                'width_mm' => (float) $row->width_mm,
                'thickness_mm' => (float) $row->thickness_mm,
                'quantity' => $row->isFullSheet() ? (int) $material->quantity : 1,
                'axis_choice_available' => (bool) ($row->length_mm && $row->width_mm),
            ],
        ]);
    }

    // ─── CUT A SHEET (full or already-cut) ─────────────────────────────────────

    public function cutSheet(Request $request, int $lager_id)
    {
        Lager::findOrFail($lager_id);

        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'sheet_id' => 'nullable|exists:material_sheets,id', // null = take from full-sheet bucket
            'cut_length' => 'required|numeric|min:0.1',
            'cut_width' => 'required|numeric|min:0.1',
            'cut_axis' => 'nullable|string|in:length,width',
        ]);

        $result = DB::transaction(function () use ($data, $lager_id) {
            $material = Material::where('lager_id', $lager_id)
                ->lockForUpdate()
                ->findOrFail($data['material_id']);

            if (! $material->isSheetMaterial()) {
                abort(400, 'Dieses Material gehört nicht zum Holzlager.');
            }

            if ($data['sheet_id']) {
                $source = MaterialSheet::where('material_id', $material->id)
                    ->where('status', 'in_stock')
                    ->lockForUpdate()
                    ->findOrFail($data['sheet_id']);
            } else {
                $source = MaterialSheet::where('material_id', $material->id)
                    ->inStock()
                    ->fullSheets()
                    ->lockForUpdate()
                    ->first();

                if (! $source) {
                    abort(400, 'Keine volle Platte mehr auf Lager.');
                }
            }

            $cutLength = (float) $data['cut_length'];
            $cutWidth = (float) $data['cut_width'];

            if ($cutLength > (float) $source->length_mm || $cutWidth > (float) $source->width_mm) {
                abort(422, 'Zuschnitt übersteigt die Größe der Platte.');
            }

            // Manual axis choice is only valid when both cuts leave a remainder.
            $bothRemainders = $cutLength < (float) $source->length_mm
                && $cutWidth < (float) $source->width_mm;

            if (! empty($data['cut_axis']) && ! $bothRemainders) {
                abort(422, 'Manuelle Achsenwahl nicht möglich — nur eine Achse hat einen Rest.');
            }

            // Auto-pick the shorter source dimension so the longer piece survives.
            $autoAxis = ((float) $source->length_mm <= (float) $source->width_mm) ? 'length' : 'width';
            $axis = $data['cut_axis'] ?? $autoAxis;

            $wasFullSheet = $source->isFullSheet();

            $source->status = 'used';
            $source->save();

            [$remainderA, $remainderB, $cutPiece] = $this->applyCut(
                $source, $material, $cutLength, $cutWidth, $axis
            );

            // A full sheet that gets touched at all stops being "full" -> quantity -1.
            // Cutting an already-cut sheet further never touches quantity (never counted there).
            if ($wasFullSheet) {
                $material->decrement('quantity', 1);
            }

            if ($remainderA && $remainderB) {
                $groupId = (string) Str::uuid();
                $remainderA->update(['sibling_group_id' => $groupId]);
                $remainderB->update(['sibling_group_id' => $groupId]);
            }

            MaterialConsumption::create([
                'material_id' => $material->id,
                'quantity' => 1,
                'consumption_type' => 'use',
                'consumption_time' => now(),
            ]);

            return [
                'cut_piece' => $cutPiece,
                'remainders' => array_values(array_filter([$remainderA, $remainderB])),
                'new_quantity' => $material->fresh()->quantity,
            ];
        });

        return response()->json($result);
    }

    /**
     * Build the three children of a cut: remainder A, remainder B, and the cut piece.
     * Either remainder may be null when the cut consumes the source along that axis.
     * All three children's parent_sheet_id points at the original source (no chaining).
     */
    private function applyCut(
        MaterialSheet $source,
        Material $material,
        float $cutLength,
        float $cutWidth,
        string $axis
    ): array {
        $base = [
            'material_id' => $material->id,
            'thickness_mm' => $source->thickness_mm,
            'parent_sheet_id' => $source->id,
        ];

        $remainderA = null;
        $remainderB = null;

        if ($axis === 'width') {
            // Cut the width first: a side strip off the source, then an end strip off the slice.
            $widthLeftover = (float) $source->width_mm - $cutWidth;
            if ($widthLeftover > 0.01) {
                $remainderA = MaterialSheet::create($base + [
                    'code' => MaterialSheet::generateCode(),
                    'length_mm' => (float) $source->length_mm,
                    'width_mm' => $widthLeftover,
                    'status' => 'in_stock',
                ]);
            }

            $lengthLeftover = (float) $source->length_mm - $cutLength;
            if ($lengthLeftover > 0.01) {
                $remainderB = MaterialSheet::create($base + [
                    'code' => MaterialSheet::generateCode(),
                    'length_mm' => $lengthLeftover,
                    'width_mm' => $cutWidth,
                    'status' => 'in_stock',
                ]);
            }
        } else {
            // Cut the length first (default): an end strip off the source, then a side strip off the slice.
            $lengthLeftover = (float) $source->length_mm - $cutLength;
            if ($lengthLeftover > 0.01) {
                $remainderA = MaterialSheet::create($base + [
                    'code' => MaterialSheet::generateCode(),
                    'length_mm' => $lengthLeftover,
                    'width_mm' => (float) $source->width_mm,
                    'status' => 'in_stock',
                ]);
            }

            $widthLeftover = (float) $source->width_mm - $cutWidth;
            if ($widthLeftover > 0.01) {
                $remainderB = MaterialSheet::create($base + [
                    'code' => MaterialSheet::generateCode(),
                    'length_mm' => $cutLength,
                    'width_mm' => $widthLeftover,
                    'status' => 'in_stock',
                ]);
            }
        }

        $cutPiece = MaterialSheet::create($base + [
            'code' => MaterialSheet::generateCode(),
            'length_mm' => $cutLength,
            'width_mm' => $cutWidth,
            'status' => 'used',
        ]);

        return [$remainderA, $remainderB, $cutPiece];
    }

    public function ungroupSiblings(Request $request, int $lager_id)
    {
        $data = $request->validate(['sheet_id' => 'required|exists:material_sheets,id']);

        $sheet = MaterialSheet::findOrFail($data['sheet_id']);
        if ($sheet->sibling_group_id) {
            MaterialSheet::where('sibling_group_id', $sheet->sibling_group_id)
                ->update(['sibling_group_id' => null]);
        }

        return response()->json(['success' => true]);
    }
}
