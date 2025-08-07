<?php

namespace App\Providers;

use App\Services\Payment\FakeXenditService;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\Payment\XenditService; // <-- Impor service asli
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentServiceInterface::class, function ($app) {
            // GANTI INI untuk beralih ke Xendit asli
            return new XenditService();

            // Baris ini bisa Anda simpan untuk testing di masa depan
            // return new FakeXenditService();
        });
    }
}
