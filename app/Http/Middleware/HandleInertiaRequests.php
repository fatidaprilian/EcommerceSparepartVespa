<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use App\Models\Cart; // Import Cart model
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // --- PERUBAHAN KUNCI ADA DI SINI ---
            'cart' => [
                // Mengubah logika untuk menghitung jumlah item unik di keranjang
                'count' => function () {
                    if ($user = Auth::user()) {
                        // Sebelumnya: ->sum('quantity')
                        // Sekarang: ->count()
                        // Ini akan menghitung jumlah baris/entri di keranjang, bukan total kuantitas.
                        return Cart::where('user_id', $user->id)->count();
                    }
                    // Jika guest, kembalikan 0.
                    return 0;
                },
            ],
            // ------------------------------------
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => fn() => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
                // Anda bisa menambahkan flash message lain jika perlu
            ],
        ];
    }
}
