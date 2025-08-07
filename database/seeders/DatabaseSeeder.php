<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Urutan ini sangat penting:
        $this->call([
            RoleSeeder::class,      // 1. Buat peran
            UserSeeder::class,      // 2. Buat user dan berikan peran
            ProductSeeder::class,   // 3. Buat produk contoh
        ]);
    }
}
