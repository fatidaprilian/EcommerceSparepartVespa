<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Reseller', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Member', 'guard_name' => 'web']);
    }
}
