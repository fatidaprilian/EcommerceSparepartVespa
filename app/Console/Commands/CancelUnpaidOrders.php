<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membatalkan pesanan yang belum dibayar setelah melewati batas waktu tertentu.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Untuk testing lokal, kita buat batas waktunya lebih singkat, misal 1 jam.
        // Anda bisa mengubahnya ke `now()->subMinutes(5)` untuk tes yang lebih cepat.
        $cancellationPeriod = now()->subHours(1);

        $ordersToCancel = Order::whereIn('payment_status', ['unpaid', 'pending_payment'])
            ->where('status', '!=', 'cancelled') // Pastikan tidak membatalkan yang sudah batal
            ->where('created_at', '<=', $cancellationPeriod)
            ->get();

        if ($ordersToCancel->isEmpty()) {
            $this->info('Tidak ada pesanan kedaluwarsa yang perlu dibatalkan.');
            return 0;
        }

        $this->info("Menemukan " . $ordersToCancel->count() . " pesanan yang akan dibatalkan...");

        foreach ($ordersToCancel as $order) {
            // Mengubah status akan secara otomatis memicu OrderObserver untuk mengembalikan stok.
            $order->update(['status' => 'cancelled']);
            Log::info("Pesanan #{$order->order_number} telah dibatalkan secara otomatis.");
            $this->line("Pesanan #{$order->order_number} berhasil dibatalkan.");
        }

        $this->info('Proses pembatalan pesanan selesai.');
        return 0;
    }
}
