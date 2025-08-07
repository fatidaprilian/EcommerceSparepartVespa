<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman pembayaran palsu.
     */
    public function showFakePaymentPage(Order $order)
    {
        // Pastikan order ini milik user yang sedang login
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Payment/FakePaymentPage', [
            'order' => $order
        ]);
    }

    /**
     * Menangani callback dari halaman pembayaran palsu.
     */
    public function handleCallback(Request $request)
    {
        $request->validate([
            'order_number' => 'required|exists:orders,order_number',
            'status' => 'required|in:success,failure',
        ]);

        $order = Order::where('order_number', $request->order_number)->firstOrFail();

        if ($request->status === 'success') {
            // Update status pesanan di database
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Ganti status dari 'pending' menjadi 'processing'
            ]);
            // Redirect ke halaman "Terima Kasih"
            return Redirect::route('order.completed');
        } else {
            // Update status pesanan menjadi gagal
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);
            // Redirect kembali ke keranjang dengan pesan error
            return Redirect::route('cart.index')->with('error', 'Pembayaran gagal. Silakan coba lagi.');
        }
    }

    /**
     * Menampilkan halaman "Terima Kasih" setelah pembayaran berhasil.
     */
    public function showCompletionPage()
    {
        return Inertia::render('Order/Completed');
    }
}
