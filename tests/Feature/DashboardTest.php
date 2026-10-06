<?php

use App\Models\User;
use App\Models\Lager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to view lager dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    // create lagers without a factory (model has no factory)
    Lager::create(['name' => 'Lager A', 'description' => 'Test', 'is_active' => true]);
    Lager::create(['name' => 'Lager B', 'description' => 'Test', 'is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.lager.dashboard'))
        ->assertStatus(200)
        ->assertSee('Lager gesamt');
});
