<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'label' => 'Super Admin',
            'is_admin' => true,
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'label' => 'User',
            'is_admin' => false,
        ]);

        $modules = [
            'teams',
            'projects',
            'time',
            'suppliers',
            'settings',
            'feedback',
            'tablar',
            'scheduler',
            'emails',
            'project_offers',
        ];

        $permissions = [];

        foreach ($modules as $module) {
            foreach (['read', 'write', 'delete'] as $action) {
                $permissions[] = Permission::firstOrCreate([
                    'module' => $module,
                    'action' => $action,
                ]);
            }
        }

        $superAdmin->permissions()->sync(
            collect($permissions)->pluck('id')->all()
        );

        $userRole->permissions()->sync([
            Permission::firstOrCreate(['module' => 'projects', 'action' => 'read'])->id,
        ]);
    }
}
