<?php

namespace App\Services\Payment;

use App\Models\Order;

class FakeXenditService implements PaymentServiceInterface
{
    public function createInvoice(Order $order): array
    {
        // Simulasi pembuatan invoice yang berhasil
        // Nanti, URL ini akan menjadi URL pembayaran dari Xendit asli
        $fakeInvoiceUrl = route('payment.fake.show', ['order' => $order->order_number]);

        return [
            'status' => 'PENDING',
            'invoice_url' => $fakeInvoiceUrl,
            'external_id' => $order->order_number,
        ];
    }
}
