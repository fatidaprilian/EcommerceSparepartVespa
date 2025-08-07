<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    // --- PERUBAHAN KUNCI ADA DI SINI ---
    // Nilai ini diubah dari '/dashboard' menjadi '/'
    // Ini akan menjadi tujuan default setelah login, registrasi,
    // atau proses otentikasi lainnya berhasil.
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Konfigurasi Rate Limiter untuk API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Mendefinisikan file rute untuk aplikasi
        $this->routes(function () {
            // Rute untuk API (stateless)
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Rute untuk Web (stateful, dengan session, cookies, dll.)
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
