<?php

namespace App\Services\Shipping;

use App\Models\Order; // <-- Ditambahkan
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealShipperService implements ShippingServiceInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('app.shipper_api_url');
        $this->apiKey = config('app.shipper_api_key');
    }

    public function calculateCost(int $destinationAreaId, int $weightInGrams): array
    {
        $originAreaId = 3173040; // Ganti dengan Area ID asal pengiriman Anda

        $payload = [
            "origin" => ["area_id" => $originAreaId],
            "destination" => ["area_id" => $destinationAreaId],
            "weight" => max(1, ceil($weightInGrams / 1000)), // Minimal 1kg
            "item_value" => 100000,
            "limit" => 20,
            "for_order" => true,
        ];

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/v3/pricing/domestic", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Shipper API calculateCost Error: ' . $response->body());
            return ['error' => 'Gagal mengambil data ongkos kirim.', 'details' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Exception saat memanggil Shipper API (calculateCost): ' . $e->getMessage());
            return ['error' => 'Terjadi kesalahan pada server kami.'];
        }
    }

    /**
     * Membuat pesanan di Shipper.id dan mengembalikan nomor resi.
     */
    public function createOrder(Order $order): ?array
    {
        $shippingDetails = json_decode($order->shipping_details, true);
        if (!$shippingDetails || !isset($shippingDetails['rate']['id'])) {
            Log::error("Gagal membuat pesanan Shipper untuk Order #{$order->order_number}: shipping_details atau rate_id tidak ada.");
            return null;
        }

        // Anda perlu menambahkan logika untuk menghitung berat total dari item pesanan
        $totalWeight = 1; // Contoh: 1 kg. Ganti dengan logika sebenarnya.

        $payload = [
            'consignee' => [
                'name' => $order->address->recipient_name,
                'phone_number' => $order->address->phone_number,
            ],
            'consigner' => [
                'name' => 'NAMA TOKO ANDA', // Ganti dengan nama toko Anda
                'phone_number' => 'NOMOR TELEPON TOKO ANDA', // Ganti dengan nomor telepon Anda
            ],
            'destination' => [
                'address' => $order->address->full_address,
                'area_id' => (int)$order->address->shipper_area_id,
            ],
            'origin' => [
                'area_id' => 3173040, // Ganti dengan Area ID asal pengiriman Anda
            ],
            'package' => [
                'items' => $order->items->map(fn($item) => [
                    'name' => $item->product->name,
                    'price' => (int)$item->price,
                    'qty' => (int)$item->quantity,
                ])->toArray(),
                'weight' => $totalWeight,
                'height' => 10,
                'length' => 10,
                'width' => 10,
            ],
            'payment' => ['type' => 'postpay'],
            'rate' => ['id' => $shippingDetails['rate']['id']],
            'external_id' => $order->order_number,
            'courier' => [
                'cod' => false,
                'rate_id' => $shippingDetails['rate']['id'],
                'use_insurance' => false,
            ]
        ];

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/v3/orders", $payload);

            if ($response->successful() && isset($response->json()['data'])) {
                $data = $response->json()['data'];
                return [
                    'tracking_number' => $data['tracking_number'] ?? $data['awb_number'] ?? null,
                    'shipper_order_id' => $data['id'] ?? null,
                ];
            }

            Log::error("Shipper API createOrder error untuk #{$order->order_number}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Exception saat createOrder Shipper #{$order->order_number}: " . $e->getMessage());
            return null;
        }
    }
}
