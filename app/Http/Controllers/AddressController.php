<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    protected string $baseUrl = 'https://api.nusakita.yuefii.site/v2';

    /**
     * Mengambil semua data provinsi.
     */
    public function getProvinces()
    {
        try {
            $response = Http::get("{$this->baseUrl}/provinsi", [
                'pagination' => 'false',
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data provinsi: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data provinsi.'], 500);
        }
    }

    /**
     * Mengambil data kota/kabupaten berdasarkan kode provinsi.
     */
    public function getCities(Request $request)
    {
        $request->validate(['provinceCode' => 'required']);
        $provinceCode = $request->query('provinceCode');

        try {
            $response = Http::get("{$this->baseUrl}/{$provinceCode}/kab-kota", [
                'pagination' => 'false',
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data kota: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data kota.'], 500);
        }
    }

    /**
     * Mengambil data kecamatan berdasarkan kode kota/kabupaten.
     */
    public function getDistricts(Request $request)
    {
        $request->validate(['cityCode' => 'required']);
        $cityCode = $request->query('cityCode');

        try {
            $response = Http::get("{$this->baseUrl}/{$cityCode}/kecamatan", [
                'pagination' => 'false',
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data kecamatan: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data kecamatan.'], 500);
        }
    }

    /**
     * Mengambil data desa/kelurahan berdasarkan kode kecamatan.
     * (Opsional, sesuai permintaan Anda)
     */
    public function getVillages(Request $request)
    {
        $request->validate(['districtCode' => 'required']);
        $districtCode = $request->query('districtCode');

        try {
            $response = Http::get("{$this->baseUrl}/{$districtCode}/desa-kel", [
                'pagination' => 'false',
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data desa: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data desa.'], 500);
        }
    }
}
