<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat atau cari user Admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'), // Ganti 'password' dengan password yang Anda inginkan
                'email_verified_at' => now(),
            ]
        );

        // Cari peran 'Admin'
        $adminRole = Role::where('name', 'Admin')->first();

        // Berikan peran 'Admin' ke user tersebut jika perannya ada
        if ($adminRole) {
            $adminUser->assignRole($adminRole);
        }
    }
}
