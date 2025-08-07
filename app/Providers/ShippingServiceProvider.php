<?php

namespace App\Providers;

use App\Services\Shipping\FakeShippingService;
use App\Services\Shipping\RajaOngkirService;
use App\Services\Shipping\ShippingServiceInterface;
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShippingServiceInterface::class, function ($app) {
            // Cek apakah RajaOngkir API key tersedia
            if (config('app.rajaongkir_api_key')) {
                return new RajaOngkirService();
            }

            // Fallback ke fake service untuk development
            return new FakeShippingService();
        });
    }

    public function boot(): void
    {
        //
    }
}
