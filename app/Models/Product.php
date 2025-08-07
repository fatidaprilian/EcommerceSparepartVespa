<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <-- Ditambahkan untuk query langsung

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'base_price',
        'stock',
        'image_url',
        'is_active',
        'erp_id',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'base_price' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['final_price'];

    /**
     * Accessor untuk mendapatkan harga final setelah diskon dengan 3 tingkat prioritas.
     */
    public function getFinalPriceAttribute(): int
    {
        $user = Auth::user();

        // Jika user tidak login atau bukan Reseller, kembalikan harga dasar.
        if (!$user || !$user->hasRole('Reseller')) {
            return $this->attributes['base_price'];
        }

        $discountPercentage = 0;

        // Prioritas 1: Cek diskon spesifik untuk item ini.
        $itemDiscount = DB::table('reseller_product_discounts')
            ->where('user_id', $user->id)
            ->where('product_id', $this->attributes['id'])
            ->value('discount_percentage');

        if ($itemDiscount > 0) {
            $discountPercentage = $itemDiscount;
        }
        // Prioritas 2: Jika tidak ada, cek diskon untuk kategori item ini.
        else {
            $categoryDiscount = DB::table('reseller_category_discounts')
                ->where('user_id', $user->id)
                ->where('category_id', $this->attributes['category_id'])
                ->value('discount_percentage');

            if ($categoryDiscount > 0) {
                $discountPercentage = $categoryDiscount;
            }
            // Prioritas 3: Jika tidak ada keduanya, gunakan diskon umum.
            else {
                $discountPercentage = $user->discount_percentage;
            }
        }

        // Jika ada persentase diskon yang ditemukan, hitung harga final.
        if ($discountPercentage > 0) {
            $discountAmount = ($this->attributes['base_price'] * $discountPercentage) / 100;
            return floor($this->attributes['base_price'] - $discountAmount);
        }

        // Jika tidak ada diskon sama sekali, kembalikan harga dasar.
        return $this->attributes['base_price'];
    }

    /**
     * Mendapatkan kategori dari produk ini.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
