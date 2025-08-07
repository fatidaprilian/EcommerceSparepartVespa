<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleXendit(Request $request)
    {
        // 1. VERIFIKASI TOKEN
        $callbackToken = $request->header('x-callback-token');
        if ($callbackToken !== config('xendit.callback_verification_token')) {
            Log::error('XENDIT WEBHOOK: Token tidak valid', [
                'ip' => $request->ip(),
                'received_token' => $callbackToken,
                'expected_token' => config('xendit.callback_verification_token')
            ]);
            return response()->json(['message' => 'Invalid token'], 403);
        }

        // 2. VALIDASI SESUAI FORMAT XENDIT YANG SEBENARNYA
        try {
            $payload = $request->validate([
                'id' => 'required|string',
                'external_id' => 'required|string',
                'payment_method' => 'required|string',
                'status' => 'required|string',
                'amount' => 'required|numeric',
                'paid_amount' => 'required|numeric'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('XENDIT WEBHOOK: Format payload tidak valid', [
                'payload' => $request->all(),
                'errors' => $e->errors()
            ]);
            return response()->json(['message' => 'Invalid payload format'], 400);
        }

        Log::info('XENDIT WEBHOOK: Data diterima', $payload);

        // 3. HANDLE INVOICE PAID (tanpa event wrapper)
        if ($payload['status'] === 'PAID') {
            try {
                $order = Order::where('order_number', $payload['external_id'])->firstOrFail();

                // 4. UPDATE JIKA MASIH UNPAID
                if ($order->payment_status === 'unpaid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'paid_at' => now(),
                        'payment_method' => 'xendit_' . strtolower($payload['payment_method']),
                        'payment_metadata' => json_encode($payload)
                    ]);

                    Log::info("XENDIT WEBHOOK: Order {$order->order_number} berhasil diupdate ke PAID");
                }

                return response()->json(['status' => 'success']);
            } catch (\Exception $e) {
                Log::error('XENDIT WEBHOOK: Gagal memproses order', [
                    'external_id' => $payload['external_id'],
                    'error' => $e->getMessage()
                ]);
                return response()->json(['message' => 'Failed to process order'], 500);
            }
        }

        Log::info('XENDIT WEBHOOK: Status tidak diproses', ['status' => $payload['status']]);
        return response()->json(['status' => 'status_not_handled'], 200);
    }
}
