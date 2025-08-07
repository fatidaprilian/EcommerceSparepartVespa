<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja dari database.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)
            ->with('product') // Eager load detail produk
            ->get()
            ->map(function ($cartItem) {
                // Pastikan product tidak null untuk menghindari error
                if (!$cartItem->product) {
                    return null;
                }
                return (object)[
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'slug' => $cartItem->product->slug,
                    'image_url' => $cartItem->product->image_url,
                    'quantity' => $cartItem->quantity,
                    'final_price' => $cartItem->product->final_price,
                    'subtotal' => $cartItem->quantity * $cartItem->product->final_price,
                ];
            })->filter(); // Hapus item null jika produknya terhapus

        $total = $cartItems->sum('subtotal');

        return Inertia::render('Cart/Index', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Menambahkan produk ke keranjang di database.
     */
    public function add(Request $request, Product $product)
    {
        $user = Auth::user();
        $quantity = $request->input('quantity', 1);

        // Cari apakah produk sudah ada di keranjang user
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            // Jika sudah ada, tambahkan kuantitasnya
            $cartItem->increment('quantity', $quantity);
        } else {
            // Jika belum ada, buat entri baru
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        return Redirect::back()->with('status', 'Produk berhasil ditambahkan ke keranjang!');
    }

    /**
     * Mengubah kuantitas produk di keranjang database.
     */
    public function update(Request $request, Product $product)
    {
        $user = Auth::user();
        $quantity = $request->input('quantity');

        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem && $quantity > 0) {
            $cartItem->update(['quantity' => $quantity]);
        } elseif ($cartItem && $quantity <= 0) {
            $cartItem->delete();
        }

        return Redirect::back();
    }

    /**
     * Menghapus produk dari keranjang database.
     */
    public function remove(Product $product)
    {
        Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return Redirect::back()->with('status', 'Produk berhasil dihapus dari keranjang.');
    }
}
