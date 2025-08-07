<?php

namespace App\Services\Shipping;

use App\Models\Order; // <-- Tambahkan ini

interface ShippingServiceInterface
{
    public function calculateCost(int $destinationAreaId, int $weightInGrams): array;

    /**
     * Membuat pesanan pengiriman di platform logistik dan mengembalikan detailnya.
     *
     * @param Order $order
     * @return array|null Detail pengiriman ['tracking_number' => ..., 'shipper_order_id' => ...] atau null jika gagal.
     */
    public function createOrder(Order $order): ?array;
}
