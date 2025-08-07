<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class UserAddressController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'province' => 'required|array',
            'city' => 'required|array',
            'district' => 'required|array',
            'postal_code' => 'required|string|max:10',
            'full_address' => 'required|string',
            'label' => 'nullable|string|max:50',
        ]);

        // --- MENCARI ID AREA SHIPPER ---
        $shipperAreaId = null;
        try {
            $response = Http::withHeaders(['X-API-Key' => config('app.shipper_api_key')])
                ->get(config('app.shipper_api_url') . '/v3/location', [
                    'adm_level' => 4,
                    'keyword' => $validatedData['district']['nama']
                ]);

            if ($response->successful() && !empty($response->json()['data'])) {
                $shipperAreaId = $response->json()['data'][0]['id'];
            } else {
                Log::warning('Shipper location not found for district: ' . $validatedData['district']['nama']);
            }
        } catch (\Exception $e) {
            Log::error("Gagal mencari location ID Shipper: " . $e->getMessage());
        }

        $addressData = [
            'user_id' => Auth::id(),
            'recipient_name' => $validatedData['recipient_name'],
            'phone_number' => $validatedData['phone_number'],
            'label' => $validatedData['label'] ?: 'Alamat',
            'full_address' => $validatedData['full_address'],
            'postal_code' => $validatedData['postal_code'],
            'province' => $validatedData['province']['nama'],
            'city' => $validatedData['city']['nama'],
            'district' => $validatedData['district']['nama'],
            'province_code' => $validatedData['province']['kode'],
            'city_code' => $validatedData['city']['kode'],
            'district_code' => $validatedData['district']['kode'],
            'shipper_area_id' => $shipperAreaId,
        ];

        if (!Auth::user()->addresses()->exists()) {
            $addressData['is_primary'] = true;
        }

        $newAddress = UserAddress::create($addressData);

        // Return JSON response untuk AJAX request
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Alamat baru berhasil disimpan!',
                'address' => $newAddress->fresh() // Ambil data terbaru dari database
            ]);
        }

        return Redirect::back()->with('status', 'Alamat baru berhasil disimpan!');
    }
}
