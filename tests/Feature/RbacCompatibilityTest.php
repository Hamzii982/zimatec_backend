<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

it('treats a legacy admin user as admin', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'role_id' => null,
    ]);

    expect($user->isAdmin())->toBeTrue();
});

it('treats a role-linked user as admin', function () {
    $role = Role::create([
        'name' => 'super_admin',
        'label' => 'Super Admin',
        'is_admin' => true,
    ]);

    $user = User::factory()->create([
        'role' => 'user',
        'role_id' => $role->id,
    ]);

    expect($user->isAdmin())->toBeTrue();
});

it('resolves module access through the assigned role permissions', function () {
    $role = Role::create([
        'name' => 'project_manager',
        'label' => 'Project Manager',
        'is_admin' => false,
    ]);

    $permission = Permission::create([
        'module' => 'projects',
        'action' => 'read',
    ]);

    $role->permissions()->attach($permission->id);

    $user = User::factory()->create([
        'role' => 'user',
        'role_id' => $role->id,
    ]);

    expect($user->canAccessModule('projects', 'read'))->toBeTrue();
    expect($user->canAccessModule('projects', 'write'))->toBeFalse();
});

it('denies access to a restricted module action when the user lacks that permission', function () {
    Route::middleware('module:projects,write')->get('/rbac-denied-check', fn () => 'ok');

    $role = Role::create([
        'name' => 'restricted_user',
        'label' => 'Restricted User',
        'is_admin' => false,
    ]);

    $permission = Permission::create([
        'module' => 'projects',
        'action' => 'read',
    ]);

    $role->permissions()->attach($permission->id);

    $user = User::factory()->create([
        'role' => 'user',
        'role_id' => $role->id,
    ]);

    $this->actingAs($user)
        ->get('/rbac-denied-check')
        ->assertForbidden();
});

it('blocks project deletion unless the user has explicit delete permission', function () {
    $role = Role::create([
        'name' => 'read_only_manager',
        'label' => 'Read Only Manager',
        'is_admin' => false,
    ]);

    $readPermission = Permission::create([
        'module' => 'projects',
        'action' => 'read',
    ]);

    $role->permissions()->attach($readPermission->id);

    $user = User::factory()->create([
        'role' => 'user',
        'role_id' => $role->id,
    ]);

    $project = \App\Models\Project::factory()->create();

    $this->actingAs($user)
        ->delete(route('admin.projects.destroy', $project))
        ->assertForbidden();
});

it('allows write and delete permissions when they are explicitly granted', function () {
    $role = Role::create([
        'name' => 'editor',
        'label' => 'Editor',
        'is_admin' => false,
    ]);

    $writePermission = Permission::create([
        'module' => 'projects',
        'action' => 'write',
    ]);

    $deletePermission = Permission::create([
        'module' => 'projects',
        'action' => 'delete',
    ]);

    $role->permissions()->attach([$writePermission->id, $deletePermission->id]);

    $user = User::factory()->create([
        'role' => 'user',
        'role_id' => $role->id,
    ]);

    expect($user->canWriteModule('projects'))->toBeTrue();
    expect($user->canDeleteModule('projects'))->toBeTrue();
});
