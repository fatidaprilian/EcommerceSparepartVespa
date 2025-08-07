<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Menampilkan halaman katalog semua produk.
     */
    public function index(): Response
    {
        // Ambil produk yang aktif, urutkan dari yang terbaru, dan batasi 12 per halaman.
        $products = Product::where('is_active', true)
            ->latest()
            ->paginate(12);

        // Render komponen Vue 'Products/Index' dan kirim data produk sebagai props.
        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * (Akan kita gunakan nanti)
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * (Akan kita gunakan nanti)
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Menampilkan halaman detail satu produk.
     * (Logika baru ditambahkan di sini)
     */
    public function show(Product $product): Response
    {
        // Pastikan produk yang diakses aktif, jika tidak, tampilkan 404 Not Found.
        if (!$product->is_active) {
            abort(404);
        }

        // Render komponen Vue 'Products/Show' dan kirim data produk yang ditemukan.
        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * (Akan kita gunakan nanti)
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * (Akan kita gunakan nanti)
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * (Akan kita gunakan nanti)
     */
    public function destroy(Product $product)
    {
        //
    }
}
