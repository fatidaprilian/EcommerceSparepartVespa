<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'discount_percentage', // Diskon umum untuk reseller
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'discount_percentage' => 'decimal:2',
    ];

    /**
     * Metode untuk memeriksa apakah user bisa mengakses Panel Filament.
     * INI ADALAH GERBANG UTAMA KEAMANAN ADMIN PANEL.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Hanya izinkan akses jika user memiliki role 'Admin'.
        return $this->hasRole('Admin');
    }

    /**
     * Mendapatkan semua pesanan milik user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Mendapatkan semua alamat milik user.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Mendapatkan semua aturan diskon per-kategori milik reseller.
     */
    public function categoryDiscounts(): HasMany
    {
        return $this->hasMany(ResellerCategoryDiscount::class);
    }

    /**
     * [BARU] Mendapatkan semua item di keranjang milik user.
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
