<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_purchase',
        'max_uses',
        'uses',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi many-to-many dengan model Role.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_voucher');
    }
}
