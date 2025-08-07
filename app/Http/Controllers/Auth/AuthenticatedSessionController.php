<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('home')->with('flash', [
            'status' => 'Anda harus login untuk mengakses fitur tersebut.',
            'message' => 'Anda harus login untuk mengakses halaman tersebut.'
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Diubah: Logika pemeriksaan verifikasi email
        if (!$user->hasVerifiedEmail()) {
            // Jika email belum terverifikasi, biarkan user tetap login,
            // tapi arahkan ke halaman pemberitahuan verifikasi.
            // Middleware 'verified' dari Laravel akan menangani sisanya.
            return redirect()->route('verification.notice');
        }
        // Akhir dari bagian yang diubah

        // Jika user yang login memiliki role 'admin', arahkan ke panel admin.
        if ($user->hasRole('Admin')) {
            return redirect('/admin');
        }

        // Jika bukan admin, lanjutkan ke intended URL atau ke halaman utama.
        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
