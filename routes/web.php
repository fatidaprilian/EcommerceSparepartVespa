<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Api\AuthController; // <-- Ditambahkan
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\VoucherController;
use App\Models\Product;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rute Halaman Utama (Welcome)
Route::get('/', function () {
    $newArrivals = Product::where('is_active', true)->latest()->take(3)->get();
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'newArrivals' => $newArrivals,
    ]);
})->name('home');

// Rute Publik lainnya
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// --- [BARU] API Routes untuk SPA Authentication (Stateful) ---
// Rute di grup ini akan memiliki session state dan perlindungan CSRF
// karena berada di dalam web.php, namun tetap bisa diakses dari frontend melalui /api/...
Route::prefix('api')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/verify', [AuthController::class, 'verify'])->name('verification.verify-otp');
    Route::post('/resend', [AuthController::class, 'resend'])->name('verification.resend-otp');

    // Logout harus di dalam middleware auth untuk memastikan hanya user terotentikasi yang bisa logout
    Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});


// Rute untuk API Wilayah & Ongkir (ini boleh tetap di sini)
Route::get('/api/provinces', [AddressController::class, 'getProvinces'])->name('api.provinces');
Route::get('/api/cities', [AddressController::class, 'getCities'])->name('api.cities');
Route::get('/api/districts', [AddressController::class, 'getDistricts'])->name('api.districts');
Route::get('/api/villages', [AddressController::class, 'getVillages'])->name('api.villages');
Route::post('/api/shipping-cost', [ShippingController::class, 'calculateCost'])->name('api.shipping.cost');

// Grup Rute yang Membutuhkan Otentikasi Web Standar
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/vouchers/apply', [VoucherController::class, 'apply'])->name('vouchers.apply');
    Route::post('/addresses', [UserAddressController::class, 'store'])->name('addresses.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:order_number}', [OrderController::class, 'show'])->name('orders.show');

    Route::middleware('role:Reseller')->group(function () {
        Route::post('/orders/{order:order_number}/upload-proof', [OrderController::class, 'uploadProof'])->name('orders.upload-proof');
    });
});

// Sertakan rute otentikasi standar dari Laravel (login, register, dll. untuk halaman web)
require __DIR__ . '/auth.php';
