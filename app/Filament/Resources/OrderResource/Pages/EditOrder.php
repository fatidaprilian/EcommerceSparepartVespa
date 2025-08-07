<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $order = $this->getRecord();
        $actions = [];

        // --- AKSI UNTUK VERIFIKASI PEMBAYARAN MANUAL (RESELLER) ---
        // Muncul hanya jika reseller sudah upload bukti dan statusnya 'pending_verification'
        if ($order->payment_status === 'pending_verification') {
            $actions[] = Actions\Action::make('approve_payment')
                ->label('Approve Payment')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function (Order $record) {
                    // Mengubah payment_status akan memicu OrderObserver untuk mengubah status order menjadi 'processing' atau 'shipped'
                    $record->update(['payment_status' => 'paid']);
                    $this->refreshFormData(['payment_status', 'status']);
                    Notification::make()->title('Pembayaran berhasil disetujui!')->success()->send();
                });

            $actions[] = Actions\Action::make('reject_payment')
                ->label('Reject Payment')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                ->action(function (Order $record, array $data) {
                    $metadata = $record->payment_metadata;
                    $metadata['rejection_reason'] = $data['rejection_reason'];
                    $record->update([
                        'payment_status' => 'payment_rejected',
                        'payment_metadata' => $metadata
                    ]);
                    $this->refreshFormData(['payment_status']);
                    Notification::make()->title('Pembayaran telah ditolak!')->warning()->send();
                });
        }

        // --- AKSI UNTUK MENANDAI PESANAN SELESAI ---
        // Muncul hanya jika pesanan sudah dalam status 'shipped'
        if ($order->status === 'shipped') {
            $actions[] = Actions\Action::make('complete_order')
                ->label('Tandai Pesanan Selesai')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->action(function (Order $record) {
                    $record->update(['status' => 'completed']);
                    $this->refreshFormData(['status']); // Refresh tampilan status di halaman
                    Notification::make()->title('Pesanan telah ditandai selesai!')->success()->send();
                });
        }

        // --- [BARU] AKSI UNTUK MEMBATALKAN PESANAN SECARA MANUAL ---
        // Muncul selama status pesanan BUKAN 'completed' atau 'cancelled'
        if (!in_array($order->status, ['completed', 'cancelled'])) {
            $actions[] = Actions\Action::make('cancel_order')
                ->label('Batalkan Pesanan')
                ->color('danger')
                ->icon('heroicon-o-archive-box-x-mark')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Pesanan')
                ->modalDescription('Apakah Anda yakin ingin membatalkan pesanan ini? Stok untuk semua item dalam pesanan ini akan dikembalikan ke sistem.')
                ->action(function (Order $record) {
                    // Mengubah status menjadi 'cancelled' akan memicu OrderObserver untuk mengembalikan stok.
                    $record->update(['status' => 'cancelled']);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Pesanan telah dibatalkan!')->danger()->send();
                });
        }

        // --- AKSI DELETE BAWAAN FILAMENT ---
        // Menghapus record pesanan dari database secara permanen. Gunakan dengan hati-hati.
        $actions[] = Actions\DeleteAction::make();

        return $actions;
    }
}
