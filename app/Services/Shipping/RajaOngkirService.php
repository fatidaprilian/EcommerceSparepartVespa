<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService implements ShippingServiceInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('app.rajaongkir_api_url');
        $this->apiKey = config('app.rajaongkir_api_key');
    }

    public function calculateCost(int $destinationAreaId, int $weightInGrams): array
    {
        try {
            // Convert weight to grams (RajaOngkir expects grams)
            $weightInGrams = max(1000, $weightInGrams); // Minimum 1kg

            // Origin: Jakarta Pusat (city ID 152)
            $originCityId = 152;

            // Destination: Untuk RajaOngkir, kita perlu mapping dari district_code ke city_id
            $destinationCityId = $this->mapAreaIdToCityId($destinationAreaId);

            $response = Http::withHeaders([
                'key' => $this->apiKey
            ])->post("{$this->baseUrl}/cost", [
                'origin' => $originCityId,
                'destination' => $destinationCityId,
                'weight' => $weightInGrams,
                'courier' => 'jne:pos:tiki' // Multiple couriers
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (
                    isset($data['rajaongkir']['status']['code']) &&
                    $data['rajaongkir']['status']['code'] == 200
                ) {

                    return $this->formatResponse($data['rajaongkir']['results']);
                }

                Log::error('RajaOngkir API Error: ' . json_encode($data));
                return ['error' => 'Gagal mengambil data ongkos kirim.'];
            }

            Log::error('RajaOngkir HTTP Error: ' . $response->status() . ' - ' . $response->body());
            return ['error' => 'Gagal menghubungi layanan ongkos kirim.'];
        } catch (\Exception $e) {
            Log::error('Exception saat memanggil RajaOngkir API: ' . $e->getMessage());
            return ['error' => 'Terjadi kesalahan pada server kami.'];
        }
    }

    private function mapAreaIdToCityId(int $areaId): int
    {
        // [FIX] Menggunakan 'match' expression yang benar secara sintaks
        return match ($areaId) {
            // Jakarta
            3173040, 3173010 => 152, // Jakarta Pusat
            3171010 => 151,         // Jakarta Selatan
            3172010 => 153,         // Jakarta Timur
            3174010 => 154,         // Jakarta Utara
            3175010 => 155,         // Jakarta Barat

            // Bogor
            3201080, 3271000 => 24,  // Bogor / Kota Bogor

            // Depok
            3276000 => 107,         // Depok

            // Tangerang
            3603010 => 455,         // Tangerang
            3671000 => 456,         // Tangerang Selatan

            // Bekasi
            3216010, 3275000 => 23,  // Bekasi

            // Default fallback ke Jakarta Pusat
            default => 152
        };
    }

    private function formatResponse(array $results): array
    {
        $formattedOptions = [];

        foreach ($results as $courier) {
            $courierCode = strtoupper($courier['code']);
            $courierName = strtoupper($courier['name']);

            foreach ($courier['costs'] as $service) {
                $formattedOptions[] = [
                    'type' => $courierCode . '_' . strtoupper($service['service']),
                    'courier' => [
                        'company' => $courierName,
                        'name' => $service['service'] . ' - ' . $service['description']
                    ],
                    'price' => (int) $service['cost'][0]['value'],
                    'min_day' => $this->parseEstimation($service['cost'][0]['etd'])['min'],
                    'max_day' => $this->parseEstimation($service['cost'][0]['etd'])['max']
                ];
            }
        }

        return [
            'data' => [
                'pricing' => $formattedOptions
            ]
        ];
    }

    private function parseEstimation(string $etd): array
    {
        // Parse estimasi dari RajaOngkir (contoh: "1-2", "2-3 HARI", "1 HARI")
        $etd = strtolower(str_replace(' hari', '', $etd));

        if (strpos($etd, '-') !== false) {
            $parts = explode('-', $etd);
            return [
                'min' => (int) trim($parts[0]),
                'max' => (int) trim($parts[1])
            ];
        }

        $days = (int) $etd;
        return [
            'min' => $days,
            'max' => $days
        ];
    }
}
