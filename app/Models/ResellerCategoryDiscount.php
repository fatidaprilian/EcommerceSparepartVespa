<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerCategoryDiscount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reseller_category_discounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'discount_percentage',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    /**
     * Mendapatkan user (reseller) yang memiliki aturan diskon ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan kategori yang terkait dengan aturan diskon ini.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
