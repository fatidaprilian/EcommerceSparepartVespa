<?php

namespace App\Services\Shipping;

class FakeShippingService implements ShippingServiceInterface
{
    public function calculateCost(int $destinationAreaId, int $weightInGrams): array
    {
        // Simulasi data respons dari Shipper.id
        // Kita buat beberapa pilihan kurir palsu
        return [
            'data' => [
                'pricing' => [
                    [
                        'courier' => ['company' => 'JNE', 'name' => 'Reguler'],
                        'type' => 'REG',
                        'price' => 18000,
                        'min_day' => 2,
                        'max_day' => 3,
                    ],
                    [
                        'courier' => ['company' => 'J&T', 'name' => 'Express'],
                        'type' => 'EZ',
                        'price' => 19000,
                        'min_day' => 2,
                        'max_day' => 3,
                    ],
                    [
                        'courier' => ['company' => 'SiCepat', 'name' => 'BEST'],
                        'type' => 'BEST',
                        'price' => 22000,
                        'min_day' => 1,
                        'max_day' => 2,
                    ],
                ]
            ]
        ];
    }
}
