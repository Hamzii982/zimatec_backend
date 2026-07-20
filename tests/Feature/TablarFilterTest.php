<?php

use App\Models\Lager;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create the "Hochregal" Lager record used by the test suite.
 * Mirrors tests/Feature/MaterialModuleTest.php — required to satisfy the
 * FK on materials.lager_id.
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

test('index filter low_stock returns only materials at or below threshold', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $mLow = Material::create(['name' => 'Schraube',  'quantity' => 3,  'threshold' => 5,  'lager_id' => 2]);
    $mOk = Material::create(['name' => 'Mutter',    'quantity' => 10, 'threshold' => 5,  'lager_id' => 2]);
    $mNone = Material::create(['name' => 'Beilagscheibe', 'quantity' => 2,  'threshold' => null, 'lager_id' => 2]);

    $response = $this->get(route('admin.tablar.index', ['lager_id' => 2, 'low_stock' => 1]));

    $response->assertOk();
    $materials = $response->viewData('materials');
    $ids = collect($materials->items())->pluck('id')->all();
    expect($ids)->toContain($mLow->id)
        ->not->toContain($mOk->id)
        ->not->toContain($mNone->id);
});

test('index filter empty returns only zero-quantity materials', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $mEmpty1 = Material::create(['name' => 'A', 'quantity' => 0, 'lager_id' => 2]);
    $mSome = Material::create(['name' => 'B', 'quantity' => 1, 'lager_id' => 2]);
    $mEmpty2 = Material::create(['name' => 'C', 'quantity' => 0, 'lager_id' => 2]);

    $response = $this->get(route('admin.tablar.index', ['lager_id' => 2, 'empty' => 1]));

    $response->assertOk();
    $ids = collect($response->viewData('materials')->items())->pluck('id')->all();
    expect($ids)->toContain($mEmpty1->id)
        ->toContain($mEmpty2->id)
        ->not->toContain($mSome->id);
});

test('index filter status returns only matching order_status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $m1 = Material::create(['name' => 'N1', 'quantity' => 1, 'lager_id' => 2, 'order_status' => 'notified']);
    $m2 = Material::create(['name' => 'O1', 'quantity' => 1, 'lager_id' => 2, 'order_status' => 'ordered']);
    $m3 = Material::create(['name' => 'B1', 'quantity' => 1, 'lager_id' => 2, 'order_status' => 'blocked']);

    $response = $this->get(route('admin.tablar.index', ['lager_id' => 2, 'status' => 'ordered']));

    $response->assertOk();
    $ids = collect($response->viewData('materials')->items())->pluck('id')->all();
    expect($ids)->toContain($m2->id)
        ->not->toContain($m1->id)
        ->not->toContain($m3->id);
});

test('supplier list returns only materials attached to the requested supplier in this lager', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $s1 = Supplier::create(['name' => 'Acme',   'company' => 'Acme GmbH']);
    $s2 = Supplier::create(['name' => 'Globex', 'company' => 'Globex AG']);

    $origin = Material::create(['name' => 'Origin', 'quantity' => 1, 'lager_id' => 2]);
    $m1 = Material::create(['name' => 'M1', 'quantity' => 1, 'lager_id' => 2]);
    $m2 = Material::create(['name' => 'M2', 'quantity' => 1, 'lager_id' => 2]);
    $m3 = Material::create(['name' => 'M3', 'quantity' => 1, 'lager_id' => 2]);
    $m4 = Material::create(['name' => 'M4', 'quantity' => 1, 'lager_id' => 2]);

    // Attach with controlled pivot timestamps so we can verify exposure.
    $origin->suppliers()->attach($s1->id, ['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)]);
    $m1->suppliers()->attach($s1->id, ['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
    $m2->suppliers()->attach($s1->id, ['created_at' => now()->subDay(),  'updated_at' => now()->subDay()]);
    $m3->suppliers()->attach($s2->id, ['created_at' => now(),            'updated_at' => now()]);
    // m4 left unattached on purpose

    $response = $this->get(route('admin.tablar.supplier-list', [
        'lager_id' => 2,
        'id' => $origin->id,
        'supplier' => $s1->id,
    ]));

    $response->assertOk();
    $materials = $response->viewData('materials');
    $ids = $materials->pluck('id')->all();

    expect($ids)->toContain($m1->id, $m2->id, $origin->id)
        ->not->toContain($m3->id, $m4->id);

    // Each row exposes the matching pivot timestamp.
    $m1Refreshed = $materials->firstWhere('id', $m1->id);
    expect($m1Refreshed->pivot_attached_at)->not->toBeNull();
});

test('index filter low_stock ignores on_hold_quantity when it brings total above threshold', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // quantity is below threshold but reserved stock brings total above
    $mReserved = Material::create([
        'name' => 'Reserviert',
        'quantity' => 2,
        'on_hold_quantity' => 10,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    // Genuinely low
    $mLow = Material::create([
        'name' => 'Wirklich leer',
        'quantity' => 1,
        'on_hold_quantity' => 0,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    $response = $this->get(route('admin.tablar.index', ['lager_id' => 2, 'low_stock' => 1]));
    $response->assertOk();
    $ids = collect($response->viewData('materials')->items())->pluck('id')->all();

    expect($ids)->toContain($mLow->id)
        ->not->toContain($mReserved->id);
});

test('index filter low_stock ignores order_quantity when it brings total above threshold', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // quantity is below threshold but ordered units bring total above
    $mOrdered = Material::create([
        'name' => 'Auf Bestellung',
        'quantity' => 1,
        'order_quantity' => 20,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    $mLow = Material::create([
        'name' => 'Einfach leer',
        'quantity' => 1,
        'order_quantity' => 0,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    $response = $this->get(route('admin.tablar.index', ['lager_id' => 2, 'low_stock' => 1]));
    $response->assertOk();
    $ids = collect($response->viewData('materials')->items())->pluck('id')->all();

    expect($ids)->toContain($mLow->id)
        ->not->toContain($mOrdered->id);
});

test('admin overview low stock list respects on_hold and order quantities', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // Below threshold on quantity alone, but reserved quantity brings it above
    Material::create([
        'name' => 'Nur scheinbar leer',
        'quantity' => 1,
        'on_hold_quantity' => 50,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    // Genuinely low (no on-hold or order to bring it above)
    Material::create([
        'name' => 'Echt knapp',
        'quantity' => 1,
        'threshold' => 5,
        'lager_id' => 2,
    ]);

    $response = $this->get(route('admin.tablar.overview', ['lager_id' => 2]));
    $response->assertOk();
    $low = $response->viewData('lowStockMaterials');

    $names = $low->pluck('name')->all();
    expect($names)->toContain('Echt knapp')
        ->not->toContain('Nur scheinbar leer');
});
