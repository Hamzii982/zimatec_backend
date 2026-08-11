<?php

use App\Models\Lager;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $lager = Lager::create([
        'name' => 'Testlager',
        'description' => 'test',
        'type' => 'standard',
        'is_active' => true,
        'status' => 'active',
    ]);
    $this->lagerId = $lager->id;

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->material = Material::create([
        'name' => 'Test-Material',
        'code' => 'TST-001',
        'quantity' => 10,
        'on_hold_quantity' => 0,
        'order_quantity' => 0,
        'type' => 'standard',
        'unit' => 'Stück',
        'lager_id' => $this->lagerId,
        'is_active' => true,
    ]);
});

it('shows the material thresholds index to admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.material-thresholds'))
        ->assertOk()
        ->assertSee('Test-Material');
});

it('updates a material threshold', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.settings.material-thresholds.update', $this->material->id), [
            'threshold' => 5,
        ])
        ->assertRedirect(route('admin.settings.material-thresholds'));

    expect($this->material->fresh()->threshold)->toBe(5);
});

it('clears the threshold when destroy is called', function () {
    $this->material->update(['threshold' => 3]);

    $this->actingAs($this->admin)
        ->delete(route('admin.settings.material-thresholds.destroy', $this->material->id))
        ->assertRedirect(route('admin.settings.material-thresholds'));

    expect($this->material->fresh()->threshold)->toBeNull();
});

it('treats an empty threshold submission as null', function () {
    $this->material->update(['threshold' => 7]);

    $this->actingAs($this->admin)
        ->post(route('admin.settings.material-thresholds.update', $this->material->id), [
            'threshold' => '',
        ])
        ->assertRedirect(route('admin.settings.material-thresholds'));

    expect($this->material->fresh()->threshold)->toBeNull();
});

it('rejects negative thresholds', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.settings.material-thresholds.update', $this->material->id), [
            'threshold' => -3,
        ])
        ->assertSessionHasErrors('threshold');

    expect($this->material->fresh()->threshold)->toBeNull();
});

it('blocks non-admin users from the threshold page', function () {
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user)
        ->get(route('admin.settings.material-thresholds'))
        ->assertForbidden();
});
