<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $code = $request->code;
        $user = Auth::user();
        $cartTotal = $this->calculateCartTotal(); // Kita akan buat fungsi ini

        // 1. Cari voucher berdasarkan kode
        $voucher = Voucher::where('code', $code)->where('is_active', true)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Kode voucher tidak ditemukan atau tidak aktif.'], 404);
        }

        // 2. Validasi tanggal kedaluwarsa
        if ($voucher->expires_at && $voucher->expires_at->isPast()) {
            return response()->json(['message' => 'Voucher ini telah kedaluwarsa.'], 422);
        }

        // 3. Validasi batas penggunaan
        if ($voucher->max_uses !== null && $voucher->uses >= $voucher->max_uses) {
            return response()->json(['message' => 'Voucher ini sudah habis digunakan.'], 422);
        }

        // 4. Validasi minimum pembelian
        if ($cartTotal < $voucher->min_purchase) {
            return response()->json(['message' => 'Minimum pembelian untuk voucher ini adalah Rp ' . number_format($voucher->min_purchase)], 422);
        }

        // 5. Validasi segmentasi per role
        if ($voucher->roles()->exists() && !$user->roles()->whereIn('id', $voucher->roles->pluck('id'))->exists()) {
            return response()->json(['message' => 'Voucher ini tidak berlaku untuk Anda.'], 403);
        }

        // --- Jika semua validasi lolos ---

        // Hitung diskon
        $discountAmount = 0;
        if ($voucher->type === 'fixed') {
            $discountAmount = $voucher->value;
        } elseif ($voucher->type === 'percentage') {
            $discountAmount = ($cartTotal * $voucher->value) / 100;
        }

        // Simpan voucher ke session agar bisa digunakan saat checkout
        session()->put('applied_voucher', [
            'code' => $voucher->code,
            'discount_amount' => $discountAmount,
        ]);

        return response()->json([
            'message' => 'Voucher berhasil diterapkan!',
            'discount_amount' => $discountAmount,
            'new_total' => $cartTotal - $discountAmount,
        ]);
    }

    // Fungsi bantuan untuk menghitung total keranjang
    private function calculateCartTotal()
    {
        $cart = session()->get('cart', []);
        $productIds = array_keys($cart);
        $products = \App\Models\Product::whereIn('id', $productIds)->get();

        return $products->reduce(function ($carry, $product) use ($cart) {
            return $carry + ($product->final_price * $cart[$product->id]['quantity']);
        }, 0);
    }
}
