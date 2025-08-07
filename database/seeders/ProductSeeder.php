<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat atau cari kategori default
        $category = Category::firstOrCreate(
            ['slug' => 'aksesoris'],
            ['name' => 'Aksesoris', 'description' => 'Berbagai aksesoris untuk melengkapi Vespa Anda.']
        );

        // 2. Definisikan data produk contoh
        $products = [
            [
                'name' => 'Vespa Racing Sixties Seat',
                'base_price' => 3200000,
                'description' => 'Jok dengan desain sporty dan elegan, terinspirasi dari era balap tahun 60-an. Memberikan kenyamanan dan gaya yang tak tertandingi.',
            ],
            [
                'name' => 'Malossi Performance Exhaust',
                'base_price' => 4500000,
                'description' => 'Knalpot performa tinggi dari Malossi untuk meningkatkan akselerasi dan memberikan suara yang lebih garang. Dibuat dari material terbaik.',
            ],
            [
                'name' => 'Classic Chrome Legshield Trim',
                'base_price' => 950000,
                'description' => 'Trim krom klasik untuk legshield yang memberikan sentuhan retro dan mewah pada Vespa modern Anda. Pemasangan mudah dan presisi.',
            ],
        ];

        // 3. Masukkan data ke database
        foreach ($products as $productData) {
            Product::create([
                'category_id' => $category->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'sku' => 'VP-' . Str::upper(Str::random(6)), // SKU acak
                'description' => $productData['description'],
                'base_price' => $productData['base_price'],
                'stock' => rand(5, 50), // Stok acak
                'is_active' => true,
                // Biarkan image_url kosong, kita akan upload manual via admin panel
                'image_url' => null,
            ]);
        }
    }
}
