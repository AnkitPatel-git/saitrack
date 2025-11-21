<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::create(['name' => 'Role-Management']);
        Permission::create(['name' => 'Employee-Management']);
        Permission::create(['name' => 'Outlet-Management']);

        $adminRole = Role::create(['name' => 'Admin']);
        $editorRole = Role::create(['name' => 'SubAdmin']);

        $adminRole->givePermissionTo([
            'Role-Management',
            'Employee-Management',
            'Outlet-Management',
        ]);

        $editorRole->givePermissionTo([
            'Outlet-Management',
        ]);
    }
}
