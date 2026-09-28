<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('label')->get();

        return view('admin.settings.roles.index', compact('roles'));
    }

    public function create()
    {
        $this->ensurePermissionsExist();

        $role = new Role();
        $modules = $this->moduleOptions();

        return view('admin.settings.roles.form', compact('role', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'label' => ['required', 'string', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'label' => $validated['label'],
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.settings.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->ensurePermissionsExist();

        $modules = $this->moduleOptions();

        return view('admin.settings.roles.form', compact('role', 'modules'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'label' => ['required', 'string', 'max:255'],
            'is_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'label' => $validated['label'],
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.settings.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('admin.settings.roles.index')->with('success', 'Role deleted successfully.');
    }

    protected function ensurePermissionsExist(): void
    {
        foreach (array_keys($this->moduleOptions()) as $module) {
            foreach (['read', 'write', 'delete'] as $action) {
                Permission::firstOrCreate([
                    'module' => $module,
                    'action' => $action,
                ]);
            }
        }
    }

    protected function moduleOptions(): array
    {
        return [
            'teams' => 'Team',
            'projects' => 'Projects',
            'time' => 'Time',
            'suppliers' => 'Suppliers',
            'settings' => 'Settings',
            'feedback' => 'Feedback',
            'tablar' => 'Lager',
            'scheduler' => 'Scheduler',
            'emails' => 'Emails',
            'project_offers' => 'Project Offers',
        ];
    }
}
