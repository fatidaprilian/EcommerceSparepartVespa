<?php

namespace App\Http\Controllers;

use App\Services\Shipping\ShippingServiceInterface;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(
        protected ShippingServiceInterface $shippingService
    ) {}

    public function calculateCost(Request $request)
    {
        $validated = $request->validate([
            'destination_id' => 'required|numeric',
            'weight' => 'required|numeric|min:1',
        ]);

        $costs = $this->shippingService->calculateCost(
            $validated['destination_id'],
            $validated['weight']
        );

        return response()->json($costs);
    }
}
