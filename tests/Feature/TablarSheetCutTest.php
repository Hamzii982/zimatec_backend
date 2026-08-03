<?php

use App\Models\Lager;
use App\Models\Material;
use App\Models\MaterialSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The sheet-cutting feature lives in the "Holz" lager; the controller gates on
 * Material::isSheetMaterial() which checks $lager->type === 'holz'. The whole
 * module is now scoped per Lager so every test needs a Lager row to satisfy
 * the FK on materials.lager_id.
 */
beforeEach(function () {
    \DB::table('lager')->insert([
        'id' => 7,
        'name' => 'Holzlager',
        'type' => 'holz',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function makeHolzMaterial(int $lagerId = 7, int $quantity = 1): Material
{
    return Material::create([
        'name' => 'MDF Platte',
        'code' => 'MDF-'.random_int(1000, 9999),
        'lager_id' => $lagerId,
        'quantity' => $quantity,
    ]);
}

function makeFullSheet(Material $material, float $length, float $width, float $thickness = 19.0): MaterialSheet
{
    return MaterialSheet::create([
        'material_id' => $material->id,
        'code' => MaterialSheet::generateCode(),
        'length_mm' => $length,
        'width_mm' => $width,
        'thickness_mm' => $thickness,
        'status' => 'in_stock',
        'parent_sheet_id' => null,
    ]);
}

function makeLeftover(Material $material, float $length, float $width, float $thickness = 19.0, ?string $siblingGroupId = null): MaterialSheet
{
    $parent = makeFullSheet($material, max($length, $width) + 50, max($length, $width) + 50, $thickness);

    return MaterialSheet::create([
        'material_id' => $material->id,
        'code' => MaterialSheet::generateCode(),
        'length_mm' => $length,
        'width_mm' => $width,
        'thickness_mm' => $thickness,
        'status' => 'in_stock',
        'parent_sheet_id' => $parent->id,
        'sibling_group_id' => $siblingGroupId,
    ]);
}

test('cut_picks_shorter_axis_when_unspecified (200x100 source, 80x50 cut)', function () {
    // Source 200 × 100, cut 80 × 50 — both remainders exist, source.length (200) > source.width (100),
    // so auto picks width-first (the shorter source dimension is width).
    $material = makeHolzMaterial(quantity: 1);
    $source = makeFullSheet($material, 200, 100);

    $response = $this->postJson(route('tablar.sheets.cut', ['lager_id' => 7]), [
        'material_id' => $material->id,
        'sheet_id' => null,
        'cut_length' => 80,
        'cut_width' => 50,
    ]);

    $response->assertOk();

    $data = $response->json();
    expect($data['cut_piece']['length_mm'])->toBe('80.00')
        ->and($data['cut_piece']['width_mm'])->toBe('50.00')
        ->and($data['remainders'])->toHaveCount(2);

    // Width-first: A = 200 × (100-50) = 200 × 50; B = (200-80) × 50 = 120 × 50.
    $a = collect($data['remainders'])->firstWhere('length_mm', '200.00');
    $b = collect($data['remainders'])->firstWhere('length_mm', '120.00');
    expect($a)->not->toBeNull()->and($a['width_mm'])->toBe('50.00');
    expect($b)->not->toBeNull()->and($b['width_mm'])->toBe('50.00');

    // Both remainders share a sibling_group_id.
    expect($a['sibling_group_id'])->toBe($b['sibling_group_id']);
});

test('cut_explicit_length_axis_swaps_remainder_dimensions', function () {
    $material = makeHolzMaterial(quantity: 1);
    $source = makeFullSheet($material, 200, 100);

    $response = $this->postJson(route('tablar.sheets.cut', ['lager_id' => 7]), [
        'material_id' => $material->id,
        'sheet_id' => null,
        'cut_length' => 80,
        'cut_width' => 50,
        'cut_axis' => 'length',
    ]);

    $response->assertOk();

    $data = $response->json();
    expect($data['remainders'])->toHaveCount(2);

    // Length-first: A = (200-80) × 100 = 120 × 100; B = 80 × (100-50) = 80 × 50.
    $a = collect($data['remainders'])->firstWhere('length_mm', '120.00');
    $b = collect($data['remainders'])->firstWhere('length_mm', '80.00');
    expect($a)->not->toBeNull()->and($a['width_mm'])->toBe('100.00');
    expect($b)->not->toBeNull()->and($b['width_mm'])->toBe('50.00');
});

test('cut_axis_rejected_when_only_one_remainder', function () {
    // Source 200 × 100, cut 200 × 50 → only width has a leftover.
    $material = makeHolzMaterial(quantity: 1);
    $source = makeFullSheet($material, 200, 100);

    $this->postJson(route('tablar.sheets.cut', ['lager_id' => 7]), [
        'material_id' => $material->id,
        'sheet_id' => null,
        'cut_length' => 200,
        'cut_width' => 50,
        'cut_axis' => 'length',
    ])->assertStatus(422);
});

test('cut_no_remainder_when_one_axis_full', function () {
    // Source 200 × 100, cut 200 × 50 → no remainder on length axis, one remainder on width.
    $material = makeHolzMaterial(quantity: 1);
    $source = makeFullSheet($material, 200, 100);

    $response = $this->postJson(route('tablar.sheets.cut', ['lager_id' => 7]), [
        'material_id' => $material->id,
        'sheet_id' => null,
        'cut_length' => 200,
        'cut_width' => 50,
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['remainders'])->toHaveCount(1);
    expect($data['remainders'][0]['length_mm'])->toBe('200.00')
        ->and($data['remainders'][0]['width_mm'])->toBe('50.00');
    // Single remainder never gets a sibling_group_id.
    expect($data['remainders'][0]['sibling_group_id'])->toBeNull();
});

test('cut_decrements_quantity_only_for_full_sheet', function () {
    $material = makeHolzMaterial(quantity: 3);
    $source = makeFullSheet($material, 200, 100);

    $this->postJson(route('tablar.sheets.cut', ['lager_id' => 7]), [
        'material_id' => $material->id,
        'sheet_id' => $source->id,
        'cut_length' => 100,
        'cut_width' => 50,
    ])->assertOk();

    expect((int) $material->fresh()->quantity)->toBe(2);

    // Cut a leftover (non-full) sheet — quantity stays the same.
    $leftover = MaterialSheet::where('material_id', $material->id)
        ->whereNotNull('parent_sheet_id')
        ->first();
    expect($leftover)->not->toBeNull();

    $this->postJson(route('tablar.sheets.cut', ['lager_id' => 7]), [
        'material_id' => $material->id,
        'sheet_id' => $leftover->id,
        'cut_length' => 10,
        'cut_width' => 10,
    ])->assertOk();

    expect((int) $material->fresh()->quantity)->toBe(2);
});

test('find_sheet_for_size_returns_smallest_area_match', function () {
    $material = makeHolzMaterial(quantity: 1);
    // Full sheet 200 × 100 (area 20000)
    $full = makeFullSheet($material, 200, 100);
    // Leftover 150 × 120 (area 18000)
    $leftover = makeLeftover($material, 150, 120);

    $response = $this->postJson(route('tablar.sheets.search', ['lager_id' => 7, 'material_id' => $material->id]), [
        'length_mm' => 100,
        'width_mm' => 100,
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['sheet'])->not->toBeNull();
    expect((float) $data['sheet']['length_mm'] * (float) $data['sheet']['width_mm'])->toBe(18000.0);
    expect($data['sheet']['code'])->toBe($leftover->code);
});

test('find_sheet_for_size_matches_either_orientation', function () {
    $material = makeHolzMaterial(quantity: 1);
    // Sheet is 100 × 200. Search 150 × 80 — strict orientation fails (length=100 < 150),
    // swapped orientation fits (length=100 >= 80, width=200 >= 150).
    $leftover = makeLeftover($material, 100, 200);

    $response = $this->postJson(route('tablar.sheets.search', ['lager_id' => 7, 'material_id' => $material->id]), [
        'length_mm' => 150,
        'width_mm' => 80,
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['sheet'])->not->toBeNull();
    expect($data['sheet']['code'])->toBe($leftover->code);
});

test('find_sheet_for_size_returns_null_when_no_match', function () {
    $material = makeHolzMaterial(quantity: 1);
    makeFullSheet($material, 200, 100);

    $response = $this->postJson(route('tablar.sheets.search', ['lager_id' => 7, 'material_id' => $material->id]), [
        'length_mm' => 500,
        'width_mm' => 500,
    ]);

    $response->assertOk();
    expect($response->json('sheet'))->toBeNull();
});

test('find_sheet_for_size_excludes_used_sheets', function () {
    $material = makeHolzMaterial(quantity: 1);
    $sheet = makeFullSheet($material, 200, 100);
    $sheet->update(['status' => 'used']);

    $response = $this->postJson(route('tablar.sheets.search', ['lager_id' => 7, 'material_id' => $material->id]), [
        'length_mm' => 100,
        'width_mm' => 100,
    ]);

    $response->assertOk();
    expect($response->json('sheet'))->toBeNull();
});

test('sheet_options_returns_axis_choice_available_flag', function () {
    $material = makeHolzMaterial(quantity: 1);
    makeFullSheet($material, 200, 100);

    $response = $this->getJson(route('tablar.sheets.options', ['lager_id' => 7, 'material' => $material->id]));

    $response->assertOk();
    $options = $response->json('options');
    expect($options)->toBeArray()->and(count($options))->toBeGreaterThan(0);
    foreach ($options as $opt) {
        expect($opt)->toHaveKey('axis_choice_available');
        expect($opt['axis_choice_available'])->toBeTrue();
    }
});
