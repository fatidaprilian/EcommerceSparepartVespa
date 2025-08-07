<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException; // <-- Pastikan ini ada

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // --- INI BAGIAN KUNCINYA ---
        // Kita tangkap error 'AuthenticationException' di sini.
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Alih-alih melempar error atau redirect ke /login,
            // kita paksa Laravel untuk kembali ke halaman utama (home)
            // dengan membawa flash message untuk memicu modal.
            return redirect()->guest(route('home'))->with('flash', [
                'status' => 'auth_required'
            ]);
        });
        // -------------------------
    }
}
