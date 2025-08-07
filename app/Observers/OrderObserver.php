<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Product; // <-- Diperlukan untuk mengupdate stok
use App\Services\Shipping\ShippingServiceInterface;
use Illuminate\Support\Facades\DB; // <-- Diperlukan untuk database transaction
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(protected ShippingServiceInterface $shippingService) {}

    /**
     * Handle the Order "updating" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function updating(Order $order): void
    {
        // KONDISI 1: Jika pembayaran baru saja disetujui ('paid')
        if ($order->isDirty('payment_status') && $order->payment_status === 'paid') {
            $this->handlePaidPayment($order);
        }

        // KONDISI 2: Jika status pesanan baru saja diubah menjadi 'cancelled'
        if ($order->isDirty('status') && $order->status === 'cancelled') {
            $this->handleCancelledOrder($order);
        }
    }

    /**
     * Menangani logika saat pembayaran pesanan telah lunas.
     *
     * @param Order $order
     */
    protected function handlePaidPayment(Order $order): void
    {
        // Tandai pesanan sebagai 'processing' dulu
        $order->status = 'processing';

        // Panggil API untuk membuat pesanan di Shipper.id
        Log::info("Mencoba membuat pesanan Shipper untuk Order #{$order->order_number}");
        $shippingResult = $this->shippingService->createOrder($order);

        if ($shippingResult && !empty($shippingResult['tracking_number'])) {
            // Jika berhasil, update dengan nomor resi dan ubah status menjadi 'shipped'
            $order->tracking_number = $shippingResult['tracking_number'];
            $order->status = 'shipped';
            Log::info("Berhasil mendapatkan resi #{$shippingResult['tracking_number']} untuk Order #{$order->order_number}");
        } else {
            Log::error("Gagal membuat pesanan di Shipper untuk Order #{$order->order_number}. Resi tidak didapatkan.");
            // Pesanan akan tetap berstatus 'processing' agar bisa ditangani manual oleh admin.
        }
    }

    /**
     * Menangani logika saat pesanan dibatalkan untuk mengembalikan stok.
     *
     * @param Order $order
     */
    protected function handleCancelledOrder(Order $order): void
    {
        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    // Gunakan increment untuk menambahkan kembali stok produk
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
                Log::info("Stok untuk Pesanan #{$order->order_number} telah berhasil dikembalikan.");
            });
        } catch (\Exception $e) {
            // Jika gagal, catat error agar bisa ditindaklanjuti
            Log::error("KRITIS: Gagal mengembalikan stok untuk Pesanan #{$order->order_number}. Error: " . $e->getMessage());
        }
    }
}
