<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_address_id',
        'order_number',
        'total_amount',
        'shipping_cost',
        'status',
        'payment_status',
        'tracking_number',
        'voucher_code',
        'discount_amount',
    ];

    /**
     * Mendapatkan user yang membuat pesanan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan alamat pengiriman pesanan.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    /**
     * Mendapatkan semua item dalam pesanan.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
