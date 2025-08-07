<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(protected PaymentServiceInterface $paymentService) {}

    /**
     * Menampilkan halaman checkout dengan data dari keranjang database.
     */
    public function index()
    {
        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        $formattedCartItems = $cartItems->map(function ($cartItem) {
            return (object)[
                'id' => $cartItem->product->id,
                'name' => $cartItem->product->name,
                'final_price' => $cartItem->product->final_price,
                'quantity' => $cartItem->quantity,
                'subtotal' => $cartItem->quantity * $cartItem->product->final_price,
            ];
        });

        $total = $formattedCartItems->sum('subtotal');
        $userAddresses = $user->addresses()->get();

        return Inertia::render('Checkout/Index', [
            'cartItems' => $formattedCartItems,
            'total' => $total,
            'userAddresses' => $userAddresses,
        ]);
    }

    /**
     * Memproses pesanan dari keranjang database.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isReseller = $user->hasRole('Reseller');

        $validated = $request->validate([
            'user_address_id' => 'required|exists:user_addresses,id,user_id,' . $user->id,
            'shipping_option' => 'required|array',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ]);

        // Ambil keranjang dari database
        $cartItems = Cart::where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Keranjang belanja Anda kosong.');
        }

        try {
            DB::beginTransaction();

            $productIds = $cartItems->pluck('product_id')->toArray();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
            $cartItemsKeyed = $cartItems->keyBy('product_id');

            // 1. Validasi Stok
            foreach ($cartItems as $item) {
                if (!isset($products[$item->product_id]) || $products[$item->product_id]->stock < $item->quantity) {
                    DB::rollBack();
                    $productName = $products[$item->product_id]->name ?? "Produk";
                    return back()->with('error', "Stok untuk produk '{$productName}' tidak mencukupi.");
                }
            }

            $subtotal = $cartItems->reduce(function ($carry, $item) use ($products) {
                return $carry + ($products[$item->product_id]->final_price * $item->quantity);
            }, 0);

            // Logika Voucher (tidak berubah)
            $voucherDiscount = 0;
            $appliedVoucherCode = null;
            if (!empty($validated['voucher_code'])) {
                $voucher = Voucher::where('code', $validated['voucher_code'])->first();
                if ($voucher && $voucher->is_active && $subtotal >= $voucher->min_purchase) {
                    $voucherDiscount = ($voucher->type === 'fixed') ? $voucher->value : ($subtotal * $voucher->value) / 100;
                    $appliedVoucherCode = $voucher->code;
                }
            }

            // 2. Buat Pesanan
            $order = Order::create([
                'user_id' => $user->id,
                'user_address_id' => $validated['user_address_id'],
                'order_number' => 'VP-' . strtoupper(Str::random(8)),
                'total_amount' => $subtotal,
                'shipping_cost' => $validated['shipping_option']['price'] ?? 0,
                'shipping_details' => json_encode($validated['shipping_option']),
                'voucher_code' => $appliedVoucherCode,
                'discount_amount' => $voucherDiscount,
                'status' => 'pending',
                'payment_status' => $isReseller ? 'pending_payment' : 'unpaid',
                'payment_method' => $isReseller ? 'manual_transfer' : null,
            ]);

            // 3. Pindahkan item dari keranjang ke item pesanan dan kurangi stok
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => (float)$products[$item->product_id]->final_price,
                ]);
                $products[$item->product_id]->decrement('stock', $item->quantity);
            }

            if ($appliedVoucherCode) {
                Voucher::where('code', $appliedVoucherCode)->increment('uses');
            }

            // 4. Kosongkan keranjang dari database
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            // Alur Pembayaran
            if ($isReseller) {
                return redirect()->route('orders.show', $order->order_number)
                    ->with('success', 'Pesanan berhasil dibuat! Silakan upload bukti transfer.');
            }

            $order->load(['items.product', 'user']);
            $invoiceResult = $this->paymentService->createInvoice($order);

            if (isset($invoiceResult['status']) && $invoiceResult['status'] === 'ERROR') {
                Log::error('Gagal membuat invoice pembayaran', ['order_id' => $order->id, 'message' => $invoiceResult['message']]);
                return redirect()->route('orders.show', $order->order_number)
                    ->with('error', 'Pesanan Anda berhasil dibuat, namun gagal membuat link pembayaran. Silakan coba bayar dari halaman detail pesanan.');
            }

            return Inertia::location($invoiceResult['invoice_url']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CHECKOUT GAGAL TOTAL: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan fatal saat memproses pesanan Anda. Silakan coba lagi.');
        }
    }
}
