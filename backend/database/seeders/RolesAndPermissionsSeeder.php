<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view classes',
            'create class',
            'edit class',
            'delete class',
            'view students',
            'add student note',
            'view student notes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'school-admin'       => $permissions,
            'coordinator'        => ['view classes', 'create class', 'edit class', 'delete class',
                                     'view students', 'add student note', 'view student notes'],
            'teacher'            => ['view classes', 'create class', 'edit class',
                                     'view students', 'add student note', 'view student notes'],
            'teachers-assistant' => ['view classes', 'view students', 'add student note', 'view student notes'],
            'read-only'          => ['view classes', 'view students', 'view student notes'],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
