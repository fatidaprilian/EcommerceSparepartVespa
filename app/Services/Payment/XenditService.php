<?php

namespace App\Services\Payment;

use App\Models\Order;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\XenditSdkException;
use Illuminate\Support\Facades\Log;

class XenditService implements PaymentServiceInterface
{
    public function __construct()
    {
        Configuration::setXenditKey(config('xendit.secret_key'));
    }

    public function createInvoice(Order $order): array
    {
        try {
            // Produk
            $itemDetails = $order->items->map(function ($item) {
                return [
                    'name' => substr((string)$item->product->name, 0, 255),
                    'quantity' => (int)$item->quantity,
                    'price' => (float)$item->price,
                ];
            })->toArray();

            // Shipping cost sebagai item
            if ($order->shipping_cost > 0) {
                $itemDetails[] = [
                    'name' => 'Biaya Pengiriman',
                    'quantity' => 1,
                    'price' => (float)$order->shipping_cost,
                ];
            }

            // Diskon voucher HANYA di description dan amount, JANGAN di items!
            $finalAmount = ($order->total_amount + $order->shipping_cost) - $order->discount_amount;

            // Deskripsi invoice, tambahkan info diskon jika ada
            $description = 'Pembayaran untuk pesanan #' . $order->order_number;
            if ($order->discount_amount > 0) {
                $description .= ' | Diskon Voucher ' . $order->voucher_code . ': Rp ' . number_format($order->discount_amount, 0, ',', '.');
            }

            $params = [
                'external_id' => (string)$order->order_number,
                'payer_email' => (string)$order->user->email,
                'description' => $description,
                'amount' => (float)$finalAmount,
                'success_redirect_url' => route('orders.show', $order->order_number),
                'failure_redirect_url' => route('checkout.index'),
                'items' => $itemDetails,
                'customer' => [
                    'given_names' => (string)$order->user->name,
                    'email' => (string)$order->user->email,
                ],
            ];

            Log::info('XENDIT: Membuat invoice dengan params', ['params' => $params]);

            $apiInstance = new InvoiceApi();
            $invoice = $apiInstance->createInvoice($params);

            $invoiceData = [
                'id' => $invoice->getId(),
                'external_id' => $invoice->getExternalId(),
                'invoice_url' => $invoice->getInvoiceUrl(),
                'status' => $invoice->getStatus(),
                'amount' => $invoice->getAmount(),
                'created' => method_exists($invoice->getCreated(), 'format')
                    ? $invoice->getCreated()->format('Y-m-d H:i:s')
                    : (string)$invoice->getCreated(),
                'items' => $invoice->getItems() ? json_decode(json_encode($invoice->getItems()), true) : [],
            ];

            $order->update([
                'payment_invoice_id' => $invoice->getId(),
                'payment_url' => $invoice->getInvoiceUrl(),
                'payment_metadata' => json_encode($invoiceData),
            ]);

            return [
                'status' => 'PENDING',
                'invoice_url' => $invoice->getInvoiceUrl(),
            ];
        } catch (XenditSdkException $e) {
            Log::error('Xendit Error: ' . $e->getMessage() . ' - Full Body: ' . json_encode($e->getFullError()));
            return ['status' => 'ERROR', 'message' => 'Gagal membuat invoice pembayaran: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Xendit General Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['status' => 'ERROR', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }
}
