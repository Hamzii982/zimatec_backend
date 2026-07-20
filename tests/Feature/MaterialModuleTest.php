<?php

use App\Models\Lager;
use App\Models\Material;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create the "Hochregal" Lager record used by the test suite.
 * The whole material module is now scoped per Lager (multi-warehouse),
 * so every test needs a Lager row to satisfy the FK on materials.lager_id.
 * id is not in Lager::$fillable, so we use a raw insert.
 */
beforeEach(function () {
    \DB::table('lager')->insert([
        'id' => 2,
        'name' => 'Hochregal',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('default threshold is null and does not trigger low stock notification', function () {
    $material = Material::create([
        'name' => 'Schraube M4',
        'quantity' => 10,
        'lager_id' => 2,
        // no threshold set on purpose
    ]);

    $this->assertNull($material->threshold);
    $this->assertSame('ok', $material->status);

    $response = $this->postJson(route('tablar.consume', ['lager_id' => 2]), [
        'material_id' => $material->id,
        'quantity' => 1,
    ]);

    $response->assertOk();

    expect(Notification::where('type', 'low_stock')->count())->toBe(0);
});

test('threshold of zero behaves the same as null and does not trigger notification', function () {
    $material = Material::create([
        'name' => 'Beilagscheibe',
        'quantity' => 10,
        'threshold' => 0,
        'lager_id' => 2,
    ]);

    expect($material->status)->toBe('ok');

    $this->postJson(route('tablar.consume', ['lager_id' => 2]), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])->assertOk();

    expect(Notification::where('type', 'low_stock')->count())->toBe(0);
});

test('explicit positive threshold triggers low stock notification when consumed below', function () {
    $material = Material::create([
        'name' => 'Bolzen',
        'quantity' => 3,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    expect($material->fresh()->status)->toBe('low');

    $this->postJson(route('tablar.consume', ['lager_id' => 2]), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])->assertOk();

    // Exactly one notification for today
    expect(Notification::where('type', 'low_stock')
        ->where('message', 'like', '%'.$material->name.'%')
        ->whereDate('created_at', now()->toDateString())
        ->count())->toBe(1);

    // Second consume on the same day does NOT create a duplicate
    $this->postJson(route('tablar.consume', ['lager_id' => 2]), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])->assertOk();

    expect(Notification::where('type', 'low_stock')
        ->where('message', 'like', '%'.$material->name.'%')
        ->whereDate('created_at', now()->toDateString())
        ->count())->toBe(1);
});

test('store material as admin persists code and description', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $response = $this->postJson(route('admin.tablar.store', ['lager_id' => 2]), [
        'name' => 'Mutter M6',
        'code' => 'ART-001',
        'description' => 'Sechskantmutter, verzinkt',
        'quantity' => 100,
        'tablar' => 'A1',
        'threshold' => 10,
        'is_active' => true,
    ]);

    $response->assertOk();

    $material = Material::where('name', 'Mutter M6')->first();
    expect($material)->not->toBeNull();
    expect($material->code)->toBe('ART-001');
    expect($material->description)->toBe('Sechskantmutter, verzinkt');
});

test('update material as admin persists code and description', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $material = Material::create([
        'name' => 'Mutter M8',
        'quantity' => 50,
        'lager_id' => 2,
    ]);

    $response = $this->putJson(
        route('admin.tablar.update', ['lager_id' => 2, 'id' => $material->id]),
        [
            'name' => 'Mutter M8',
            'code' => 'ART-002',
            'description' => 'Aktualisierte Notiz',
            'quantity' => 50,
            'tablar' => 'A2',
            'threshold' => 5,
            'is_active' => true,
        ]
    );

    $response->assertOk();

    $material->refresh();
    expect($material->code)->toBe('ART-002');
    expect($material->description)->toBe('Aktualisierte Notiz');
});

test('admin tablar overview excludes materials with null or zero threshold from low stock', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // Material without threshold — should NOT appear
    Material::create([
        'name' => 'Ohne Schwelle',
        'quantity' => 1,
        'threshold' => null,
        'lager_id' => 2,
    ]);

    // Material with threshold = 0 but quantity = 1 — should NOT appear
    Material::create([
        'name' => 'Schwelle null',
        'quantity' => 1,
        'threshold' => 0,
        'lager_id' => 2,
    ]);

    // Material with threshold = 5 and quantity = 3 — SHOULD appear
    Material::create([
        'name' => 'Bolzen',
        'quantity' => 3,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    // Material with threshold = 5 and quantity = 5 — boundary case, IS included
    // (whereColumn('quantity', '<=', 'threshold') matches quantity 5 with threshold 5)
    Material::create([
        'name' => 'Genug',
        'quantity' => 5,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    // Material well above its threshold — should NOT appear
    Material::create([
        'name' => 'Viel',
        'quantity' => 100,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    $response = $this->get(route('admin.tablar.overview', ['lager_id' => 2]));
    $response->assertOk();

    $lowStockMaterials = $response->viewData('lowStockMaterials');

    $names = $lowStockMaterials->pluck('name')->all();

    expect($names)->toContain('Bolzen');
    expect($names)->toContain('Genug');
    expect($names)->not->toContain('Ohne Schwelle');
    expect($names)->not->toContain('Schwelle null');
    expect($names)->not->toContain('Viel');
});

test('low stock notification suppressed when reserved quantity keeps available total above threshold', function () {
    $material = Material::create([
        'name' => 'Niete',
        'quantity' => 2,
        'on_hold_quantity' => 10,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    // available_total = 2 + 10 = 12, threshold = 5 -> status should be ok
    expect($material->fresh()->status)->toBe('ok');

    $this->postJson(route('tablar.consume', ['lager_id' => 2]), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])->assertOk();

    expect(Notification::where('type', 'low_stock')
        ->where('message', 'like', '%'.$material->name.'%')
        ->whereDate('created_at', now()->toDateString())
        ->count())->toBe(0);
});

test('low stock notification suppressed when order quantity keeps available total above threshold', function () {
    $material = Material::create([
        'name' => 'Dichtungsring',
        'quantity' => 1,
        'order_quantity' => 20,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    // available_total = 1 + 0 + 20 = 21, threshold = 5 -> status should be ok
    expect($material->fresh()->status)->toBe('ok');

    $this->postJson(route('tablar.consume', ['lager_id' => 2]), [
        'material_id' => $material->id,
        'quantity' => 1,
    ])->assertOk();

    expect(Notification::where('type', 'low_stock')
        ->where('message', 'like', '%'.$material->name.'%')
        ->whereDate('created_at', now()->toDateString())
        ->count())->toBe(0);
});

test('available_total accessor sums quantity, on_hold_quantity and order_quantity', function () {
    $material = Material::create([
        'name' => 'Kombi',
        'quantity' => 7,
        'on_hold_quantity' => 3,
        'order_quantity' => 4,
        'threshold' => 20,
        'lager_id' => 2,
    ]);

    expect((int) $material->available_total)->toBe(14);
    // 14 <= 20 -> low
    expect($material->status)->toBe('low');

    // Bump order so the total strictly exceeds the threshold
    $material->order_quantity = 11;
    $material->save();
    expect($material->fresh()->available_total)->toBe(21);
    expect($material->fresh()->status)->toBe('ok');
});
