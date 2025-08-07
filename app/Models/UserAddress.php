<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone_number',
        'province',
        'city',
        'district',
        'province_code',
        'city_code',
        'district_code',
        'postal_code',
        'full_address',
        'is_primary',
        'shipper_area_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
