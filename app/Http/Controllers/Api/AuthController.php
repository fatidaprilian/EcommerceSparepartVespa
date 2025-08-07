<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendVerificationCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Menangani permintaan registrasi pengguna baru.
     * Membuat pengguna, menetapkan role, dan mengirim kode verifikasi.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Tetapkan role default untuk pengguna baru
        $user->assignRole('Member');

        // Kirim kode verifikasi ke email pengguna
        $this->generateAndSendVerificationCode($user);

        // Kembalikan respons JSON yang akan ditangkap oleh frontend (RegisterForm.vue)
        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil, silakan cek email Anda untuk kode verifikasi.',
            'email' => $user->email
        ]);
    }

    /**
     * Memverifikasi email pengguna menggunakan kode OTP.
     * Jika berhasil, pengguna akan otomatis login.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|string|min:6|max:6',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Periksa apakah pengguna ada atau kode tidak cocok
        if (!$user || $user->verification_code !== $request->verification_code) {
            throw ValidationException::withMessages([
                'verification_code' => ['Kode verifikasi yang Anda masukkan tidak valid.'],
            ]);
        }

        // 2. Periksa apakah kode sudah kedaluwarsa
        if (now()->gt($user->verification_code_expires_at)) {
            throw ValidationException::withMessages([
                'verification_code' => ['Kode verifikasi Anda telah kedaluwarsa. Silakan minta kode baru.'],
            ]);
        }

        // 3. (Best Practice) Periksa apakah email sudah terverifikasi sebelumnya
        if ($user->hasVerifiedEmail()) {
            return response()->json(['status' => 'success', 'message' => 'Email ini sudah terverifikasi.']);
        }

        // Tandai email sebagai terverifikasi dan hapus data kode
        $user->forceFill([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        // Login pengguna secara otomatis setelah verifikasi berhasil
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['status' => 'success', 'message' => 'Verifikasi berhasil! Anda sekarang sudah login.']);
    }

    /**
     * Menangani permintaan login pengguna.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Coba untuk mengautentikasi pengguna
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $user = $request->user();

        // Jika autentikasi berhasil, periksa apakah email sudah diverifikasi
        if (!$user->hasVerifiedEmail()) {
            // Jika belum, logout pengguna dan kirim respons khusus
            Auth::logout();

            return response()->json([
                'email_unverified' => true,
                'email' => $user->email,
                'errors' => ['email' => ['Alamat email Anda belum diverifikasi. Silakan cek email Anda untuk kode OTP.']],
            ], 403); // 403 Forbidden
        }

        // Jika semua berhasil, buat sesi baru yang aman
        $request->session()->regenerate();

        return response()->json(['status' => 'success', 'message' => 'Login berhasil!']);
    }

    /**
     * Mengirim ulang kode verifikasi email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->first();

        // Jangan kirim ulang jika email sudah terverifikasi
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email ini sudah terverifikasi.'], 400);
        }

        $this->generateAndSendVerificationCode($user);

        return response()->json(['status' => 'success', 'message' => 'Kode verifikasi baru telah dikirim.']);
    }

    /**
     * Menangani proses logout pengguna.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Untuk API, lebih baik mengembalikan respons JSON daripada redirect
        return response()->json(['status' => 'success', 'message' => 'Logout berhasil.']);
    }

    /**
     * Fungsi helper untuk membuat dan mengirim kode verifikasi.
     *
     * @param User $user
     */
    protected function generateAndSendVerificationCode(User $user): void
    {
        $user->verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->verification_code_expires_at = now()->addMinutes(10); // Kode berlaku 10 menit
        $user->save();

        Mail::to($user->email)->send(new SendVerificationCode($user->verification_code));
    }
}
